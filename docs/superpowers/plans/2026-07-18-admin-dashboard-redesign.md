# Admin Dashboard Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the admin dashboard's public-marketing-page look (full site nav/footer, cramped centered column, horizontal pill tabs) with a dedicated admin shell — dark left sidebar navigation + light content area — without touching any panel functionality or JS logic.

**Architecture:** Two new slim include files (`includes/admin-header.php`, `includes/admin-footer.php`) replace `includes/header.php`/`includes/footer.php` for `admin/index.php` only. The existing `.tabs`/`.tab`/`[data-panel]` markup and `assets/js/admin.js` are wrapped in a new `.admin-shell` layout, unchanged in every attribute the JS depends on. CSS adds the shell/sidebar styling and fixes the print stylesheet's now-stale `.admin` selector.

**Tech Stack:** Plain PHP 8.2 (no framework), vanilla JS, hand-written CSS (no build step, no preprocessor). No test runner exists in this repo (no `composer.json`/`package.json`/`phpunit.xml`) — verification is `php -l` for syntax and live browser checks via the `playwright` MCP server for behavior/visuals, matching how every other spec in `docs/superpowers/specs/` documents testing as manual/browser verification.

## Global Constraints

- Do not modify `assets/js/admin.js` — every task must preserve `data-tabs`, `.tab`/`data-tab`, and `[data-panel]` exactly as they exist today (see `assets/js/admin.js:808-827`).
- Do not modify `includes/header.php` or `includes/footer.php` — the public marketing site must be visually unaffected.
- New admin pages must send `<meta name="robots" content="noindex, nofollow">` and omit OG/Twitter/JSON-LD meta.
- Sidebar theme tokens: `--adm-sidebar: #10182a`, `--adm-sidebar-text: #cbd5e1`, `--adm-content-bg: #f7f8fa`, scoped to `.admin-shell` (not `:root`).
- Mobile collapse breakpoint: `900px`, CSS-only (no new JS).
- Local dev URL: `http://localhost/randy_project/admin/` (Apache/XAMPP, confirmed via `curl -I`). Admin login: `admin@randyspaintdrywall.com` / `changeme123` (from `config.php`, local dev only).

---

## File Structure

- Create: `includes/admin-header.php` — slim `<head>`/`<body>` opener for the admin page only (no nav, no marketing SEO meta).
- Create: `includes/admin-footer.php` — closes the page, loads `assets/js/app.js` (required by `admin.js` for `window.api`/`window.toast`), no marketing footer, no `chat.js`.
- Modify: `admin/index.php` — new `.admin-shell` markup (sidebar + content), uses the two files above instead of `includes/header.php`/`includes/footer.php`.
- Modify: `assets/css/styles.css` — fix the `@media print` block (`:460-467`) which currently targets the soon-to-be-removed `.admin` class, and replace the `/* ADMIN DASHBOARD */` section (`:507-544`) with the new shell/sidebar CSS plus a `900px` responsive collapse.

---

### Task 1: Create `includes/admin-header.php`

**Files:**
- Create: `includes/admin-header.php`

**Interfaces:**
- Consumes: `current_user()`, `business_info()`, `url()`, `e()` (all already loaded by `includes/app.php`, which every page requires before including this file). Expects the including page to have already set `$page_title` and to pass through `$u` (the array returned by `require_admin_page()`) — this file does **not** call `current_user()` itself, it relies on `$u` already being in scope from the including page.
- Produces: opens `<html>`/`<head>`/`<body>`, leaves `<body>` open (no wrapping `<main>`) for the including page to place its own shell markup. Sets `window.BASE_URL`.

- [ ] **Step 1: Write `includes/admin-header.php`**

```php
<?php
/**
 * Slim head/body opener for the admin dashboard only.
 * No marketing nav, no SEO meta/JSON-LD — the admin panel isn't a public page.
 * Expects $u (from require_admin_page()) and $page_title to already be set
 * by the including page.
 */
$b = business_info();
$title = isset($page_title) ? "$page_title — {$b['name']}" : $b['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(url('assets/img/favicon-32.png')) ?>">
    <link rel="icon" href="<?= e(url('assets/img/favicon.ico')) ?>" sizes="any">
    <link rel="apple-touch-icon" href="<?= e(url('assets/img/apple-touch-icon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php $css_v = @filemtime(__DIR__ . '/../assets/css/styles.css') ?: 1; ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/styles.css')) ?>?v=<?= $css_v ?>">
    <script>window.BASE_URL = <?= json_encode(url('')) ?>;</script>
</head>
<body>
```

- [ ] **Step 2: Verify PHP syntax**

Run: `php -l includes/admin-header.php`
Expected: `No syntax errors detected in includes/admin-header.php`

- [ ] **Step 3: Commit**

```bash
git add includes/admin-header.php
git commit -m "feat(admin): add slim admin-only page header"
```

---

### Task 2: Create `includes/admin-footer.php`

**Files:**
- Create: `includes/admin-footer.php`

**Interfaces:**
- Consumes: `$u` (in scope from the including page), `url()`, `e()`.
- Produces: closes `</body></html>`, includes the `.toast` element required by `window.toast` (defined in `assets/js/app.js`), sets `window.CURRENT_USER`, loads `assets/js/app.js`.

- [ ] **Step 1: Write `includes/admin-footer.php`**

```php
<?php
/**
 * Slim footer for the admin dashboard only — no marketing footer, no chat widget.
 * Still loads app.js: admin.js depends on its window.api / window.toast helpers.
 */
?>
<div class="toast" data-toast></div>

<script>
    window.CURRENT_USER = <?= json_encode($u ? ['id' => $u['id'], 'email' => $u['email'], 'role' => $u['role']] : null) ?>;
</script>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
```

- [ ] **Step 2: Verify PHP syntax**

Run: `php -l includes/admin-footer.php`
Expected: `No syntax errors detected in includes/admin-footer.php`

- [ ] **Step 3: Commit**

```bash
git add includes/admin-footer.php
git commit -m "feat(admin): add slim admin-only page footer"
```

---

### Task 3: Rewrite `admin/index.php` with the new shell markup

**Files:**
- Modify: `admin/index.php` (currently 36 lines, full file shown below is the complete replacement)

**Interfaces:**
- Consumes: `includes/admin-header.php` (Task 1), `includes/admin-footer.php` (Task 2) — both expect `$u` and `$page_title` set before inclusion.
- Produces: `.admin-shell > .admin-sidebar + .admin-content` markup that Task 4's CSS targets. Keeps `data-tabs`, every `.tab`/`data-tab`, and every `[data-panel]` identical to the current file so `assets/js/admin.js` needs no changes.

- [ ] **Step 1: Replace the full contents of `admin/index.php`**

```php
<?php
require_once __DIR__ . '/../includes/app.php';
$u = require_admin_page();
$b = business_info();

$page_title = 'Admin Dashboard';
require __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-sidebar__brand" href="<?= e(url('admin/index.php')) ?>">
            <img src="<?= e(url('assets/img/logo.png')) ?>" alt="<?= e($b['name']) ?>">
            <span><?= e($b['name']) ?></span>
        </a>

        <div class="tabs" data-tabs role="tablist">
            <button class="tab is-active" data-tab="overview" role="tab">Overview</button>
            <button class="tab" data-tab="chat" role="tab">Live Chat</button>
            <button class="tab" data-tab="leads" role="tab">CRM / Leads</button>
            <button class="tab" data-tab="bookings" role="tab">Bookings</button>
            <button class="tab" data-tab="reports" role="tab">Reports</button>
            <button class="tab" data-tab="gallery" role="tab">Gallery</button>
            <button class="tab" data-tab="blog" role="tab">Blog</button>
            <button class="tab" data-tab="reviews" role="tab">Reviews</button>
            <button class="tab" data-tab="careers" role="tab">Careers</button>
        </div>

        <div class="admin-sidebar__foot">
            <span class="admin-sidebar__user"><?= e($u['email']) ?></span>
            <a href="<?= e(url('logout.php')) ?>">Log out</a>
        </div>
    </aside>

    <div class="admin-content">
        <h1 class="app-title">Admin Dashboard</h1>
        <p class="app-sub">Manage chats, bookings, the gallery, and the blog.</p>

        <div data-panel="overview"></div>
        <div data-panel="chat" hidden></div>
        <div data-panel="leads" hidden></div>
        <div data-panel="bookings" hidden></div>
        <div data-panel="reports" hidden></div>
        <div data-panel="gallery" hidden></div>
        <div data-panel="blog" hidden></div>
        <div data-panel="reviews" hidden></div>
        <div data-panel="careers" hidden></div>
    </div>
</div>
<script src="<?= e(url('assets/js/admin.js')) ?>"></script>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
```

- [ ] **Step 2: Verify PHP syntax**

Run: `php -l admin/index.php`
Expected: `No syntax errors detected in admin/index.php`

- [ ] **Step 3: Commit**

```bash
git add admin/index.php
git commit -m "feat(admin): switch admin dashboard to dedicated shell markup"
```

(The page will render unstyled/broken between this commit and Task 4 — that's expected and fixed by the next task. If executing task-by-task with review gates, it's fine to land this before CSS lands; if you want a visually-working intermediate state, do Task 4 in the same sitting before checking the page in a browser.)

---

### Task 4: Add shell/sidebar CSS and fix the stale print rule

**Files:**
- Modify: `assets/css/styles.css`

**Interfaces:**
- Consumes: existing custom properties `--slate`, `--slate-2`, `--line`, `--radius`, `--shadow-sm`, `--shadow-md`, `--font-display`, `--gutter`, `--muted`, `--ink`, `--coral` (all defined in `:root`, `assets/css/styles.css:7-48`).
- Produces: `.admin-shell`, `.admin-sidebar`, `.admin-sidebar__brand`, `.admin-sidebar__foot`, `.admin-sidebar__user`, `.admin-content` selectors that Task 3's markup targets. Leaves the base `.tabs`/`.tab`/`.tab.is-active` rules untouched (they still style every in-panel filter-pill group in `assets/js/admin.js`, e.g. `[data-bk-filter] .tab`, `[data-rp-filter] .tab`, `[data-crm-filter] .tab`) and adds a higher-specificity `.admin-sidebar .tabs`/`.admin-sidebar .tab` override for the sidebar nav only.

- [ ] **Step 1: Fix the `@media print` block so the Reports "print to PDF" view still isolates correctly**

The current rule (`assets/css/styles.css:460-467`) targets `.admin`, a class this redesign removes from the markup. Without this fix, printing from the Reports tab would show the sidebar and every other panel instead of just the report.

Find:
```css
@media print {
  .site-header, .site-footer, .chat-bubble, .chat-panel, .toast { display: none !important; }
  .admin .tabs, .admin [data-panel]:not([data-panel="reports"]) { display: none !important; }
  .admin .app-title, .admin .app-sub { display: none !important; }
  .admin { padding: 0; max-width: none; }
  .report-print-heading { display: block; margin-bottom: 1rem; font-weight: 700; font-size: 1.1rem; }
  [data-rp-filter], [data-rp-print] { display: none !important; }
}
```

Replace with:
```css
@media print {
  .site-header, .site-footer, .chat-bubble, .chat-panel, .toast { display: none !important; }
  .admin-sidebar { display: none !important; }
  .admin-content [data-panel]:not([data-panel="reports"]) { display: none !important; }
  .admin-content .app-title, .admin-content .app-sub { display: none !important; }
  .admin-shell { display: block; }
  .admin-content { padding: 0; max-width: none; }
  .report-print-heading { display: block; margin-bottom: 1rem; font-weight: 700; font-size: 1.1rem; }
  [data-rp-filter], [data-rp-print] { display: none !important; }
}
```

- [ ] **Step 2: Replace the `/* ADMIN DASHBOARD */` section**

Find (`assets/css/styles.css:507-511`, the opening of the section):
```css
/* =====================  ADMIN DASHBOARD  ===================== */
.admin { max-width: 64rem; margin-inline: auto; padding: 1.5rem var(--gutter) 3rem; }
.tabs { display: inline-flex; gap: .25rem; background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: .25rem; margin: 1rem 0 1.5rem; box-shadow: var(--shadow-sm); flex-wrap: wrap; }
.tab { border: 0; background: transparent; color: var(--muted); font-weight: 600; font-size: .9rem; padding: .45rem .9rem; border-radius: 7px; cursor: pointer; }
.tab.is-active { background: var(--slate); color: #fff; }
```

Replace with:
```css
/* =====================  ADMIN DASHBOARD  ===================== */
.admin-shell {
  --adm-sidebar: #10182a;
  --adm-sidebar-text: #cbd5e1;
  --adm-content-bg: #f7f8fa;
  display: flex;
  min-height: 100vh;
  align-items: stretch;
}

.admin-sidebar {
  width: 15rem;
  flex: none;
  background: var(--adm-sidebar);
  color: var(--adm-sidebar-text);
  display: flex;
  flex-direction: column;
  padding: 1.25rem 1rem;
}
.admin-sidebar__brand { display: flex; align-items: center; gap: .6rem; padding: .5rem .25rem 1.25rem; text-decoration: none; color: #fff; }
.admin-sidebar__brand img { height: 32px; width: auto; display: block; }
.admin-sidebar__brand span { font-family: var(--font-display); font-weight: 800; font-size: 1rem; }

.admin-sidebar__foot { margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,.12); display: flex; flex-direction: column; gap: .4rem; }
.admin-sidebar__user { font-size: .78rem; color: var(--adm-sidebar-text); word-break: break-all; }
.admin-sidebar__foot a { font-size: .85rem; font-weight: 700; color: #fff; text-decoration: none; }
.admin-sidebar__foot a:hover { text-decoration: underline; }

.admin-content { flex: 1; min-width: 0; background: var(--adm-content-bg); padding: 1.75rem var(--gutter) 3rem; }

/* Base tab-pill look — still used by every in-panel filter group
   (e.g. [data-bk-filter] .tab, [data-rp-filter] .tab, [data-crm-filter] .tab). */
.tabs { display: inline-flex; gap: .25rem; background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: .25rem; margin: 1rem 0 1.5rem; box-shadow: var(--shadow-sm); flex-wrap: wrap; }
.tab { border: 0; background: transparent; color: var(--muted); font-weight: 600; font-size: .9rem; padding: .45rem .9rem; border-radius: 7px; cursor: pointer; }
.tab.is-active { background: var(--slate); color: #fff; }

/* Sidebar nav override — higher specificity than the base .tabs/.tab rules above,
   so only the sidebar's [data-tabs] list turns into a vertical nav. */
.admin-sidebar .tabs { flex-direction: column; align-items: stretch; background: transparent; border: 0; box-shadow: none; padding: 0; margin: 0; gap: .15rem; }
.admin-sidebar .tab { text-align: left; color: var(--adm-sidebar-text); border-radius: 8px; border-left: 3px solid transparent; padding: .6rem .75rem; }
.admin-sidebar .tab:hover { background: rgba(255,255,255,.06); }
.admin-sidebar .tab.is-active { background: rgba(255,255,255,.08); color: #fff; border-left-color: var(--slate-2); }

@media (max-width: 900px) {
  .admin-shell { flex-direction: column; min-height: 0; }
  .admin-sidebar { width: 100%; padding: 1rem; }
  .admin-sidebar .tabs { flex-direction: row; overflow-x: auto; }
  .admin-sidebar .tab { border-left: 0; border-bottom: 3px solid transparent; white-space: nowrap; }
  .admin-sidebar .tab.is-active { border-left-color: transparent; border-bottom-color: var(--slate-2); }
  .admin-sidebar__foot { flex-direction: row; align-items: center; justify-content: space-between; margin-top: .75rem; padding-top: .75rem; }
}
```

Everything below this point in the section (`.kpi-section`, `.kpi-grid`, `.kpi`, `.kpi__label`, `.kpi__badge*`, `.kpi__value`, `button.kpi`, `.admin-chat*`, `.admin-queue*`, `.gallery-admin__*`) stays exactly as-is — those rules already use `--line`/`--shadow-sm`/`--slate`/`--coral` tokens rather than the old page-background color, so they sit correctly on the new `--adm-content-bg` without changes.

- [ ] **Step 3: Confirm no other file references the removed `.admin` class**

Run: `grep -rn "class=\"admin\"" --include=*.php --include=*.js .` (or use the Grep tool with pattern `class="admin"`)
Expected: no matches (the only prior usage was `admin/index.php:8`, replaced in Task 3).

- [ ] **Step 4: Commit**

```bash
git add assets/css/styles.css
git commit -m "feat(admin): style the new sidebar shell, fix stale print selector"
```

---

### Task 5: Full browser verification pass

**Files:** none (verification only)

**Interfaces:**
- Consumes: the running local site at `http://localhost/randy_project/`, the `playwright` MCP server (already configured — `mcp__playwright__browser_navigate`, `browser_fill_form`, `browser_click`, `browser_snapshot`, `browser_take_screenshot`, `browser_console_messages`, `browser_resize`).

- [ ] **Step 1: Log in as admin and load the dashboard**

Use `mcp__playwright__browser_navigate` to `http://localhost/randy_project/login.php`, then `mcp__playwright__browser_fill_form` with email `admin@randyspaintdrywall.com` and password `changeme123`, submit, then navigate to `http://localhost/randy_project/admin/index.php`.

Expected: page loads with a dark left sidebar (brand + 9 nav items + email/Log out at the bottom) and a light content area showing the Overview KPIs. No `<header class="site-header">` nav or marketing `<footer>` anywhere — confirm with `mcp__playwright__browser_snapshot` (no "Services"/"Commercial" dropdown links, no footer social icons/"Get in touch" block in the accessibility tree).

- [ ] **Step 2: Click through all 9 sidebar tabs**

For each of `chat`, `leads`, `bookings`, `reports`, `gallery`, `blog`, `reviews`, `careers`, `overview`: use `mcp__playwright__browser_click` on the corresponding sidebar nav item, then `browser_snapshot` to confirm the matching `[data-panel]` becomes visible and populates (e.g. Reports shows a table, Gallery shows the upload form/grid, Bookings shows the booking list with its own filter-pill row still styled as pills — confirming the base `.tabs`/`.tab` rule from Task 4 Step 2 still applies to in-panel filters, not just the sidebar).

Expected: every panel loads with no JavaScript errors — check via `mcp__playwright__browser_console_messages` after each click (no new `error`-level entries).

- [ ] **Step 3: Verify the mobile collapse**

Use `mcp__playwright__browser_resize` to `375x800`, then `browser_snapshot`/`browser_take_screenshot`.

Expected: sidebar becomes a horizontal scrollable strip at the top (per the `@media (max-width: 900px)` rule from Task 4); all 9 tabs remain clickable via `browser_click`.

Resize back to `1280x800` afterward.

- [ ] **Step 4: Verify a toast fires (confirms `app.js` still loads correctly from the new footer)**

In the CRM/Leads panel, click a stage-filter pill (or perform any action the panel exposes that calls `window.toast` on completion, e.g. updating a lead). Use `browser_snapshot` right after to confirm a `.toast.is-show` element with a message briefly appears.

Expected: toast appears — proves `assets/js/app.js` (which defines `window.toast`/`window.api`, used throughout `admin.js`) is loading correctly from `includes/admin-footer.php`.

- [ ] **Step 5: Verify Log out**

`mcp__playwright__browser_click` the "Log out" link in the sidebar footer.

Expected: redirected away from the admin dashboard (to the public site), confirming `require_admin_page()`'s guard still applies on a subsequent visit to `/admin/`.

- [ ] **Step 6: Spot-check the public marketing site is unaffected**

`mcp__playwright__browser_navigate` to `http://localhost/randy_project/index.php`, `browser_snapshot`.

Expected: full marketing nav (with Services/Commercial dropdowns) and footer render exactly as before — confirming `includes/header.php`/`includes/footer.php` were untouched.

- [ ] **Step 7: Re-check the Reports print rule by inspection**

Native print dialogs can't be asserted through Playwright, so verify this one by inspection instead: re-read the `@media print` block written in Task 4 Step 1 and confirm every selector it references (`.admin-sidebar`, `.admin-content [data-panel]`, `.admin-content .app-title`/`.app-sub`, `.admin-shell`, `.admin-content`) matches a class that actually exists in the Task 3 markup (it does — cross-check against `admin/index.php`). This is a code-inspection check, not a browser action.

Expected: every selector in the print block resolves to real markup; no leftover reference to the removed `.admin` class anywhere in `assets/css/styles.css` (re-run Task 4 Step 3's grep if unsure).

- [ ] **Step 8: Final commit (if any fixes were needed during verification)**

```bash
git add -A
git commit -m "fix(admin): address issues found in dashboard verification pass"
```

(Skip this commit if Steps 1-7 all passed with no changes needed.)
