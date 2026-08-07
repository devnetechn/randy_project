# Blog Magazine Layout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the public blog listing page (`blog.php`) into a magazine-style layout — the latest published post renders as a large featured hero, the rest render in the existing grid below it — without touching the database, the single-article page, or any other blog code.

**Architecture:** Pure presentation change. `blog.php` renders `$posts[0]` as a new `.blog-hero` block, then renders the *same* `$posts` array (including index 0) through the existing `.blog-grid`/`.blog-card` loop unchanged, marking the index-0 card with an extra `blog-card--hero-dup` class that CSS hides by default. When the visitor searches, `blog-search.js` toggles an `is-searching` class on a new wrapper (`#blogList`) that swaps visibility: hero hides, the duplicate card rejoins the grid and participates in the existing search-filter logic — so search results include the featured post too.

**Tech Stack:** Vanilla PHP 8 + PDO/MySQL (XAMPP), plain JS (no framework, no build step), `assets/css/styles.css`. **No test framework exists** — verification is via `php -l` (lint), short PHP CLI scripts run with `C:\xampp\php\php.exe`, and browser/HTTP checks against `http://localhost/randy_project/...`.

## Global Constraints

- No changes to `blog_posts` schema, `includes/blog.php`, `blog-post.php`, the About-page teaser, or the admin Blog tab — this is `blog.php` + `assets/css/styles.css` + `assets/js/blog-search.js` only (per the spec's scope).
- Reuse existing brand fonts (Archivo / Hanken Grotesk) and design tokens (`--brand`, `--muted`, `--radius`, etc.) already defined in `assets/css/styles.css` — no new fonts, no new CSS variables.
- Hero visual style: boxed card (white background, rounded corners, shadow, hover lift) matching the existing `.blog-card` language — not an open/borderless style.
- 0 posts → existing empty state unchanged. 1 post → hero renders alone, no visible grid.
- Commit after each task. Current branch: `feat/sitewide-seasonal-banner`.

Spec: `docs/superpowers/specs/2026-08-07-blog-magazine-layout-design.md`

---

## File Structure

| File | Responsibility | Action |
|------|----------------|--------|
| `blog.php` | Public blog listing — render hero + grid | Modify |
| `assets/css/styles.css` | Hero styles, search-mode visibility rules | Modify |
| `assets/js/blog-search.js` | Toggle `is-searching` state during live search | Modify |

---

## Task 1: Hero markup + styling (`blog.php` + CSS)

**Files:**
- Modify: `blog.php:24-52` (from `<?php if ($posts): ?>` through the closing `</div>` of `.blog-grid` and the `.blog-noresults` div, inside the `<section class="section section--tight">` block)
- Modify: `assets/css/styles.css` (insert after the existing `.blog-card__more svg` rule, before the `/* Single article */` comment — currently around line 734-736)

**Interfaces:**
- Produces: `.blog-list#blogList` wrapper, `.blog-hero` block, `.blog-card--hero-dup` class on the first grid card — all three names are consumed by Task 2's JS/CSS.

- [ ] **Step 1: Wrap the hero + grid in `#blogList` and add the hero block**

In `blog.php`, replace the `<?php if ($posts): ?> ... <div class="blog-grid" id="blogGrid"> ... </div> <div class="blog-noresults" ...>` section (lines 24-52) with:

```php
            <?php if ($posts): ?>
                <div class="blog-search">
                    <span class="blog-search__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
                    </span>
                    <input type="search" id="blogSearch" class="blog-search__input" placeholder="Search posts&hellip; (e.g. drywall, ceiling)" aria-label="Search blog posts" autocomplete="off">
                </div>
                <div class="blog-list" id="blogList">
                    <?php $hero = $posts[0]; ?>
                    <a class="blog-hero" href="<?= e(url('blog-post.php?id=' . (int) $hero['id'])) ?>">
                        <div class="blog-hero__img">
                            <?php if ($hero['image']): ?>
                                <img src="<?= e(blog_image_url($hero['image'])) ?>" alt="">
                            <?php else: ?>
                                <div class="ph ph--warm"><span class="ph__tag">Blog</span></div>
                            <?php endif; ?>
                        </div>
                        <div class="blog-hero__body">
                            <div class="blog-hero__date"><?= e(blog_date($hero['created_at'])) ?></div>
                            <h2 class="blog-hero__title"><?= e($hero['title']) ?></h2>
                            <?php if ($hero['excerpt']): ?><p class="blog-hero__excerpt"><?= e($hero['excerpt']) ?></p><?php endif; ?>
                            <span class="blog-hero__more">Read more<?= svg_arrow() ?></span>
                        </div>
                    </a>
                    <div class="blog-grid" id="blogGrid">
                        <?php foreach ($posts as $i => $post): ?>
                            <a class="blog-card<?= $i === 0 ? ' blog-card--hero-dup' : '' ?>" href="<?= e(url('blog-post.php?id=' . (int) $post['id'])) ?>" data-search="<?= e(strtolower($post['title'] . ' ' . ($post['excerpt'] ?? '') . ' ' . strip_tags($post['body']))) ?>">
                                <div class="blog-card__img">
                                    <?php if ($post['image']): ?>
                                        <img src="<?= e(blog_image_url($post['image'])) ?>" alt="">
                                    <?php else: ?>
                                        <div class="ph ph--warm"><span class="ph__tag">Blog</span></div>
                                    <?php endif; ?>
                                </div>
                                <div class="blog-card__body">
                                    <div class="blog-card__date"><?= e(blog_date($post['created_at'])) ?></div>
                                    <h2 class="blog-card__title"><?= e($post['title']) ?></h2>
                                    <?php if ($post['excerpt']): ?><p class="blog-card__excerpt"><?= e($post['excerpt']) ?></p><?php endif; ?>
                                    <span class="blog-card__more">Read more<?= svg_arrow() ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="blog-noresults" id="blogNoResults" hidden>
                    <p>No posts match <strong id="blogNoResultsTerm"></strong> — try another keyword.</p>
                </div>
            <?php else: ?>
```

Everything else in the file (the page-hero section above, the `else` empty-state branch, the CTA band, the closing `</div>`) is unchanged.

- [ ] **Step 2: Add the hero + search-mode CSS**

In `assets/css/styles.css`, insert immediately after the `.blog-card__more svg { width: 1em; height: 1em; }` rule and before the `/* Single article */` comment:

```css
.blog-hero {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 0;
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(22, 35, 63, .08), 0 8px 24px rgba(22, 35, 63, .06);
  text-decoration: none;
  color: inherit;
  margin-bottom: 2.5rem;
  transition: transform .2s ease, box-shadow .2s ease;
}
.blog-hero:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(22, 35, 63, .12), 0 16px 40px rgba(22, 35, 63, .10);
}
.blog-hero__img { aspect-ratio: 4 / 3; overflow: hidden; }
.blog-hero__img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.blog-hero__img .ph { width: 100%; height: 100%; }
.blog-hero__body { padding: 2rem 2.25rem; display: flex; flex-direction: column; justify-content: center; gap: .6rem; }
.blog-hero__date { font-size: .8rem; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
.blog-hero__title { font-size: clamp(1.5rem, 1.1rem + 1.6vw, 1.9rem); line-height: 1.15; margin: 0; }
.blog-hero__excerpt { color: var(--muted); font-size: 1.02rem; margin: 0; }
.blog-hero__more {
  display: inline-flex; align-items: center; gap: .35rem;
  margin-top: .4rem; font-weight: 700; color: var(--brand, #d8322b); font-size: .9rem;
}
.blog-hero__more svg { width: 1em; height: 1em; }

.blog-card--hero-dup { display: none; }
.blog-list.is-searching .blog-hero { display: none; }
.blog-list.is-searching .blog-card--hero-dup { display: flex; }

@media (max-width: 900px) {
  .blog-hero { grid-template-columns: 1fr; }
  .blog-hero__img { aspect-ratio: 16 / 10; }
  .blog-hero__body { padding: 1.5rem 1.5rem 1.75rem; }
}
```

- [ ] **Step 3: Lint the PHP**

Run: `C:\xampp\php\php.exe -l blog.php`
Expected: `No syntax errors detected in blog.php`

- [ ] **Step 4: Verify hero markup renders with the current single post**

The local `randy_db` currently has exactly 1 published post (id 1, "asd"), so this also exercises the "1 post → hero only, no visible grid" edge case. Run in PowerShell:

```powershell
$h = (Invoke-WebRequest "http://localhost/randy_project/blog.php" -UseBasicParsing).Content
$h -match 'class="blog-hero"'
$h -match 'id="blogList"'
$h -match 'blog-card--hero-dup'
([regex]::Matches($h, 'class="blog-card')).Count
```

Expected: first three `True`, and the count is `1` (only the hidden hero-dup card — no other posts yet).

- [ ] **Step 5: Verify hero + grid render together with multiple posts**

Insert two temporary published posts via PHP CLI, check the page, then remove them:

```powershell
C:\xampp\php\php.exe -r "require 'includes/db.php'; db()->exec(\"INSERT INTO blog_posts (title, excerpt, body, status, created_at) VALUES ('Plan Test Post A', 'Excerpt A', 'Body A', 'published', NOW() - INTERVAL 1 MINUTE), ('Plan Test Post B', 'Excerpt B', 'Body B', 'published', NOW())\"); echo 'inserted', PHP_EOL;"
$h = (Invoke-WebRequest "http://localhost/randy_project/blog.php" -UseBasicParsing).Content
($h -split 'class="blog-hero"')[1] -match 'Plan Test Post B'
([regex]::Matches($h, 'class="blog-card"')).Count
C:\xampp\php\php.exe -r "require 'includes/db.php'; db()->exec(\"DELETE FROM blog_posts WHERE title IN ('Plan Test Post A','Plan Test Post B')\"); echo 'cleaned', PHP_EOL;"
```

The `blog-card--hero-dup` card's `class` attribute is `class="blog-card blog-card--hero-dup"`, so the exact substring `class="blog-card"` (closing quote immediately after) only ever matches the *non*-hero grid cards.

Expected: the hero shows "Plan Test Post B" (the newest of the three), the second command's match is `True`, the grid-card count is `2` (Plan Test Post A + the original "asd" post — Plan Test Post B is the hero and only appears as the hidden duplicate). Confirm `cleaned` prints last so the DB is back to its original 1-post state.

- [ ] **Step 6: Visual check in the browser**

Open `http://localhost/randy_project/blog.php`. Confirm the hero looks like the approved mockup (boxed card, image left / text right on desktop). Resize the window to ≤900px and confirm the hero stacks (image on top, text below).

- [ ] **Step 7: Commit**

```bash
git add blog.php assets/css/styles.css
git commit -m "feat(blog): magazine-style hero card on the blog listing page"
```

---

## Task 2: Search reveals the hero post in the grid (`blog-search.js`)

**Files:**
- Modify: `assets/js/blog-search.js:1-34` (entire file — small, shown in full below)

**Interfaces:**
- Consumes: `#blogList` (from Task 1), `.blog-card--hero-dup` (from Task 1, already included in `grid.querySelectorAll('.blog-card')` since it carries both classes).

- [ ] **Step 1: Add the `is-searching` toggle to `filter()`**

Replace the full contents of `assets/js/blog-search.js` with:

```js
/* Public blog: live-filter the post cards as the visitor types. */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('blogSearch');
    const list = document.getElementById('blogList');
    const grid = document.getElementById('blogGrid');
    const noResults = document.getElementById('blogNoResults');
    const noResultsTerm = document.getElementById('blogNoResultsTerm');
    if (!input || !grid) return;

    const cards = Array.prototype.slice.call(grid.querySelectorAll('.blog-card'));

    function filter() {
      const query = input.value.trim().toLowerCase();
      const terms = query.split(/\s+/).filter(Boolean);
      let visible = 0;

      if (list) list.classList.toggle('is-searching', terms.length > 0);

      cards.forEach(function (card) {
        const haystack = card.getAttribute('data-search') || '';
        const match = terms.every(function (t) { return haystack.indexOf(t) !== -1; });
        card.hidden = !match;
        if (match) visible++;
      });

      if (noResults) {
        const showEmpty = terms.length > 0 && visible === 0;
        noResults.hidden = !showEmpty;
        if (showEmpty && noResultsTerm) noResultsTerm.textContent = '“' + input.value.trim() + '”';
      }
    }

    input.addEventListener('input', filter);
  });
})();
```

The only functional change from the current file is the `list` lookup and the `is-searching` toggle line — the matching/hiding logic is untouched.

- [ ] **Step 2: Manual browser verification — searching reveals the featured post**

Using the current single post ("asd"), open `http://localhost/randy_project/blog.php`, then in the browser devtools console (or the search box, if the excerpt/body contain a matching word) confirm the interaction end to end:

1. Type a character in the search box that matches the hero post's title/excerpt/body (for post "asd", typing `asd` matches).
2. Confirm the hero disappears and a card for that same post appears in the grid (previously invisible `blog-card--hero-dup`).
3. Clear the search box.
4. Confirm the hero reappears and the grid card for that post disappears again.

- [ ] **Step 3: Manual browser verification — no match still shows the empty state**

Type a nonsense term (e.g. `zzzxxqq`) into the search box. Confirm:
- The hero hides (searching is active).
- No grid cards are visible.
- The "No posts match "zzzxxqq" — try another keyword." message appears.

Clear the box and confirm the hero and the (now-hidden) duplicate card return to their normal resting state.

- [ ] **Step 4: Commit**

```bash
git add assets/js/blog-search.js
git commit -m "feat(blog): include the featured post in live search results"
```

---

## Final verification

- [ ] Lint: `C:\xampp\php\php.exe -l blog.php`
- [ ] `http://localhost/randy_project/blog.php` loads with no console errors (check devtools console).
- [ ] With 1 post: hero only, no grid cards visible, no console errors.
- [ ] With 3+ posts (temporarily inserted, then cleaned up per Task 1 Step 5): hero shows the newest, grid shows the rest.
- [ ] Search matches the featured post: hero hides, its card appears filtered into the grid; clearing restores the hero.
- [ ] Search with no matches: hero hides, "no posts match" message shows.
- [ ] ≤900px viewport: hero stacks (image above text).
- [ ] `blog-post.php`, the About-page teaser, and the admin Blog tab are visually unchanged (spot check each).
- [ ] `randy_db.blog_posts` has exactly the same rows as before starting (no leftover test posts).

---

## Spec coverage check

- Featured hero + grid pattern, hero-alone when 1 post → Task 1 ✓
- Boxed card visual style matching existing `.blog-card` → Task 1 Step 2 ✓
- Reuse of existing brand fonts/tokens, no new fonts → Task 1 Step 2 ✓
- Hero stacks on mobile (existing 900px breakpoint) → Task 1 Step 2 (`@media (max-width: 900px)`) ✓
- Search hides hero and includes the featured post in filterable results → Task 2 ✓
- No DB/schema/API changes, `blog-post.php`/`includes/blog.php`/admin untouched → enforced by Global Constraints + Final verification ✓
- 0-posts empty state unchanged → untouched `else` branch, confirmed by Task 1 Step 1 diff scope ✓
