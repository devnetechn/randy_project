# CRM Autonomous Agent Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Claude-powered agent that autonomously reviews CRM leads (`appointments` table) — via both a periodic cron sweep and instant hooks on booking/status changes — and decides & executes the next action (update stage, email the customer, alert the owner via SMS, or sync the calendar), with no human approval step, bounded by a kill switch, a per-run cap, and a full audit log.

**Architecture:** A deterministic SQL pre-filter narrows leads to ones plausibly needing attention; only those go to Claude (`includes/claude.php`), which returns a strict-JSON decision from a fixed action enum. `includes/crm_agent.php` executes that decision by reusing the codebase's existing best-effort notification functions (`send_crm_stage_email`, `notify_owner_sms`, `gcal_sync_for_appointment`) and logs every outcome to a new `crm_agent_log` table.

**Tech Stack:** PHP 8 (no Composer/frameworks), PDO/MySQL, cURL for the Anthropic Messages API — matching the existing `includes/gemini.php` / `includes/sms.php` dependency-free style.

## Global Constraints

- No test framework exists in this codebase (no PHPUnit/Composer) — verification is via small standalone CLI-run PHP scripts (matching the existing `sms_test.php` convention), deleted after use, plus manual end-to-end checks against the local XAMPP DB.
- Every new function that calls an external service (Claude, Twilio, Gmail, Google Calendar) must be best-effort: catch `Throwable`, log, never let a failure propagate up and break a booking or admin request — this is the pattern every existing notification function in this codebase already follows.
- All DB access goes through `db()` (`includes/db.php`) — no new connection logic.
- Follow the existing `config('section')` accessor pattern (`includes/db.php:4`) for all new settings; don't invent a second config mechanism.
- The `settings` table (`includes/settings.php`) is the only place for runtime-toggleable values (kill switch, per-run cap) — no new tables for simple key/value flags.

---

### Task 1: `crm_agent_log` table

**Files:**
- Modify: `sql/tables.sql` (append after `job_applications`, the existing pattern already runs every `CREATE TABLE IF NOT EXISTS` on every `setup.php` visit — no `setup.php` code change needed)
- Test: `test_crm_agent_schema.php` (repo root, temporary)

**Interfaces:**
- Produces: table `crm_agent_log(id, appointment_id, action, reasoning, created_at)` — consumed by `crm_agent_log()` in Task 4.

- [ ] **Step 1: Write the failing verification script**

Create `test_crm_agent_schema.php` in the repo root:

```php
<?php
require_once __DIR__ . '/includes/app.php';

$st = db()->prepare(
    'SELECT COLUMN_NAME FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
);
$st->execute(['crm_agent_log']);
$cols = array_column($st->fetchAll(), 'COLUMN_NAME');

$expected = ['id', 'appointment_id', 'action', 'reasoning', 'created_at'];
$missing = array_diff($expected, $cols);

if (empty($cols)) {
    echo "FAIL: crm_agent_log table does not exist\n";
} elseif ($missing) {
    echo "FAIL: missing columns: " . implode(', ', $missing) . "\n";
} else {
    echo "PASS: crm_agent_log has all expected columns\n";
}
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php test_crm_agent_schema.php`
Expected: `FAIL: crm_agent_log table does not exist`

- [ ] **Step 3: Add the table to `sql/tables.sql`**

Append at the end of `sql/tables.sql` (after the `job_applications` table, line 143):

```sql

CREATE TABLE IF NOT EXISTS crm_agent_log (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  appointment_id BIGINT UNSIGNED NOT NULL,
  action         VARCHAR(32) NOT NULL,
  reasoning      TEXT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_crm_agent_log_appt (appointment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 4: Apply it locally by visiting setup.php**

Run: `php -r "require 'setup.php';"` from the project root (or open `http://localhost/randy_project/setup.php` in a browser if using XAMPP's Apache — either re-runs the idempotent table creation).
Expected: no errors; the page/output lists "Tables created (or already present)."

- [ ] **Step 5: Run the verification script again to confirm it passes**

Run: `php test_crm_agent_schema.php`
Expected: `PASS: crm_agent_log has all expected columns`

- [ ] **Step 6: Delete the temporary test script and commit**

```bash
rm test_crm_agent_schema.php
git add sql/tables.sql
git commit -m "feat(db): add crm_agent_log table for CRM agent audit trail"
```

---

### Task 2: Anthropic config section

**Files:**
- Modify: `config.example.php`
- Modify: `config.php` (local only, git-ignored — not committed)
- Test: `test_crm_agent_config.php` (repo root, temporary)

**Interfaces:**
- Produces: `config('anthropic')` returns `['api_key' => string, 'model' => string]` — consumed by `includes/claude.php` in Task 3.

- [ ] **Step 1: Write the failing verification script**

Create `test_crm_agent_config.php`:

```php
<?php
require_once __DIR__ . '/includes/app.php';

$c = config('anthropic');
if (!is_array($c) || !array_key_exists('api_key', $c) || !array_key_exists('model', $c)) {
    echo "FAIL: config('anthropic') missing expected keys\n";
} else {
    echo "PASS: config('anthropic') has api_key and model keys\n";
}
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php test_crm_agent_config.php`
Expected: `FAIL: config('anthropic') missing expected keys`

- [ ] **Step 3: Add the section to `config.example.php`**

In `config.example.php`, add after the `gemini` block (after line 25):

```php
    // ----- Anthropic Claude (powers the autonomous CRM agent) -----
    // Leave empty to disable the CRM agent (crm_agent_enabled setting still
    // gates it, but with no key it can't make any decisions).
    'anthropic' => [
        'api_key' => '',
        'model'   => 'claude-sonnet-5',
    ],

```

- [ ] **Step 4: Add the same section to local `config.php`**

Edit `config.php` (your local, git-ignored copy) and add the same `anthropic` block right after the existing `gemini` block, filling in your real Anthropic API key:

```php
    'anthropic' => [
        'api_key' => 'YOUR_REAL_ANTHROPIC_API_KEY_HERE',
        'model'   => 'claude-sonnet-5',
    ],

```

- [ ] **Step 5: Run the verification script again to confirm it passes**

Run: `php test_crm_agent_config.php`
Expected: `PASS: config('anthropic') has api_key and model keys`

- [ ] **Step 6: Delete the temporary test script and commit**

```bash
rm test_crm_agent_config.php
git add config.example.php
git commit -m "feat(config): add anthropic section for the CRM agent"
```

(`config.php` is git-ignored — nothing to stage there.)

---

### Task 3: `includes/claude.php` — decision client + strict-JSON parser

**Files:**
- Create: `includes/claude.php`
- Test: `test_claude_parse.php` (repo root, temporary)

**Interfaces:**
- Consumes: `config('anthropic')` (Task 2).
- Produces:
  - `claude_is_configured(): bool`
  - `claude_parse_decision(string $raw): array` — pure function, throws `InvalidArgumentException` on malformed input, returns `['action' => string, 'reasoning' => string, 'lead_stage'? => string]`.
  - `claude_decide_action(array $lead): array` — same return shape as `claude_parse_decision`; throws `RuntimeException` on API failure. Consumed by `crm_agent_review_lead()` in Task 4.
  - Constants `CLAUDE_CRM_ACTIONS` (`['update_stage','send_email','alert_owner_sms','sync_calendar','no_action']`) and `CLAUDE_CRM_STAGES` (`['new','contacted','quoted','won','lost']`).

- [ ] **Step 1: Write the failing test for the pure parser**

Create `test_claude_parse.php`:

```php
<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/claude.php';

$pass = 0;
$fail = 0;
function check(bool $cond, string $label): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS: $label\n"; }
    else       { $fail++; echo "FAIL: $label\n"; }
}

// Valid no_action.
$d = claude_parse_decision('{"action":"no_action","reasoning":"too soon"}');
check($d['action'] === 'no_action' && $d['reasoning'] === 'too soon', 'parses valid no_action');

// Valid update_stage.
$d = claude_parse_decision('{"action":"update_stage","lead_stage":"contacted","reasoning":"first review"}');
check($d['action'] === 'update_stage' && $d['lead_stage'] === 'contacted', 'parses valid update_stage');

// Invalid JSON.
try {
    claude_parse_decision('not json');
    check(false, 'rejects invalid JSON');
} catch (InvalidArgumentException $e) {
    check(true, 'rejects invalid JSON');
}

// Unknown action.
try {
    claude_parse_decision('{"action":"delete_everything","reasoning":"oops"}');
    check(false, 'rejects unknown action');
} catch (InvalidArgumentException $e) {
    check(true, 'rejects unknown action');
}

// update_stage without a valid lead_stage.
try {
    claude_parse_decision('{"action":"update_stage","reasoning":"missing stage"}');
    check(false, 'rejects update_stage with no lead_stage');
} catch (InvalidArgumentException $e) {
    check(true, 'rejects update_stage with no lead_stage');
}

echo "\n$pass passed, $fail failed\n";
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php test_claude_parse.php`
Expected: fatal error — `includes/claude.php` doesn't exist yet.

- [ ] **Step 3: Write `includes/claude.php`**

```php
<?php
/** Anthropic Claude integration via cURL — powers the CRM autonomous agent. */

const CLAUDE_CRM_ACTIONS = ['update_stage', 'send_email', 'alert_owner_sms', 'sync_calendar', 'no_action'];
const CLAUDE_CRM_STAGES  = ['new', 'contacted', 'quoted', 'won', 'lost'];

function claude_is_configured(): bool
{
    $c = config('anthropic');
    return !empty($c['api_key']);
}

/**
 * Validate + normalize Claude's raw text response into a decision array.
 * Pure function — no network/DB access — so it's directly testable.
 * Throws InvalidArgumentException on any malformed/out-of-contract response.
 */
function claude_parse_decision(string $raw): array
{
    $data = json_decode(trim($raw), true);
    if (!is_array($data)) {
        throw new InvalidArgumentException('Response is not valid JSON: ' . $raw);
    }
    $action = $data['action'] ?? null;
    if (!in_array($action, CLAUDE_CRM_ACTIONS, true)) {
        throw new InvalidArgumentException('Unknown action: ' . json_encode($action));
    }
    $decision = [
        'action'    => $action,
        'reasoning' => is_string($data['reasoning'] ?? null) ? $data['reasoning'] : '',
    ];
    if ($action === 'update_stage') {
        $stage = $data['lead_stage'] ?? null;
        if (!in_array($stage, CLAUDE_CRM_STAGES, true)) {
            throw new InvalidArgumentException('update_stage requires a valid lead_stage: ' . json_encode($stage));
        }
        $decision['lead_stage'] = $stage;
    }
    return $decision;
}

/** System prompt describing the CRM's stages and the fixed action contract. */
function claude_crm_system_prompt(): string
{
    return "You are a CRM assistant for a painting & drywall business. Given a lead's " .
        "current state, decide the single best next action. Respond with ONLY a JSON " .
        "object, no other text, in exactly this shape:\n" .
        '{"action": "update_stage|send_email|alert_owner_sms|sync_calendar|no_action", ' .
        '"lead_stage": "new|contacted|quoted|won|lost" (only if action is update_stage), ' .
        '"reasoning": "one sentence why"}' . "\n\n" .
        "Rules: move a lead from 'new' to 'contacted' once it's been reviewed for the " .
        "first time. Use 'send_email' for a stage-appropriate nudge if the lead has gone " .
        "quiet. Use 'alert_owner_sms' only when a lead needs a human's urgent attention " .
        "(e.g. gone cold after a quote, or an unusual situation). Use 'sync_calendar' " .
        "only if the lead was just confirmed/rescheduled/completed and might be out of " .
        "sync. Prefer 'no_action' when nothing productive can be done yet.";
}

/** Call Claude's Messages API for one lead. Throws on any failure. */
function claude_decide_action(array $lead): array
{
    $c = config('anthropic');
    if (empty($c['api_key'])) {
        throw new RuntimeException('Anthropic is not configured');
    }
    $model = $c['model'] ?: 'claude-sonnet-5';

    $snapshot = [
        'lead_stage'         => $lead['lead_stage'] ?? 'new',
        'status'             => $lead['status'] ?? null,
        'service_type'       => $lead['service_type'] ?? null,
        'created_at'         => $lead['created_at'] ?? null,
        'crm_notes'          => $lead['crm_notes'] ?? null,
        'crm_last_email_at'  => $lead['crm_last_email_at'] ?? null,
    ];

    $payload = [
        'model'      => $model,
        'max_tokens' => 300,
        'system'     => claude_crm_system_prompt(),
        'messages'   => [
            ['role' => 'user', 'content' => json_encode($snapshot)],
        ],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $c['api_key'],
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT    => 20,
    ]);
    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($body === false || $err) {
        throw new RuntimeException('Claude request failed: ' . $err);
    }
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("Claude returned HTTP {$status}: {$body}");
    }
    $data = json_decode($body, true);
    $text = $data['content'][0]['text'] ?? null;
    if (!is_string($text) || $text === '') {
        throw new RuntimeException('Claude returned no text');
    }
    return claude_parse_decision($text);
}
```

- [ ] **Step 4: Run the test again to confirm it passes**

Run: `php test_claude_parse.php`
Expected: `5 passed, 0 failed`

- [ ] **Step 5: Delete the temporary test script and commit**

```bash
rm test_claude_parse.php
git add includes/claude.php
git commit -m "feat(crm-agent): add Claude decision client with strict-JSON parsing"
```

---

### Task 4: `includes/crm_agent.php` — pre-filter, stage setter, orchestrator

**Files:**
- Create: `includes/crm_agent.php`
- Modify: `api/crm/update.php:22-37` (replace the inline stage-update block with a call to the new shared `crm_set_lead_stage()`, so both the admin API and the agent update leads identically)
- Test: `test_crm_agent_core.php` (repo root, temporary)

**Interfaces:**
- Consumes: `setting_get()`/`setting_set()` (`includes/settings.php`), `claude_decide_action()` (Task 3), `send_crm_stage_email()` (`includes/email.php:275`), `notify_owner_sms()`/`twilio_send_sms()` (`includes/sms.php`), `gcal_sync_for_appointment()` (`includes/gcal.php:225`).
- Produces:
  - `crm_set_lead_stage(int $id, string $stage): void`
  - `crm_agent_enabled(): bool`
  - `crm_agent_max_per_run(): int`
  - `crm_agent_log(int $appointmentId, string $action, ?string $reasoning): void`
  - `crm_agent_leads_needing_review(int $limit = 20): array`
  - `crm_agent_review_lead(array $lead): void` — consumed by Task 5 (cron) and Task 6 (inline hooks).

- [ ] **Step 1: Write the failing test for the DB-backed pieces**

This uses your local XAMPP `randy_db` directly (matching this project's existing manual-testing convention — no mocking layer exists). It creates one throwaway lead, exercises the pure/DB helpers, then cleans up.

Create `test_crm_agent_core.php`:

```php
<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/crm_agent.php';

$pass = 0;
$fail = 0;
function check(bool $cond, string $label): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS: $label\n"; }
    else       { $fail++; echo "FAIL: $label\n"; }
}

// --- Seed one throwaway lead. ---
db()->prepare(
    "INSERT INTO appointments (guest_name, guest_email, guest_phone, service_type, preferred_at, address, lead_stage)
     VALUES ('Test Lead', 'test@example.com', '5555550000', 'Interior Painting', NOW(), '123 Test St', 'new')"
)->execute();
$id = (int) db()->lastInsertId();

// crm_agent_enabled() defaults true with no setting row.
check(crm_agent_enabled() === true, 'crm_agent_enabled defaults to true');

// crm_agent_max_per_run() defaults to 20.
check(crm_agent_max_per_run() === 20, 'crm_agent_max_per_run defaults to 20');

// crm_set_lead_stage rejects invalid stage.
try {
    crm_set_lead_stage($id, 'bogus');
    check(false, 'crm_set_lead_stage rejects invalid stage');
} catch (InvalidArgumentException $e) {
    check(true, 'crm_set_lead_stage rejects invalid stage');
}

// crm_set_lead_stage updates a valid stage.
crm_set_lead_stage($id, 'contacted');
$st = db()->prepare('SELECT lead_stage, crm_last_email_at FROM appointments WHERE id = ?');
$st->execute([$id]);
$row = $st->fetch();
check($row['lead_stage'] === 'contacted', 'crm_set_lead_stage updates lead_stage');
check($row['crm_last_email_at'] !== null, 'crm_set_lead_stage stamps crm_last_email_at for contacted');

// crm_agent_leads_needing_review finds a 'new' lead.
db()->prepare("UPDATE appointments SET lead_stage = 'new' WHERE id = ?")->execute([$id]);
$leads = crm_agent_leads_needing_review(50);
$ids = array_column($leads, 'id');
check(in_array($id, $ids, true), 'crm_agent_leads_needing_review includes a new lead');

// crm_agent_log writes a row.
crm_agent_log($id, 'no_action', 'test reasoning');
$st = db()->prepare('SELECT action, reasoning FROM crm_agent_log WHERE appointment_id = ?');
$st->execute([$id]);
$log = $st->fetch();
check($log && $log['action'] === 'no_action' && $log['reasoning'] === 'test reasoning', 'crm_agent_log writes a row');

// crm_agent_review_lead no-ops when disabled (kill switch), no new log row.
setting_set('crm_agent_enabled', '0');
$before = (int) db()->query('SELECT COUNT(*) FROM crm_agent_log WHERE appointment_id = ' . $id)->fetchColumn();
crm_agent_review_lead(['id' => $id, 'lead_stage' => 'new', 'status' => 'pending']);
$after = (int) db()->query('SELECT COUNT(*) FROM crm_agent_log WHERE appointment_id = ' . $id)->fetchColumn();
check($before === $after, 'crm_agent_review_lead no-ops when kill switch is off');
setting_set('crm_agent_enabled', '1');

// --- Cleanup. ---
db()->prepare('DELETE FROM crm_agent_log WHERE appointment_id = ?')->execute([$id]);
db()->prepare('DELETE FROM appointments WHERE id = ?')->execute([$id]);

echo "\n$pass passed, $fail failed\n";
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `php test_crm_agent_core.php`
Expected: fatal error — `includes/crm_agent.php` doesn't exist yet.

- [ ] **Step 3: Write `includes/crm_agent.php`**

```php
<?php
/** The CRM autonomous agent — decides & executes the next action per lead. */

const CRM_AGENT_VALID_STAGES = ['new', 'contacted', 'quoted', 'won', 'lost'];

/** Shared stage-update logic used by both the admin API (api/crm/update.php) and the agent. */
function crm_set_lead_stage(int $id, string $stage): void
{
    if (!in_array($stage, CRM_AGENT_VALID_STAGES, true)) {
        throw new InvalidArgumentException('Invalid lead stage: ' . $stage);
    }
    $sets = ['lead_stage = ?'];
    $args = [$stage];
    if (in_array($stage, ['contacted', 'quoted'], true)) {
        $sets[] = 'crm_last_email_at = NOW()';
    }
    $args[] = $id;
    db()->prepare('UPDATE appointments SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($args);
}

function crm_agent_enabled(): bool
{
    return setting_get('crm_agent_enabled', '1') === '1';
}

function crm_agent_max_per_run(): int
{
    return (int) setting_get('crm_agent_max_per_run', '20');
}

/** Write one audit row. Never throws — a logging failure shouldn't break the agent. */
function crm_agent_log(int $appointmentId, string $action, ?string $reasoning): void
{
    try {
        db()->prepare(
            'INSERT INTO crm_agent_log (appointment_id, action, reasoning) VALUES (?, ?, ?)'
        )->execute([$appointmentId, $action, $reasoning]);
    } catch (Throwable $e) {
        error_log('[crm_agent] failed to write log for #' . $appointmentId . ': ' . $e->getMessage());
    }
}

/** Deterministic pre-filter: leads plausibly needing a review. */
function crm_agent_leads_needing_review(int $limit = 20): array
{
    $st = db()->prepare(
        "SELECT a.*,
                COALESCE(u.full_name, a.guest_name) AS customer_name,
                COALESCE(u.email, a.guest_email)    AS customer_email
           FROM appointments a LEFT JOIN users u ON u.id = a.customer_id
          WHERE a.lead_stage = 'new'
             OR (a.lead_stage IN ('contacted', 'quoted')
                 AND a.crm_last_email_at IS NOT NULL
                 AND a.crm_last_email_at <= NOW() - INTERVAL 24 HOUR)
          ORDER BY a.updated_at ASC
          LIMIT ?"
    );
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/**
 * Review one lead: ask Claude, execute the decision, log the outcome.
 * Best-effort — never throws, matching this codebase's existing notification pattern.
 */
function crm_agent_review_lead(array $lead): void
{
    if (!crm_agent_enabled()) {
        return;
    }
    $id = (int) $lead['id'];

    try {
        require_once __DIR__ . '/claude.php';
        $decision = claude_decide_action($lead);
    } catch (Throwable $e) {
        error_log('[crm_agent] decision failed for #' . $id . ': ' . $e->getMessage());
        crm_agent_log($id, 'error', $e->getMessage());
        return;
    }

    $action = $decision['action'];
    try {
        switch ($action) {
            case 'update_stage':
                crm_set_lead_stage($id, $decision['lead_stage']);
                if (in_array($decision['lead_stage'], ['contacted', 'quoted'], true)) {
                    require_once __DIR__ . '/email.php';
                    send_crm_stage_email($lead, $decision['lead_stage']);
                }
                break;
            case 'send_email':
                require_once __DIR__ . '/email.php';
                send_crm_stage_email($lead, $lead['lead_stage'] ?? 'contacted', true);
                break;
            case 'alert_owner_sms':
                require_once __DIR__ . '/sms.php';
                if (sms_is_configured()) {
                    $cfg = config('twilio');
                    twilio_send_sms($cfg, $cfg['owner_phone'], 'CRM alert — lead #' . $id . ': ' . $decision['reasoning']);
                }
                break;
            case 'sync_calendar':
                require_once __DIR__ . '/gcal.php';
                gcal_sync_for_appointment($lead);
                break;
            case 'no_action':
                break;
        }
        crm_agent_log($id, $action, $decision['reasoning']);
    } catch (Throwable $e) {
        error_log('[crm_agent] action failed for #' . $id . ' (' . $action . '): ' . $e->getMessage());
        crm_agent_log($id, 'error', $e->getMessage());
    }
}
```

- [ ] **Step 4: Run the test again to confirm it passes**

Run: `php test_crm_agent_core.php`
Expected: `8 passed, 0 failed`

- [ ] **Step 5: Delete the temporary test script**

```bash
rm test_crm_agent_core.php
```

- [ ] **Step 6: Replace the duplicated stage-update logic in `api/crm/update.php` with the shared function**

In `api/crm/update.php`, find this entire block (original lines 22-49 — everything from the `$sets = []` declaration through the final `UPDATE ... execute($args);` call) and replace it in one shot:

```php
$sets = [];
$args = [];

$emailStage = null;
if (array_key_exists('leadStage', $payload)) {
    $valid = ['new', 'contacted', 'quoted', 'won', 'lost'];
    if (!in_array($payload['leadStage'], $valid, true)) {
        json_error('Invalid lead stage', 422);
    }
    $sets[] = 'lead_stage = ?';
    $args[] = $payload['leadStage'];
    if (in_array($payload['leadStage'], ['contacted', 'quoted'], true)) {
        $sets[] = 'crm_last_email_at = NOW()';
        $emailStage = $payload['leadStage'];
    }
}

if (array_key_exists('notes', $payload)) {
    $sets[] = 'crm_notes = ?';
    $args[] = trim((string) $payload['notes']) ?: null;
}

if (!$sets) {
    json_error('Nothing to update', 422);
}

$args[] = $id;
db()->prepare('UPDATE appointments SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($args);
```

becomes:

```php
$emailStage = null;
if (array_key_exists('leadStage', $payload)) {
    try {
        crm_set_lead_stage($id, $payload['leadStage']);
    } catch (InvalidArgumentException $e) {
        json_error('Invalid lead stage', 422);
    }
    if (in_array($payload['leadStage'], ['contacted', 'quoted'], true)) {
        $emailStage = $payload['leadStage'];
    }
}

if (array_key_exists('notes', $payload)) {
    db()->prepare('UPDATE appointments SET crm_notes = ? WHERE id = ?')
        ->execute([trim((string) $payload['notes']) ?: null, $id]);
}
```

Also add `require_once __DIR__ . '/../../includes/crm_agent.php';` near the top of the file (alongside the existing `require_once __DIR__ . '/../../includes/app.php';` on line 3), since `crm_set_lead_stage()` now lives there.

- [ ] **Step 7: Manually verify `api/crm/update.php` still works**

Start XAMPP Apache + MySQL, log in as admin locally, open a lead in the admin dashboard, change its stage, and confirm: (a) the stage updates in the UI, (b) the stage-change email still sends (check `error_log` for `[email] crm stage email sent`), (c) no PHP errors in `C:\xampp\php\logs\php_error_log`.

- [ ] **Step 8: Commit**

```bash
git add includes/crm_agent.php api/crm/update.php
git commit -m "feat(crm-agent): add pre-filter, shared stage setter, and review orchestrator"
```

---

### Task 5: `cron/crm_agent.php` — periodic sweep

**Files:**
- Create: `cron/crm_agent.php`

**Interfaces:**
- Consumes: `crm_agent_enabled()`, `crm_agent_max_per_run()`, `crm_agent_leads_needing_review()`, `crm_agent_review_lead()` (all Task 4).

- [ ] **Step 1: Write `cron/crm_agent.php`**

```php
<?php
// CRM autonomous agent sweep. Run periodically via cron, e.g. every 30 minutes:
//
//   */30 * * * *  php /home/USER/public_html/cron/crm_agent.php
//
// Reviews leads needing attention and lets Claude decide + execute the next
// action for each (see includes/crm_agent.php). Safe to re-run — capped per
// run and gated by the crm_agent_enabled kill switch.
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/crm_agent.php';

if (!crm_agent_enabled()) {
    fwrite(STDOUT, "[crm_agent] disabled via kill switch — nothing to do.\n");
    return;
}

$leads = crm_agent_leads_needing_review(crm_agent_max_per_run());
fwrite(STDOUT, '[crm_agent] reviewing ' . count($leads) . " lead(s).\n");

foreach ($leads as $lead) {
    crm_agent_review_lead($lead);
    fwrite(STDOUT, '[crm_agent] #' . $lead['id'] . " reviewed.\n");
}

fwrite(STDOUT, "[crm_agent] done.\n");
```

- [ ] **Step 2: Seed two test leads locally and run it from the CLI**

Run this once to seed data (adjust as needed), then run the sweep:

```bash
php -r "require 'includes/app.php'; db()->exec(\"INSERT INTO appointments (guest_name, guest_email, guest_phone, service_type, preferred_at, address, lead_stage) VALUES ('CLI Test 1','clitest1@example.com','5555550001','Drywall Repair',NOW(),'1 Test Ave','new'), ('CLI Test 2','clitest2@example.com','5555550002','Exterior Painting',NOW(),'2 Test Ave','new')\");"
php cron/crm_agent.php
```

Expected: output like:
```
[crm_agent] reviewing 2 lead(s).
[crm_agent] #21 reviewed.
[crm_agent] #22 reviewed.
[crm_agent] done.
```
(no `[crm_agent] decision failed` lines — if Claude isn't configured yet with a real key, you'll see decision failures logged and `error` rows in `crm_agent_log`, which is expected until Task 2's local `config.php` has a real key.)

- [ ] **Step 3: Inspect the log table and clean up the test leads**

```bash
php -r "require 'includes/app.php'; foreach (db()->query(\"SELECT appointment_id, action, reasoning FROM crm_agent_log ORDER BY id DESC LIMIT 5\") as \$r) { echo json_encode(\$r) . \"\n\"; }"
php -r "require 'includes/app.php'; db()->exec(\"DELETE FROM crm_agent_log WHERE appointment_id IN (SELECT id FROM appointments WHERE guest_email IN ('clitest1@example.com','clitest2@example.com'))\"); db()->exec(\"DELETE FROM appointments WHERE guest_email IN ('clitest1@example.com','clitest2@example.com')\");"
```

Expected: two `crm_agent_log` rows printed (one per test lead) showing a real `action` and `reasoning` from Claude, then a clean deletion with no errors.

- [ ] **Step 4: Commit**

```bash
git add cron/crm_agent.php
git commit -m "feat(crm-agent): add periodic sweep cron entry point"
```

---

### Task 6: Instant hooks on booking/status changes

**Files:**
- Modify: `book.php:74-76` (after `gcal_sync_for_appointment($appt);`)
- Modify: `api/appointments/update.php` (after the email-notification block, before `json_out`)

**Interfaces:**
- Consumes: `crm_agent_review_lead()` (Task 4).

- [ ] **Step 1: Add the hook to `book.php`**

In `book.php`, right after the existing notification block (lines 74-76):

```php
            send_booking_notification($appt);   // email the business
            notify_owner_sms($appt);            // text Randy
            gcal_sync_for_appointment($appt);   // add to Google Calendar
```

add one more line:

```php
            require_once __DIR__ . '/includes/crm_agent.php';
            crm_agent_review_lead($appt);       // let the CRM agent take a first look
```

- [ ] **Step 2: Add the hook to `api/appointments/update.php`**

At the end of the file, right before the final `json_out(['appointment' => $updated]);` line, add:

```php
// Let the CRM agent react to this status change.
require_once __DIR__ . '/../../includes/crm_agent.php';
if ($updated) {
    crm_agent_review_lead($updated);
}
```

- [ ] **Step 3: Manually verify both paths locally**

1. On `http://localhost/randy_project/book.php`, submit a test booking as a guest. Check `error_log` / `crm_agent_log` (via the same `php -r` query from Task 5 Step 3) for a new row tied to that booking's appointment id.
2. In the admin dashboard, confirm/decline/complete an existing test appointment. Check `crm_agent_log` again for a new row tied to that appointment.
3. Confirm neither action visibly slows down or breaks the booking/admin flow (the CRM agent call should complete in a couple seconds, same latency class as the existing email/SMS calls already happening inline).

- [ ] **Step 4: Commit**

```bash
git add book.php api/appointments/update.php
git commit -m "feat(crm-agent): trigger instant review on new bookings and status changes"
```

---

### Task 7: Twilio trial-account fix (operational, not code)

**Files:** none (no code changes — the existing `includes/sms.php` is already correct; Twilio itself rejects the request)

- [ ] **Step 1: Verify the owner's number in the Twilio console**

Log in at twilio.com/console, go to **Phone Numbers → Verified Caller IDs**, and add `+14845463660` (Randy's phone, from `config.php`'s `twilio.owner_phone`). Alternatively, upgrade the account from trial to a paid plan (removes the verified-number restriction entirely) — the site owner's call to make based on expected SMS volume.

- [ ] **Step 2: Verify the fix using the existing test script**

The repo already has `sms_test.php` for exactly this. Run it locally (or upload to production temporarily):

Run: `php sms_test.php` (or visit it in a browser if served via Apache)
Expected: `SUCCESS — SMS sent!` (previously this failed with `Twilio HTTP 400... unverified`, per the production error log).

- [ ] **Step 3: No commit needed** — this task is purely an account-configuration change on Twilio's side, verified with a script that already exists in the repo.

---

### Task 8: Production deployment

**Files:** none (deployment checklist only)

- [ ] **Step 1: Upload the new/changed files to Hostinger `public_html`**

Via hPanel File Manager (or your usual upload method), upload:
- `sql/tables.sql` (updated)
- `includes/claude.php` (new)
- `includes/crm_agent.php` (new)
- `api/crm/update.php` (updated)
- `cron/crm_agent.php` (new)
- `book.php` (updated)
- `api/appointments/update.php` (updated)
- `config.example.php` (updated — informational only, `config.php` on the server is separate and untracked)

- [ ] **Step 2: Add the Anthropic key to production `config.php`**

Via File Manager, edit `public_html/config.php` directly on the server and add the same `anthropic` block from Task 2 Step 4, with the real API key.

- [ ] **Step 3: Re-run `setup.php` once to create `crm_agent_log` on production**

Re-upload `setup.php` (if it was deleted per the earlier 500-error fix) and visit `https://randyspaintdrywall.com/setup.php` once. Confirm "Tables created (or already present)" in the output, then delete `setup.php` from the server again.

- [ ] **Step 4: Add the cron job in hPanel**

hPanel → **Websites → Manage → Advanced → Cron Jobs** → add a new job:
- Command: `php /home/u872227812/domains/randyspaintdrywall.com/public_html/cron/crm_agent.php`
- Schedule: every 30 minutes (`*/30 * * * *`)

- [ ] **Step 5: Confirm the kill switch is on (agent enabled) and watch the first live run**

No action needed — `crm_agent_enabled()` defaults to `'1'` with no `settings` row present. After the first scheduled run (or the next real booking), check `crm_agent_log` in phpMyAdmin to confirm rows are appearing with sensible actions/reasoning before trusting it further. To pause it at any time without a deploy: `UPDATE settings SET svalue = '0' WHERE skey = 'crm_agent_enabled'` (insert the row if it doesn't exist yet: `INSERT INTO settings (skey, svalue) VALUES ('crm_agent_enabled', '0')`).
