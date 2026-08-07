# Blog SEO: Slug URLs + Internal Links — Design

**Date:** 2026-08-05
**Status:** Approved

## Problem

An SEO review of the blog surfaced two issues:

1. Blog posts are served at `/blog-post?id=34` — an opaque, non-descriptive URL. Search
   engines and readers both do better with `/blog/how-to-prepare-your-home-for-interior-painting`.
2. Blog post bodies are plain escaped text (`blog_render_body()` only recognizes blank-line
   paragraph breaks and a `## heading` prefix) — there is no way to link a phrase like
   "Level 5 Drywall Finish" to `/level-5-drywall`. Most existing posts therefore have zero
   internal links to the service pages they mention.

## Scope

**In:**
- A `slug` column on `blog_posts`, auto-generated from the title, editable in the admin form.
- `/blog/{slug}` as the canonical post URL, with `/blog-post.php?id=N` 301-redirecting to it
  (existing posts are already indexed, so link equity must carry over).
- `[text](url)` markdown-style link syntax inside the blog body, rendered as real `<a>` tags.

**Out:**
- Retroactively adding internal links to existing published posts — separate follow-up task
  once this capability exists.
- Any change to `blog-search.js` — it only filters cards `blog.php` has already rendered by
  title/excerpt/body text; it holds no URLs of its own.
- Rich/WYSIWYG editing, image embeds in body text, or any markdown beyond the single link
  pattern and the existing `## heading` convention.

## Data model

```sql
ALTER TABLE blog_posts ADD COLUMN slug VARCHAR(220) NOT NULL DEFAULT '' AFTER title;
ALTER TABLE blog_posts ADD UNIQUE INDEX idx_blog_slug (slug);
```

A one-off migration script (`sql/add-blog-slug.php`, following the pattern of
`sql/add-gallery-description.php`) backfills every existing row: slugify the title, and on
collision append `-2`, `-3`, etc. Run once, manually, against the live DB — same deployment
model as the other `sql/add-*.php` scripts in this repo.

## Slug generation

New helpers in `includes/blog.php`:

```php
/** Lowercase-hyphenate a title into a URL-safe slug candidate. */
function blog_slugify(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'post';
}

/** blog_slugify() plus a numeric suffix to make it unique, excluding $excludeId. */
function blog_unique_slug(string $title, ?int $excludeId = null): string
{
    $base = blog_slugify($title);
    $slug = $base;
    $n = 2;
    while (blog_slug_taken($slug, $excludeId)) {
        $slug = $base . '-' . $n++;
    }
    return $slug;
}
```

`api/blog/save.php` changes:
- Accepts an optional `slug` POST field (from the admin form).
- If blank, derives it from `title` via `blog_slugify()`.
- Always runs the candidate through the uniqueness check (excluding the row's own id on
  update), appending `-2`/`-3` on collision — this covers both the auto-generated case and an
  admin typing a slug that happens to collide.
- Stores the final slug alongside title/excerpt/body as today.

## Routing

New rule in `.htaccess`, inserted after rule 1 (index redirect) and before rule 2 (the
generic `.php` → clean-URL redirect) so it wins before the general-purpose rules run:

```apache
# --- 1b. Blog post slug URLs ---
RewriteRule ^blog/([a-z0-9-]+)/?$ blog-post.php?slug=$1 [L,QSA]
```

`blog-post.php`:
- Reads `$_GET['slug']` first. New `blog_find_published_by_slug(string $slug): ?array` in
  `includes/blog.php`, mirroring the existing `blog_find_published(int $id)`.
- If there's no `slug` param but there is an `id` param (the old link shape), look the post up
  by id and, if found and published, issue `301 Location: <url('blog/' . $post['slug'])>` and
  exit — no page render. If not found, fall through to today's 404 page.
- If neither `slug` nor `id` resolves a published post, render the existing "We couldn't find
  that post" 404 page unchanged.

## Other URL builders

Switch from `blog-post.php?id=' . $post['id']` to `blog/' . $post['slug']`:

- `blog.php` — blog card links
- `about.php` — blog card links
- `sitemap.php` — canonical `<loc>` per post

## Admin UI

`assets/js/admin.js`, blog form (`initBlog`):
- New "URL slug" text input directly under Title, name `slug`.
- JS auto-fills it from the Title field's `input` event using the same slugify rule
  (lowercase, non-alphanumeric → hyphen, trim), but only while the admin hasn't hand-edited
  the slug field themselves (track via a `data-slug-touched` flag set on the slug input's own
  `input` event) — standard "auto until overridden" behavior.
- A small `/blog/…` preview line under the field, updated live, so the admin sees the real
  URL before saving.
- Small hint text under the Body textarea: `Links: [Link text](/level-5-drywall) or
  [Link text](https://example.com)`.

## Inline links in body text

`includes/blog.php`, `blog_render_body()` gains an inline pass applied to both `## heading`
text and paragraph blocks, before `nl2br()`:

```php
function blog_render_inline_links(string $text): string
{
    $parts = preg_split('/(\[[^\]]+\]\([^)\s]+\))/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    $html = '';
    foreach ($parts as $part) {
        if (preg_match('/^\[([^\]]+)\]\(([^)\s]+)\)$/', $part, $m)) {
            $href = blog_safe_link_href($m[2]);
            if ($href === null) {
                $html .= e($part); // unrecognized/unsafe URL — keep the literal text
                continue;
            }
            $external = !str_starts_with($href, '/');
            $html .= '<a href="' . e($href) . '"' . ($external ? ' target="_blank" rel="noopener"' : '') . '>' . e($m[1]) . '</a>';
        } else {
            $html .= e($part);
        }
    }
    return $html;
}

/** Internal relative path, or https:// external. Anything else (javascript:, data:, bare
 *  http://, protocol-relative //) is rejected. */
function blog_safe_link_href(string $href): ?string
{
    if (str_starts_with($href, '/') && !str_starts_with($href, '//')) {
        return $href;
    }
    return preg_match('#^https://#i', $href) ? $href : null;
}
```

`blog_render_body()` calls `blog_render_inline_links()` where it currently calls `e()` on the
heading text and the paragraph block, then wraps the result in `<h3>`/`<p>` + `nl2br()` as
today.

## Security

Blog body text comes from the admin form (trusted operator, not public input), but it's still
rendered as HTML, so the link parser only ever emits `<a href="…">` with:
- The label always passed through `e()` — never raw.
- The href restricted to `/…` relative paths or `https://` — no `javascript:`, `data:`, bare
  `http://`, or `//host` protocol-relative URLs. A rejected pattern degrades to escaped literal
  text rather than being dropped silently, so a mistyped link is visible/obvious to the admin
  rather than vanishing.

## Error handling

- Slug collision on save: resolved automatically via `blog_unique_slug()` — no user-facing
  error, matches existing "just save it" UX of the blog editor.
- `/blog/unknown-slug` → existing 404 page.
- `/blog-post.php?id=<deleted-or-unpublished-id>` → existing 404 page (no redirect, since
  there's no live slug to send it to).
- Malformed `[text](url)` (e.g. unbalanced brackets, url containing a space) simply doesn't
  match the pattern and renders as literal escaped text — no error, no crash.

## Verification

No test framework in this repo; verification is manual, matching the rest of the admin
features:

1. Create a post titled "How to Prepare Your Home for Interior Painting" — slug field
   auto-fills to `how-to-prepare-your-home-for-interior-painting`; save; visiting
   `/blog/how-to-prepare-your-home-for-interior-painting` renders it.
2. Create a second post with the same title — slug auto-resolves to `…-2`.
3. Hand-edit the slug field before saving — the typed value is respected (post-uniqueness-check).
4. Visit `/blog-post.php?id=<existing-published-id>` — 301s to `/blog/{slug}`.
5. Visit `/blog-post.php?id=<nonexistent-id>` — 404 page, no redirect loop.
6. Body containing `[Level 5 Drywall Finish](/level-5-drywall)` renders a working link on the
   public post page.
7. Body containing `[test](javascript:alert(1))` renders the literal escaped text, not a link.
8. `blog.php`, `about.php`, and `/sitemap.xml` all link to `/blog/{slug}`, not `?id=`.
