# CRM Autonomous Agent — Design

## Problem

The CRM pipeline (`appointments.lead_stage`: new → contacted → quoted → won/lost) is
currently automated only by fixed timers: `cron/followup_emails.php` re-emails a lead
24h after it was last contacted/quoted, and stage-change emails only fire when an admin
manually updates a lead via `api/crm/update.php`. There's no judgment applied — no one
(human or otherwise) is deciding *whether* a lead actually needs a nudge, just whether
enough time has passed.

## Goal

Replace/augment the timer-only automation with a Claude-powered agent that reviews
leads and autonomously decides & executes the next action — no human approval step —
while remaining auditable and safely bounded.

## Approach

**Hybrid: deterministic pre-filter + Claude for the actual decision.**

A cheap SQL filter (`crm_agent_leads_needing_review()`) narrows the field to leads that
plausibly need attention (never contacted, or stale past their stage's threshold) —
avoiding an API call for every lead on every tick. Only leads that pass the filter go to
Claude, which decides the concrete action.

Rejected alternative: calling Claude for every lead on every trigger, no pre-filter.
Simpler, but burns API calls/latency on leads that obviously don't need action yet
(e.g. updated 5 minutes ago).

## Triggers

Both, per requirements:
1. **Scheduled sweep** — `cron/crm_agent.php`, run periodically via Hostinger Cron Jobs
   (e.g. every 30 min), same operational pattern as the existing
   `cron/followup_emails.php`.
2. **Instant reaction** — inline call to `crm_agent_review_lead($lead)` right after a
   booking is created (`book.php`) or a lead's status/stage changes
   (`api/appointments/update.php`, `api/crm/update.php`). Synchronous, best-effort —
   matches the existing pattern already used there for email/SMS/calendar side effects.

## Allowed actions

All four, decided per-lead by Claude:
- **Update lead stage** (`new → contacted → quoted → won/lost`)
- **Send email** — reuses `send_crm_stage_email()`
- **Alert owner via SMS** — reuses `notify_owner_sms()`. Important scope note: the
  existing Twilio integration only texts the business owner (Randy), not customers —
  there is no customer-facing SMS sender in this codebase. "Autonomous SMS" here means
  the agent can decide to alert Randy (e.g. "lead has gone cold, needs a human look"),
  not text customers directly.
- **Sync Google Calendar** — reuses `gcal_sync_for_appointment()` / `gcal_delete_for_appointment()`

## Files

- `includes/claude.php` — new. Mirrors `includes/gemini.php`'s shape:
  - `claude_is_configured(): bool`
  - `claude_decide_action(array $lead): array` — calls Anthropic's Messages API
    (cURL, no SDK, matching this codebase's dependency-free style). Sends a compact
    JSON snapshot of the lead (stage, status, timestamps, notes, service type, time
    since last contact) plus a system prompt listing the allowed actions. Requires a
    strict-JSON response: `{"action": "...", "lead_stage"?: "...", "reasoning": "..."}`.
    Any response that doesn't parse into that shape is treated as a failure (logged,
    no action taken) — no free-text parsing, no partial trust of model output beyond
    the fixed action enum.
- `includes/crm_agent.php` — new. The shared brain:
  - `crm_agent_leads_needing_review(): array` — deterministic SQL pre-filter.
  - `crm_agent_review_lead(array $lead): void` — kill-switch check → `claude_decide_action()`
    → execute the chosen action via existing functions (see below) → write one row to
    `crm_agent_log` (including `no_action` and `error` outcomes).
  - `crm_set_lead_stage()` — extracted from `api/crm/update.php`'s inline stage-update
    SQL into a shared function, so the admin API and the agent update leads identically
    (avoids duplicating that logic).
- `cron/crm_agent.php` — new. Periodic sweep entry point; loads leads needing review,
  processes up to the per-run cap, logs to stdout like `followup_emails.php` does.
- Inline hook additions (one line each) in `book.php`, `api/appointments/update.php`,
  `api/crm/update.php`.
- `sql/tables.sql` — add `crm_agent_log` table.
- `config.php` / `config.example.php` — add an `anthropic` section:
  ```php
  'anthropic' => [
      'api_key' => '',
      'model'   => 'claude-sonnet-5',
  ],
  ```

## Data model

```sql
CREATE TABLE IF NOT EXISTS crm_agent_log (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  appointment_id BIGINT UNSIGNED NOT NULL,
  action         VARCHAR(32) NOT NULL,   -- update_stage | send_email | alert_owner_sms | sync_calendar | no_action | error
  reasoning      TEXT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_crm_agent_log_appt (appointment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Guardrails

1. **Kill switch** — `settings` key `crm_agent_enabled` (default `'1'`). Checked first
   in both `cron/crm_agent.php` and `crm_agent_review_lead()`. Flip to `'0'` to disable
   instantly, no deploy required.
2. **Per-run cap** — `settings` key `crm_agent_max_per_run` (default `'20'`). The cron
   sweep stops after touching that many leads. Inline hooks are naturally capped at one
   lead per event.
3. **Action log** — every decision (including `no_action` and `error`) is written to
   `crm_agent_log` with Claude's stated reasoning, for after-the-fact audit even though
   there's no pre-approval step.

## Error handling

Matches the existing codebase convention exactly: Claude API failures, malformed JSON,
or any downstream action failure (email/SMS/calendar) are caught individually
(`try/catch Throwable`) and logged — never thrown up to break a booking or admin
request. One lead's failure never stops the rest of a batch in the cron sweep.

## Twilio trial-account limitation (operational, not code)

The Twilio account is currently in trial mode, which rejects SMS to any unverified
number — this is why `[sms] owner alert failed` appears repeatedly in production logs.
The code is already correct (best-effort, catches and logs without breaking bookings).
Fix is operational: verify `+14845463660` at
twilio.com/user/account/phone-numbers/verified, or upgrade to a paid Twilio account.
Tracked as a checklist item in the implementation plan, not a code change.

## Testing

No test framework exists in this codebase (vanilla PHP, no Composer/PHPUnit). Manual
verification plan:
1. Add `crm_agent_log` via `setup.php` (extends `sql/tables.sql`, same idempotent
   CREATE TABLE IF NOT EXISTS pattern already used for every other table).
2. Seed a couple of test leads locally (XAMPP), run `php cron/crm_agent.php` from the
   CLI, inspect `crm_agent_log` rows and confirm decisions look sane before wiring in
   a real Claude API key.
3. Manually submit a test booking on localhost and confirm a `crm_agent_log` row
   appears alongside the existing email/SMS/calendar log lines from the inline hook.
4. Deploy to production with the kill switch left on (`crm_agent_enabled = '1'`), watch
   `crm_agent_log` after the first real cron run before trusting it further.

## Out of scope

- Customer-facing SMS (Twilio integration only supports owner alerts today).
- An admin UI toggle for the kill switch / max-per-run settings (direct DB edit for now).
- Retrying failed Claude calls or failed actions — a failure is logged and skipped;
  the next scheduled sweep or the next inline event will naturally re-evaluate the lead.
