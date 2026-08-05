# Sitewide Seasonal Banner — Design

**Date:** 2026-08-05
**Status:** Approved

## Problem

Boss Randy wants to promote a seasonal push — "fall is approaching, book exterior painting before winter" — sitewide, and wants to be able to turn it on/off and edit the wording himself without asking a developer.

## Scope

**In:**
- A dismissible announcement bar shown on every public page, above the header.
- Admin-editable message text; blank = hidden.
- Fixed "Free Estimate" CTA to `/book.php` (the site's existing conversion destination, same as the nav's permanent pill) — not admin-configurable, to keep the admin UI to one field.
- Per-visitor dismiss via `localStorage`, keyed to the banner's exact text so editing the message re-shows it.

**Out:**
- Scheduling (auto-show/hide by date range) — Boss Randy toggles it manually by blanking the field.
- Multiple simultaneous banners, or per-page targeting — one sitewide message at a time.
- A/B testing or analytics on banner CTR.

## Backend

No schema change: reuses the existing `settings` key/value table and `setting_get()`/`setting_set()` helpers (`includes/settings.php`), the same mechanism already backing the "External blog URL" setting. New key: `site_banner_text`.

New admin-only endpoint `api/admin/banner.php`, modeled on `api/blog/settings.php`:

```php
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

Unlike `blog/settings.php` (whose `GET` is public, since the About page needs it), `GET` here is admin-only — the only consumer is the admin form. Public rendering reads the setting directly server-side (below), no API round-trip needed.

## Public rendering — `includes/header.php`

Immediately after `<body>`, before `<header class="site-header">`:

```php
<?php $bannerText = setting_get('site_banner_text', ''); ?>
<?php if ($bannerText !== ''): ?>
<div class="site-banner" data-site-banner data-banner-text="<?= e($bannerText) ?>">
    <div class="site-banner__inner">
        <span class="site-banner__text"><?= e($bannerText) ?></span>
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
```

**Why the inline script instead of leaving this to `app.js`:** `app.js` loads at the end of `<body>` (see `includes/footer.php`) and only runs on `DOMContentLoaded`. If the dismiss check lived there, a returning visitor who already closed the banner would see it flash in, then get removed — a layout shift below it (every other page element jumps up). That's a Core Web Vitals (CLS) regression, which is an SEO signal, not just a cosmetic one. Running the check inline, synchronously, immediately after the banner element exists in the DOM (before the browser has parsed/painted anything below it) avoids the flash entirely — same technique commonly used to prevent dark-mode flash-of-wrong-theme.

The banner sits in normal document flow (not `position: fixed`), so it can never become a full-screen or content-covering interstitial — this keeps it clear of Google's intrusive-interstitial penalty, which specifically targets popups/overlays that block main content on mobile right after arriving from search.

## Dismiss wiring — `assets/js/app.js`

A new block inside the existing `DOMContentLoaded` handler (alongside the nav toggle, accordions, etc.), matching that file's existing `[data-*]`-attribute style:

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

This block only ever needs to wire the click — the inline script above already handles the "was this already dismissed" case on load.

## Admin UI — `assets/js/admin.js`, Overview tab

A small card at the top of `initOverview`, matching the shape of the Blog tab's "External blog URL" form:

```javascript
'<form class="app-card" data-banner-form>' +
'<label class="field"><span>Sitewide banner (blank = hidden)</span>' +
'<input type="text" name="text" maxlength="200" placeholder="e.g. Fall is approaching — book your exterior painting before winter."></label>' +
'<button class="btn-primary" type="submit">Save banner</button>' +
'</form>'
```

Loads current value via `GET api/admin/banner.php` on init; `submit` posts `{text}` via `api.post('api/admin/banner.php', {...})` and shows a toast on success/failure, following the exact pattern of the blog URL form in `initBlog`.

## Visual style — `assets/css/styles.css`

```css
.site-banner { background: var(--slate-deep); color: #fff; }
.site-banner__inner { max-width: var(--container); margin: 0 auto; padding: .6rem var(--gutter); display: flex; align-items: center; justify-content: center; gap: 1rem; flex-wrap: wrap; text-align: center; }
.site-banner__text { font-size: .9rem; font-weight: 600; }
.site-banner__cta { background: var(--coral); color: #fff; padding: .4rem 1rem; border-radius: 999px; font-size: .82rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
.site-banner__close { background: none; border: none; color: #fff; opacity: .7; font-size: 1.3rem; line-height: 1; cursor: pointer; padding: 0 .25rem; }
.site-banner__close:hover { opacity: 1; }
```

Dark `--slate-deep` background matches the site's existing dark-emphasis sections; the coral CTA matches every other primary button on the site (nav pill, hero CTAs), so the banner doesn't introduce a new color language.

## Error handling

- `POST` with text over 200 chars → `422 {"error":"Banner text must be 200 characters or fewer"}`.
- `POST`/`GET` from a non-admin or logged-out session → `401` via `require_admin_api()`; the admin form surfaces it through the existing `toast(err.message, 'error')` path.
- Blank text after trim → stored as `''`, banner simply doesn't render (no error, this is the "off" state).
- Banner text is HTML-escaped via `e()` in `header.php` — plain text only, no markup/links inside it (unlike the blog body's `[text](url)` support), so there's no injection surface.
- `localStorage` throwing (private browsing, storage disabled) is caught and fails open in both the inline script and the close handler — worst case, the banner just doesn't stay dismissed for that visitor; it never breaks the page.

## SEO/Core Web Vitals notes

- No layout shift on load, including for returning visitors who already dismissed it (see inline-script rationale above).
- Not a fixed/overlay element — never covers main content, so it can't trigger Google's mobile intrusive-interstitial penalty.
- Plain server-rendered text, present in the initial HTML (not injected after the fact), so it doesn't affect Largest Contentful Paint measurement of the actual page content below it.
- The "Free Estimate" link is a real `<a href>` to `/book.php` — an additional sitewide internal link to the site's primary conversion page, on every page, which is mildly positive for internal linking signal (the same destination the nav pill already links everywhere, so this doesn't dilute anything, just reinforces it).

## Verification

No test framework in this repo; verification is manual, matching every other feature here:

1. Admin saves banner text → banner appears on the homepage and at least one other page (e.g. `/services`), above the header, on every page load.
2. Clicking `×` hides it immediately and it stays hidden on reload (same browser).
3. Admin edits the text to something different → the banner re-appears (even though the visitor previously dismissed the old text).
4. Admin blanks the field and saves → banner disappears from all pages.
5. `curl` a `POST` with a 201-character string → `422` with the length error.
6. `curl` the endpoint logged out → `401`.
7. View source on a page with the banner active → text appears escaped/plain, "Free Estimate" link points to `/book.php`.
8. Chrome DevTools Performance/Lighthouse (or just visual check) confirms no visible flash/jump for a dismissed-then-reloaded page.
