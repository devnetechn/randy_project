# Applicant Status-Change Email Notifications Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** When the admin changes a job applicant's status (`new`, `reviewed`, `hired`, `rejected`) in the Careers tab, automatically send the applicant a best-effort email reflecting the new status.

**Architecture:** One new function `send_job_application_status_email(array $application, string $status): void` added to `includes/email.php`, following the exact structure of the existing `send_crm_stage_email()` (quote-confirmation email): `email_is_configured()` guard, `Hi {name},` greeting + business signature block, a `switch` over the four statuses picking subject/body, `smtp_send_mail()` wrapped in try/catch, `error_log()` on failure, never throws. Wired into `api/careers/applications-update.php` immediately after the status `UPDATE` succeeds, reusing the row this endpoint already fetches (which includes `email`, `name`, `position_title`).

**Tech Stack:** Vanilla PHP 8 + PDO/MySQL (XAMPP). **No test framework exists** — verification is via `php -l` (lint), short PHP CLI scripts run with `C:\xampp\php\php.exe`, and manual browser checks against `http://localhost/randy/admin/index.php` (Careers tab) with a real inbox check (email is live-configured in `config.php` against a real Gmail account).

## Global Constraints

- Email sending is always best-effort: never let a failed/misconfigured email send break the status-update API response or its JSON contract.
- No new email-preference/opt-out mechanism, no HTML templates, no "did status actually change" comparison — matches every other transactional email function in `includes/email.php` (see spec's Non-Goals).
- Exact subject/body copy per status is fixed by the spec (`docs/superpowers/specs/2026-07-14-applicant-status-email-design.md`) — do not improvise wording.
- Commit after each task.

---

## File Structure

| File | Responsibility | Action |
|------|----------------|--------|
| `includes/email.php` | `send_job_application_status_email()` | Modify |
| `api/careers/applications-update.php` | Call the new function after a successful status update | Modify |

---

## Task 1: Add `send_job_application_status_email()` to `includes/email.php`

**Files:**
- Modify: `includes/email.php` (append after `send_job_application_notification()`, which currently ends at line 387)

**Interfaces:**
- Produces: `send_job_application_status_email(array $application, string $status): void` — expects `$application` to have keys `id`, `email`, `name`, and `position_title` (falls back to `position_title_snapshot` if `position_title` is absent, then to the literal string `'the position'`). `$status` must be one of `'new'`, `'reviewed'`, `'hired'`, `'rejected'`; any other value is a silent no-op (no email sent, no error).

- [ ] **Step 1: Append the new function**

Open `includes/email.php` and add the following after the closing `}` of `send_job_application_notification()` (currently line 387):

```php

/**
 * Email the applicant when the admin changes their application status.
 * Best-effort — never throws.
 */
function send_job_application_status_email(array $application, string $status): void
{
    if (!email_is_configured()) {
        return;
    }
    $email = $application['email'] ?? '';
    if (!$email) {
        error_log('[email] application status email skipped #' . ($application['id'] ?? '?') . ' — no applicant email');
        return;
    }

    $cfg  = config('email');
    $b    = business_info();
    $name = $application['name'] ?? '';
    $hi   = $name ? 'Hi ' . $name . ',' : 'Hello,';
    $position = $application['position_title'] ?? $application['position_title_snapshot'] ?? 'the position';
    $sig = implode("\r\n", ['', $b['owner'], $b['name'], $b['phone'], $b['website']]);

    try {
        switch ($status) {
            case 'hired':
                $subject = 'Congratulations — ' . $position . ' at ' . $b['name'];
                $body = implode("\r\n", [
                    $hi, '',
                    "Congratulations! We'd like to offer you the " . $position . ' role.',
                    "We'll be in touch shortly with next steps. Feel free to reply to this email or call/text us at " . $b['phone'] . ' with any questions.',
                    $sig,
                ]);
                break;
            case 'rejected':
                $subject = 'Update on your application — ' . $position;
                $body = implode("\r\n", [
                    $hi, '',
                    'Thank you for your interest in ' . $position . '.',
                    "After careful review, we've decided to move forward with other candidates at this time. We'll keep your application on file for future openings.",
                    $sig,
                ]);
                break;
            case 'reviewed':
                $subject = 'Update on your application — ' . $position;
                $body = implode("\r\n", [
                    $hi, '',
                    "We've reviewed your application for " . $position . " and are still considering candidates — we'll follow up soon.",
                    $sig,
                ]);
                break;
            case 'new':
                $subject = "We've received your application — " . $position;
                $body = implode("\r\n", [
                    $hi, '',
                    'Thanks for applying' . ($name ? ', ' . $name : '') . "! We've received your application for " . $position . ' and will review it shortly.',
                    $sig,
                ]);
                break;
            default:
                return;
        }

        smtp_send_mail($cfg, $email, $subject, $body);
        error_log('[email] application status email (' . $status . ') sent for #' . $application['id'] . ' → ' . $email);
    } catch (Throwable $e) {
        error_log('[email] application status email failed for #' . ($application['id'] ?? '?') . ': ' . $e->getMessage());
    }
}
```

- [ ] **Step 2: Lint the file**

```bash
C:\xampp\php\php.exe -l includes/email.php
```

Expected: `No syntax errors detected in includes/email.php`

- [ ] **Step 3: Verify the function sends for all four statuses**

Replace `you@example.com` below with an inbox you can actually check. This calls the function directly, bypassing the API/auth layer entirely.

```bash
C:\xampp\php\php.exe -r "
require 'includes/app.php';
require 'includes/email.php';
\$app = ['id' => 999999, 'email' => 'you@example.com', 'name' => 'Test Applicant', 'position_title' => 'Test Position'];
foreach (['new','reviewed','hired','rejected'] as \$status) {
    send_job_application_status_email(\$app, \$status);
    echo \$status . ' done' . PHP_EOL;
}
"
```

Expected: four lines (`new done`, `reviewed done`, `hired done`, `rejected done`), no PHP errors/warnings printed. Check `C:\xampp\apache\logs\error.log` (or wherever `error_log()` writes in this XAMPP setup) for four `[email] application status email (...) sent for #999999 → you@example.com` lines. Check the test inbox for four emails with the subjects from the table in the spec, each with the correct body and a signature block.

- [ ] **Step 4: Verify unknown status and missing email are silent no-ops**

```bash
C:\xampp\php\php.exe -r "
require 'includes/app.php';
require 'includes/email.php';
send_job_application_status_email(['id' => 1, 'email' => 'you@example.com', 'name' => 'X'], 'bogus');
send_job_application_status_email(['id' => 2, 'email' => '', 'name' => 'X'], 'hired');
echo 'no crash' . PHP_EOL;
"
```

Expected: `no crash` printed, no fatal errors. `error.log` should show a `no applicant email` line for id #2 and nothing for id #1 (the `bogus` status hits the `default: return;` branch silently).

- [ ] **Step 5: Commit**

```bash
git add includes/email.php
git commit -m "feat(email): add applicant status-change email notification"
```

---

## Task 2: Wire the email into `api/careers/applications-update.php`

**Files:**
- Modify: `api/careers/applications-update.php` (currently 38 lines; full current contents shown below)

**Interfaces:**
- Consumes: `send_job_application_status_email(array $application, string $status): void` from Task 1.

Current contents of `api/careers/applications-update.php`:

```php
<?php
/** Update a job application's status. Body: { id, status } */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$id = (int) ($payload['id'] ?? 0);
$status = $payload['status'] ?? '';
$valid = ['new', 'reviewed', 'hired', 'rejected'];

if ($id <= 0) {
    json_error('A valid application id is required', 422);
}
if (!in_array($status, $valid, true)) {
    json_error('Invalid status', 422);
}

$st = db()->prepare('SELECT id FROM job_applications WHERE id = ?');
$st->execute([$id]);
if (!$st->fetch()) {
    json_error('Application not found', 404);
}

db()->prepare('UPDATE job_applications SET status = ? WHERE id = ?')->execute([$status, $id]);

$st = db()->prepare(
    'SELECT a.*, COALESCE(p.title, a.position_title_snapshot) AS position_title
       FROM job_applications a
       LEFT JOIN job_positions p ON p.id = a.position_id
      WHERE a.id = ?'
);
$st->execute([$id]);
json_out(['application' => $st->fetch()]);
```

- [ ] **Step 1: Split the final fetch into a variable and call the email function**

Replace the last three lines (from `$st = db()->prepare(` through `json_out(['application' => $st->fetch()]);`) with:

```php
$st = db()->prepare(
    'SELECT a.*, COALESCE(p.title, a.position_title_snapshot) AS position_title
       FROM job_applications a
       LEFT JOIN job_positions p ON p.id = a.position_id
      WHERE a.id = ?'
);
$st->execute([$id]);
$application = $st->fetch();

require_once __DIR__ . '/../../includes/email.php';
send_job_application_status_email($application, $status);

json_out(['application' => $application]);
```

- [ ] **Step 2: Lint the file**

```bash
C:\xampp\php\php.exe -l api/careers/applications-update.php
```

Expected: `No syntax errors detected in api/careers/applications-update.php`

- [ ] **Step 3: Manual end-to-end verification through the admin UI**

1. In `config.php`, temporarily note the current `email.to`/`email.user` — no change needed, just be aware which inbox receives mail.
2. Insert a throwaway application row you can email to yourself:
   ```bash
   C:\xampp\php\php.exe -r "
   require 'includes/app.php';
   db()->prepare('INSERT INTO job_applications (position_id, position_title_snapshot, name, email, phone, resume_path, status) VALUES (NULL, ?, ?, ?, ?, ?, ?)')
       ->execute(['Test Position', 'Test Applicant', 'you@example.com', '555-0100', 'placeholder.pdf', 'new']);
   echo db()->lastInsertId() . PHP_EOL;
   "
   ```
   Note the printed id.
3. Log into `http://localhost/randy/admin/index.php`, open the **Careers** tab → **Applicants** sub-view, find the test applicant, and change its status dropdown to **Reviewed**, then **Hired**, then **Rejected** (one at a time, waiting for each save to complete).
4. Expected after each change: the status persists in the UI on refresh, and an email arrives at `you@example.com` with the subject/body matching that status from the spec table.
5. Clean up the test row:
   ```bash
   C:\xampp\php\php.exe -r "require 'includes/app.php'; db()->exec('DELETE FROM job_applications WHERE name = \'Test Applicant\' AND email = \'you@example.com\'');"
   ```

- [ ] **Step 4: Confirm the API response shape is unchanged**

Re-read the diff and confirm `json_out(['application' => $application])` still returns the same `application` object shape as before (now built from a variable instead of an inline `$st->fetch()` call) — no field added or removed from the JSON response.

- [ ] **Step 5: Commit**

```bash
git add api/careers/applications-update.php
git commit -m "feat(careers): email applicant on status change"
```

---

## Done

Both tasks together deliver the full spec: `docs/superpowers/specs/2026-07-14-applicant-status-email-design.md`. No further tasks — this is a two-file, additive change with no schema or UI changes required (the status `<select>` and its change handler already exist from the Careers feature).
