# Booking Contact Report (PDF) — Design

**Date:** 2026-07-23
**Status:** Approved

## Problem

The admin Reports tab shows only aggregate counts (bookings per day/week/month, broken
down by status). Randy needs the opposite: a printable list of *who* booked, with their
contact details, so he can work through it as a call sheet.

## Scope

**In:** A date-ranged, one-row-per-booking contact list inside the Reports tab, printable
to PDF via the browser's print dialog.

**Out:**
- Any PDF library or Composer dependency — the live host has neither, and the existing
  Reports PDF already works through `window.print()`.
- Address and customer notes columns — they don't fit a portrait table and were
  explicitly traded away for readability.
- Changes to the existing Summary report.

## Decisions

| Question | Decision |
|---|---|
| Which bookings? | All statuses (pending, confirmed, declined, cancelled, completed) in the range |
| Columns | Date, Name, Phone, Email, Service, Status |
| Date range control | From/To date inputs plus quick presets |
| Which date does the range filter on? | `created_at` — when the booking was submitted |

Filtering on `created_at` matches the existing `api/admin/reports.php`, which documents the
same choice in its header comment. The two reports therefore cover the same rows for the
same range.

## Placement

Sub-tabs inside the existing Reports panel:

```
Reports
 ├─ [ Summary ]  [ Contact list ]
 └─ exactly one visible at a time
```

The print stylesheet (`assets/css/styles.css:481-490`) prints the whole
`[data-panel="reports"]` element. With sub-tabs only one block is in the DOM-visible state,
so the existing `window.print()` call prints the right one with no new print-scoping rules.
Two side-by-side blocks would put both into every PDF.

## Backend

New endpoint `api/admin/contact-report.php`, following `api/admin/reports.php`:

```php
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();
```

**Request:** `GET api/admin/contact-report.php?from=YYYY-MM-DD&to=YYYY-MM-DD`
Omitting both parameters means "all time".

**Query:**

```sql
SELECT a.id, a.created_at, a.status, a.service_type,
       COALESCE(u.full_name, a.guest_name)  AS customer_name,
       COALESCE(u.email,     a.guest_email) AS customer_email,
       COALESCE(a.phone,     a.guest_phone) AS customer_phone
  FROM appointments a
  LEFT JOIN users u ON u.id = a.customer_id
 WHERE a.created_at >= ? AND a.created_at < ? + INTERVAL 1 DAY
 ORDER BY a.created_at DESC
 LIMIT 2000
```

For "all time" the `WHERE` clause is omitted entirely and no date parameters are bound; the
`SELECT`, `ORDER BY` and `LIMIT` are otherwise identical.

The `COALESCE` pairs mirror `api/crm/update.php:40-45`, which resolves the same
registered-user-or-guest split.

`to` is compared with `< to + INTERVAL 1 DAY` so a booking created at 14:30 on the `to`
date is included — a plain `<= to` would silently drop the final day's rows.

**Response:**

```json
{ "rows": [ { "id": 23, "createdAt": "2026-07-22 20:13:13", "name": "…",
              "phone": "…", "email": "…", "serviceType": "…", "status": "confirmed" } ],
  "from": "2026-07-01", "to": "2026-07-31" }
```

`from`/`to` are echoed back so the client renders the heading from what the server actually
applied rather than from its own inputs.

**Validation:**

- Each of `from`/`to`, when present, must match `YYYY-MM-DD` and be a real calendar date —
  otherwise `json_error(…, 422)`.
- Both absent → all time (no date filtering).
- One present and the other absent: the absent one defaults to the first day of the current
  month (`from`) or today (`to`). `from`/`to` in the response then report those defaults.
- `from > to` → `json_error('Start date must be on or before the end date', 422)`.
- Result set capped at 2000 rows to bound the response and the printed document.

## Frontend

`initReports()` in `assets/js/admin.js:136-196` gains a sub-tab switch. The existing summary
code moves into a `renderSummary()` path unchanged; a new `renderContacts()` path is added.

```
[ Summary ]  [ Contact list ]

[ This month ] [ Last 30 days ] [ This year ] [ All time ]

From: [2026-07-01]   To: [2026-07-31]        [ Download PDF ]

Date      Name             Phone         Email                  Service          Status
Jul 24    Meredith Spear   610-762-6882  meredith.f@gmail.com   Drywall repair   Confirmed
```

Presets compute `from`/`to` in JS and reload; "All time" clears both. The table reuses the
existing `.report-table` / `.report-table-wrap` classes.

Empty range renders `No bookings in this range.` in muted text, matching the summary
report's empty state.

All six cell values go through the existing `escapeHtml()` helper — name, phone, email and
service type all originate from the public booking form.

## PDF output

The Download PDF button sets the shared `.report-print-heading` element and calls
`window.print()`, as the summary report does:

```
Randy's Painting & Drywall Services — Booking Contact Report
Jul 1 – Jul 31, 2026 · 14 bookings · Generated Jul 23, 2026
```

Two additions to the `@media print` block in `assets/css/styles.css`:

- `.report-table thead { display: table-header-group; }` so column headers repeat on every
  printed page.
- Explicit cell borders, since the screen style leans on background fills that most browsers
  drop when printing.

The new sub-tab strip and the date controls are hidden in print alongside the existing
`[data-rp-filter], [data-rp-print]` rule.

## Error handling

- Non-admin or logged-out request → `require_admin_api()` returns 401; `admin.js` surfaces it
  through the existing `toast(err.message, 'error')` path.
- Invalid dates → 422 with a human-readable message, shown in the same toast.
- Network or server failure → the `api.get` helper throws
  `Request failed (<status>)` (`assets/js/app.js:32`) and the toast shows it.

## Privacy

The generated PDF contains customer names, phone numbers and email addresses. It is reachable
only behind an admin session and is downloaded locally, but the file itself carries no access
control — anyone it is forwarded to holds the full customer list.

## Verification

The repo has no test framework, so verification is manual, matching how the rest of the admin
features are checked:

1. Seed or use existing bookings spanning at least two months.
2. Each preset returns the expected row count; the boundary booking on the `to` date appears.
3. `from` after `to` shows the 422 message rather than an empty table.
4. A logged-out `curl` to the endpoint returns 401 JSON, not data.
5. Print preview: headers repeat on page 2, controls are absent, heading shows the right
   range and count.
6. A booking from a registered user and one from a guest both resolve name/email/phone.
