# Reports Tab PDF Download Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Download PDF" button to the admin Reports tab that triggers the browser's native print-to-PDF flow, showing only a generated heading and the currently-visible report table — no site chrome.

**Architecture:** Extend the existing `initReports(panel)` function in `assets/js/admin.js` with a print button and a hidden heading element; add one `@media print` CSS block to `assets/css/styles.css` that hides site/admin chrome and reveals the heading. No new dependencies, no server-side changes — pure client-side print stylesheet + `window.print()`.

**Tech Stack:** Vanilla PHP 8 (unchanged in this plan), plain JS, `assets/css/styles.css`. **No test framework exists** — verification is via `node --check` (JS syntax), a live Playwright browser session, and manual print-preview inspection.

**Conventions to follow:**
- This plan modifies the `initReports` function created in the prior `docs/superpowers/plans/2026-07-02-admin-booking-reports.md` plan (already merged to `main`) — read the current code exactly as shown in each task's "Files" section, since these are precise line-anchored edits, not fresh file creation.
- `.btn-soft` is an existing button class (used elsewhere in `admin.js`, e.g. the Decline button in `initBookings`) — reuse it for the new button rather than inventing a new class.
- Commit after each task. Work happens on branch `feature/reports-pdf-download` (created in Task 0).

---

## File Structure

| File | Responsibility | Action |
|------|----------------|--------|
| `assets/js/admin.js` | Add Download PDF button + print heading + click handler inside `initReports()` | Modify |
| `assets/css/styles.css` | New `@media print` block: hide chrome, show heading+table only | Modify |

---

## Task 0: Create the feature branch

- [ ] **Step 1: Create and switch to the branch**

```bash
git checkout main
git pull
git checkout -b feature/reports-pdf-download
```

(Same as prior features on this project: a regular branch checked out in place, not a worktree, since XAMPP serves this exact directory and pages must stay testable at `http://localhost/randy/...` throughout.)

---

## Task 1: Add the Download PDF button and print heading to `initReports()`

**Files:**
- Modify: `assets/js/admin.js:98-149` (the `initReports` function)

- [ ] **Step 1: Add the button and heading element to the panel markup**

In `assets/js/admin.js`, inside `initReports(panel)`, this exact block currently exists:

```js
    panel.innerHTML =
      '<div class="tabs" data-rp-filter style="margin-top:0">' +
      VIEWS.map((v) => '<button class="tab' + (v === view ? ' is-active' : '') + '" data-v="' + v + '">' + LABELS[v] + '</button>').join('') +
      '</div><div class="report-table-wrap" data-rp-table></div>';
    const tableEl = panel.querySelector('[data-rp-table]');
```

Replace it with:

```js
    panel.innerHTML =
      '<div class="tabs" data-rp-filter style="margin-top:0">' +
      VIEWS.map((v) => '<button class="tab' + (v === view ? ' is-active' : '') + '" data-v="' + v + '">' + LABELS[v] + '</button>').join('') +
      '<button type="button" class="btn-soft" data-rp-print style="margin-left:.5rem">Download PDF</button>' +
      '</div><div class="report-print-heading" data-rp-heading></div><div class="report-table-wrap" data-rp-table></div>';
    const tableEl = panel.querySelector('[data-rp-table]');
    const headingEl = panel.querySelector('[data-rp-heading]');
```

- [ ] **Step 2: Add the click handler for the Download PDF button**

In `assets/js/admin.js`, this exact block currently exists immediately after the markup above:

```js
    panel.querySelector('[data-rp-filter]').addEventListener('click', (e) => {
      const b = e.target.closest('[data-v]'); if (!b) return;
      view = b.dataset.v;
      panel.querySelectorAll('[data-rp-filter] .tab').forEach((t) => t.classList.toggle('is-active', t === b));
      load();
    });
```

Add this new block immediately after it (before the `function periodLabel(p) {` line):

```js

    panel.querySelector('[data-rp-print]').addEventListener('click', () => {
      const today = new Date().toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
      headingEl.textContent = 'Booking Report — ' + LABELS[view] + ' — Generated ' + today;
      window.print();
    });
```

- [ ] **Step 3: Verify JS syntax**

Run: `node --check assets/js/admin.js`
Expected: no output / exit code 0 (silent success means valid syntax).

- [ ] **Step 4: Verify live in the browser (Playwright or manual)**

Log in as admin (`admin@randyspaintdrywall.com` / `changeme123`) at `http://localhost/randy/login`, open `http://localhost/randy/admin/`, click the "Reports" tab. Confirm:
- A "Download PDF" button appears to the right of the Daily/Weekly/Monthly toggles.
- Open the browser console and run `document.querySelector('[data-rp-heading]').textContent` before clicking — should be empty string.
- Click "Download PDF". The browser's print dialog should open (in an automated/headless context, you can instead intercept via `page.on('dialog', ...)` or check that `window.print` was called — e.g. temporarily stub `window.print = () => { window.__printed = true; }` before clicking, then click, then confirm `window.__printed === true` and `document.querySelector('[data-rp-heading]').textContent` now reads something like `"Booking Report — Monthly — Generated Jul 3, 2026"`).
- No JS console errors from clicking the button.

- [ ] **Step 5: Commit**

```bash
git add assets/js/admin.js
git commit -m "feat(admin): add Download PDF button to Reports tab"
```

---

## Task 2: Add the print stylesheet

**Files:**
- Modify: `assets/css/styles.css:451` (immediately after the `.report-table tbody tr:last-child td` rule, before the `CHAT WIDGET` section comment)

- [ ] **Step 1: Add the print CSS**

In `assets/css/styles.css`, this exact line currently exists at line 451, immediately followed by the `/* =====================  CHAT WIDGET + ROOM  ===================== */` comment:

```css
.report-table tbody tr:last-child td { border-bottom: 0; }
```

Immediately after that line (and before the CHAT WIDGET comment), add:

```css

/* Reports — print to PDF */
.report-print-heading { display: none; }

@media print {
  .site-header, .site-footer, .chat-bubble, .chat-panel, .toast { display: none !important; }
  .admin .tabs, .admin [data-panel]:not([data-panel="reports"]) { display: none !important; }
  .admin { padding: 0; max-width: none; }
  .report-print-heading { display: block; margin-bottom: 1rem; font-weight: 700; font-size: 1.1rem; }
  [data-rp-filter], [data-rp-print] { display: none !important; }
}
```

- [ ] **Step 2: Verify the CSS variables/classes referenced actually exist**

Run: `grep -n "site-header\|site-footer\|chat-bubble\|chat-panel\|class=\"toast\"\|data-toast" assets/css/styles.css includes/header.php includes/footer.php`

Confirm `.site-header`, `.site-footer`, `.chat-bubble`, `.chat-panel` are real classes used in `includes/header.php`/`includes/footer.php`, and that the toast element (`<div class="toast" data-toast></div>` in `includes/footer.php`) uses class `toast` (matching the `.toast` selector above).

- [ ] **Step 3: Verify the served stylesheet includes the new rules**

```
curl -s "http://localhost/randy/assets/css/styles.css" | grep -c "report-print-heading"
```
Expected: a number greater than 0.

- [ ] **Step 4: Verify the print output live (Playwright or manual)**

With the same admin session from Task 1, on the Reports tab: use Playwright's `page.emulateMedia({ media: 'print' })` (or your tooling's equivalent) to switch the page into print-media CSS mode without actually opening the OS print dialog, then take a screenshot. Confirm:
- `.site-header`, `.site-footer`, the chat bubble, and the main admin tab bar are NOT visible.
- The `.report-print-heading` element IS visible (after Task 1's click handler has set its text — click the Download PDF button first, or manually set `document.querySelector('[data-rp-heading]').textContent = 'Booking Report — Monthly — Generated Jul 3, 2026'` via `page.evaluate` for this check if you don't want to trigger a real print dialog).
- The report table is still visible and readable.
- Switch back to `page.emulateMedia({ media: 'screen' })` afterward and confirm the page returns to normal (heading hidden again, chrome visible again).

- [ ] **Step 5: Commit**

```bash
git add assets/css/styles.css
git commit -m "style(admin): add print stylesheet for Reports tab PDF download"
```

---

## Final verification

- [ ] `node --check assets/js/admin.js` passes.
- [ ] Live browser check: Reports tab → Download PDF button visible next to toggles → clicking it populates the heading and triggers print → print-media view (via `emulateMedia`) shows only heading + table, no chrome.
- [ ] Confirm the Reports tab still works normally end-to-end (toggle Daily/Weekly/Monthly, table renders) — this feature only adds to the panel, it shouldn't change any existing behavior.
- [ ] Confirm no regression on 2 other tabs (e.g. Bookings, Overview) after the CSS change — the new `@media print` rules should only ever apply during print, never affecting normal screen rendering.

---

## Spec coverage check

- "Download PDF" button next to toggles → Task 1 ✓
- Click triggers `window.print()` with heading populated (period + generated date) → Task 1 ✓
- Print view shows only heading + table, no site/admin chrome → Task 2 ✓
- Works for whichever period view is currently selected → Task 1 (`LABELS[view]` reads the live `view` variable already tracked by the existing toggle logic) ✓
- No new dependencies/build step → Tasks 1–2 (pure JS + CSS, no `<script>` CDN tags, no Composer) ✓
- No print styling changes to other pages' content → Task 2 (`.admin`-scoped selectors for tab/panel hiding; only sitewide-safe chrome classes hidden unconditionally) ✓
