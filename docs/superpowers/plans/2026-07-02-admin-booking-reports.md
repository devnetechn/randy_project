# Admin Booking Reports Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Reports" tab to the admin dashboard showing booking counts (with status breakdown) grouped by day, week, or month, based on when each booking was submitted (`appointments.created_at`).

**Architecture:** One new read-only API endpoint (`api/admin/reports.php`) runs a single grouped/aggregated SQL query per view (daily/weekly/monthly) against the existing `appointments` table — no schema changes. One new frontend module (`initReports()` in `assets/js/admin.js`) follows the exact same tab-toggle + fetch + render pattern already used by `initBookings()`, registered into the existing `MODULES` map. A small new `.report-table` CSS block styles the (first-ever) HTML `<table>` in the admin panel.

**Tech Stack:** Vanilla PHP 8 + PDO/MySQL (XAMPP), plain JS (no framework, no build step), `assets/css/styles.css`. **No test framework exists** — verification is via `php -l` (lint), short PHP CLI scripts run with `C:\xampp\php\php.exe`, HTTP/browser checks against `http://localhost/randy/...`, and admin login for manual UI verification.

**Conventions to follow:**
- Admin API endpoints: `require_once __DIR__ . '/../../includes/app.php';` then `require_admin_api();` (from `includes/session.php`) before touching the DB. Returns via `json_out($data)` / `json_error($msg, $status)`.
- Admin JS modules: one `init<Name>(panel)` function per tab, registered in the `MODULES` map at the bottom of `assets/js/admin.js`; panels are lazy-loaded on first tab click and support a `refresh.<name>()` re-fetch on subsequent clicks (see `initBookings` / `initOverview` for the exact shape).
- Globals available inside admin.js functions (defined in `assets/js/app.js`, loaded after `admin.js` in the HTML but before `DOMContentLoaded` fires, so safe to use inside any function that only runs on/after `DOMContentLoaded`): `api.get(path)`, `api.post(path, obj)`, `escapeHtml(str)`, `toast(msg, type)`, `cap(str)`.
- Commit after each task. Work happens on branch `feature/admin-booking-reports` (created in Task 0 below).

---

## File Structure

| File | Responsibility | Action |
|------|----------------|--------|
| `api/admin/reports.php` | Grouped booking counts + status breakdown, by period | Create |
| `admin/index.php` | Reports tab button + panel `<div>` | Modify |
| `assets/js/admin.js` | `initReports()` — toggle/fetch/render, registered in `MODULES` | Modify |
| `assets/css/styles.css` | `.report-table` styling | Modify |

---

## Task 0: Create the feature branch

- [ ] **Step 1: Create and switch to the branch**

```bash
git checkout main
git pull
git checkout -b feature/admin-booking-reports
```

(This project is a live XAMPP site served directly from `C:\xampp\htdocs\randy`, so — same as the previous location-pages feature — work happens on a regular branch checked out in place, not a worktree, so pages remain testable at `http://localhost/randy/...` throughout.)

---

## Task 1: Reports API endpoint (`api/admin/reports.php`)

**Files:**
- Create: `api/admin/reports.php`

- [ ] **Step 1: Create the endpoint**

Create `api/admin/reports.php`:

```php
<?php
/**
 * Admin booking reports: counts + status breakdown grouped by day, week, or
 * month, based on when each booking was submitted (created_at) — not the
 * scheduled service date.
 */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

$period = $_GET['period'] ?? 'monthly';
if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
    $period = 'monthly';
}

$pdo = db();

if ($period === 'daily') {
    $sql = "SELECT DATE(created_at) AS period,
                   COUNT(*) AS total,
                   SUM(status='pending')   AS pending,
                   SUM(status='confirmed') AS confirmed,
                   SUM(status='declined')  AS declined,
                   SUM(status='cancelled') AS cancelled,
                   SUM(status='completed') AS completed
            FROM appointments
            WHERE created_at >= CURDATE() - INTERVAL 29 DAY
            GROUP BY DATE(created_at)
            ORDER BY period DESC";
} elseif ($period === 'weekly') {
    $sql = "SELECT DATE(created_at - INTERVAL WEEKDAY(created_at) DAY) AS period,
                   COUNT(*) AS total,
                   SUM(status='pending')   AS pending,
                   SUM(status='confirmed') AS confirmed,
                   SUM(status='declined')  AS declined,
                   SUM(status='cancelled') AS cancelled,
                   SUM(status='completed') AS completed
            FROM appointments
            WHERE created_at >= CURDATE() - INTERVAL 84 DAY
            GROUP BY period
            ORDER BY period DESC";
} else {
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m-01') AS period,
                   COUNT(*) AS total,
                   SUM(status='pending')   AS pending,
                   SUM(status='confirmed') AS confirmed,
                   SUM(status='declined')  AS declined,
                   SUM(status='cancelled') AS cancelled,
                   SUM(status='completed') AS completed
            FROM appointments
            WHERE created_at >= DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH, '%Y-%m-01')
            GROUP BY period
            ORDER BY period DESC";
}

$rows = array_map(function ($r) {
    return [
        'period'    => $r['period'],
        'total'     => (int) $r['total'],
        'pending'   => (int) $r['pending'],
        'confirmed' => (int) $r['confirmed'],
        'declined'  => (int) $r['declined'],
        'cancelled' => (int) $r['cancelled'],
        'completed' => (int) $r['completed'],
    ];
}, $pdo->query($sql)->fetchAll());

json_out(['period' => $period, 'rows' => $rows]);
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l api/admin/reports.php`
Expected: `No syntax errors detected in api/admin/reports.php`

- [ ] **Step 3: Verify the auth guard rejects unauthenticated requests**

```powershell
try { Invoke-WebRequest "http://localhost/randy/api/admin/reports.php?period=monthly" -UseBasicParsing } catch { $_.Exception.Response.StatusCode.value__ }
```
Expected: `401` (not a 200) — confirms `require_admin_api()` is actually blocking unauthenticated access.

- [ ] **Step 4: Verify the SQL itself is correct, bypassing auth (CLI, direct DB access)**

This confirms all three queries run without SQL errors and return sane shapes, independent of the HTTP/auth layer:

```
C:\xampp\php\php.exe -r "require 'includes/db.php'; foreach (['daily','weekly','monthly'] as $period) { if ($period==='daily') { $sql=\"SELECT DATE(created_at) AS period, COUNT(*) AS total, SUM(status='pending') AS pending, SUM(status='confirmed') AS confirmed, SUM(status='declined') AS declined, SUM(status='cancelled') AS cancelled, SUM(status='completed') AS completed FROM appointments WHERE created_at >= CURDATE() - INTERVAL 29 DAY GROUP BY DATE(created_at) ORDER BY period DESC\"; } elseif ($period==='weekly') { $sql=\"SELECT DATE(created_at - INTERVAL WEEKDAY(created_at) DAY) AS period, COUNT(*) AS total, SUM(status='pending') AS pending, SUM(status='confirmed') AS confirmed, SUM(status='declined') AS declined, SUM(status='cancelled') AS cancelled, SUM(status='completed') AS completed FROM appointments WHERE created_at >= CURDATE() - INTERVAL 84 DAY GROUP BY period ORDER BY period DESC\"; } else { $sql=\"SELECT DATE_FORMAT(created_at, '%Y-%m-01') AS period, COUNT(*) AS total, SUM(status='pending') AS pending, SUM(status='confirmed') AS confirmed, SUM(status='declined') AS declined, SUM(status='cancelled') AS cancelled, SUM(status='completed') AS completed FROM appointments WHERE created_at >= DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH, '%Y-%m-01') GROUP BY period ORDER BY period DESC\"; } $rows = db()->query($sql)->fetchAll(); echo $period . ': ' . count($rows) . ' rows' . PHP_EOL; if ($rows) { echo '  sample: ' . json_encode($rows[0]) . PHP_EOL; } }"
```

Expected: 3 lines (`daily: N rows`, `weekly: N rows`, `monthly: N rows`), no PHP/PDO errors. `N` will match however many appointments currently exist in each window — 0 rows is fine if the table is empty or has no recent bookings, that's still a successful run (no SQL error), not a failure. If any row is printed, confirm its keys look like `{"period":"...","total":N,"pending":N,"confirmed":N,"declined":N,"cancelled":N,"completed":N}` and that `total` equals `pending+confirmed+declined+cancelled+completed` for that row.

- [ ] **Step 5: Commit**

```bash
git add api/admin/reports.php
git commit -m "feat(admin): add booking reports API (daily/weekly/monthly)"
```

---

## Task 2: Wire the Reports tab into the admin dashboard

**Files:**
- Modify: `admin/index.php` (tabs list + panels list)
- Modify: `assets/js/admin.js` (new `initReports()` + `MODULES` registration)

- [ ] **Step 1: Add the tab button and panel to `admin/index.php`**

In `admin/index.php`, the tabs block currently reads:

```html
    <div class="tabs" data-tabs role="tablist">
        <button class="tab is-active" data-tab="overview" role="tab">Overview</button>
        <button class="tab" data-tab="chat" role="tab">Live Chat</button>
        <button class="tab" data-tab="leads" role="tab">CRM / Leads</button>
        <button class="tab" data-tab="bookings" role="tab">Bookings</button>
        <button class="tab" data-tab="gallery" role="tab">Gallery</button>
        <button class="tab" data-tab="blog" role="tab">Blog</button>
        <button class="tab" data-tab="reviews" role="tab">Reviews</button>
    </div>

    <div data-panel="overview"></div>
    <div data-panel="chat" hidden></div>
    <div data-panel="leads" hidden></div>
    <div data-panel="bookings" hidden></div>
    <div data-panel="gallery" hidden></div>
    <div data-panel="blog" hidden></div>
    <div data-panel="reviews" hidden></div>
```

Replace it with:

```html
    <div class="tabs" data-tabs role="tablist">
        <button class="tab is-active" data-tab="overview" role="tab">Overview</button>
        <button class="tab" data-tab="chat" role="tab">Live Chat</button>
        <button class="tab" data-tab="leads" role="tab">CRM / Leads</button>
        <button class="tab" data-tab="bookings" role="tab">Bookings</button>
        <button class="tab" data-tab="reports" role="tab">Reports</button>
        <button class="tab" data-tab="gallery" role="tab">Gallery</button>
        <button class="tab" data-tab="blog" role="tab">Blog</button>
        <button class="tab" data-tab="reviews" role="tab">Reviews</button>
    </div>

    <div data-panel="overview"></div>
    <div data-panel="chat" hidden></div>
    <div data-panel="leads" hidden></div>
    <div data-panel="bookings" hidden></div>
    <div data-panel="reports" hidden></div>
    <div data-panel="gallery" hidden></div>
    <div data-panel="blog" hidden></div>
    <div data-panel="reviews" hidden></div>
```

- [ ] **Step 2: Lint the PHP**

Run: `C:\xampp\php\php.exe -l admin/index.php`
Expected: `No syntax errors detected in admin/index.php`

- [ ] **Step 3: Add `initReports()` to `assets/js/admin.js`**

In `assets/js/admin.js`, find the `initBookings` function (it ends with `refresh.bookings = load;` followed by a blank line and the `/* ----------  Gallery  ---------- */` comment, around line 96). Insert this new function immediately after `initBookings` ends and before the Gallery section comment:

```js
  /* ----------  Reports  ---------- */
  function initReports(panel) {
    const VIEWS = ['daily', 'weekly', 'monthly'];
    const LABELS = { daily: 'Daily', weekly: 'Weekly', monthly: 'Monthly' };
    const COLS = ['total', 'pending', 'confirmed', 'declined', 'cancelled', 'completed'];
    let view = 'monthly';
    panel.innerHTML =
      '<div class="tabs" data-rp-filter style="margin-top:0">' +
      VIEWS.map((v) => '<button class="tab' + (v === view ? ' is-active' : '') + '" data-v="' + v + '">' + LABELS[v] + '</button>').join('') +
      '</div><div class="report-table-wrap" data-rp-table></div>';
    const tableEl = panel.querySelector('[data-rp-table]');

    panel.querySelector('[data-rp-filter]').addEventListener('click', (e) => {
      const b = e.target.closest('[data-v]'); if (!b) return;
      view = b.dataset.v;
      panel.querySelectorAll('[data-rp-filter] .tab').forEach((t) => t.classList.toggle('is-active', t === b));
      load();
    });

    function periodLabel(p) {
      const d = new Date(p + 'T00:00:00');
      if (view === 'daily') return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
      if (view === 'weekly') {
        const end = new Date(d);
        end.setDate(end.getDate() + 6);
        return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ' – ' + end.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
      }
      return d.toLocaleDateString(undefined, { month: 'short', year: 'numeric' });
    }

    function render(rows) {
      if (!rows.length) {
        tableEl.innerHTML = '<p style="color:var(--muted)">No bookings in this range.</p>';
        return;
      }
      let html = '<table class="report-table"><thead><tr><th>Period</th><th>Total</th><th>Pending</th><th>Confirmed</th><th>Declined</th><th>Cancelled</th><th>Completed</th></tr></thead><tbody>';
      rows.forEach((r) => {
        html += '<tr><td>' + escapeHtml(periodLabel(r.period)) + '</td>' +
          COLS.map((c) => '<td>' + (r[c] || 0) + '</td>').join('') + '</tr>';
      });
      html += '</tbody></table>';
      tableEl.innerHTML = html;
    }

    async function load() {
      tableEl.innerHTML = '<p style="color:var(--muted)">Loading&hellip;</p>';
      try {
        const d = await api.get('api/admin/reports.php?period=' + view);
        render(d.rows || []);
      } catch (e) { toast(e.message, 'error'); }
    }
    load();
    refresh.reports = load;
  }

```

- [ ] **Step 4: Register `initReports` in the `MODULES` map**

In `assets/js/admin.js`, find this line (near the bottom of the file):

```js
  const MODULES = { overview: initOverview, chat: initChat, leads: initCrm, bookings: initBookings, gallery: initGallery, blog: initBlog, reviews: initReviews };
```

Replace it with:

```js
  const MODULES = { overview: initOverview, chat: initChat, leads: initCrm, bookings: initBookings, reports: initReports, gallery: initGallery, blog: initBlog, reviews: initReviews };
```

- [ ] **Step 5: Manual browser verification**

Log in as an admin user and open `http://localhost/randy/admin/`. Confirm:
- A "Reports" tab button appears between "Bookings" and "Gallery".
- Clicking it shows three toggle buttons (Daily / Weekly / Monthly), with **Monthly active by default**.
- The panel shows either a table (Period / Total / Pending / Confirmed / Declined / Cancelled / Completed columns) or the "No bookings in this range." message if there's no data in that window.
- Clicking "Daily" or "Weekly" re-fetches and re-renders the table with different period labels (e.g. a date like "Jul 2, 2026" for Daily, a date range like "Jun 30 – Jul 6, 2026" for Weekly, a month like "Jul 2026" for Monthly).
- Switching to another tab (e.g. Bookings) and back to Reports doesn't error, and re-fetches the currently-selected view (test via the browser's Network tab, or simply trust the existing `refresh.<name>()` pattern already proven by every other tab).
- Open the browser console — no JS errors on load or on toggle clicks.

- [ ] **Step 6: Commit**

```bash
git add admin/index.php assets/js/admin.js
git commit -m "feat(admin): add Reports tab UI (daily/weekly/monthly booking counts)"
```

---

## Task 3: Style the report table

**Files:**
- Modify: `assets/css/styles.css` (after the `.booking-actions` rule, ~line 444)

- [ ] **Step 1: Add the CSS**

In `assets/css/styles.css`, immediately after this existing line (~line 444):

```css
.booking-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .8rem; align-items: center; }
```

Add:

```css

/* Report table */
.report-table-wrap { overflow-x: auto; }
.report-table { width: 100%; border-collapse: collapse; background: var(--paper); border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden; }
.report-table th, .report-table td { padding: .6rem .9rem; text-align: left; border-bottom: 1px solid var(--line); font-size: .9rem; white-space: nowrap; }
.report-table th { background: var(--plaster-2); color: var(--muted); font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.report-table tbody tr:last-child td { border-bottom: 0; }
```

- [ ] **Step 2: Verify in the browser**

Reload `http://localhost/randy/admin/` (hard refresh to bypass any CSS cache — the stylesheet link already has a `?v=<filemtime>` cache-buster from `includes/header.php`, so a normal reload is enough), open the Reports tab. Confirm the table has a bordered, card-like appearance consistent with the rest of the admin panel (matches the `.kpi` / `.booking-item` visual style — white/paper background, `var(--line)` borders, rounded corners), with a muted, uppercase header row.

- [ ] **Step 3: Commit**

```bash
git add assets/css/styles.css
git commit -m "style(admin): add report table styling"
```

---

## Final verification

- [ ] Lint everything touched: `C:\xampp\php\php.exe -l api/admin/reports.php; C:\xampp\php\php.exe -l admin/index.php`
- [ ] As an admin, click through Daily → Weekly → Monthly at least twice each (confirm no stale data / no duplicate rows / no JS errors on repeated toggling).
- [ ] Confirm `SUM(pending+confirmed+declined+cancelled+completed) == total` holds for at least one non-empty row in the live UI (spot check against the Bookings tab list for the same window, e.g. count pending bookings shown in the Bookings tab and compare to today's row in the Daily report).
- [ ] Confirm a non-admin (or logged-out) user cannot load `admin/index.php` at all (existing `require_admin_page()` guard, unrelated to this change, but worth a sanity check that nothing in this change weakened it) and cannot call `api/admin/reports.php` directly (401, confirmed in Task 1).

---

## Spec coverage check

- New "Reports" tab, positioned after Bookings → Task 2 ✓
- Daily (30d) / Weekly (12wk) / Monthly (12mo, default) toggle views → Task 1 (SQL windows) + Task 2 (UI toggle, default) ✓
- Total + per-status breakdown per period → Task 1 (`SUM(status=...)` columns) + Task 2 (table columns) ✓
- Grouped by `created_at` (submission date), not `scheduled_at` → Task 1 ✓
- Read-only, no new DB writes/tables → Task 1 (SELECT-only endpoint) ✓
- No chart library, table only → Task 2/3 (plain `<table>`, no Chart.js) ✓
- No lead_stage breakdown → Task 1 (query only touches `status`, not `lead_stage`) ✓
- Zero-count periods omitted, not shown as explicit rows → Task 1 (`GROUP BY` naturally excludes empty buckets) ✓
