# Blog Magazine Layout — Design Spec

**Date:** 2026-08-07
**Project:** Randy's Painting & Drywall (vanilla PHP/MySQL XAMPP app)

## Goal

Redesign the public blog listing page (`blog.php`) into a magazine-style layout:
the latest post becomes a large featured hero, with the rest of the published
posts in a grid below. Scope is the listing page only — `blog-post.php` (the
single-article page) is unchanged.

## Decisions (from brainstorming)

- **Featured hero + grid** pattern: latest published post gets a large hero
  treatment; remaining posts stay in a grid below it. With only 1 published
  post, the hero shows alone (no empty grid section).
- **Visual style: boxed cards**, matching the current site's existing card
  language (white background, rounded corners, soft shadow, hover lift) rather
  than an open/borderless editorial style — keeps the blog consistent with the
  rest of the marketing pages.
- **Typography:** reuse the existing brand fonts (Archivo for headings, Hanken
  Grotesk for body) with a larger scale for the hero headline. No new font
  loaded.
- **Search:** while the visitor is searching, the hero is hidden and the
  featured post becomes a normal filterable card in the grid, so search
  results include every published post, not just the non-featured ones. When
  the search is cleared, the hero reappears.
- **No new data:** no categories, tags, author, or read-time fields are added.
  `blog_posts` schema and `includes/blog.php` are unchanged.

## Architecture

No database or API changes. This is a template (`blog.php`) + stylesheet
(`assets/css/styles.css`) + search script (`assets/js/blog-search.js`) change.

### 1. Template — `blog.php`

`$posts = blog_published();` stays as-is (no new query, no `array_shift`).

- If `$posts` is non-empty, render a `.blog-hero` block from `$posts[0]`:
  featured image (or `.ph` placeholder), date, title, excerpt, "Read more"
  link — the whole card is a single `<a>`, same pattern as `.blog-card`.
- The `.blog-grid` loop iterates over **all** of `$posts` (including index 0)
  exactly as it does today, so no post's markup/data-search payload changes.
  The card generated for `$posts[0]` gets one extra class,
  `blog-card--hero-dup`, added alongside the existing `blog-card` class.
- Both the hero and the grid are wrapped in a new `<div class="blog-list"
  id="blogList">` so the search script has a single element to toggle
  search-mode on.
- `.blog-noresults` stays where it is, inside the same section, after
  `.blog-list`.

This means the featured post's markup exists twice in the DOM (once as hero,
once as a hidden grid card) — intentional, so search can surface it without
extra JS/DOM-moving logic. Only one copy is ever visible at a time.

### 2. Styles — `assets/css/styles.css`

New rules alongside the existing `/* Blog */` section (existing `.blog-grid`,
`.blog-card` rules are untouched):

```
.blog-hero { 2-column grid (≈1.3fr / 1fr) on desktop, boxed card style
             matching .blog-card (white bg, var(--radius), shadow, hover lift);
             stacks to 1 column (image on top) at the existing 900px breakpoint. }
.blog-hero__title { larger scale than .blog-card__title (~1.9rem, clamp for mobile) }
.blog-card--hero-dup { display: none; }  /* hidden outside search mode */
.blog-list.is-searching .blog-hero { display: none; }
.blog-list.is-searching .blog-card--hero-dup { display: flex; }  /* rejoin normal card rules incl. [hidden] */
```

The `.is-searching .blog-card--hero-dup` rule has higher specificity than the
plain `.blog-card--hero-dup { display: none; }` rule, so search mode correctly
overrides the default hidden state, and once revealed the card is subject to
the same `.blog-card[hidden] { display: none; }` matching rule as every other
grid card.

### 3. Search — `assets/js/blog-search.js`

Minimal change to the existing `filter()` function: also toggle an
`is-searching` class on `#blogList` based on whether there are any search
terms (`terms.length > 0`). The existing per-card `data-search` matching logic
is unchanged and already covers `blog-card--hero-dup` since it's just another
`.blog-card` in the `cards` array.

## Data flow

1. Visitor loads `blog.php` → `blog_published()` returns all published posts,
   newest first (unchanged query).
2. `$posts[0]` renders as the hero; the full `$posts` array renders as the
   grid (with the first card pre-hidden via CSS).
3. Visitor types in search → `blog-search.js` marks `#blogList` as
   `is-searching`, hiding the hero and revealing/filtering the duplicate card
   in the grid via the existing match logic.
4. Visitor clears search → `is-searching` removed, hero reappears, duplicate
   card returns to its default hidden state regardless of its last match
   result.

## Error handling / edge cases

- **0 published posts:** existing empty state ("No posts yet — check back
  soon!") is unchanged; no hero or grid markup renders.
- **1 published post:** hero renders alone; the grid contains only the one
  (hidden) duplicate card, so nothing visible appears below the hero.
- **Few posts (2–4):** grid keeps its existing `auto-fit, minmax(260px, 1fr)`
  sizing, so it doesn't look sparse or forced into empty columns.
- **Search with no matches:** existing `.blog-noresults` message and logic are
  unchanged.

## Testing

- Manual, against the local `randy_db` data (currently 1 published post):
  - Publish 3–4 posts via the admin Blog tab; confirm the newest renders as
    the hero and the rest appear in the grid below it.
  - Search for a term that only exists in the hero post's title/excerpt/body →
    hero hides, its card appears (filtered in) in the grid.
  - Clear the search → hero reappears, grid returns to its normal (all posts
    minus the hero-dup) state.
  - Search for a term matching nothing → "No posts match…" message shows.
  - Resize to ≤900px → hero stacks (image above text); grid reflows per
    existing breakpoints.
- Confirm `blog-post.php`, `includes/blog.php`, the About-page teaser, and the
  admin Blog tab are all unaffected (no shared markup/CSS selectors changed).

## Out of scope (YAGNI)

- Redesigning `blog-post.php` (the single-article page).
- Categories, tags, author bylines, read-time estimates — no schema support
  exists and none is being added.
- Pagination / "load more" — still out of scope, as in the original blog spec.
- A secondary "trending/popular" or sidebar module.
