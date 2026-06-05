# Blog Feature — Design Spec

**Date:** 2026-06-03
**Project:** Randy's Painting & Drywall (vanilla PHP/MySQL XAMPP app at `C:\xampp\htdocs\randy`)

## Goal

Add an admin-managed blog to the site. Blog posts are created, edited, and
deleted from the existing admin dashboard, each with a featured image. The blog
is **not** added to the header navigation — readers reach it only through a
teaser section on the About page. Each post opens on its own page
(`blog-post.php?id=X`) for SEO, sharing, and a clean About page.

## Decisions (from brainstorming)

- **Dynamic, admin-managed** (DB-backed CRUD), mirroring the existing gallery feature.
- **Separate post page** per article (recommended over inline/modal): real URL,
  shareable, indexable, fits the existing multi-page PHP architecture.
- **Featured image per post**, uploaded via the same flow/rules as the gallery.
- **Not in the header nav** — discoverable only from the About page.
- Added during design: a **draft/published** status and a short **excerpt**
  field for teaser cards.

## Architecture

The app is multi-page PHP with a small JSON API under `api/`, session-based auth
(`require_admin_api()` / `require_admin_page()`, no CSRF token, SameSite=Lax
cookies), and an admin SPA (`admin/index.php` + `assets/js/admin.js`) of tabbed
panels that call the API. The blog reuses every one of these patterns; the
gallery feature is the direct template.

### 1. Database — new table `blog_posts`

Added to `sql/tables.sql` (auto-created on a `setup.php` re-run, which is
idempotent via `CREATE TABLE IF NOT EXISTS`).

```sql
CREATE TABLE IF NOT EXISTS blog_posts (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(200) NOT NULL,
  excerpt      VARCHAR(300) NULL,        -- short blurb for teaser cards
  body         MEDIUMTEXT NOT NULL,       -- full article, plain text paragraphs
  image        VARCHAR(255) NULL,         -- featured photo filename in uploads/blog/
  status       ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_blog_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Uploads

New folder `uploads/blog/`. `setup.php` adds an `mkdir` for it alongside the
existing `uploads/gallery` step. Image validation matches the gallery: JPEG /
PNG / WebP only, ≤ 5 MB, stored under a random hex filename.

### 3. API endpoints — new `api/blog/`

| Endpoint | Method | Auth | Behavior |
|---|---|---|---|
| `api/blog/list.php` | GET | public | List **published** posts ordered newest first. Supports `?limit=N` (used by the About teaser). Excludes `body` from list payload (returns id, title, excerpt, image url, date). |
| `api/blog/get.php` | GET | public | Single post by `?id=`. Returns 404 JSON if missing or `status='draft'`. Includes full `body`. |
| `api/blog/save.php` | POST (multipart) | admin | Create, or update when an `id` field is present. Fields: `title`, `excerpt`, `body`, `status`, optional `image`. On update with a new image, the old image file is replaced/removed. |
| `api/blog/delete.php` | POST (JSON `{id}`) | admin | Delete the post and remove its image file from `uploads/blog/`. |

Endpoints follow the gallery code style: `require_once includes/app.php`, method
check, `require_admin_api()` for admin routes, `json_out()` / `json_error()`.

### 4. Admin panel — new "Blog" tab

- `admin/index.php`: add a `Blog` tab button and a `data-panel="blog"` div.
- `assets/js/admin.js`: add `initBlog(panel)` to the `MODULES` map, modeled on
  `initGallery`. It renders:
  - A form: title, excerpt, body (textarea), status (draft/published) dropdown,
    featured image upload → submits to `save.php` via the `api.upload` helper.
  - A list of **all** posts (drafts + published) with **Edit** and **Delete**.
  - Edit loads the post into the form (carrying its `id`) so a re-save updates it.

### 5. Public view

**a) About page (`about.php`)** — a new section inserted before the closing CTA
band (`mkt_cta_band(...)`):
- Eyebrow + heading (e.g. "From our blog").
- The 3 latest **published** posts as cards: featured image, title, excerpt,
  date — each linking to `blog-post.php?id=X`.
- If there are no published posts, the section renders nothing (no empty band).

**b) New `blog-post.php?id=X`** — single-article page:
- Uses the existing `includes/header.php` / `includes/footer.php` and `.mkt`
  styles (page-hero, breadcrumb "Home / About / [title]", `.section`,
  `.container`).
- Shows featured image, title, date, and the full body rendered as
  HTML-escaped paragraphs (`e()` + paragraph splitting on blank lines / `nl2br`).
- Includes a "← Back to About" link and the shared CTA band.
- If the id is missing, not found, or the post is a draft, shows a friendly
  not-found message (HTTP 404).

### 6. Styles

Add CSS to the existing stylesheet for blog teaser cards and the article layout,
reusing the established design tokens (`--plaster`, `.section`, `.container`,
`.eyebrow`, button classes) so the blog matches the rest of the marketing pages.

## Data flow

1. Admin opens the Blog tab → `initBlog` GETs `api/blog/list.php` (admin sees all)
   and renders the management list.
2. Admin submits the form → `api/blog/save.php` validates, stores the image,
   inserts/updates the row, returns the saved post.
3. A visitor on About → the page server-side queries the 3 latest published
   posts (direct DB read in `about.php`, like `mkt_*` helpers) for the teaser.
4. Visitor clicks a card → `blog-post.php?id=X` server-side loads the published
   post and renders the article.

## Error handling

- Non-admin hitting `save`/`delete` → `403` via `require_admin_api()`.
- Invalid image type/size on upload → `400` with message (gallery rules).
- Missing/draft post on public `get` or `blog-post.php` → `404` + friendly message.
- Empty teaser query → section omitted, no error.

## Testing

- Run `setup.php`, confirm `blog_posts` table and `uploads/blog/` are created.
- Admin: create a draft → not visible on About; publish → appears; edit title →
  reflected; upload image → shows on card and post page; delete → removed and
  image file gone.
- Public: `blog-post.php?id=<draft>` and `?id=<missing>` return the not-found page.
- Verify the blog appears in **no** header nav link.

## Out of scope (YAGNI)

- A standalone `blog.php` index/listing page (foundation is ready to add later).
- Slug-based pretty URLs, categories/tags, comments, pagination, rich-text/Markdown
  editor, author attribution.
