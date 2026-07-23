# Booking Contact Report (PDF) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a date-ranged, printable contact list of bookings (name, phone, email, service, status) to the admin Reports tab.

**Architecture:** A new admin-only JSON endpoint returns one row per booking for a date range filtered on `created_at`. The existing Reports panel gains two sub-tabs — Summary (unchanged) and Contact list (new) — so only one block is visible at a time and the existing `window.print()` handler produces the right PDF without new print-scoping rules.

**Tech Stack:** PHP 8.3 (no Composer, no dependencies), vanilla JS, plain CSS. Print-to-PDF via the browser's own print dialog.

**Spec:** `docs/superpowers/specs/2026-07-23-booking-contact-report-design.md`

## Global Constraints

- No Composer, no PDF library, no new dependencies — the live host (Hostinger, PHP 8.3.31) has no `vendor/` directory and none is to be introduced.
- The repo has **no test framework**. Verification is by `curl` against the local XAMPP server plus browser checks, matching how every other admin feature in this repo is verified.
- All admin API endpoints call `require_once __DIR__ . '/../../includes/app.php';` then `require_admin_api();` before anything else.
- All DB access uses `db()` (PDO) with bound parameters — never string interpolation.
- All user-supplied values rendered into HTML go through the global `escapeHtml()` helper (`assets/js/app.js:49`).
- Date range filters on `appointments.created_at` (submission date), matching `api/admin/reports.php`.
- Do not modify the existing Summary report behaviour.

## Prerequisites

Local XAMPP must be running with the `randy_db` database. Start Apache and MySQL from the XAMPP Control Panel before Task 1. Verify:

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/randy_project/index.php
```

Expected: `200`

---

## File Structure

| File | Responsibility |
|---|---|
| `api/admin/contact-report.php` (create) | Validate the date range, query one row per booking, return JSON |
| `assets/js/admin.js:135-196` (modify) | Sub-tab switch in `initReports()`; new contact-list render + load path |
| `assets/css/styles.css:472-490` (modify) | Table borders and repeating header row in the print stylesheet |

---

## Task 1: Contact report endpoint

**Files:**
- Create: `api/admin/contact-report.php`

**Interfaces:**
- Consumes: `require_admin_api()`, `db()`, `json_out()`, `json_error()` from `includes/app.php`
- Produces: `GET api/admin/contact-report.php?from=YYYY-MM-DD&to=YYYY-MM-DD` returning
  `{"rows": [{"id": int, "createdAt": string, "name": string, "phone": string, "email": string, "serviceType": string, "status": string}], "from": string|null, "to": string|null}`.
  Task 2 reads `rows`, `from` and `to` by exactly these names.

- [ ] **Step 1: Read the endpoint this one is modelled on**

Read `api/admin/reports.php` in full. Note three things you will copy: the two require/auth lines at the top, the `array_map` that casts DB strings to typed values before `json_out`, and the header comment explaining the `created_at` choice.

Also read `api/crm/update.php:40-45` — the `COALESCE(u.full_name, a.guest_name)` pattern for resolving a registered user vs. a guest booking is reused verbatim below.

- [ ] **Step 2: Create the endpoint**

Create `api/admin/contact-report.php`:

```php
<?php
/**
 * Admin booking contact list: one row per booking with the customer's contact
 * details, for a date range based on when the booking was submitted
 * (created_at) — not the scheduled service date, matching reports.php.
 *
 * GET ?from=YYYY-MM-DD&to=YYYY-MM-DD — omit both for all time.
 */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

/** True only for a real calendar date in YYYY-MM-DD form. */
function contact_report_valid_date(string $s): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $s);
    return $d !== false && $d->format('Y-m-d') === $s;
}

$from = trim((string) ($_GET['from'] ?? ''));
$to   = trim((string) ($_GET['to'] ?? ''));

if ($from !== '' && !contact_report_valid_date($from)) {
    json_error('Start date must be a valid date (YYYY-MM-DD)', 422);
}
if ($to !== '' && !contact_report_valid_date($to)) {
    json_error('End date must be a valid date (YYYY-MM-DD)', 422);
}

// One side given, the other blank: fill the blank with a sensible bound so the
// range is never half-open.
if ($from !== '' && $to === '') {
    $to = date('Y-m-d');
} elseif ($from === '' && $to !== '') {
    $from = date('Y-m-01');
}

if ($from !== '' && $from > $to) {
    json_error('Start date must be on or before the end date', 422);
}

$select =
    'SELECT a.id, a.created_at, a.status, a.service_type,
            COALESCE(u.full_name, a.guest_name)  AS customer_name,
            COALESCE(u.email,     a.guest_email) AS customer_email,
            COALESCE(a.phone,     a.guest_phone) AS customer_phone
       FROM appointments a
       LEFT JOIN users u ON u.id = a.customer_id';

if ($from !== '') {
    // `< to + 1 day` rather than `<= to` so a booking made at 14:30 on the end
    // date is still inside the range.
    $sql  = $select . ' WHERE a.created_at >= ? AND a.created_at < ? + INTERVAL 1 DAY
                        ORDER BY a.created_at DESC LIMIT 2000';
    $args = [$from, $to];
} else {
    $sql  = $select . ' ORDER BY a.created_at DESC LIMIT 2000';
    $args = [];
}

$st = db()->prepare($sql);
$st->execute($args);

$rows = array_map(function ($r) {
    return [
        'id'          => (int) $r['id'],
        'createdAt'   => $r['created_at'],
        'name'        => $r['customer_name'] ?: 'Guest',
        'phone'       => $r['customer_phone'] ?: '',
        'email'       => $r['customer_email'] ?: '',
        'serviceType' => $r['service_type'],
        'status'      => $r['status'],
    ];
}, $st->fetchAll());

json_out([
    'rows' => $rows,
    'from' => $from !== '' ? $from : null,
    'to'   => $to   !== '' ? $to   : null,
]);
```

- [ ] **Step 3: Verify the endpoint rejects unauthenticated callers**

```bash
curl -s -w "\n[%{http_code}]\n" "http://localhost/randy_project/api/admin/contact-report.php"
```

Expected: `{"error":"Authentication required"}` and `[401]`.

If you get a 500 with an empty body instead, the file has a syntax error — run `php -l api/admin/contact-report.php` and fix it before continuing.

- [ ] **Step 4: Log in and verify the happy path**

```bash
cd /tmp && rm -f rj.txt
curl -s -c rj.txt -b rj.txt -L --post301 -o /dev/null \
  -X POST -d "email=admin@randyspaintdrywall.com&password=changeme123" \
  http://localhost/randy_project/login.php

curl -s -b rj.txt -w "\n[%{http_code}]\n" \
  "http://localhost/randy_project/api/admin/contact-report.php?from=2026-01-01&to=2026-12-31"
```

Expected: `[200]` and a JSON object whose `rows` array contains objects with exactly the keys `id`, `createdAt`, `name`, `phone`, `email`, `serviceType`, `status`, and whose `from`/`to` echo `2026-01-01` / `2026-12-31`.

(Substitute your local admin credentials from `config.php` if they differ.)

- [ ] **Step 5: Verify all-time, the single-sided range, and the two rejections**

```bash
# All time — from/to come back null
curl -s -b rj.txt "http://localhost/randy_project/api/admin/contact-report.php" | head -c 200

# Only `from` given — `to` fills in with today
curl -s -b rj.txt "http://localhost/randy_project/api/admin/contact-report.php?from=2026-01-01" | tail -c 80

# Reversed range → 422
curl -s -b rj.txt -w " [%{http_code}]\n" \
  "http://localhost/randy_project/api/admin/contact-report.php?from=2026-12-31&to=2026-01-01"

# Nonsense date → 422 (note: 2026-02-30 is not a real date)
curl -s -b rj.txt -w " [%{http_code}]\n" \
  "http://localhost/randy_project/api/admin/contact-report.php?from=2026-02-30&to=2026-03-01"
```

Expected in order:
1. `"from":null,"to":null` present in the output.
2. `"to":"<today's date>"`.
3. `{"error":"Start date must be on or before the end date"} [422]`
4. `{"error":"Start date must be a valid date (YYYY-MM-DD)"} [422]`

- [ ] **Step 6: Verify the end-date boundary**

This is the bug the `+ INTERVAL 1 DAY` guards against. Pick the newest booking's date from the Step 4 output — call it `$D` — and request a range ending exactly on it:

```bash
curl -s -b rj.txt "http://localhost/randy_project/api/admin/contact-report.php?from=$D&to=$D" | head -c 300
```

Expected: the booking created at a non-midnight time on `$D` **is** in `rows`. If `rows` is empty, the comparison is wrong — recheck that the SQL says `< ? + INTERVAL 1 DAY` and not `<= ?`.

- [ ] **Step 7: Verify both booking types resolve a name and contact details**

Most bookings in this database are guests (`customer_id` NULL, details in `guest_name`/`guest_email`/`guest_phone`); bookings from a registered user carry `customer_id` and take their details from `users`. The `COALESCE` pairs must handle both.

```bash
curl -s -b rj.txt "http://localhost/randy_project/api/admin/contact-report.php" \
  | php -r '$d=json_decode(file_get_contents("php://stdin"),true);
            foreach($d["rows"] as $r) printf("%-4s %-22s %-14s %s\n",$r["id"],$r["name"],$r["phone"],$r["email"]);'
```

Expected: no row shows an empty name — a booking with no resolvable name reads `Guest`. If every row is a guest booking, create one registered-user booking (log in as a non-admin user on `book.php`) and re-run before ticking this step.

- [ ] **Step 8: Commit**

```bash
git add api/admin/contact-report.php
git commit -m "feat(admin): add booking contact report endpoint"
```

---

## Task 2: Reports sub-tabs and contact list UI

**Files:**
- Modify: `assets/js/admin.js:135-196` (the whole `initReports` function)

**Interfaces:**
- Consumes: `GET api/admin/contact-report.php` from Task 1 (`rows`/`from`/`to`); the globals `api.get` (`assets/js/app.js:11`), `escapeHtml` (`assets/js/app.js:49`), `toast`, and the module-local `cap()` / `fmt()` (`assets/js/admin.js:8-13`).
- Produces: nothing consumed by later tasks. Task 3 styles the markup this task emits — specifically `.report-table` inside `[data-rp-contacts]`, and the controls `[data-rp-sub]`, `[data-rp-presets]`, `[data-rp-dates]`.

- [ ] **Step 1: Read the function being replaced**

Read `assets/js/admin.js:135-196`. The existing `initReports` builds a filter strip, a `[data-rp-heading]` element, and a `[data-rp-table]` element, and its print button sets the heading then calls `window.print()`. All of that is preserved below — the summary logic (`periodLabel`, `render`, `load`) is unchanged, only renamed and nested under a sub-tab.

- [ ] **Step 2: Replace `initReports` with the sub-tabbed version**

Replace the whole block from `/* ----------  Reports  ---------- */` through the closing brace of `initReports` (currently lines 135-196) with:

```javascript
  /* ----------  Reports  ---------- */
  function initReports(panel) {
    const VIEWS = ['daily', 'weekly', 'monthly'];
    const LABELS = { daily: 'Daily', weekly: 'Weekly', monthly: 'Monthly' };
    const COLS = ['total', 'pending', 'confirmed', 'declined', 'cancelled', 'completed'];
    const PRESETS = [
      ['month', 'This month'],
      ['30d', 'Last 30 days'],
      ['year', 'This year'],
      ['all', 'All time'],
    ];
    let view = 'monthly';
    let sub = 'summary';

    panel.innerHTML =
      '<div class="tabs" data-rp-sub style="margin-top:0">' +
      '<button class="tab is-active" data-s="summary">Summary</button>' +
      '<button class="tab" data-s="contacts">Contact list</button>' +
      '<button type="button" class="btn-soft" data-rp-print style="margin-left:.5rem">Download PDF</button>' +
      '</div>' +
      '<div class="report-print-heading" data-rp-heading></div>' +
      '<div data-rp-summary>' +
      '<div class="tabs" data-rp-filter>' +
      VIEWS.map((v) => '<button class="tab' + (v === view ? ' is-active' : '') + '" data-v="' + v + '">' + LABELS[v] + '</button>').join('') +
      '</div><div class="report-table-wrap" data-rp-table></div></div>' +
      '<div data-rp-contacts hidden>' +
      '<div class="tabs" data-rp-presets>' +
      PRESETS.map((p) => '<button class="tab' + (p[0] === 'month' ? ' is-active' : '') + '" data-p="' + p[0] + '">' + p[1] + '</button>').join('') +
      '</div>' +
      '<div class="report-dates" data-rp-dates>' +
      '<label>From <input type="date" data-rp-from></label>' +
      '<label>To <input type="date" data-rp-to></label>' +
      '</div><div class="report-table-wrap" data-rp-clist></div></div>';

    const tableEl = panel.querySelector('[data-rp-table]');
    const headingEl = panel.querySelector('[data-rp-heading]');
    const summaryEl = panel.querySelector('[data-rp-summary]');
    const contactsEl = panel.querySelector('[data-rp-contacts]');
    const clistEl = panel.querySelector('[data-rp-clist]');
    const fromEl = panel.querySelector('[data-rp-from]');
    const toEl = panel.querySelector('[data-rp-to]');

    panel.querySelector('[data-rp-sub]').addEventListener('click', (e) => {
      const b = e.target.closest('[data-s]'); if (!b) return;
      sub = b.dataset.s;
      panel.querySelectorAll('[data-rp-sub] .tab').forEach((t) => t.classList.toggle('is-active', t === b));
      summaryEl.hidden = sub !== 'summary';
      contactsEl.hidden = sub !== 'contacts';
      if (sub === 'contacts') loadContacts();
    });

    panel.querySelector('[data-rp-filter]').addEventListener('click', (e) => {
      const b = e.target.closest('[data-v]'); if (!b) return;
      view = b.dataset.v;
      panel.querySelectorAll('[data-rp-filter] .tab').forEach((t) => t.classList.toggle('is-active', t === b));
      loadSummary();
    });

    panel.querySelector('[data-rp-presets]').addEventListener('click', (e) => {
      const b = e.target.closest('[data-p]'); if (!b) return;
      panel.querySelectorAll('[data-rp-presets] .tab').forEach((t) => t.classList.toggle('is-active', t === b));
      applyPreset(b.dataset.p);
      loadContacts();
    });

    panel.querySelector('[data-rp-dates]').addEventListener('change', () => {
      panel.querySelectorAll('[data-rp-presets] .tab').forEach((t) => t.classList.remove('is-active'));
      loadContacts();
    });

    panel.querySelector('[data-rp-print]').addEventListener('click', () => {
      headingEl.innerHTML = sub === 'summary' ? summaryHeading() : contactsHeading();
      window.print();
    });

    /* ---- shared ---- */
    function iso(d) {
      return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function today() {
      return new Date().toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function prettyDate(s) {
      if (!s) return '';
      return new Date(s + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }

    /* ---- summary ---- */
    function summaryHeading() {
      return escapeHtml('Booking Report — ' + LABELS[view] + ' — Generated ' + today());
    }
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
    function renderSummary(rows) {
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
    async function loadSummary() {
      tableEl.innerHTML = '<p style="color:var(--muted)">Loading&hellip;</p>';
      try {
        const d = await api.get('api/admin/reports.php?period=' + view);
        renderSummary(d.rows || []);
      } catch (e) { toast(e.message, 'error'); }
    }

    /* ---- contact list ---- */
    let contactRange = { from: null, to: null, count: 0 };

    function applyPreset(p) {
      const now = new Date();
      if (p === 'all') { fromEl.value = ''; toEl.value = ''; return; }
      toEl.value = iso(now);
      if (p === 'month') fromEl.value = iso(new Date(now.getFullYear(), now.getMonth(), 1));
      else if (p === 'year') fromEl.value = iso(new Date(now.getFullYear(), 0, 1));
      else { const s = new Date(now); s.setDate(s.getDate() - 29); fromEl.value = iso(s); }
    }

    function contactsHeading() {
      const range = contactRange.from
        ? prettyDate(contactRange.from) + ' – ' + prettyDate(contactRange.to)
        : 'All time';
      return escapeHtml("Randy's Painting & Drywall Services — Booking Contact Report") +
        '<br><span style="font-weight:400;font-size:.9rem">' +
        escapeHtml(range + ' · ' + contactRange.count + ' booking' + (contactRange.count === 1 ? '' : 's') +
          ' · Generated ' + today()) + '</span>';
    }

    function renderContacts(rows) {
      if (!rows.length) {
        clistEl.innerHTML = '<p style="color:var(--muted)">No bookings in this range.</p>';
        return;
      }
      let html = '<table class="report-table"><thead><tr><th>Date</th><th>Name</th><th>Phone</th><th>Email</th><th>Service</th><th>Status</th></tr></thead><tbody>';
      rows.forEach((r) => {
        html += '<tr>' +
          '<td>' + escapeHtml(fmt(r.createdAt)) + '</td>' +
          '<td>' + escapeHtml(r.name) + '</td>' +
          '<td>' + escapeHtml(r.phone || '—') + '</td>' +
          '<td>' + escapeHtml(r.email || '—') + '</td>' +
          '<td>' + escapeHtml(r.serviceType) + '</td>' +
          '<td>' + escapeHtml(cap(r.status)) + '</td>' +
          '</tr>';
      });
      html += '</tbody></table>';
      clistEl.innerHTML = html;
    }

    async function loadContacts() {
      clistEl.innerHTML = '<p style="color:var(--muted)">Loading&hellip;</p>';
      const qs = [];
      if (fromEl.value) qs.push('from=' + encodeURIComponent(fromEl.value));
      if (toEl.value) qs.push('to=' + encodeURIComponent(toEl.value));
      try {
        const d = await api.get('api/admin/contact-report.php' + (qs.length ? '?' + qs.join('&') : ''));
        const rows = d.rows || [];
        contactRange = { from: d.from, to: d.to, count: rows.length };
        renderContacts(rows);
      } catch (e) {
        clistEl.innerHTML = '<p style="color:var(--muted)">Could not load the contact list.</p>';
        toast(e.message, 'error');
      }
    }

    applyPreset('month');
    loadSummary();
    refresh.reports = () => (sub === 'summary' ? loadSummary() : loadContacts());
  }
```

Note three behaviours worth understanding rather than just transcribing:

1. `refresh.reports` now dispatches on the active sub-tab, so the dashboard's periodic refresh reloads whichever list is on screen instead of always the summary.
2. Editing a date input clears the preset highlight — the buttons are shortcuts that fill the inputs, not a separate mode.
3. `contactRange` is populated from the server's echoed `from`/`to`, not from the inputs, so the printed heading reports the range that was actually applied.

- [ ] **Step 3: Verify the summary tab still works**

Open `http://localhost/randy_project/admin/` in a browser, log in, click **Reports**.

Expected: the Summary sub-tab is active; Daily/Weekly/Monthly still switch the table; the numbers match what they showed before this change.

- [ ] **Step 4: Verify the contact list**

Click **Contact list**.

Expected:
- "This month" is pre-selected and the table shows this month's bookings, newest first.
- Columns read Date, Name, Phone, Email, Service, Status.
- Clicking **All time** shows every booking; clicking **Last 30 days** and **This year** each narrow it.
- Typing a `From` date later than `To` shows the error toast `Start date must be on or before the end date` and the table shows "Could not load the contact list."
- The browser console has no errors.

- [ ] **Step 5: Commit**

```bash
git add assets/js/admin.js
git commit -m "feat(admin): add contact list sub-tab to the Reports panel"
```

---

## Task 3: Print styling

**Files:**
- Modify: `assets/css/styles.css:472-490`

**Interfaces:**
- Consumes: the markup emitted by Task 2 — `[data-rp-sub]`, `[data-rp-presets]`, `[data-rp-dates]`, and `.report-table` inside `.report-table-wrap`.
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Add the on-screen date-control style**

Immediately after the `.report-table-wrap` rule at `assets/css/styles.css:472`, add:

```css
.report-dates { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; margin: .75rem 0; }
.report-dates label { display: inline-flex; align-items: center; gap: .4rem; font-size: .85rem; color: var(--muted); }
.report-dates input { padding: .4rem .6rem; border: 1px solid var(--line); border-radius: var(--radius); font: inherit; }
```

- [ ] **Step 2: Extend the print block**

The `@media print` block currently ends (at line 489) with:

```css
  [data-rp-filter], [data-rp-print] { display: none !important; }
}
```

Replace those two lines with:

```css
  [data-rp-filter], [data-rp-print], [data-rp-sub], [data-rp-presets], [data-rp-dates] { display: none !important; }
  .report-table-wrap { overflow-x: visible; }
  .report-table { border: 1px solid #999; }
  .report-table thead { display: table-header-group; }
  .report-table th, .report-table td { border: 1px solid #999; white-space: normal; font-size: .78rem; padding: .35rem .5rem; }
  .report-table tbody tr:last-child td { border-bottom: 1px solid #999; }
  .report-table tr { page-break-inside: avoid; }
}
```

Each rule earns its place: `display: table-header-group` repeats the column headers on every printed page; `overflow-x: visible` stops a long table being clipped to one page-width; explicit `#999` borders replace the screen style's background fills, which most browsers drop when printing; `white-space: normal` overrides the `nowrap` at line 474 so a long email wraps instead of pushing the table off the page.

- [ ] **Step 3: Verify the printed contact list**

In the browser, go to Reports → Contact list → **All time**, then click **Download PDF** and inspect the print preview.

Expected:
- The heading reads `Randy's Painting & Drywall Services — Booking Contact Report` with the range, booking count and today's date beneath it.
- Sub-tabs, preset buttons, date inputs and the Download button are all absent.
- No other admin panel and no site header/footer appear.
- Every column is on the page — nothing clipped at the right edge.
- If the list runs past one page, the column headers repeat at the top of page 2.

- [ ] **Step 4: Verify the summary print did not regress**

Switch to the **Summary** sub-tab and click **Download PDF**.

Expected: the heading reads `Booking Report — Monthly — Generated <today>`, the summary table prints with borders, and the contact list does **not** appear in the PDF.

- [ ] **Step 5: Commit**

```bash
git add assets/css/styles.css
git commit -m "style(admin): print styling for the booking contact report"
```

---

## Deployment note

Three files change: `api/admin/contact-report.php` (new), `assets/js/admin.js`, `assets/css/styles.css`. All must be uploaded to the live host together — the JS calls an endpoint that does not exist there yet, and a partial upload would surface as the same empty-body 500 currently affecting `api/crm/update.php`.

No database migration is required.
