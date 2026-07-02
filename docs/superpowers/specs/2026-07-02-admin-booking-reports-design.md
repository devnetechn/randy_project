# Admin Booking Reports (Daily/Weekly/Monthly)

**Date:** 2026-07-02
**Status:** Approved (design)

## Summary

Add a "Reports" tab to the admin dashboard showing how many bookings (appointments) were made, broken down by day, week, or month, plus a status breakdown (pending/confirmed/declined/cancelled/completed) per period. Grouped by `created_at` — the date the booking request came in, not the scheduled service date.

## Goals

- New "Reports" tab in `admin/index.php`, alongside the existing Overview/Live Chat/CRM/Bookings/Gallery/Blog/Reviews tabs.
- Three toggleable views: Daily (last 30 days), Weekly (last 12 ISO weeks), Monthly (last 12 months) — Monthly is the default view on tab open.
- Each row: period label, total bookings, and a per-status count (pending, confirmed, declined, cancelled, completed).
- Read-only reporting — no new database writes, no changes to the booking workflow itself.

## Non-Goals (YAGNI)

- No chart/graph — table only, per the owner's preference (no charting library exists in the codebase today; adding one is out of scope).
- No custom date-range picker — three fixed, canned lookback windows (30 days / 12 weeks / 12 months) cover the "daily/weekly/monthly" request without adding date-picker UI/validation complexity.
- No `lead_stage` (CRM pipeline) breakdown — this report is about the `appointments.status` operational lifecycle only, matching the owner's request ("pila kabook nag book or nagpa appointment"). CRM lead-stage reporting is a separate concern if requested later.
- No export (CSV/PDF) — on-screen table only.
- No explicit zero-count rows — periods with no bookings are simply absent from the table rather than shown as a `0` row. This keeps the query simple (one `GROUP BY`, no calendar-generation step) and matches how the rest of the admin panel reports live data (e.g. `api/admin/overview.php` never synthesizes empty state). If the owner later wants to see gaps explicitly, that's a small follow-up (build a period list in PHP and left-join).

## Architecture

### Backend: `api/admin/reports.php` (new)

- `GET api/admin/reports.php?period=daily|weekly|monthly` (default `monthly` if param missing/invalid).
- Guarded with `require_admin_api()`, same as every other `api/admin/*` and `api/appointments/*` endpoint.
- One SQL query per period type, grouping `appointments` by `created_at` and computing status counts via conditional `SUM()` (mirrors the aggregation style already used in `api/admin/overview.php`):

```sql
-- daily (period=daily)
SELECT DATE(created_at) AS period,
       COUNT(*) AS total,
       SUM(status='pending')   AS pending,
       SUM(status='confirmed') AS confirmed,
       SUM(status='declined')  AS declined,
       SUM(status='cancelled') AS cancelled,
       SUM(status='completed') AS completed
FROM appointments
WHERE created_at >= CURDATE() - INTERVAL 29 DAY
GROUP BY DATE(created_at)
ORDER BY period DESC
```

```sql
-- weekly (period=weekly): group by the Monday-start date of each ISO week
SELECT DATE(created_at - INTERVAL (WEEKDAY(created_at)) DAY) AS period,
       COUNT(*) AS total,
       SUM(status='pending')   AS pending,
       SUM(status='confirmed') AS confirmed,
       SUM(status='declined')  AS declined,
       SUM(status='cancelled') AS cancelled,
       SUM(status='completed') AS completed
FROM appointments
WHERE created_at >= CURDATE() - INTERVAL 84 DAY   -- 12 weeks
GROUP BY period
ORDER BY period DESC
```

```sql
-- monthly (period=monthly, default)
SELECT DATE_FORMAT(created_at, '%Y-%m-01') AS period,
       COUNT(*) AS total,
       SUM(status='pending')   AS pending,
       SUM(status='confirmed') AS confirmed,
       SUM(status='declined')  AS declined,
       SUM(status='cancelled') AS cancelled,
       SUM(status='completed') AS completed
FROM appointments
WHERE created_at >= DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH, '%Y-%m-01')
GROUP BY period
ORDER BY period DESC
```

Response shape:

```json
{
  "period": "monthly",
  "rows": [
    { "period": "2026-07-01", "total": 12, "pending": 2, "confirmed": 4, "declined": 1, "cancelled": 0, "completed": 5 }
  ]
}
```

`period` in each row is always a `YYYY-MM-DD` string (a specific date for daily, the Monday of the week for weekly, the 1st of the month for monthly); the frontend formats it into a human label per view (e.g. "Jul 2026" for monthly, "Jul 6 – Jul 12" for weekly).

### Frontend: `initReports(panel)` in `assets/js/admin.js` (new function)

Mirrors the existing `initBookings(panel)` structure:

- Three toggle buttons (`Daily` / `Weekly` / `Monthly`), styled with the existing `.tab` class exactly like the Bookings status filter row. `Monthly` starts active.
- Clicking a toggle re-fetches `api/admin/reports.php?period=<x>` and re-renders the table.
- Table columns: Period | Total | Pending | Confirmed | Declined | Cancelled | Completed.
- Registered in the `MODULES` map (`reports: initReports`) and given a panel in `admin/index.php` (tab button + `data-panel="reports"` div), following the exact pattern every other tab already uses.

### New CSS

A small `.report-table` block added to `assets/css/styles.css` (simple bordered table, muted header row) since no `<table>` element exists anywhere in the admin panel today — every other panel uses `<ul>`/card layouts. This is new, minimal, and scoped only to this table.

## Testing

- Manual: open each of the 3 toggle views in the admin dashboard, confirm the row counts match manual `SELECT COUNT(*)` queries against the `appointments` table for a couple of spot-checked periods.
- Confirm `require_admin_api()` rejects unauthenticated/non-admin requests (401/403), matching every other `api/admin/*` endpoint.
- Confirm an appointments table with zero rows in a given window renders an empty table (no PHP warnings), not an error.
