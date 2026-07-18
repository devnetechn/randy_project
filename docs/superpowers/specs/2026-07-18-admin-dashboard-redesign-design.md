# Admin Dashboard Redesign

**Date:** 2026-07-18
**Status:** Approved (design)

## Summary

Replace `admin/index.php`'s current look — the full public marketing chrome (`includes/header.php`'s nav with Services/Commercial dropdowns and hero logo, `includes/footer.php`'s footer with social links and marketing CTAs) wrapped around a cramped 64rem centered column of horizontal pill tabs — with a dedicated admin shell: a dark left sidebar for navigation plus a light content area, in the site's existing brand colors but restyled to feel like a real internal tool rather than a customer-facing page.

This is a visual/structural reskin only. No panel functionality, no `assets/js/admin.js` logic, and no API endpoints change.

## Goals

- A dedicated `includes/admin-header.php` / `includes/admin-footer.php` pair used only by `admin/index.php`, with no marketing nav, dropdowns, footer, or floating chat bubble markup.
- Admin pages are `noindex,nofollow` and skip the marketing `LocalBusiness` JSON-LD (they're not public/SEO-relevant pages, unlike today).
- A left sidebar shell: brand mark at top, the 9 existing tabs (Overview, Live Chat, CRM/Leads, Bookings, Reports, Gallery, Blog, Reviews, Careers) as a vertical nav list, admin email + Log out pinned at the bottom.
- A dark navy sidebar (`#10182a`) against a light content area (`#f7f8fa`), with brand blue reserved for primary actions/active nav state and brand red reserved for destructive/error actions only (not a wash of marketing color across the whole UI).
- Below 900px, the sidebar collapses into a horizontal scrollable strip at the top (CSS-only, no new JS) rather than a drawer/hamburger.
- Existing components (`.kpi`, `.admin-chat`, `.admin-queue`, `.gallery-admin__*`, `.blog-admin__*`, filter tab-pills, tables) get restyled to match the new theme — same markup, same classes, same JS hooks, new colors/spacing/shadows.

## Non-Goals (YAGNI)

- No changes to `assets/js/admin.js` — the tab-switching logic (`document.querySelector('[data-tabs]')`, `.tab` / `data-tab` / `data-panel`) stays byte-for-byte identical; the shell is restyled around it, not rewired.
- No restructuring of what's *inside* individual panels (no new KPI layout, no CRM table redesign, etc.) — reskin only, per the owner's explicit choice to keep this scoped.
- No changes to `includes/header.php` / `includes/footer.php` — the public marketing site is untouched; zero risk of regressing customer-facing pages.
- No hamburger-menu/drawer JS for mobile — a pure CSS collapse (sidebar → horizontal scroll strip) matches the codebase's existing lightweight-JS conventions and avoids new interaction bugs.
- No dark-mode toggle or user-configurable theming — one fixed dark-sidebar/light-content theme.

## Architecture

### `includes/admin-header.php` (new)

Slim `<head>` + `<body>` opener used only by `admin/index.php`, replacing `includes/header.php` for this page:

- `<title>`, favicon links, Google Fonts preconnect, `assets/css/styles.css` (same file, cache-busted the same way via `filemtime()`).
- `<meta name="robots" content="noindex,nofollow">` — no canonical/OG/Twitter meta, no JSON-LD (those are marketing-page concerns that don't apply here).
- `window.BASE_URL = <?= json_encode(url('')) ?>;` — kept, since `assets/js/app.js`'s `window.api` helper depends on it.
- Calls `current_user()` and `business_info()` itself (header.php currently does this) so the sidebar can render the brand name/logo and the logged-in admin's email.
- No `<header class="site-header">` / `<nav>` block at all.

### `includes/admin-footer.php` (new)

- Closes the shell markup, includes the `<div class="toast" data-toast"></div>` element (used by `window.toast` in `app.js`) and `window.CURRENT_USER` script.
- Loads `assets/js/app.js` (required — defines `window.api` and `window.toast`, both used throughout `admin.js`) but **not** `assets/js/chat.js` (no floating chat widget on admin; it's already suppressed for admin role in the current `footer.php` via `$show_widget`, so dropping the script entirely here is equivalent and simpler).
- No `<footer class="site-footer">` marketing block.

### `admin/index.php` markup

Wrap the existing content in a shell, without changing the tab/panel machinery:

```html
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="admin-sidebar__brand"><!-- logo + business name --></div>

    <div class="tabs" data-tabs role="tablist"><!-- existing 9 .tab buttons, unchanged --></div>

    <div class="admin-sidebar__foot">
      <span class="admin-sidebar__user"><!-- $u['email'] --></span>
      <a href="<?= e(url('logout.php')) ?>">Log out</a>
    </div>
  </aside>

  <div class="admin-content">
    <h1 class="app-title">Admin Dashboard</h1>
    <p class="app-sub">Manage chats, bookings, the gallery, and the blog.</p>
    <!-- existing 9 [data-panel] divs, unchanged -->
  </div>
</div>
```

`data-tabs`, every `.tab`/`data-tab`, and every `[data-panel]` div keep their exact current attributes — `admin.js`'s `document.querySelector('[data-tabs]')` / `panels[name]` lookups keep working with zero JS changes. Only the CSS turns `.tabs` from a horizontal pill row into a vertical sidebar list.

### CSS (`assets/css/styles.css`, `/* ADMIN DASHBOARD */` section)

- New theme custom properties scoped under `.admin-shell` (not `:root`), so the marketing site's palette is untouched:
  - `--adm-sidebar: #10182a`, `--adm-sidebar-text: #cbd5e1`, `--adm-content-bg: #f7f8fa`, plus reuse of the existing `--slate`/`--coral` tokens for accents.
- `.admin-shell { display: flex; min-height: 100vh; }`, `.admin-sidebar { width: 15rem; flex: none; background: var(--adm-sidebar); ... }`, `.admin-content { flex: 1; background: var(--adm-content-bg); padding: ...; }`.
- `.admin-sidebar .tabs` restyled: `flex-direction: column`, full-width buttons, active state = left accent bar (`border-left: 3px solid var(--slate-2)`) + white text instead of the current filled pill.
- `.kpi`, `.admin-chat`, `.admin-queue`, `.gallery-admin__*`, `.blog-admin__*`, and in-panel filter `.tab` pills get color/spacing/shadow updates to sit naturally on `--adm-content-bg` and match the new accent usage — same selectors, same structure.
- `@media (max-width: 900px)`: `.admin-shell { flex-direction: column; }`, `.admin-sidebar { width: 100%; }`, `.admin-sidebar .tabs { flex-direction: row; overflow-x: auto; }` — sidebar becomes a horizontal scrollable strip, brand/user-footer stack above/below it.

## Testing

- Manual: load `/admin` as the admin user — confirm no marketing nav/footer/chat-bubble appears anywhere on the page, and view source shows `noindex,nofollow` with no JSON-LD block.
- Click through all 9 sidebar tabs — confirm each panel still loads/refreshes exactly as before (KPIs populate, chat queue loads, CRM/bookings/reports/gallery/blog/reviews/careers all function unchanged) since `admin.js` is untouched.
- Confirm Log out from the sidebar footer works.
- Resize below 900px — confirm the sidebar collapses to a horizontal scroll strip and all tabs remain reachable/tappable.
- Confirm the public marketing site (home, services, etc.) is visually unchanged — `includes/header.php`/`footer.php` were not touched.
- Confirm `assets/js/app.js`-dependent features on the admin page still work (toasts on save/delete actions, since `window.toast`/`window.api` come from `app.js`).
