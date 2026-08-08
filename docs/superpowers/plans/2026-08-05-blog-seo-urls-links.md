# Blog SEO: Slug URLs + Internal Links Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace `/blog-post?id=34` with SEO-friendly `/blog/{slug}` URLs (301-redirecting the old links), and let blog post bodies contain `[text](url)` links to service pages.

**Architecture:** A new `slug` column on `blog_posts`, generated from the title and kept unique by `includes/blog.php` helpers shared by the admin save endpoint and the one-time migration. A new `.htaccess` rule maps `/blog/{slug}` to `blog-post.php?slug=…`; the old `?id=` entry point 301-redirects to the canonical slug URL instead of rendering. Blog body text gains a minimal, security-scoped markdown-link parser so admins can hyperlink service-page mentions.

**Tech Stack:** PHP 8.3 (no Composer, no dependencies), vanilla JS, plain CSS, Apache `mod_rewrite`.

**Spec:** `docs/superpowers/specs/2026-08-05-blog-seo-urls-links-design.md`

## Global Constraints

- No Composer, no new dependencies — the live host (Hostinger, PHP 8.3.31) has no `vendor/` directory and none is to be introduced.
- The repo has **no test framework**. Verification is by `curl` against the local XAMPP server, `php -l`/`php -r` one-liners, and browser checks, matching how every other admin feature in this repo is verified.
- All admin API endpoints call `require_once __DIR__ . '/../../includes/app.php';` then `require_admin_api();` before anything else.
- All DB access uses `db()` (PDO, bound parameters) — never string interpolation.
- All user-supplied values rendered into HTML go through the global `e()` (PHP) or `escapeHtml()` (JS) helpers.
- Schema changes for `blog_posts` go through `setup.php`'s idempotent `$colExists(...)` migration pattern (see the existing `faqs` column there), not a standalone one-off script — that is the established mechanism for this exact table on both XAMPP and the live Hostinger host.
- Link hrefs accepted in blog body text are restricted to `/`-relative paths or `https://` URLs — no `javascript:`, `data:`, bare `http://`, or protocol-relative `//` — per the spec's Security section.
- Do not modify the existing Summary/Contact-list Reports panel, gallery, or any other admin module.

## Prerequisites

Local XAMPP must be running with the `randy_db` database. Start Apache and MySQL from the XAMPP Control Panel before Task 1. Verify:

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/randy_project/index.php
```

Expected: `200`

---

## File Structure

| File | Responsibility |
|---|---|
| `sql/tables.sql` (modify) | `blog_posts` gets a `slug` column + unique index for brand-new installs |
| `setup.php` (modify) | Idempotent migration: add `slug`, backfill every existing row with a unique slug, then add the unique index |
| `includes/blog.php` (modify) | `blog_slugify()`, `blog_slug_taken()`, `blog_unique_slug()`, `blog_find_published_by_slug()`; `blog_published()` selects `slug`; inline-link rendering (`blog_render_inline_links()`, `blog_safe_link_href()`) wired into `blog_render_body()` |
| `api/blog/save.php` (modify) | Generate/normalize/unique-ify the slug on create and update |
| `api/blog/get.php` (modify) | Return `slug` for the admin edit form |
| `api/blog/list.php` (modify) | Return `slug` for the admin list view |
| `assets/js/admin.js` (modify) | Admin blog form: slug field, title→slug auto-fill, `/blog/…` preview, list-item link, body-hint text |
| `.htaccess` (modify) | `/blog/{slug}` → `blog-post.php?slug={slug}` |
| `blog-post.php` (modify) | Look up by `slug`; old `?id=` 301-redirects to the canonical slug URL |
| `blog.php`, `about.php`, `sitemap.php` (modify) | Blog links/entries use `/blog/{slug}` instead of `?id=` |

---

## Task 1: Slug schema, migration, and lookup helpers

**Files:**
- Modify: `sql/tables.sql:90-101` (the `blog_posts` table definition)
- Modify: `setup.php:7` (require) and `setup.php:102-108` (insert new migration step after the `faqs` step)
- Modify: `includes/blog.php` (add helpers, update `blog_published()`)

**Interfaces:**
- Consumes: `db()`, `e()` (already available via `includes/app.php`, already required by every consumer of `includes/blog.php`).
- Produces: `blog_slugify(string $text): string`, `blog_slug_taken(string $slug, ?int $excludeId = null): bool`, `blog_unique_slug(string $candidate, ?int $excludeId = null): string`, `blog_find_published_by_slug(string $slug): ?array`. Task 2 calls `blog_unique_slug()`; Task 4 calls `blog_find_published_by_slug()`; `blog_published()` rows now carry a `slug` key that Task 5 reads.

- [ ] **Step 1: Add `slug` to the table definition**

In `sql/tables.sql`, change the `blog_posts` table (currently lines 90-101):

```sql
CREATE TABLE IF NOT EXISTS blog_posts (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(200) NOT NULL,
  slug         VARCHAR(220) NOT NULL DEFAULT '',
  excerpt      VARCHAR(300) NULL,
  body         MEDIUMTEXT NOT NULL,
  image        VARCHAR(255) NULL,
  faqs         JSON NULL,
  status       ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_blog_status (status, created_at),
  UNIQUE INDEX idx_blog_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

This only affects installs that run `tables.sql` from scratch (it's `CREATE TABLE IF NOT EXISTS`, so it never touches your existing local `randy_db`). The migration for existing databases is Step 2.

- [ ] **Step 2: Add the slug helpers to `includes/blog.php`**

Read `includes/blog.php` in full first — note `blog_decode_faqs()` is the pattern every "find one post" function already follows, and `blog_published()` currently lists its SELECT columns explicitly (not `SELECT *`), so it needs `slug` added by name.

In `includes/blog.php`, change the `blog_published()` SELECT (currently `'SELECT id, title, excerpt, body, image, faqs, status, created_at'`) to include `slug`:

```php
    $sql = 'SELECT id, title, slug, excerpt, body, image, faqs, status, created_at
            FROM blog_posts WHERE status = \'published\'
            ORDER BY created_at DESC';
```

Then add these functions after `blog_find_published()` (i.e. right before `blog_decode_faqs()`):

```php
/** Lowercase, hyphenated, URL-safe slug candidate from arbitrary text. */
function blog_slugify(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'post';
}

/** True if some other row already uses this exact slug. */
function blog_slug_taken(string $slug, ?int $excludeId = null): bool
{
    if ($excludeId !== null) {
        $st = db()->prepare('SELECT 1 FROM blog_posts WHERE slug = ? AND id != ? LIMIT 1');
        $st->execute([$slug, $excludeId]);
    } else {
        $st = db()->prepare('SELECT 1 FROM blog_posts WHERE slug = ? LIMIT 1');
        $st->execute([$slug]);
    }
    return (bool) $st->fetchColumn();
}

/** blog_slugify($candidate), with a "-2", "-3", ... suffix appended until unique. */
function blog_unique_slug(string $candidate, ?int $excludeId = null): string
{
    $base = blog_slugify($candidate);
    $slug = $base;
    $n = 2;
    while (blog_slug_taken($slug, $excludeId)) {
        $slug = $base . '-' . $n++;
    }
    return $slug;
}

/** A single published post by slug, or null. */
function blog_find_published_by_slug(string $slug): ?array
{
    $st = db()->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
    $st->execute([$slug]);
    $row = $st->fetch();
    return $row ? blog_decode_faqs($row) : null;
}
```

`blog_find_published(int $id)` already does `SELECT *`, so it picks up the new `slug` column automatically — no change needed there.

- [ ] **Step 3: Verify the file still parses**

```bash
php -l includes/blog.php
```

Expected: `No syntax errors detected in includes/blog.php`

- [ ] **Step 4: Add the migration step to `setup.php`**

In `setup.php`, add the require right after the existing `require_once __DIR__ . '/includes/app.php';` (line 7):

```php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/blog.php';
```

Then, immediately after the existing `faqs` migration block (currently lines 102-108, ending `} else { $steps[] = 'Blog post FAQs already present.'; }`), add a new step:

```php
    // 2f) Blog post slugs for clean /blog/{slug} URLs (SEO-friendly, replaces
    //     the old ?id= links). Backfill runs before the unique index is added,
    //     so every existing row gets a distinct slug derived from its title.
    if (!$colExists('blog_posts', 'slug')) {
        $pdo->exec("ALTER TABLE blog_posts ADD COLUMN slug VARCHAR(220) NOT NULL DEFAULT '' AFTER title");

        $posts = $pdo->query('SELECT id, title FROM blog_posts ORDER BY id ASC')->fetchAll();
        foreach ($posts as $post) {
            $slug = blog_unique_slug($post['title'], (int) $post['id']);
            $pdo->prepare('UPDATE blog_posts SET slug = ? WHERE id = ?')->execute([$slug, $post['id']]);
        }

        $pdo->exec('ALTER TABLE blog_posts ADD UNIQUE INDEX idx_blog_slug (slug)');
        $steps[] = 'Upgraded blog_posts table with unique slugs for ' . count($posts) . ' post(s).';
    } else {
        $steps[] = 'Blog post slugs already present.';
    }
```

`blog_unique_slug()` reads/writes through `db()` (its own lazily-created `PDO` connection, same credentials as `setup.php`'s `$pdo`), while the `ALTER`/`UPDATE` statements around it use `$pdo` directly, matching every other block in this file. Both connect to the same database with autocommit on, so each sees the other's committed rows immediately — no coordination needed.

- [ ] **Step 5: Run the migration locally**

```bash
php -l setup.php
```

Expected: `No syntax errors detected in setup.php`

Open `http://localhost/randy_project/setup.php` in a browser.

Expected: "Setup complete!" with a line reading `Upgraded blog_posts table with unique slugs for N post(s).` (N = however many posts already exist locally; `0` is fine if the table is empty).

- [ ] **Step 6: Verify the column and backfilled values directly**

```bash
php -r '
require "includes/app.php";
$rows = db()->query("SELECT id, title, slug FROM blog_posts ORDER BY id")->fetchAll();
foreach ($rows as $r) { printf("%-4s %-40s %s\n", $r["id"], $r["title"], $r["slug"]); }
'
```

Expected: every row has a non-empty `slug` — lowercase, hyphenated, no leading/trailing hyphens, no two rows sharing the same value.

- [ ] **Step 7: Verify re-running setup.php is a no-op**

Reload `http://localhost/randy_project/setup.php`.

Expected: the line now reads `Blog post slugs already present.` and the slug values from Step 6 are unchanged (re-query to confirm).

- [ ] **Step 8: Commit**

```bash
git add sql/tables.sql setup.php includes/blog.php
git commit -m "feat(blog): add slug column, migration, and slug lookup helpers"
```

---

## Task 2: Slug generation on save

**Files:**
- Modify: `api/blog/save.php`
- Modify: `api/blog/get.php`
- Modify: `api/blog/list.php`

**Interfaces:**
- Consumes: `blog_unique_slug(string $candidate, ?int $excludeId = null): string` from Task 1.
- Produces: `api/blog/save.php`'s JSON response gains a `slug` string field on `post`; so do `api/blog/get.php` and `api/blog/list.php`. Task 3's admin JS reads `p.slug` from all three.

- [ ] **Step 1: Read the file being modified**

Read `api/blog/save.php` in full — note it already requires `includes/app.php` (which does **not** pull in `includes/blog.php`), so the new helper call needs an explicit require.

- [ ] **Step 2: Add the require and slug computation**

In `api/blog/save.php`, add the require right after the existing one (line 3):

```php
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/blog.php';
```

Then, right after the existing `$title = mb_substr($title, 0, 200);` / `$excerpt = ...;` lines (currently lines 20-21), add:

```php
$slugInput = trim($_POST['slug'] ?? '');
```

And right after `$isNew = ($id === 0);` (currently line 60), add:

```php
$slug = blog_unique_slug($slugInput !== '' ? $slugInput : $title, $id > 0 ? $id : null);
```

`blog_unique_slug()` always runs the candidate through `blog_slugify()` first, so this covers both the "admin left the slug field blank, derive it from the title" case and the "admin typed something" case — either way the stored value ends up lowercase, hyphenated, and unique.

- [ ] **Step 3: Add `slug` to the INSERT and UPDATE statements**

Change the two SQL statements (currently lines 77-78 and 81-82):

```php
    db()->prepare('UPDATE blog_posts SET title = ?, slug = ?, excerpt = ?, body = ?, image = ?, status = ?, faqs = ? WHERE id = ?')
        ->execute([$title, $slug, $excerpt, $body, $image, $status, $faqsJson, $id]);
} else {
    // Create new post.
    db()->prepare('INSERT INTO blog_posts (title, slug, excerpt, body, image, status, faqs) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$title, $slug, $excerpt, $body, $newFilename, $status, $faqsJson]);
```

- [ ] **Step 4: Add `slug` to the response**

In the final `json_out([...])` block, add `'slug' => $post['slug'],` right after `'title' => $post['title'],`.

- [ ] **Step 5: Verify the endpoint still parses**

```bash
php -l api/blog/save.php
```

Expected: `No syntax errors detected in api/blog/save.php`

- [ ] **Step 6: Add `slug` to `api/blog/get.php`**

In `api/blog/get.php`, add `'slug' => $post['slug'],` right after `'title' => $post['title'],` in the `json_out([...])` block.

- [ ] **Step 7: Add `slug` to `api/blog/list.php`**

In `api/blog/list.php`, add `slug` to both SQL SELECTs (the `is_admin()` branch and the public branch — both currently `'SELECT id, title, excerpt, image, status, created_at FROM blog_posts ...'`) and to the mapped array, right after `'title' => $p['title'],`.

- [ ] **Step 8: Verify both files still parse**

```bash
php -l api/blog/get.php
php -l api/blog/list.php
```

Expected: `No syntax errors detected` for both.

- [ ] **Step 9: Log in and verify a new post gets an auto-generated slug**

```bash
cd /tmp && rm -f rj.txt
curl -s -c rj.txt -b rj.txt -L --post301 -o /dev/null \
  -X POST -d "email=admin@randyspaintdrywall.com&password=changeme123" \
  http://localhost/randy_project/login.php

curl -s -b rj.txt \
  -F "title=How to Prepare Your Home for Interior Painting" \
  -F "body=Test body." \
  -F "status=draft" \
  http://localhost/randy_project/api/blog/save.php
```

(Substitute your local admin credentials from `config.php` if they differ.)

Expected: JSON response with `"slug":"how-to-prepare-your-home-for-interior-painting"`. Note the returned `id` — call it `$ID1`.

- [ ] **Step 10: Verify a second post with the same title gets a `-2` suffix**

```bash
curl -s -b rj.txt \
  -F "title=How to Prepare Your Home for Interior Painting" \
  -F "body=Second test body." \
  -F "status=draft" \
  http://localhost/randy_project/api/blog/save.php
```

Expected: `"slug":"how-to-prepare-your-home-for-interior-painting-2"`. Note this `id` — call it `$ID2`.

- [ ] **Step 11: Verify a hand-typed slug is respected and normalized**

```bash
curl -s -b rj.txt \
  -F "id=$ID1" \
  -F "title=How to Prepare Your Home for Interior Painting" \
  -F "slug=Custom Slug!! For This One" \
  -F "body=Test body." \
  -F "status=draft" \
  http://localhost/randy_project/api/blog/save.php
```

(Replace `$ID1` with the actual id from Step 9.)

Expected: `"slug":"custom-slug-for-this-one"`.

- [ ] **Step 12: Verify `get.php` and `list.php` return the slug**

```bash
curl -s -b rj.txt "http://localhost/randy_project/api/blog/get.php?id=$ID2" | grep -o '"slug":"[^"]*"'
curl -s -b rj.txt "http://localhost/randy_project/api/blog/list.php" | grep -o '"slug":"[^"]*"' | head -5
```

Expected: `get.php` shows `"slug":"how-to-prepare-your-home-for-interior-painting-2"`; `list.php` shows a `slug` key on each listed post.

- [ ] **Step 13: Clean up the test posts**

```bash
curl -s -b rj.txt -X POST -H "Content-Type: application/json" -d "{\"id\":$ID1}" http://localhost/randy_project/api/blog/delete.php
curl -s -b rj.txt -X POST -H "Content-Type: application/json" -d "{\"id\":$ID2}" http://localhost/randy_project/api/blog/delete.php
```

Expected: both return a success response (check `api/blog/delete.php` for its exact response shape if unsure).

- [ ] **Step 14: Commit**

```bash
git add api/blog/save.php api/blog/get.php api/blog/list.php
git commit -m "feat(blog): generate and persist unique slugs on save"
```

---

## Task 3: Admin UI — slug field

**Files:**
- Modify: `assets/js/admin.js:430-567` (`initBlog`)

**Interfaces:**
- Consumes: `slug` field from `api/blog/save.php`, `api/blog/get.php`, `api/blog/list.php` (Task 2); module globals `api`, `escapeHtml`, `toast`, `fmt`.
- Produces: nothing consumed by later tasks (Task 4-6 don't touch the admin UI).

- [ ] **Step 1: Read the function being modified**

Read `assets/js/admin.js:430-567` (`initBlog`) in full. Note `clearForm()` and the `[data-edit]` click handler are the two places the form gets populated — both need to keep the new slug field in sync.

- [ ] **Step 2: Add the slug field to the form markup**

In the form HTML string inside `initBlog` (currently starting at line 437), insert a new field right after the Title field and before the Excerpt field:

```javascript
      '<label class="field"><span>Title</span><input type="text" name="title" maxlength="200" required></label>' +
      '<label class="field"><span>URL slug</span><input type="text" name="slug" maxlength="220" placeholder="auto-generated-from-title"></label>' +
      '<p style="margin:-.5rem 0 .5rem;color:var(--muted);font-size:.85rem" data-blog-slug-preview></p>' +
      '<label class="field"><span>Excerpt (short blurb for cards, optional)</span><input type="text" name="excerpt" maxlength="300"></label>' +
```

- [ ] **Step 3: Wire up auto-fill and the preview**

Right after the existing `const faqsInput = panel.querySelector('[data-blog-faqs-input]');` line, add:

```javascript
    const slugInput = form.querySelector('[name="slug"]');
    const titleInput = form.querySelector('[name="title"]');
    const slugPreview = panel.querySelector('[data-blog-slug-preview]');

    function slugify(s) {
      return s.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }
    function updateSlugPreview() {
      slugPreview.textContent = slugInput.value ? '/blog/' + slugInput.value : '';
    }
    titleInput.addEventListener('input', () => {
      if (slugInput.dataset.touched === 'true') return;
      slugInput.value = slugify(titleInput.value);
      updateSlugPreview();
    });
    slugInput.addEventListener('input', () => {
      slugInput.dataset.touched = 'true';
      updateSlugPreview();
    });
```

- [ ] **Step 4: Reset the touched flag when the form clears**

In `clearForm()`, after `form.reset();`, add:

```javascript
      slugInput.dataset.touched = 'false';
      updateSlugPreview();
```

- [ ] **Step 5: Populate the slug field when editing, and mark it touched**

In the `[data-edit]` click handler, right after `form.querySelector('[name="excerpt"]').value = p.excerpt || '';`, add:

```javascript
          slugInput.value = p.slug || '';
          slugInput.dataset.touched = 'true';
          updateSlugPreview();
```

Marking it touched on load prevents a title edit from silently rewriting the slug of a post that may already be indexed by search engines — the admin must deliberately clear/retype the slug field to change it.

- [ ] **Step 6: Show the slug in the post list**

In the `load()` function, inside the `posts.map((p) => ...)` template, add a line after `'<div class="blog-admin__date">' + fmt(p.date) + '</div>'`:

```javascript
          '<div class="blog-admin__date">' + fmt(p.date) + '</div>' +
          (p.slug ? '<a href="/blog/' + escapeHtml(p.slug) + '" target="_blank" rel="noopener" style="font-size:.8rem;color:var(--muted)">/blog/' + escapeHtml(p.slug) + '</a>' : '') +
          '</div>' +
```

- [ ] **Step 7: Verify in the browser**

Open `http://localhost/randy_project/admin/`, log in, click **Blog**.

Expected:
- A "URL slug" field appears between Title and Excerpt.
- Typing in Title live-fills the slug field and the `/blog/…` preview line beneath it.
- Manually editing the slug field stops it from following further Title edits.
- Clicking **New / clear** resets both fields and the preview.
- Existing posts in the list show a small `/blog/{slug}` link (opens the post in a new tab once Task 4 is done — for now it will 404 or hit the old page, that's expected until routing lands).
- Clicking **Edit** on an existing post fills the slug field with its stored slug and further Title edits do *not* change it.
- Browser console has no errors.

- [ ] **Step 8: Commit**

```bash
git add assets/js/admin.js
git commit -m "feat(admin): add URL slug field to the blog editor"
```

---

## Task 4: Routing — `/blog/{slug}` and the old-URL redirect

**Files:**
- Modify: `.htaccess`
- Modify: `blog-post.php`

**Interfaces:**
- Consumes: `blog_find_published_by_slug(string $slug): ?array` and `blog_find_published(int $id): ?array` from Task 1; `url(string $path): string` from `includes/helpers.php`.
- Produces: `GET /blog/{slug}` renders the post; `GET blog-post.php?id={id}` 301-redirects to it. Task 5's links point at this route.

- [ ] **Step 1: Add the rewrite rule**

In `.htaccess`, insert a new rule between rule 1 and rule 2:

```apache
# --- 1. Redirect "index.php" (any folder) to its directory root ---
RewriteCond %{THE_REQUEST} \s/+(.*?)index\.php[\s?] [NC]
RewriteRule ^ /%1 [R=301,L]

# --- 1b. Blog post slug URLs: /blog/{slug} -> blog-post.php?slug={slug} ---
RewriteRule ^blog/([a-z0-9-]+)/?$ blog-post.php?slug=$1 [L,QSA]

# --- 2. Redirect "/page.php" to the clean "/page" (skip the API) ---
```

- [ ] **Step 2: Read the file being modified**

Read `blog-post.php` in full. The 404 block (currently lines 9-28) stays exactly as-is; only the lookup logic above it (currently lines 6-7) changes.

- [ ] **Step 3: Replace the lookup logic**

Replace the current lines 6-7:

```php
$id = (int) ($_GET['id'] ?? 0);
$post = $id ? blog_find_published($id) : null;
```

with:

```php
$slug = trim((string) ($_GET['slug'] ?? ''));
$id   = (int) ($_GET['id'] ?? 0);

$post = $slug !== '' ? blog_find_published_by_slug($slug) : null;

// Old "?id=" link with no slug in the path: if it still resolves to a
// published post, send the visitor (and any indexed search-engine link
// equity) to the canonical /blog/{slug} URL instead of rendering here.
if (!$post && $slug === '' && $id > 0) {
    $byId = blog_find_published($id);
    if ($byId) {
        header('Location: ' . url('blog/' . $byId['slug']), true, 301);
        exit;
    }
}
```

Everything from `if (!$post) {` onward (the 404 page) is unchanged.

- [ ] **Step 4: Verify the file still parses**

```bash
php -l blog-post.php
```

Expected: `No syntax errors detected in blog-post.php`

- [ ] **Step 5: Verify the new slug URL renders a post**

Using the admin UI (or `api/blog/save.php` as in Task 2), create and **publish** a post titled "Interior Painting Prep Tips", then:

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost/randy_project/blog/interior-painting-prep-tips
```

Expected: `200`

```bash
curl -s http://localhost/randy_project/blog/interior-painting-prep-tips | grep -o '<h1[^>]*>[^<]*'
```

Expected: the post's title appears in the `<h1>`.

- [ ] **Step 6: Verify the old `?id=` link redirects**

Note the post's id from the admin list or from `api/blog/list.php`, then:

```bash
curl -s -o /dev/null -w "%{http_code} -> %{redirect_url}\n" \
  "http://localhost/randy_project/blog-post.php?id=<ID>"
```

Expected: `301 -> http://localhost/randy_project/blog/interior-painting-prep-tips` (host/base path will match your local setup).

- [ ] **Step 7: Verify a nonexistent id does not redirect**

```bash
curl -s -o /dev/null -w "%{http_code}\n" \
  "http://localhost/randy_project/blog-post.php?id=999999"
```

Expected: `404`

- [ ] **Step 8: Verify a nonexistent slug 404s**

```bash
curl -s -o /dev/null -w "%{http_code}\n" \
  http://localhost/randy_project/blog/this-slug-does-not-exist
```

Expected: `404`

- [ ] **Step 9: Verify a draft post is not reachable by slug**

Save a post with `status=draft`, then request `/blog/{its-slug}`.

Expected: `404` (drafts are excluded by `blog_find_published_by_slug`'s `status = 'published'` clause, same as the old id-based lookup).

- [ ] **Step 10: Clean up test posts created in this task**

Delete the "Interior Painting Prep Tips" post and any draft test post via the admin UI or `api/blog/delete.php`.

- [ ] **Step 11: Commit**

```bash
git add .htaccess blog-post.php
git commit -m "feat(blog): serve posts at /blog/{slug}, redirect old ?id= links"
```

---

## Task 5: Update URL builders

**Files:**
- Modify: `blog.php:33`
- Modify: `about.php:64`
- Modify: `sitemap.php:49`

**Interfaces:**
- Consumes: `$post['slug']` (present on every row returned by `blog_published()` since Task 1) and the `/blog/{slug}` route from Task 4.
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Update the blog listing page**

In `blog.php`, change line 33:

```php
                        <a class="blog-card" href="<?= e(url('blog/' . $post['slug'])) ?>" data-search="<?= e(strtolower($post['title'] . ' ' . ($post['excerpt'] ?? '') . ' ' . strip_tags($post['body']))) ?>">
```

- [ ] **Step 2: Update the About page's blog teaser**

In `about.php`, change line 64:

```php
                    <a class="blog-card" href="<?= e(url('blog/' . $post['slug'])) ?>">
```

- [ ] **Step 3: Update the sitemap**

In `sitemap.php`, change line 49:

```php
        'loc'        => $origin . url('blog/' . $post['slug']),
```

- [ ] **Step 4: Verify all three files still parse**

```bash
php -l blog.php
php -l about.php
php -l sitemap.php
```

Expected: `No syntax errors detected` for all three.

- [ ] **Step 5: Verify the blog listing links to the new URLs**

Publish a test post (or reuse an existing published one), then:

```bash
curl -s http://localhost/randy_project/blog | grep -o 'href="[^"]*blog/[a-z0-9-]*"' | head -5
```

Expected: every link matches `/blog/{slug}` — none say `blog-post.php?id=`.

- [ ] **Step 6: Verify the About page**

```bash
curl -s http://localhost/randy_project/about | grep -o 'href="[^"]*blog/[a-z0-9-]*"'
```

Expected: same as Step 5 (About only shows a few teasers, so this may be a shorter list — it's fine if empty when there are zero published posts).

- [ ] **Step 7: Verify the sitemap**

```bash
curl -s http://localhost/randy_project/sitemap.xml | grep -A1 '<loc>.*blog/' | head -10
```

Expected: `<loc>` entries for blog posts end in `/blog/{slug}`, not `blog-post.php?id=`.

- [ ] **Step 8: Commit**

```bash
git add blog.php about.php sitemap.php
git commit -m "feat(blog): link to /blog/{slug} from the blog list, about page, and sitemap"
```

---

## Task 6: Inline links in blog body text

**Files:**
- Modify: `includes/blog.php` (`blog_render_body()`, new `blog_render_inline_links()`, `blog_safe_link_href()`)
- Modify: `assets/js/admin.js` (body-field hint text)

**Interfaces:**
- Consumes: nothing new from earlier tasks.
- Produces: nothing consumed by later tasks — this is the last task.

- [ ] **Step 1: Read the function being modified**

Read `blog_render_body()` in `includes/blog.php`. It currently calls `e()` directly on the heading text and on each paragraph block before wrapping them in `<h3>`/`<p>` (+ `nl2br()` for paragraphs). Both call sites change to go through a new inline-link-aware escaper instead.

- [ ] **Step 2: Add the inline-link functions**

Add these two functions to `includes/blog.php`, near `blog_render_body()`:

```php
/** Convert "[text](url)" spans into safe <a> tags; everything else is HTML-escaped. */
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

/** Internal relative path ("/…") or an external "https://…" URL — anything else
 *  (javascript:, data:, bare http://, protocol-relative "//") is rejected. */
function blog_safe_link_href(string $href): ?string
{
    if (str_starts_with($href, '/') && !str_starts_with($href, '//')) {
        return $href;
    }
    return preg_match('#^https://#i', $href) ? $href : null;
}
```

- [ ] **Step 3: Wire it into `blog_render_body()`**

In `blog_render_body()`, change:

```php
        if (str_starts_with($block, '## ')) {
            $html .= '<h3>' . e(trim(substr($block, 3))) . '</h3>';
            continue;
        }
        $html .= '<p>' . nl2br(e($block)) . '</p>';
```

to:

```php
        if (str_starts_with($block, '## ')) {
            $html .= '<h3>' . blog_render_inline_links(trim(substr($block, 3))) . '</h3>';
            continue;
        }
        $html .= '<p>' . nl2br(blog_render_inline_links($block)) . '</p>';
```

- [ ] **Step 4: Verify the file still parses**

```bash
php -l includes/blog.php
```

Expected: `No syntax errors detected in includes/blog.php`

- [ ] **Step 5: Unit-check the parser directly**

```bash
php -r '
require "includes/app.php";
require "includes/blog.php";
echo blog_render_body(
    "Check out our [Level 5 Drywall Finish](/level-5-drywall) service.\n\n" .
    "External: [our reviews](https://www.google.com/maps) look great.\n\n" .
    "Bad: [click me](javascript:alert(1)) should stay plain text.\n\n" .
    "Bare http: [old link](http://example.com) should stay plain text.\n\n" .
    "## Section heading with a [link](/services)"
), "\n";
'
```

Expected output includes:
- `<a href="/level-5-drywall">Level 5 Drywall Finish</a>` — no `target`/`rel` (internal).
- `<a href="https://www.google.com/maps" target="_blank" rel="noopener">our reviews</a>` — external.
- The literal text `[click me](javascript:alert(1))`, HTML-escaped (parentheses/brackets as-is, no `<a>` tag) — **not** a working link.
- The literal text `[old link](http://example.com)`, also not a link (bare `http://` is rejected).
- `<h3>Section heading with a <a href="/services">link</a></h3>`.

- [ ] **Step 6: Verify end-to-end on a real post**

Create and publish a post whose body includes:

```
We offer a full [Level 5 Drywall Finish](/level-5-drywall) for glass-smooth walls, plus [Interior Painting](/services) and [Power Washing](/services) for the full package.
```

Visit its `/blog/{slug}` page in a browser.

Expected: "Level 5 Drywall Finish", "Interior Painting", and "Power Washing" are clickable links to the right pages, styled like any other link in the post body (no broken markup, no visible brackets/parentheses).

Delete the test post afterward.

- [ ] **Step 7: Add the body-field hint in the admin editor**

In `assets/js/admin.js`, inside `initBlog`, change the Body field:

```javascript
      '<label class="field"><span>Body</span><textarea name="body" rows="8" required></textarea></label>' +
```

to:

```javascript
      '<label class="field"><span>Body</span><textarea name="body" rows="8" required></textarea>' +
      '<span style="display:block;margin-top:.35rem;color:var(--muted);font-size:.85rem">Links: [Link text](/level-5-drywall) or [Link text](https://example.com)</span></label>' +
```

- [ ] **Step 8: Verify in the browser**

Open the admin Blog panel.

Expected: the hint text appears under the Body field, and the browser console has no errors.

- [ ] **Step 9: Commit**

```bash
git add includes/blog.php assets/js/admin.js
git commit -m "feat(blog): support [text](url) internal/external links in post bodies"
```

---

## Deployment note

Every file in this plan must ship to the live host together — a partial upload leaves `blog-post.php` expecting a `slug` column that isn't there yet, or `.htaccess` routing to a lookup the old `blog-post.php` doesn't support.

Unlike prior blog changes, this one **requires running the migration on the live host**: after uploading, open `https://randyspaintdrywall.com/setup.php` once (restore it from git history first if it was deleted after the last setup run, per its own "you can now delete setup.php for production" note) to add the `slug` column and backfill existing posts, then re-delete it. Skipping this step means every existing published post 404s at its new `/blog/{slug}` URL until the column exists and is backfilled — check the setup output shows `Upgraded blog_posts table with unique slugs for N post(s).` (or `already present` on a second run) before considering the deploy done.
