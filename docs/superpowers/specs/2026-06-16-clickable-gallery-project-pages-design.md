# Clickable Gallery → Project Detail Pages

**Date:** 2026-06-16
**Status:** Approved (design)

## Summary

Make every gallery photo clickable. Clicking a photo opens a blog-style
**project detail page** (`project.php?id=N`) showing the full image, the caption
as a heading, an optional longer write-up, and a call to action. One photo maps
to one page. Detail-page content is auto-built from existing data (caption +
category); an optional `description` field lets the owner add a richer story per
photo from the admin dashboard.

This extends the existing gallery (DB-driven `gallery_images` + `uploads/gallery/`)
without introducing a new "projects" concept or multi-photo grouping.

## Goals

- Each gallery photo links to its own detail page.
- Detail pages work immediately for all existing photos with zero extra content
  (caption + category + CTA).
- Owner can optionally add/edit a description (and caption/category) per photo in
  the admin dashboard — including the existing photos.
- Detail pages are indexed (added to the dynamic sitemap).

## Non-Goals (YAGNI)

- No multi-photo "projects" — one photo = one page.
- No URL slugs or rewrite rules — keep `?id=N`, matching `blog-post.php?id=N`.
- No "related photos" block on the detail page (can be added later).
- No new title field — the existing `caption` serves as the page heading.

## Data Model

Add one nullable column to `gallery_images`:

```sql
ALTER TABLE gallery_images ADD COLUMN description TEXT NULL AFTER caption;
```

- Update `sql/schema.sql` so fresh installs (`setup.php`) get the column.
- Provide a small idempotent migration script
  (`sql/add-gallery-description.php`) that runs the `ALTER TABLE` only if the
  column is missing, so existing local databases pick it up.

Final columns: `id, filename, caption, description, category, sort_order, created_at`.

## Components

### 1. `includes/gallery.php` (new helper)

Mirrors the style of `includes/blog.php`. Provides:

- `gallery_find(int $id): ?array` — fetch a single row by id, or `null`.
- `gallery_all(): array` — all photos ordered `sort_order ASC, created_at DESC`
  (used by the sitemap; the public/admin list API keeps its own queries).

The helper returns raw DB rows; URL building stays with the existing `url()`
helper at the call site.

### 2. `project.php?id=N` (new public page)

Pattern follows `blog-post.php`:

1. `$id = (int)($_GET['id'] ?? 0); $photo = $id ? gallery_find($id) : null;`
2. If not found → `http_response_code(404)` + a friendly "not found" hero with a
   "Back to gallery" button, then exit (same shape as the blog 404).
3. On success, render inside `<div class="mkt">`:
   - **page-hero** — breadcrumb `Home / Gallery / <Category label>`, eyebrow
     "Our work", `H1` = `caption` (fallback: "Recent project"), and a subtle
     date line from `created_at` via `blog_date()`.
   - **Hero image** — `<img>` of `uploads/gallery/<filename>` with `alt` = caption.
   - **Body** — if `description` is non-empty, render with `blog_render_body()`
     (reused: blank line = paragraph, HTML-escaped). If empty, show the caption
     as a single lead paragraph. Show a category badge.
   - **CTA band** — reuse `mkt_cta_band(...)`.
   - **Back link** — "← Back to gallery" → `gallery.php`.
4. SEO: `$page_title = caption` (fallback "Project — Randy's Painting & Drywall");
   `$page_description` = description (trimmed/cut) or caption.

### 3. Gallery grid becomes clickable (`assets/js/gallery.js`)

The render loop currently emits `<figure class="gallery-item">…</figure>`.
Wrap each item in an anchor to its detail page (the list API already returns
`id`):

```js
'<a class="gallery-item" href="' + escapeHtml(img.projectUrl) + '">' +
  '<img …>' +
  '<figcaption class="gallery-item__cap">…<span>…</span></figcaption>' +
'</a>'
```

- `<figure>` becomes `<a>` (or `<a>` wrapping the `<figure>`); keep the
  `gallery-item` class so existing styles apply.
- The link base must respect the app's `url()` base path. gallery.js currently
  uses `img.url` (already absolute via the API). Add an `app`/base-aware way to
  build the project URL — simplest is to have the API also return a
  `projectUrl` (`url('project.php?id=' . id)`) so the JS doesn't need to know the
  base path. **Decision:** add `projectUrl` to `api/gallery/list.php` output and
  use it directly in the grid.
- Category filtering is unchanged.

### 4. CSS (hover affordance)

Add minimal styles for the now-clickable items: pointer cursor, a subtle
hover lift/zoom, and a "View project →" affordance on hover. Reuse existing
gallery tokens/variables; no redesign.

### 5. Admin: add + edit description (and caption/category)

Current admin gallery (`assets/js/admin.js` `initGallery`) supports **upload +
delete only**. Add editing:

- **Upload form** — add a "Description (optional)" `<textarea name="description">`.
  `api/gallery/upload.php` reads and stores it (nullable, no length cap beyond
  TEXT; trim and store `null` if blank).
- **New endpoint `api/gallery/update.php`** — `POST {id, caption, category,
  description}`. Admin-guarded via `require_admin_api()`. Validates category
  against the existing whitelist, trims caption to 200 chars, stores description
  (nullable). Returns the updated row.
- **Admin grid** — add an "Edit" button per item that reveals an inline form
  (caption, category, description) pre-filled from the item; submitting calls
  `update.php` and reloads the grid. Keep the existing "Delete" button.
- The list API (`api/gallery/list.php`) must include `description` so the admin
  edit form can pre-fill it.

### 6. Sitemap (`sitemap.php`)

After the blog-post loop, add a loop over `gallery_all()` appending
`project.php?id=N` URLs (`changefreq` monthly, `priority` 0.6, `lastmod`
from `created_at`). New photos then appear automatically.

## Data Flow

```
Visitor → gallery.php → gallery.js → GET api/gallery/list.php
        → grid of <a href="project.php?id=N"> (+ category filter, client-side)
Visitor clicks a photo → project.php?id=N → gallery_find(N) → render detail page
Owner → admin → Gallery tab:
        upload (image, caption, category, description) → upload.php
        edit (caption, category, description)          → update.php
        delete                                          → delete.php
Crawler → sitemap.xml → static pages + blog posts + every project.php?id=N
```

## Error Handling

- `project.php` with a missing/invalid `id` → 404 page (mirrors blog-post.php).
- `update.php`: 405 on non-POST, 400 on invalid id / unknown category, 404 if the
  id does not exist; admin-guarded like the other gallery endpoints.
- Description rendering always goes through `blog_render_body()` (HTML-escaped),
  so stored text cannot inject markup.

## Testing / Verification

- Migration: run `sql/add-gallery-description.php` twice → column added once,
  second run is a no-op.
- `project.php?id=<valid>` renders image + caption; with a description it shows
  paragraphs; without one it falls back to the caption. `?id=999999` → 404.
- Gallery grid: every tile is a link to the correct `project.php?id=N`; category
  filter still works.
- Admin: upload with a description persists it; editing an existing photo's
  caption/category/description saves and re-renders; delete still works.
- `sitemap.xml` includes a `<url>` for every gallery photo.

## Files Touched

- `sql/schema.sql` — add `description` column.
- `sql/add-gallery-description.php` — new idempotent migration.
- `includes/gallery.php` — new helper (`gallery_find`, `gallery_all`).
- `project.php` — new public detail page.
- `assets/js/gallery.js` — wrap tiles in anchors.
- `assets/css/styles.css` — hover affordance for clickable tiles.
- `api/gallery/list.php` — include `description` + `projectUrl`.
- `api/gallery/upload.php` — accept/store `description`.
- `api/gallery/update.php` — new edit endpoint.
- `assets/js/admin.js` — description field + inline edit.
- `sitemap.php` — list project pages.
