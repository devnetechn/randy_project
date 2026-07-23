# Overview Chart — Month Picker with Daily Breakdown

**Date:** 2026-07-23
**Status:** Approved

## Problem

The admin Overview chart plots one point per month across the last six months. It answers
"how are we trending across the year" but not "how did this month actually go" — Randy
cannot see which days brought quote requests.

## Scope

**In:** Replace the six-month trend with a single-month view. A month picker above the chart
selects which month; the chart plots one point per day within it.

**Out:**
- The KPI cards above the chart (Live now / Today / This month) — untouched.
- Any change to the three series or their colours.
- The Reports tab, which keeps its own separate monthly/weekly/daily summary.

## Decisions

| Question | Decision |
|---|---|
| Keep the six-month view as an option? | No — the chart always shows exactly one month |
| Which months appear in the picker? | Only months that have data, newest first |
| Chart type | Line, unchanged — 31 points × 3 series would collide as bars |
| Default selection | The newest month that has data; today's month if the database is empty |
| Current, unfinished month | Truncated at today — no trailing run of zeros for days that haven't happened |

## Backend

`api/admin/analytics.php` is rewritten from a six-month aggregate to a month-scoped one.

**Request:** `GET api/admin/analytics.php?month=YYYY-MM`, the parameter optional.

**Available months** — the union of the three source tables, newest first:

```sql
SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') AS m FROM appointments
UNION SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') FROM conversations
UNION SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') FROM users WHERE role = 'customer'
ORDER BY m DESC
```

**Daily series** — for the selected month, grouped by day and zero-filled so the line stays
continuous across days with no activity:

```sql
SELECT DAY(created_at) AS d, COUNT(*) AS total
  FROM appointments
 WHERE created_at >= :start AND created_at < :start + INTERVAL 1 MONTH
 GROUP BY d
```

The same shape runs against `conversations` and `users` (the latter filtered to
`role = 'customer'`), mirroring how the current endpoint reuses one closure across the three
series.

**Day range.** The series runs from day 1 to the last day of the month, except when the
selected month is the current month — then it stops at today. A half-finished month would
otherwise show a cliff to zero for days that have not happened yet, which reads as a
collapse in business rather than an absence of data.

**Response:**

```json
{
  "months":   [{"value": "2026-07", "label": "July 2026"}, {"value": "2026-06", "label": "June 2026"}],
  "month":    "2026-07",
  "labels":   ["1", "2", "3", "…", "23"],
  "bookings": [0, 0, 1, "…"],
  "leads":    [0, 0, 0, "…"],
  "signups":  [0, 1, 0, "…"]
}
```

`months` is echoed on every response so the client populates its picker from one round trip.
`month` reports what the server actually selected, which may differ from what was asked
(omitted parameter, or a month with no data).

**Validation:**

- `month`, when present, must match `^\d{4}-(0[1-9]|1[0-2])$` — otherwise `json_error(…, 422)`.
- Absent or empty → the newest month in `months`, or the current month when `months` is empty.
- A syntactically valid month with no data is served as an all-zero series rather than an
  error; the picker cannot produce one, but a hand-typed URL can.

## Frontend

`initOverview()` in `assets/js/admin.js:16-72`.

The section heading `Last 6 months` (line 25) becomes `Daily activity`, with the picker
beside it:

```
DAILY ACTIVITY                    [ July 2026 ▾ ]
┌──────────────────────────────────────────────┐
│  ● Quotes requested  ● New leads  ● New signups
│ 4 ┤        ╱╲                                │
│ 2 ┤   ╱╲__╱  ╲___╱╲                          │
│ 0 ┼───────────────────────────────────────── │
│   1  4  7  10 13 16 19 22 25 28 31           │
└──────────────────────────────────────────────┘
```

`loadChart()` gains a `month` argument. The picker is rebuilt from `d.months` on each load
and its value set to `d.month`, so the server stays the authority on what is selected.
Changing the picker reloads the chart for that month.

The existing 30-second `setInterval` refresh and `refresh.overview` both keep the selected
month rather than resetting to the default — a dashboard left open on June must not jump
back to July.

Chart type, the three datasets, their colours and all Chart.js options are unchanged; only
`labels` and the data arrays differ.

## Error handling

- Non-admin or logged-out → `require_admin_api()` returns 401.
- Invalid `month` → 422 with a readable message.
- The current `loadChart()` swallows all errors (`catch (_) {}`) so a chart failure never
  breaks the KPI cards. That behaviour is kept.

## Verification

No test framework; verification is manual, as with the rest of the admin dashboard.

1. The picker lists only months with data, newest first, and no duplicates across the three
   source tables.
2. Selecting an older month redraws the chart with that month's days.
3. The current month's line stops at today, not at the end of the month.
4. A past month runs to its true last day — 30 for June, 31 for May, 28 for February 2026.
5. `?month=2026-13` and `?month=garbage` both return 422.
6. `?month=2019-01` (valid, no data) returns an all-zero series, not an error.
7. A logged-out `curl` returns 401 JSON.
8. Leaving the dashboard open for 30+ seconds on a non-default month does not reset it.
