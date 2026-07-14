# Applicant Status-Change Email Notifications

**Date:** 2026-07-14
**Status:** Approved (design)

## Summary

When the admin changes a job applicant's status (`new`, `reviewed`, `hired`, `rejected`) in the Careers tab, automatically send the applicant a best-effort email reflecting the new status — mirroring the existing pattern where confirming a quote (`lead_stage = 'quoted'`) emails the customer via `send_crm_stage_email()`.

## Goals

- New function `send_job_application_status_email(array $application, string $status): void` in `includes/email.php`, following the same structure/conventions as `send_crm_stage_email()`: `email_is_configured()` guard, `smtp_send_mail()`, wrapped in try/catch, logs via `error_log()`, never throws.
- Called from `api/careers/applications-update.php` immediately after the status `UPDATE` succeeds, using the freshly-fetched updated row (already selects `email`, `name`, and `position_title` via `COALESCE(p.title, a.position_title_snapshot)`).
- Fires for all four valid statuses — `new`, `reviewed`, `hired`, `rejected` — every time this endpoint completes a successful update, regardless of whether the status actually changed (matches `api/crm/update.php`, which does not compare old vs. new stage before emailing).
- Applicant email is read directly from `job_applications.email` — no guest/registered-user fallback lookup needed (unlike appointments, this table always has a direct email column).

## Non-Goals (YAGNI)

- No email preference/opt-out mechanism for applicants — same as every other transactional email in this codebase (booking, quote, CRM stage emails all have none).
- No "did status change" comparison/dedup logic — not present in the CRM email trigger this mirrors, so not added here either.
- No HTML email templates — plain text via `smtp_send_mail()`, matching every other email in `includes/email.php`.

## Email Content

Each template follows the existing greeting/body/signature shape used by `send_crm_stage_email()`: `Hi {name},` (or `Hello,` if no name), blank line, body, blank line, business signature block (`$b['owner']`, `$b['name']`, `$b['phone']`, `$b['website']`).

`$position` resolves from `$application['position_title'] ?? $application['position_title_snapshot'] ?? 'the position'`.

| Status | Subject | Body |
|---|---|---|
| `new` | `We've received your application — {position}` | Thanks for applying, {name}! We've received your application for {position} and will review it shortly. |
| `reviewed` | `Update on your application — {position}` | We've reviewed your application for {position} and are still considering candidates — we'll follow up soon. |
| `hired` | `Congratulations — {position} at {business}` | Congratulations! We'd like to offer you the {position} role. We'll be in touch shortly with next steps. Feel free to reply to this email or call/text us at {phone} with any questions. |
| `rejected` | `Update on your application — {position}` | Thank you for your interest in {position}. After careful review, we've decided to move forward with other candidates at this time. We'll keep your application on file for future openings. |

## Architecture

### `includes/email.php`

Add `send_job_application_status_email()` after the existing `send_job_application_notification()`. Structure:

```php
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

    $cfg = config('email');
    $b   = business_info();
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
                    "We've reviewed your application for " . $position . ' and are still considering candidates — we\'ll follow up soon.',
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

### `api/careers/applications-update.php`

After the existing final `SELECT ... AS position_title` fetch (which already returns `email`, `name`, `position_title`), add:

```php
require_once __DIR__ . '/../../includes/email.php';
send_job_application_status_email($application, $status);
```

placed after `$st->fetch()` is assigned to a variable (currently inlined directly into `json_out(['application' => $st->fetch()])` — this needs to be split into a variable first so it can be passed to the email function before being returned).

## Testing

- Manual: in the admin Careers tab, change an applicant's status through each of the four values (`new`, `reviewed`, `hired`, `rejected`) and confirm an email is received at the applicant's address with the matching subject/body for each.
- Confirm the API response and status persistence are unaffected if email sending fails (best-effort — check `error_log` for a `[email] application status email failed` line, e.g. by testing with `email_is_configured()` returning false).
- Confirm no email is sent, and no error, when `job_applications.email` is unexpectedly empty (defensive guard, though the column is `NOT NULL` in the schema).
