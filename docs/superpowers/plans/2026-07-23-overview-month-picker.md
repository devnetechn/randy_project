# Overview Month Picker Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the Overview dashboard's six-month trend chart with a month picker and a daily breakdown of the selected month.

**Architecture:** `api/admin/analytics.php` changes from a six-month aggregate to a month-scoped one, returning both the list of months that have data and one data point per day of the selected month. `initOverview()` renders a `<select>` from that list and reloads the chart when it changes. Chart.js configuration is untouched — only the labels and data arrays differ.

**Tech Stack:** PHP 8.3 (no Composer), vanilla JS, Chart.js (already loaded), plain CSS.

**Spec:** `docs/superpowers/specs/2026-07-23-overview-monthly-chart-design.md`

## Global Constraints

- No Composer, no new dependencies.
- No test framework in this repo. Verification is by `curl` against local XAMPP plus browser checks.
- Admin endpoints call `require_once __DIR__ . '/../../includes/app.php';` then `require_admin_api();` first.
- All DB access via `db()` (PDO) with bound parameters.
- The three series keep their current labels and colours: Quotes requested `#2a66d6`, New leads `#b4241d`, New signups `#059669`.
- The current month's series stops at today; past months run to their true last day.
- `loadChart()` keeps its swallow-all `catch (_) {}` so a chart failure never breaks the KPI cards.

## Prerequisites

XAMPP Apache and MySQL running with `randy_db`. The database needs rows spread across at least two months and across more than one of `appointments` / `conversations` / `users` — otherwise the month-list union and the picker cannot be verified.

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/randy_project/index.php
```

Expected: `200` (after redirect: the site rewrites `.php` away, so use `-L` if you get a 301).

---

## File Structure

| File | Responsibility |
|---|---|
| `api/admin/analytics.php` (rewrite) | Validate `month`, list months with data, return one daily series per source table |
| `assets/js/admin.js:16-72` (modify) | Render the picker, reload the chart on change, keep the selection across refreshes |
| `assets/css/styles.css:590-591` (modify) | Lay the picker out beside the section heading |

---

## Task 1: Month-scoped analytics endpoint

**Files:**
- Modify (full rewrite): `api/admin/analytics.php`

**Interfaces:**
- Consumes: `require_admin_api()`, `db()`, `json_out()`, `json_error()` from `includes/app.php`
- Produces: `GET api/admin/analytics.php?month=YYYY-MM` returning
  `{"months": [{"value": "2026-07", "label": "July 2026"}], "month": "2026-07", "labels": ["1","2"], "bookings": [int], "leads": [int], "signups": [int]}`.
  Task 2 reads `months`, `month`, `labels`, `bookings`, `leads`, `signups` by exactly these names.

- [ ] **Step 1: Read the file being replaced**

Read `api/admin/analytics.php` in full. Two things carry over: the `$series` closure that runs one prepared statement per source table and zero-fills the result, and the three source queries (`appointments`, `conversations`, and `users` filtered to `role = 'customer'`). What changes is the grouping — day within one month instead of month across six.

- [ ] **Step 2: Rewrite the endpoint**

Replace the entire contents of `api/admin/analytics.php` with:

```php
<?php
/**
 * Admin dashboard chart: daily counts within one month.
 *
 * GET ?month=YYYY-MM — omit for the newest month that has data.
 * Also returns the list of months that have any data, so the client can build
 * its picker from a single request.
 */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

$pdo = db();

// Months with data in any of the three source tables, newest first.
$months = $pdo->query(
    "SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') AS m FROM appointments
     UNION SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') FROM conversations
     UNION SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') FROM users WHERE role = 'customer'
     ORDER BY m DESC"
)->fetchAll(PDO::FETCH_COLUMN);

$month = trim((string) ($_GET['month'] ?? ''));
if ($month !== '' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    json_error('Month must be in YYYY-MM form', 422);
}
if ($month === '') {
    // No data anywhere: fall back to the current month so the chart still draws.
    $month = $months[0] ?? date('Y-m');
}

$start = $month . '-01';
// The current month is truncated at today — days that have not happened yet
// would otherwise read as a collapse to zero rather than as absent data.
$lastDay = $month === date('Y-m')
    ? (int) date('j')
    : (int) date('t', strtotime($start));

$series = function (string $sql) use ($pdo, $start, $lastDay): array {
    $counts = array_fill(1, $lastDay, 0);
    $st = $pdo->prepare($sql);
    $st->execute(['start' => $start]);
    foreach ($st->fetchAll() as $row) {
        $d = (int) $row['d'];
        if ($d >= 1 && $d <= $lastDay) {
            $counts[$d] = (int) $row['total'];
        }
    }
    return array_values($counts);
};

$bookings = $series(
    "SELECT DAY(created_at) AS d, COUNT(*) AS total
       FROM appointments
      WHERE created_at >= :start AND created_at < :start + INTERVAL 1 MONTH
      GROUP BY d"
);
$leads = $series(
    "SELECT DAY(created_at) AS d, COUNT(*) AS total
       FROM conversations
      WHERE created_at >= :start AND created_at < :start + INTERVAL 1 MONTH
      GROUP BY d"
);
$signups = $series(
    "SELECT DAY(created_at) AS d, COUNT(*) AS total
       FROM users
      WHERE role = 'customer'
        AND created_at >= :start AND created_at < :start + INTERVAL 1 MONTH
      GROUP BY d"
);

json_out([
    'months'   => array_map(
        fn ($m) => ['value' => $m, 'label' => date('F Y', strtotime($m . '-01'))],
        $months
    ),
    'month'    => $month,
    'labels'   => array_map('strval', range(1, $lastDay)),
    'bookings' => $bookings,
    'leads'    => $leads,
    'signups'  => $signups,
]);
```

Two details worth understanding rather than transcribing:

1. `array_fill(1, $lastDay, 0)` produces keys 1..lastDay, so `$counts[$d]` from `DAY()` indexes directly with no offset arithmetic. `array_values` then flattens it to the 0-based array the client plots.
2. The `if ($d >= 1 && $d <= $lastDay)` guard matters only for the current month: rows created later today than `date('j')` cannot exist, but a row dated in the future (clock skew, seeded test data) would otherwise write past the end of the array.

- [ ] **Step 3: Verify auth and syntax**

```bash
/c/xampp/php/php.exe -l api/admin/analytics.php
curl -s -w "\n[%{http_code}]\n" "http://localhost/randy_project/api/admin/analytics.php"
```

Expected: `No syntax errors detected`, then `{"error":"Authentication required"}` and `[401]`.

- [ ] **Step 4: Log in and verify the default month**

```bash
cd /tmp && rm -f aj.txt
curl -s -c aj.txt -b aj.txt -L --post301 -o /dev/null \
  -X POST -d "email=admin@randyspaintdrywall.com&password=changeme123" \
  http://localhost/randy_project/login.php

curl -s -b aj.txt "http://localhost/randy_project/api/admin/analytics.php" \
  | /c/xampp/php/php.exe -r '$d=json_decode(stream_get_contents(STDIN),true);
      echo "month:  ".$d["month"]."\n";
      echo "months: ".implode(", ", array_column($d["months"],"value"))."\n";
      echo "labels: ".$d["labels"][0]."..".end($d["labels"])." (".count($d["labels"]).")\n";
      echo "series: ".count($d["bookings"])."/".count($d["leads"])."/".count($d["signups"])."\n";'
```

Expected: `month` is the newest month with data; `months` lists each month once, newest first; `labels` runs from `1`; all three series have the same length as `labels`.

- [ ] **Step 5: Verify the current-month truncation**

This is the behaviour the spec singles out. With today being the 23rd of the current month:

```bash
curl -s -b aj.txt "http://localhost/randy_project/api/admin/analytics.php?month=$(date +%Y-%m)" \
  | /c/xampp/php/php.exe -r '$d=json_decode(stream_get_contents(STDIN),true);
      echo "last label: ".end($d["labels"])." (today is ".date("j").")\n";'
```

Expected: the last label equals today's day-of-month, not 30 or 31.

- [ ] **Step 6: Verify a past month runs to its true last day**

```bash
for m in 2026-06 2026-05 2026-02; do
  printf "%s -> " "$m"
  curl -s -b aj.txt "http://localhost/randy_project/api/admin/analytics.php?month=$m" \
    | /c/xampp/php/php.exe -r '$d=json_decode(stream_get_contents(STDIN),true); echo end($d["labels"])." days\n";'
done
```

Expected: `2026-06 -> 30 days`, `2026-05 -> 31 days`, `2026-02 -> 28 days`.

- [ ] **Step 7: Verify the rejections and the empty-but-valid month**

```bash
printf "month=2026-13 : "; curl -s -o /dev/null -b aj.txt -w "%{http_code}\n" "http://localhost/randy_project/api/admin/analytics.php?month=2026-13"
printf "month=garbage : "; curl -s -o /dev/null -b aj.txt -w "%{http_code}\n" "http://localhost/randy_project/api/admin/analytics.php?month=garbage"
printf "month=2026-00 : "; curl -s -o /dev/null -b aj.txt -w "%{http_code}\n" "http://localhost/randy_project/api/admin/analytics.php?month=2026-00"
printf "month=2019-01 : "; curl -s -b aj.txt "http://localhost/randy_project/api/admin/analytics.php?month=2019-01" \
  | /c/xampp/php/php.exe -r '$d=json_decode(stream_get_contents(STDIN),true); echo "sum=".array_sum($d["bookings"])." days=".count($d["labels"])."\n";'
```

Expected: the first three are `422`; `2019-01` returns `sum=0 days=31` — a valid month with no data is an all-zero series, not an error.

- [ ] **Step 8: Commit**

```bash
git add api/admin/analytics.php
git commit -m "feat(admin): scope the overview chart to one month, by day"
```

---

## Task 2: Month picker in the Overview panel

**Files:**
- Modify: `assets/js/admin.js:16-72` (`initOverview`)

**Interfaces:**
- Consumes: `GET api/admin/analytics.php` from Task 1 (`months`/`month`/`labels`/`bookings`/`leads`/`signups`); the globals `api.get` (`assets/js/app.js:11`), `escapeHtml` (`assets/js/app.js:49`), `window.Chart`.
- Produces: the markup Task 3 styles — `.kpi-section__head` wrapping the `<h2>`, and `[data-overview-month]` as the `<select>`.

- [ ] **Step 1: Change the section heading and add the picker**

In `assets/js/admin.js`, replace line 25:

```javascript
      '<div class="kpi-section"><h2>Last 6 months</h2><div class="chart-card"><canvas data-overview-chart height="90"></canvas></div></div>';
```

with:

```javascript
      '<div class="kpi-section">' +
      '<div class="kpi-section__head"><h2>Daily activity</h2>' +
      '<select data-overview-month aria-label="Month"></select></div>' +
      '<div class="chart-card"><canvas data-overview-chart height="90"></canvas></div></div>';
```

- [ ] **Step 2: Make `loadChart` month-aware**

Replace the whole `loadChart` function (currently `assets/js/admin.js:29-58`, starting at `let chart = null;`) with:

```javascript
    let chart = null;
    let month = '';                       // '' on first load — the server picks the default
    const monthEl = panel.querySelector('[data-overview-month]');

    monthEl.addEventListener('change', () => { month = monthEl.value; loadChart(); });

    async function loadChart() {
      const canvas = panel.querySelector('[data-overview-chart]');
      if (!canvas || !window.Chart) return;
      try {
        const d = await api.get('api/admin/analytics.php' + (month ? '?month=' + encodeURIComponent(month) : ''));

        // The server is the authority on which month is selected: it resolves the
        // default and may hand back a different month than was asked for.
        month = d.month;
        monthEl.innerHTML = (d.months || [])
          .map((m) => '<option value="' + escapeHtml(m.value) + '">' + escapeHtml(m.label) + '</option>')
          .join('');
        monthEl.value = month;

        const datasets = [
          { label: 'Quotes requested', data: d.bookings, borderColor: '#2a66d6', backgroundColor: '#2a66d6' },
          { label: 'New leads', data: d.leads, borderColor: '#b4241d', backgroundColor: '#b4241d' },
          { label: 'New signups', data: d.signups, borderColor: '#059669', backgroundColor: '#059669' },
        ].map((ds) => Object.assign(ds, { borderWidth: 2, pointRadius: 4, pointHoverRadius: 5, tension: 0.25, fill: false }));

        if (chart) {
          chart.data.labels = d.labels;
          chart.data.datasets = datasets;
          chart.update();
          return;
        }
        chart = new Chart(canvas.getContext('2d'), {
          type: 'line',
          data: { labels: d.labels, datasets },
          options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top', labels: { boxWidth: 12, usePointStyle: true } } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
          },
        });
      } catch (_) { /* ignore */ }
    }
```

`month` is module-local to `initOverview` and is only ever overwritten from `d.month`, so the 30-second `setInterval` and `refresh.overview` — both of which call `loadChart()` with no argument — re-request whatever month is currently selected instead of resetting to the default. No change is needed at lines 68-71.

- [ ] **Step 3: Verify the picker and the default month**

Open `http://localhost/randy_project/admin/` and log in. The Overview panel is the default tab.

Expected:
- The heading reads `DAILY ACTIVITY` with a month dropdown to its right.
- The dropdown lists only months that have data, newest first, each appearing once.
- The dropdown's selection matches the month plotted.
- The x-axis shows day numbers starting at `1`.
- The browser console has no errors.

- [ ] **Step 4: Verify switching months and the refresh behaviour**

Select an older month from the dropdown.

Expected: the chart redraws with that month's days, and the x-axis runs to that month's true last day (30 for June, 31 for May).

Then leave the tab open for at least 35 seconds without touching it.

Expected: the dropdown still shows the month you selected and the chart still plots it — the periodic refresh must not jump back to the newest month.

- [ ] **Step 5: Commit**

```bash
git add assets/js/admin.js
git commit -m "feat(admin): add a month picker to the overview chart"
```

---

## Task 3: Picker layout

**Files:**
- Modify: `assets/css/styles.css:590-591`

**Interfaces:**
- Consumes: `.kpi-section__head` and `[data-overview-month]` from Task 2.
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Add the layout rules**

The existing rules at `assets/css/styles.css:590-591` are:

```css
.kpi-section { margin-bottom: 1.5rem; }
.kpi-section h2 { font-size: .72rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); margin-bottom: .6rem; }
```

Immediately after them, add:

```css
.kpi-section__head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.kpi-section__head h2 { margin-bottom: 0; }
[data-overview-month] { padding: .35rem .6rem; border: 1px solid var(--line); border-radius: var(--radius-sm); background: #fff; color: var(--ink); font: inherit; font-size: .85rem; cursor: pointer; margin-bottom: .6rem; }
```

`.kpi-section__head h2 { margin-bottom: 0 }` is needed because the base `h2` rule above carries a `.6rem` bottom margin that would push the heading out of vertical alignment with the select inside a flex row.

- [ ] **Step 2: Verify the layout**

Reload the Overview panel.

Expected:
- The heading sits on the left, the dropdown on the right of the same row, vertically centred.
- The dropdown matches the dashboard's other controls in border and radius.
- At a 375px-wide viewport the row wraps instead of overflowing horizontally.

- [ ] **Step 3: Commit**

```bash
git add assets/css/styles.css
git commit -m "style(admin): lay out the overview month picker beside its heading"
```

---

## Deployment note

Three files change: `api/admin/analytics.php`, `assets/js/admin.js`, `assets/css/styles.css`. Upload them together — the JS requests a response shape the old endpoint does not return, so a partial upload leaves the chart blank (silently, because `loadChart` swallows its errors).

No database migration is required.
