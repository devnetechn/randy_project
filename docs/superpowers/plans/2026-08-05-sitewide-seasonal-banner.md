# Sitewide Seasonal Banner Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an admin-editable, dismissible announcement bar shown on every public page, promoting a seasonal push ("fall is approaching, book exterior painting before winter") with a fixed "Free Estimate" CTA.

**Architecture:** A single new `settings` row (`site_banner_text`) drives everything — blank means hidden. `includes/header.php` renders it server-side on every page (no schema change, no new table). An inline script right after the banner markup does a synchronous pre-paint check against `localStorage` so a returning visitor who dismissed it never sees a flash-then-remove layout shift; `assets/js/app.js` only has to wire the close button's click. A new admin-only endpoint plus a small form in the Overview tab let Boss Randy edit/clear the text himself.

**Tech Stack:** PHP 8.3 (no Composer, no dependencies), vanilla JS, plain CSS.

**Spec:** `docs/superpowers/specs/2026-08-05-sitewide-seasonal-banner-design.md`

## Global Constraints

- No Composer, no new dependencies, no database schema change — reuses the existing `settings` key/value table and `setting_get()`/`setting_set()` helpers already backing the "External blog URL" setting.
- The repo has **no test framework**. Verification is by `curl`, `php -l`, and browser checks, matching how every other feature in this repo is verified.
- All admin API endpoints call `require_once __DIR__ . '/../../includes/app.php';` then `require_admin_api();` before anything else.
- All user-supplied values rendered into HTML go through `e()` (PHP) or `escapeHtml()` (JS).
- The banner must stay in normal document flow (no `position: fixed`/overlay) and must not cause a layout shift for a visitor who already dismissed it — both are explicit SEO/Core Web Vitals requirements from the spec.
- The CTA is fixed to "Free Estimate" → `/book.php` — not admin-configurable, per the spec's scope decision to keep the admin form to one field.

---

## File Structure

| File | Responsibility |
|---|---|
| `api/admin/banner.php` (create) | Admin-only GET/POST of the `site_banner_text` setting |
| `includes/header.php` (modify) | Server-render the banner + inline pre-paint dismiss check |
| `assets/js/app.js` (modify) | Wire the close button's click (write `localStorage`, hide) |
| `assets/css/styles.css` (modify) | Banner visual style |
| `assets/js/admin.js` (modify) | `initOverview` gains a "Sitewide banner" form |

---

## Task 1: Admin endpoint for the banner setting

**Files:**
- Create: `api/admin/banner.php`

**Interfaces:**
- Consumes: `require_admin_api()`, `read_json()`, `json_out()`, `json_error()` from `includes/app.php`; `setting_get()`/`setting_set()` from `includes/settings.php` (already loaded via `app.php`).
- Produces: `GET api/admin/banner.php` → `{"text": string}`. `POST api/admin/banner.php` with body `{"text": string}` → `{"text": string}` (the saved value) or `422`/`401`/`403` on failure. Task 2 reads the setting directly via `setting_get('site_banner_text', '')` (not through this endpoint — that's for the public page). Task 5's admin form calls this endpoint by exactly this shape.

- [ ] **Step 1: Read the endpoint this one is modelled on**

Read `api/blog/settings.php` in full — same GET/POST-in-one-file shape. The one difference: that file's `GET` is public (the About page needs the blog URL); this one's `GET` is admin-only, since the only consumer is the admin form — the public page never calls this endpoint, it reads the setting directly in PHP.

- [ ] **Step 2: Create the endpoint**

Create `api/admin/banner.php`:

```php
<?php
/** Sitewide announcement banner text. Admin-only: GET reads it, POST saves it. Blank = hidden. */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin_api();
    $payload = read_json();
    $text = trim((string) ($payload['text'] ?? ''));

    if (mb_strlen($text) > 200) {
        json_error('Banner text must be 200 characters or fewer', 422);
    }

    setting_set('site_banner_text', $text);
    json_out(['text' => $text]);
}

require_admin_api();
json_out(['text' => setting_get('site_banner_text', '')]);
```

- [ ] **Step 3: Verify the endpoint parses**

```bash
php -l api/admin/banner.php
```

Expected: `No syntax errors detected in api/admin/banner.php`

- [ ] **Step 4: Verify it rejects unauthenticated callers**

```bash
curl -s -w "\n[%{http_code}]\n" "http://localhost/randy_project/api/admin/banner.php"
```

Expected: `{"error":"Authentication required"}` and `[401]`.

- [ ] **Step 5: Log in and verify the happy path**

```bash
cd /tmp && rm -f rj.txt
curl -s -c rj.txt -b rj.txt -L --post301 -o /dev/null \
  -X POST -d "email=admin@randyspaintdrywall.com&password=changeme123" \
  http://localhost/randy_project/login.php

curl -s -b rj.txt "http://localhost/randy_project/api/admin/banner.php"
```

(Substitute your local admin credentials from `config.php` if they differ.)

Expected: `{"text":""}` (nothing saved yet).

```bash
curl -s -b rj.txt -X POST -H "Content-Type: application/json" \
  -d '{"text":"Fall is approaching — book your exterior painting before winter."}' \
  http://localhost/randy_project/api/admin/banner.php
```

Expected: `{"text":"Fall is approaching — book your exterior painting before winter."}`.

```bash
curl -s -b rj.txt "http://localhost/randy_project/api/admin/banner.php"
```

Expected: same text echoed back — confirms it persisted.

- [ ] **Step 6: Verify the length limit**

```bash
curl -s -b rj.txt -w "\n[%{http_code}]\n" -X POST -H "Content-Type: application/json" \
  -d "{\"text\":\"$(printf 'a%.0s' {1..201})\"}" \
  http://localhost/randy_project/api/admin/banner.php
```

Expected: `{"error":"Banner text must be 200 characters or fewer"} [422]`.

- [ ] **Step 7: Reset to blank for the next task**

```bash
curl -s -b rj.txt -X POST -H "Content-Type: application/json" \
  -d '{"text":""}' \
  http://localhost/randy_project/api/admin/banner.php
```

Expected: `{"text":""}`.

- [ ] **Step 8: Commit**

```bash
git add api/admin/banner.php
git commit -m "feat(admin): add sitewide banner text setting endpoint"
```

---

## Task 2: Public rendering with pre-paint dismiss check

**Files:**
- Modify: `includes/header.php`

**Interfaces:**
- Consumes: `setting_get('site_banner_text', ''): ?string`, `e()`, `url()` — all already available in `header.php`.
- Produces: a `[data-site-banner]` element with a `data-banner-text` attribute and a `[data-banner-close]` button, present in the DOM on every page when the setting is non-empty. Task 3 queries `[data-site-banner]`/`[data-banner-close]`; Task 4 styles `.site-banner`/`.site-banner__inner`/`.site-banner__text`/`.site-banner__cta`/`.site-banner__close`.

- [ ] **Step 1: Read the insertion point**

Read `includes/header.php`. Find the closing `</head>` and opening `<body>` tags, immediately followed by `<header class="site-header">`. The banner goes between `<body>` and `<header class="site-header">`.

- [ ] **Step 2: Add the banner block**

In `includes/header.php`, change:

```php
<body>
<header class="site-header">
```

to:

```php
<body>
<?php $banner_text = setting_get('site_banner_text', ''); ?>
<?php if ($banner_text !== ''): ?>
<div class="site-banner" data-site-banner data-banner-text="<?= e($banner_text) ?>">
    <div class="site-banner__inner">
        <span class="site-banner__text"><?= e($banner_text) ?></span>
        <a class="site-banner__cta" href="<?= e(url('book.php')) ?>">Free Estimate</a>
        <button class="site-banner__close" type="button" data-banner-close aria-label="Dismiss">&times;</button>
    </div>
</div>
<script>
(function () {
    try {
        var el = document.currentScript.previousElementSibling;
        if (localStorage.getItem('dismissedBanner') === el.getAttribute('data-banner-text')) {
            el.style.display = 'none';
        }
    } catch (e) { /* localStorage unavailable — fail open, banner stays visible */ }
})();
</script>
<?php endif; ?>
<header class="site-header">
```

The inline `<script>` runs synchronously as the parser reaches it — immediately after the banner `<div>` exists — so a returning visitor who already dismissed this exact text never sees it flash in before disappearing.

- [ ] **Step 3: Verify the file parses**

```bash
php -l includes/header.php
```

Expected: `No syntax errors detected in includes/header.php`

- [ ] **Step 4: Verify the banner is absent when the setting is blank**

```bash
curl -s http://localhost/randy_project/index.php | grep -c 'data-site-banner'
```

Expected: `0` (Task 1 left the setting blank).

- [ ] **Step 5: Verify the banner renders when the setting is set**

```bash
curl -s -b /tmp/rj.txt -X POST -H "Content-Type: application/json" \
  -d '{"text":"Fall is approaching — book your exterior painting before winter."}' \
  http://localhost/randy_project/api/admin/banner.php

curl -s http://localhost/randy_project/index.php | grep -o 'data-banner-text="[^"]*"'
curl -s http://localhost/randy_project/services | grep -c 'data-site-banner'
```

Expected: the first command shows the escaped banner text in `data-banner-text`; the second shows `1` — confirms it's sitewide (renders on `/services`, not just the homepage), since both pages share `includes/header.php`.

- [ ] **Step 6: Verify the CTA link and no unescaped HTML**

```bash
curl -s http://localhost/randy_project/index.php | grep -A2 'data-site-banner'
```

Expected: an `<a class="site-banner__cta" href=".../book">Free Estimate</a>` and the banner text appears as plain escaped text (no raw `<`/`>` from the content itself — there isn't any in this test string, but confirm the structure looks right).

- [ ] **Step 7: Commit**

```bash
git add includes/header.php
git commit -m "feat(site): render sitewide banner with pre-paint dismiss check"
```

---

## Task 3: Dismiss wiring

**Files:**
- Modify: `assets/js/app.js`

**Interfaces:**
- Consumes: `[data-site-banner]`, `[data-banner-close]`, and the `data-banner-text` attribute from Task 2.
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Read the file being modified**

Read `assets/js/app.js`. The `DOMContentLoaded` handler (starting at line 55) already has several independent `[data-*]`-driven blocks (nav toggle, accordions, modals, before/after sliders) — the new block follows the same shape and goes among them.

- [ ] **Step 2: Add the dismiss-wiring block**

Inside the existing `document.addEventListener('DOMContentLoaded', function () { ... })` in `assets/js/app.js`, add (for example, right after the "Mobile nav toggle" block):

```javascript
    // Seasonal banner dismiss
    const banner = document.querySelector('[data-site-banner]');
    if (banner) {
      const closeBtn = banner.querySelector('[data-banner-close]');
      if (closeBtn) {
        closeBtn.addEventListener('click', function () {
          try { localStorage.setItem('dismissedBanner', banner.getAttribute('data-banner-text')); } catch (e) { /* ignore */ }
          banner.style.display = 'none';
        });
      }
    }
```

This only wires the click — Task 2's inline script already handles hiding it on load for a visitor who dismissed it on a previous visit.

- [ ] **Step 3: Verify the file still parses**

```bash
node --check assets/js/app.js
```

Expected: no output (success).

- [ ] **Step 4: Verify dismiss-and-persist in the browser**

The banner text is still set from Task 2's Step 5. Open `http://localhost/randy_project/index.php` in a browser with dev tools open.

Expected:
- The banner is visible with the fall message and a "Free Estimate" button.
- Clicking `×` hides it immediately, no page reload.
- `localStorage.getItem('dismissedBanner')` in the console now equals the banner text.
- Reloading the page: the banner does **not** flash in — it stays hidden throughout, including the very first frame.

- [ ] **Step 5: Verify editing the text re-surfaces it**

```bash
curl -s -b /tmp/rj.txt -X POST -H "Content-Type: application/json" \
  -d '{"text":"Fall is here — schedule your exterior touch-up before winter."}' \
  http://localhost/randy_project/api/admin/banner.php
```

Reload the same browser tab from Step 4.

Expected: the banner reappears with the new text (`dismissedBanner` in `localStorage` no longer matches `data-banner-text`).

- [ ] **Step 6: Commit**

```bash
git add assets/js/app.js
git commit -m "feat(site): wire banner dismiss button"
```

---

## Task 4: Visual style

**Files:**
- Modify: `assets/css/styles.css:336` (immediately before the existing `.site-header` rule)

**Interfaces:**
- Consumes: the markup classes from Task 2 (`.site-banner`, `.site-banner__inner`, `.site-banner__text`, `.site-banner__cta`, `.site-banner__close`) and the existing CSS custom properties `--slate-deep`, `--coral`, `--container`, `--gutter`.
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Add the banner styles**

In `assets/css/styles.css`, immediately before the `.site-header { ... }` rule (currently line 336), add:

```css
.site-banner { background: var(--slate-deep); color: #fff; }
.site-banner__inner { max-width: var(--container); margin: 0 auto; padding: .6rem var(--gutter); display: flex; align-items: center; justify-content: center; gap: 1rem; flex-wrap: wrap; text-align: center; }
.site-banner__text { font-size: .9rem; font-weight: 600; }
.site-banner__cta { background: var(--coral); color: #fff; padding: .4rem 1rem; border-radius: 999px; font-size: .82rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
.site-banner__close { background: none; border: none; color: #fff; opacity: .7; font-size: 1.3rem; line-height: 1; cursor: pointer; padding: 0 .25rem; }
.site-banner__close:hover { opacity: 1; }
```

- [ ] **Step 2: Verify visually in the browser**

The banner text is still set from Task 3. Reload `http://localhost/randy_project/index.php` (clear `localStorage.dismissedBanner` first if it's hidden from earlier testing: run `localStorage.removeItem('dismissedBanner')` in the console, then reload).

Expected:
- Dark blue bar spanning the full width above the header, white centered text.
- A pill-shaped coral "Free Estimate" button next to the text.
- A `×` close button on the right, slightly transparent, fully opaque on hover.
- Resize the window to a narrow (mobile) width: the text, button, and `×` wrap or shrink without any content getting clipped or overlapping.

- [ ] **Step 3: Commit**

```bash
git add assets/css/styles.css
git commit -m "style(site): visual style for the sitewide banner"
```

---

## Task 5: Admin UI

**Files:**
- Modify: `assets/js/admin.js:16-89` (`initOverview`)

**Interfaces:**
- Consumes: `GET`/`POST api/admin/banner.php` from Task 1 (`{"text": string}`); module globals `api`, `toast`.
- Produces: nothing consumed by later tasks — this is the last task.

- [ ] **Step 1: Read the function being modified**

Read `assets/js/admin.js:16-89` (`initOverview`). Note the Blog tab's "External blog URL" form (`assets/js/admin.js`, `initBlog`, the `urlForm` block) is the pattern to copy: a one-field `<form class="app-card">`, a `loadX()` that GETs the current value on init, and a `submit` handler that POSTs and toasts.

- [ ] **Step 2: Add the banner form to the panel markup**

In `initOverview`, change the `panel.innerHTML = ...` assignment (currently lines 21-28) to prepend a new form before the `kpi-section` blocks:

```javascript
    panel.innerHTML =
      '<form class="app-card" data-banner-form>' +
      '<label class="field"><span>Sitewide banner (blank = hidden)</span>' +
      '<input type="text" name="text" maxlength="200" placeholder="e.g. Fall is approaching — book your exterior painting before winter."></label>' +
      '<button class="btn-primary" type="submit">Save banner</button>' +
      '</form>' +
      '<div class="kpi-section"><h2>Live now</h2><div class="kpi-grid">' + LIVE.map((c) => card(c[0], c[1], 'live')).join('') + '</div></div>' +
      '<div class="kpi-section"><h2>Today</h2><div class="kpi-grid">' + TODAY.map((c) => card(c[0], c[1], 'today')).join('') + '</div></div>' +
      '<div class="kpi-section"><h2>This month</h2><div class="kpi-grid">' + MONTH.map((c) => card(c[0], c[1], 'month')).join('') + '</div></div>' +
      '<div class="kpi-section">' +
      '<div class="kpi-section__head"><h2>Daily activity</h2>' +
      '<select data-overview-month aria-label="Month"></select></div>' +
      '<div class="chart-card"><canvas data-overview-chart height="90"></canvas></div></div>';
```

- [ ] **Step 3: Wire the form**

Right after the existing `const monthEl = panel.querySelector('[data-overview-month]');` line, add:

```javascript
    const bannerForm = panel.querySelector('[data-banner-form]');
    async function loadBanner() {
      try { bannerForm.querySelector('[name="text"]').value = (await api.get('api/admin/banner.php')).text || ''; }
      catch (_) { /* ignore */ }
    }
    bannerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = bannerForm.querySelector('button');
      btn.disabled = true;
      try {
        await api.post('api/admin/banner.php', { text: bannerForm.querySelector('[name="text"]').value.trim() });
        toast('Banner saved');
      } catch (err) { toast(err.message, 'error'); }
      finally { btn.disabled = false; }
    });
    loadBanner();
```

- [ ] **Step 4: Verify the file still parses**

```bash
node --check assets/js/admin.js
```

Expected: no output (success).

- [ ] **Step 5: Verify in the browser**

Open `http://localhost/randy_project/admin/`, log in, land on **Overview** (the default tab).

Expected:
- A "Sitewide banner" field appears at the top, prefilled with the text set in Task 3's Step 5 (`Fall is here — schedule your exterior touch-up before winter.`).
- Change the text, click **Save banner** → a "Banner saved" toast appears.
- Open the public homepage in another tab (or clear `localStorage.dismissedBanner` and reload) — the new text shows there.
- Clear the field and click **Save banner** → reload the public homepage → banner is gone.
- Browser console has no errors.

- [ ] **Step 6: Restore a real value for Boss Randy**

Since the field was blanked in Step 5's last check, set it back to the intended live message before finishing:

```bash
curl -s -b /tmp/rj.txt -X POST -H "Content-Type: application/json" \
  -d '{"text":"Fall is approaching — book your exterior painting before winter."}' \
  http://localhost/randy_project/api/admin/banner.php
```

(Or leave it blank if you'd rather have Boss Randy turn it on himself from the admin panel — either is fine; note which one you left it as when reporting completion.)

- [ ] **Step 7: Commit**

```bash
git add assets/js/admin.js
git commit -m "feat(admin): add sitewide banner editor to the Overview tab"
```

---

## Deployment note

Five files change: `api/admin/banner.php` (new), `includes/header.php`, `assets/js/app.js`, `assets/css/styles.css`, `assets/js/admin.js`. Upload all together — a partial upload leaves the admin form calling an endpoint that doesn't exist yet, or the dismiss button with nothing to wire up.

**No database migration required** — the `settings` table already exists on the live host (it backs the blog URL setting). The banner is off by default (`setting_get()` returns `''` for a key that was never set), so deploying this is a no-op for visitors until someone saves text into the admin field.
