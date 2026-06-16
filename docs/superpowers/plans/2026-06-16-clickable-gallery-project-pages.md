# Clickable Gallery → Project Detail Pages — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make every gallery photo clickable, opening a blog-style project detail page (`project.php?id=N`) with an optional per-photo description editable from the admin dashboard.

**Architecture:** Extend the existing DB-driven gallery (`gallery_images` + `uploads/gallery/`). Add one nullable `description` column, a small data helper (`includes/gallery.php`), a new public page (`project.php`), an admin update endpoint, and wire clickable tiles + sitemap entries. No new tables, no multi-photo grouping.

**Tech Stack:** Vanilla PHP 8 + PDO/MySQL (XAMPP), plain JS (no framework, no build step), `assets/css/styles.css`. **No test framework exists** — verification is via `php -l` (lint), short PHP CLI scripts run with `C:\xampp\php\php.exe`, and browser/HTTP checks against `http://localhost/randy/...`.

**Conventions to follow:**
- Pages bootstrap with `require_once __DIR__ . '/includes/app.php';`.
- Output escaping: `e($value)`. URLs: `url('path.php?id=1')` (strips `.php` for clean links; direct `.php` URLs still work via Apache).
- JSON API: `json_out($data, $status)`, `json_error($msg, $status)`, `read_json()`, `require_admin_api()`.
- JS API client: `api.get(path)`, `api.post(path, obj)` (JSON body), `api.upload(path, FormData)`; globals `escapeHtml`, `toast`, `cap`.
- Commit after each task. Work happens on branch `feature/clickable-gallery` (already created).

---

## File Structure

| File | Responsibility | Action |
|------|----------------|--------|
| `sql/schema.sql` | Fresh-install schema | Modify (add column) |
| `sql/add-gallery-description.php` | Idempotent migration for existing DBs | Create |
| `includes/gallery.php` | Gallery data helpers (`gallery_find`, `gallery_all`) | Create |
| `project.php` | Public per-photo detail page | Create |
| `api/gallery/list.php` | Public/admin listing | Modify (add `description`, `projectUrl`) |
| `api/gallery/upload.php` | Admin create | Modify (accept `description`) |
| `api/gallery/update.php` | Admin edit caption/category/description | Create |
| `assets/js/gallery.js` | Public grid render | Modify (clickable tiles) |
| `assets/css/styles.css` | Tile hover affordance | Modify |
| `assets/js/admin.js` | Admin gallery UI | Modify (description field + inline edit) |
| `sitemap.php` | Dynamic XML sitemap | Modify (list project pages) |

---

## Task 1: Add `description` column to `gallery_images`

**Files:**
- Modify: `sql/schema.sql` (gallery_images CREATE TABLE, ~line 77-85)
- Create: `sql/add-gallery-description.php`

- [ ] **Step 1: Update the schema for fresh installs**

In `sql/schema.sql`, find the `gallery_images` table and add the `description` line after `caption`:

```sql
CREATE TABLE IF NOT EXISTS gallery_images (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  filename    VARCHAR(255) NOT NULL,
  caption     VARCHAR(200) NULL,
  description TEXT NULL,
  category    ENUM('interior','exterior','drywall','commercial','other') NOT NULL DEFAULT 'other',
  sort_order  INT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_gallery_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 2: Create the idempotent migration script**

Create `sql/add-gallery-description.php`:

```php
<?php
/**
 * Add the `description` column to gallery_images (powers project detail pages).
 *
 * Run from the project root:
 *     C:\xampp\php\php.exe sql\add-gallery-description.php
 *
 * Idempotent: only adds the column if it is not already present.
 */

require_once __DIR__ . '/../includes/db.php';

$db = db();
$exists = $db->query("SHOW COLUMNS FROM gallery_images LIKE 'description'")->fetch();
if ($exists) {
    echo "Column `description` already exists — nothing to do.\n";
    exit;
}
$db->exec('ALTER TABLE gallery_images ADD COLUMN description TEXT NULL AFTER caption');
echo "Added `description` column to gallery_images.\n";
```

- [ ] **Step 3: Run the migration**

Run: `C:\xampp\php\php.exe sql\add-gallery-description.php`
Expected: `Added `description` column to gallery_images.`

- [ ] **Step 4: Verify idempotency (run again)**

Run: `C:\xampp\php\php.exe sql\add-gallery-description.php`
Expected: `Column `description` already exists — nothing to do.`

- [ ] **Step 5: Verify the column exists**

Run:
```
C:\xampp\php\php.exe -r "require 'includes/db.php'; var_dump((bool) db()->query(\"SHOW COLUMNS FROM gallery_images LIKE 'description'\")->fetch());"
```
Expected: `bool(true)`

- [ ] **Step 6: Commit**

```bash
git add sql/schema.sql sql/add-gallery-description.php
git commit -m "feat(gallery): add description column + idempotent migration"
```

---

## Task 2: Gallery data helper (`includes/gallery.php`)

**Files:**
- Create: `includes/gallery.php`

- [ ] **Step 1: Create the helper**

Create `includes/gallery.php`:

```php
<?php
/** Gallery data helpers — shared by the project detail page and the sitemap. */

require_once __DIR__ . '/db.php';

/** A single gallery photo row, or null if not found. */
function gallery_find(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM gallery_images WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Every gallery photo, ordered the same way as the public grid. */
function gallery_all(): array
{
    return db()->query('SELECT * FROM gallery_images ORDER BY sort_order ASC, created_at DESC')->fetchAll();
}
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l includes/gallery.php`
Expected: `No syntax errors detected in includes/gallery.php`

- [ ] **Step 3: Verify against the live DB**

Run:
```
C:\xampp\php\php.exe -r "require 'includes/gallery.php'; $a=gallery_all(); echo 'count='.count($a).PHP_EOL; $f=gallery_find((int)$a[0]['id']); echo 'found='.($f?$f['category']:'NULL').PHP_EOL; var_dump(gallery_find(99999999));"
```
Expected: `count=25` (or current total), `found=<a category>`, and `NULL` for the missing id.

- [ ] **Step 4: Commit**

```bash
git add includes/gallery.php
git commit -m "feat(gallery): add gallery_find/gallery_all helpers"
```

---

## Task 3: Public project detail page (`project.php`)

**Files:**
- Create: `project.php`

- [ ] **Step 1: Create the page**

Create `project.php`. It mirrors `blog-post.php` (hero + image + body + CTA, with a 404 branch). The category label fills the hero eyebrow; `description` (if present) renders via the reused `blog_render_body()`, otherwise the caption shows as a single paragraph.

```php
<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
require_once __DIR__ . '/includes/blog.php';     // blog_render_body(), blog_date()
require_once __DIR__ . '/includes/gallery.php';  // gallery_find()

$CATS = [
    'interior'   => 'Interior',
    'exterior'   => 'Exterior',
    'drywall'    => 'Drywall',
    'commercial' => 'Commercial',
    'other'      => 'Our work',
];

$id = (int) ($_GET['id'] ?? 0);
$photo = $id ? gallery_find($id) : null;

if (!$photo) {
    http_response_code(404);
    $page_title = 'Project not found';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="mkt">
        <section class="page-hero">
            <div class="page-hero__bg" aria-hidden="true"></div>
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> <a href="<?= e(url('gallery.php')) ?>">Gallery</a><span>/</span> Not found</nav>
                <h1 style="margin-top:1rem">We couldn&apos;t find that project</h1>
                <p>It may have been removed. Browse the full gallery instead.</p>
                <div style="margin-top:2rem"><a href="<?= e(url('gallery.php')) ?>" class="btn btn--slate">Back to the gallery<?= svg_arrow() ?></a></div>
            </div>
        </section>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$caption  = $photo['caption'] ?: 'Recent project';
$catLabel = $CATS[$photo['category']] ?? 'Our work';
$hasBody  = trim((string) ($photo['description'] ?? '')) !== '';

$page_title       = $caption;
$page_description = $hasBody ? mb_substr(trim($photo['description']), 0, 160) : $caption;
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> <a href="<?= e(url('gallery.php')) ?>">Gallery</a><span>/</span> <?= e($catLabel) ?></nav>
            <span class="eyebrow"><?= e($catLabel) ?></span>
            <h1 style="margin-top:1rem"><?= e($caption) ?></h1>
            <p><?= e(blog_date($photo['created_at'])) ?></p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container blog-article">
            <div class="blog-article__img"><img src="<?= e(url('uploads/gallery/' . $photo['filename'])) ?>" alt="<?= e($caption) ?>"></div>
            <div class="blog-article__body">
                <?php if ($hasBody): ?>
                    <?= blog_render_body($photo['description']) ?>
                <?php else: ?>
                    <p><?= e($caption) ?></p>
                <?php endif; ?>
            </div>
            <div style="margin-top:2rem"><a href="<?= e(url('gallery.php')) ?>" class="btn btn--slate">&larr; Back to the gallery</a></div>
        </div>
    </section>

    <?php mkt_cta_band("Let's talk", 'Ready to start your project?', 'Free estimates across the Lehigh Valley and Bucks County, PA.'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l project.php`
Expected: `No syntax errors detected in project.php`

- [ ] **Step 3: Verify a valid photo renders (HTTP)**

Pick a real id (e.g. 14). Run in PowerShell:
```
(Invoke-WebRequest "http://localhost/randy/project.php?id=14" -UseBasicParsing).StatusCode
```
Expected: `200`. Then confirm the page contains the image + heading:
```
$h = (Invoke-WebRequest "http://localhost/randy/project.php?id=14" -UseBasicParsing).Content
$h -match 'uploads/gallery/' ; $h -match '<h1'
```
Expected: both `True`.

- [ ] **Step 4: Verify a missing photo 404s**

```
try { Invoke-WebRequest "http://localhost/randy/project.php?id=99999999" -UseBasicParsing } catch { $_.Exception.Response.StatusCode.value__ }
```
Expected: `404`.

- [ ] **Step 5: Commit**

```bash
git add project.php
git commit -m "feat(gallery): add project.php detail page"
```

---

## Task 4: Expose `description` + `projectUrl` in the list API

**Files:**
- Modify: `api/gallery/list.php` (the `array_map` block, ~lines 15-23)

- [ ] **Step 1: Add the two fields**

Replace the `array_map` callback in `api/gallery/list.php` with:

```php
$images = array_map(function ($img) {
    return [
        'id'          => (int) $img['id'],
        'url'         => url('uploads/gallery/' . $img['filename']),
        'projectUrl'  => url('project.php?id=' . (int) $img['id']),
        'caption'     => $img['caption'],
        'description' => $img['description'] ?? null,
        'category'    => $img['category'],
        'sortOrder'   => (int) $img['sort_order'],
    ];
}, $st->fetchAll());
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l api/gallery/list.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Verify the JSON includes the new fields**

```
$j = (Invoke-WebRequest "http://localhost/randy/api/gallery/list.php" -UseBasicParsing).Content | ConvertFrom-Json
$j.images[0].projectUrl ; $j.images[0].PSObject.Properties.Name -contains 'description'
```
Expected: a URL like `/randy/project?id=14` and `True`.

- [ ] **Step 4: Commit**

```bash
git add api/gallery/list.php
git commit -m "feat(gallery): expose description + projectUrl in list API"
```

---

## Task 5: Make gallery tiles clickable (grid + CSS)

**Files:**
- Modify: `assets/js/gallery.js` (the `render()` map, lines 21-28)
- Modify: `assets/css/styles.css` (after the `.gallery-item__cap span` rule, ~line 214)

- [ ] **Step 1: Wrap each tile in an anchor**

In `assets/js/gallery.js`, replace the `grid.innerHTML = visible.map(...)` block with:

```js
      grid.innerHTML = visible.map(function (img) {
        return (
          '<a class="gallery-item" href="' + escapeHtml(img.projectUrl) + '">' +
          '<img src="' + escapeHtml(img.url) + '" alt="' + escapeHtml(img.caption || 'Project photo') + '" loading="lazy">' +
          '<span class="gallery-item__cap">' + escapeHtml(img.caption || 'Recent work') +
          ' <span>' + escapeHtml(img.category) + '</span></span>' +
          '<span class="gallery-item__view">View project &rarr;</span>' +
          '</a>'
        );
      }).join('') || '<p class="center" style="color:var(--muted)">No photos in this category yet.</p>';
```

(Note: `<figure>`/`<figcaption>` become `<a>`/`<span>` so the markup is valid inside an anchor; the `gallery-item__cap` class keeps the existing styling.)

- [ ] **Step 2: Add hover affordance CSS**

In `assets/css/styles.css`, immediately after the `.mkt .gallery-item__cap span { ... }` rule (~line 214), add:

```css
.mkt a.gallery-item { display: block; text-decoration: none; color: inherit; cursor: pointer; }
.mkt .gallery-item__view { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; gap: .4rem; background: rgba(15,27,51,.5); color: #fff; font-weight: 700; letter-spacing: .02em; opacity: 0; transition: opacity .3s var(--ease); }
.mkt .gallery-item:hover .gallery-item__view { opacity: 1; }
```

- [ ] **Step 3: Verify in the browser**

Open `http://localhost/randy/gallery.php`. Confirm:
- Each tile is a link; hovering shows the "View project →" overlay.
- Clicking a tile navigates to `project.php?id=N` and the detail page loads.
- The category filter buttons still filter the grid.

- [ ] **Step 4: Commit**

```bash
git add assets/js/gallery.js assets/css/styles.css
git commit -m "feat(gallery): clickable tiles linking to project pages"
```

---

## Task 6: Accept `description` on upload

**Files:**
- Modify: `api/gallery/upload.php` (after the `$caption` block ~line 30-31; the INSERT ~line 42-43; the response ~lines 46-53)

- [ ] **Step 1: Read the description from the POST body**

In `api/gallery/upload.php`, directly after the existing two `$caption` lines, add:

```php
$description = trim($_POST['description'] ?? '');
$description = $description !== '' ? $description : null;
```

- [ ] **Step 2: Store it in the INSERT**

Replace the existing INSERT statement with:

```php
db()->prepare('INSERT INTO gallery_images (filename, caption, description, category) VALUES (?, ?, ?, ?)')
    ->execute([$filename, $caption, $description, $category]);
```

- [ ] **Step 3: Return it in the response**

In the `json_out([...])` `image` array, add a `description` key and a `projectUrl` for consistency:

```php
json_out([
    'image' => [
        'id'          => $id,
        'url'         => url('uploads/gallery/' . $filename),
        'projectUrl'  => url('project.php?id=' . $id),
        'caption'     => $caption,
        'description' => $description,
        'category'    => $category,
    ],
], 201);
```

- [ ] **Step 4: Lint**

Run: `C:\xampp\php\php.exe -l api/gallery/upload.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add api/gallery/upload.php
git commit -m "feat(gallery): store description on upload"
```

---

## Task 7: Admin update endpoint (`api/gallery/update.php`)

**Files:**
- Create: `api/gallery/update.php`

- [ ] **Step 1: Create the endpoint**

Mirrors `api/gallery/delete.php` (POST, `require_admin_api()`, `read_json()`):

```php
<?php
/** Admin gallery update. Body: { id, caption, category, description } */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$id = (int) ($payload['id'] ?? 0);

$st = db()->prepare('SELECT * FROM gallery_images WHERE id = ?');
$st->execute([$id]);
$img = $st->fetch();
if (!$img) {
    json_error('Image not found', 404);
}

$category = $payload['category'] ?? $img['category'];
$valid = ['interior', 'exterior', 'drywall', 'commercial', 'other'];
if (!in_array($category, $valid, true)) {
    $category = 'other';
}

$caption = trim((string) ($payload['caption'] ?? ''));
$caption = $caption !== '' ? mb_substr($caption, 0, 200) : null;

$description = trim((string) ($payload['description'] ?? ''));
$description = $description !== '' ? $description : null;

db()->prepare('UPDATE gallery_images SET caption = ?, description = ?, category = ? WHERE id = ?')
    ->execute([$caption, $description, $category, $id]);

json_out(['image' => [
    'id'          => $id,
    'url'         => url('uploads/gallery/' . $img['filename']),
    'projectUrl'  => url('project.php?id=' . $id),
    'caption'     => $caption,
    'description' => $description,
    'category'    => $category,
]]);
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l api/gallery/update.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Verify auth guard (unauthenticated request is rejected)**

```
try { Invoke-WebRequest "http://localhost/randy/api/gallery/update.php" -Method POST -Body '{"id":14}' -ContentType 'application/json' -UseBasicParsing } catch { $_.Exception.Response.StatusCode.value__ }
```
Expected: `401` (require_login_api) or `403` — i.e. NOT a 200. This confirms the admin guard works.

- [ ] **Step 4: Verify the update logic against the DB (bypassing auth, CLI)**

This confirms the SQL works without needing an admin session. Run:
```
C:\xampp\php\php.exe -r "require 'includes/db.php'; $id=(int)db()->query('SELECT id FROM gallery_images ORDER BY id LIMIT 1')->fetchColumn(); db()->prepare('UPDATE gallery_images SET description=? WHERE id=?')->execute(['__plan_test__',$id]); $v=db()->query('SELECT description FROM gallery_images WHERE id='.$id)->fetchColumn(); echo $v.PHP_EOL; db()->prepare('UPDATE gallery_images SET description=NULL WHERE id=?')->execute([$id]); echo 'reset'.PHP_EOL;"
```
Expected: `__plan_test__` then `reset` (writes then restores NULL).

- [ ] **Step 5: Commit**

```bash
git add api/gallery/update.php
git commit -m "feat(gallery): add admin update endpoint"
```

---

## Task 8: Admin UI — description field + inline edit (`admin.js`)

**Files:**
- Modify: `assets/js/admin.js` (`initGallery`, lines 99-137)

- [ ] **Step 1: Add the description field to the upload form**

In `initGallery`, inside the upload form template string, add a description textarea after the Caption field (between the Caption `<label>` and the Category `<label>`):

```js
      '<label class="field"><span>Description (optional)</span><textarea name="description" rows="3"></textarea></label>' +
```

- [ ] **Step 2: Render an Edit button + inline edit form per item**

Replace the `load()` function's `grid.innerHTML = ...` item template with:

```js
        grid.innerHTML = imgs.length ? imgs.map((img) =>
          '<div class="gallery-admin__item" data-id="' + img.id + '">' +
            '<img src="' + escapeHtml(img.url) + '" alt="">' +
            '<div class="gallery-admin__meta"><div class="cat">' + escapeHtml(img.category) + '</div>' + escapeHtml(img.caption || '') + '</div>' +
            '<div class="gallery-admin__actions">' +
              '<button class="gallery-admin__edit" data-edit="' + img.id + '">Edit</button>' +
              '<button class="gallery-admin__del" data-del="' + img.id + '">Delete</button>' +
            '</div>' +
            '<form class="gallery-admin__form" data-edit-form="' + img.id + '" hidden>' +
              '<label class="field"><span>Caption</span><input type="text" name="caption" maxlength="200" value="' + escapeHtml(img.caption || '') + '"></label>' +
              '<label class="field"><span>Category</span><select name="category">' + CATS.map((c) => '<option value="' + c + '"' + (c === img.category ? ' selected' : '') + '>' + cap(c) + '</option>').join('') + '</select></label>' +
              '<label class="field"><span>Description</span><textarea name="description" rows="3">' + escapeHtml(img.description || '') + '</textarea></label>' +
              '<div><button class="btn-primary" type="submit">Save</button> <button type="button" class="gallery-admin__cancel">Cancel</button></div>' +
            '</form>' +
          '</div>').join('')
          : '<p style="color:var(--muted)">No photos yet.</p>';
```

- [ ] **Step 3: Handle Edit toggle, Cancel, and Delete (click delegation)**

Replace the existing `grid.addEventListener('click', ...)` handler with one that covers all three buttons:

```js
    grid.addEventListener('click', async (e) => {
      const editBtn = e.target.closest('[data-edit]');
      if (editBtn) {
        const f = grid.querySelector('[data-edit-form="' + editBtn.dataset.edit + '"]');
        if (f) f.hidden = !f.hidden;
        return;
      }
      const cancelBtn = e.target.closest('.gallery-admin__cancel');
      if (cancelBtn) {
        const f = cancelBtn.closest('[data-edit-form]');
        if (f) f.hidden = true;
        return;
      }
      const delBtn = e.target.closest('[data-del]');
      if (!delBtn) return;
      if (!confirm('Delete this photo?')) return;
      try { await api.post('api/gallery/delete.php', { id: +delBtn.dataset.del }); await load(); }
      catch (err) { toast(err.message, 'error'); }
    });
```

- [ ] **Step 4: Handle edit-form submit (save)**

Add this submit handler immediately after the click handler from Step 3:

```js
    grid.addEventListener('submit', async (e) => {
      const f = e.target.closest('[data-edit-form]');
      if (!f) return;
      e.preventDefault();
      const id = +f.getAttribute('data-edit-form');
      const body = {
        id,
        caption: f.caption.value.trim(),
        category: f.category.value,
        description: f.description.value.trim(),
      };
      const btn = f.querySelector('button[type="submit"]');
      btn.disabled = true;
      try { await api.post('api/gallery/update.php', body); await load(); toast('Photo updated'); }
      catch (err) { toast(err.message, 'error'); }
      finally { btn.disabled = false; }
    });
```

- [ ] **Step 5: Verify in the browser (admin)**

Log in as admin, open the dashboard, Gallery tab. Confirm:
- The upload form now has a Description textarea; uploading with text persists it (re-open Edit to see it).
- Each item has an Edit button; clicking it reveals a pre-filled form.
- Changing caption/category/description and clicking Save shows a "Photo updated" toast and the grid reflects the change.
- Cancel hides the form; Delete still works.
- Open the matching `project.php?id=N` and confirm the description now renders.

- [ ] **Step 6: Commit**

```bash
git add assets/js/admin.js
git commit -m "feat(gallery): admin description field + inline edit"
```

---

## Task 9: List project pages in the sitemap

**Files:**
- Modify: `sitemap.php` (add require near line 8; add loop after the blog-post loop ~line 43)

- [ ] **Step 1: Require the gallery helper**

In `sitemap.php`, after the existing `require_once __DIR__ . '/includes/blog.php';` line, add:

```php
require_once __DIR__ . '/includes/gallery.php';
```

- [ ] **Step 2: Append a URL per gallery photo**

Immediately after the `foreach (blog_published() as $post) { ... }` loop, add:

```php
// Every gallery photo gets a project detail page.
foreach (gallery_all() as $photo) {
    $ts = strtotime((string) ($photo['created_at'] ?? ''));
    $urls[] = [
        'loc'        => $origin . url('project.php?id=' . (int) $photo['id']),
        'lastmod'    => $ts ? date('Y-m-d', $ts) : $today,
        'changefreq' => 'monthly',
        'priority'   => '0.6',
    ];
}
```

- [ ] **Step 3: Lint**

Run: `C:\xampp\php\php.exe -l sitemap.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Verify project URLs appear**

```
$x = (Invoke-WebRequest "http://localhost/randy/sitemap.php" -UseBasicParsing).Content
([regex]::Matches($x, 'project\?id=')).Count
```
Expected: a number equal to the gallery photo count (e.g. 25).

- [ ] **Step 5: Commit**

```bash
git add sitemap.php
git commit -m "feat(gallery): add project pages to sitemap"
```

---

## Final verification

- [ ] Lint all touched/new PHP:
  `C:\xampp\php\php.exe -l project.php; C:\xampp\php\php.exe -l includes/gallery.php; C:\xampp\php\php.exe -l api/gallery/update.php; C:\xampp\php\php.exe -l api/gallery/upload.php; C:\xampp\php\php.exe -l api/gallery/list.php; C:\xampp\php\php.exe -l sitemap.php`
- [ ] End-to-end: gallery.php tile → project page renders (with and without a description) → 404 on bad id.
- [ ] Admin: upload-with-description and edit-existing both persist and surface on the public page.
- [ ] `sitemap.xml` lists every project page.

---

## Spec coverage check

- Data model (`description` column + migration) → Task 1 ✓
- `includes/gallery.php` helper → Task 2 ✓
- `project.php` detail page (hero, image, body fallback, CTA, 404, SEO) → Task 3 ✓
- List API `description` + `projectUrl` → Task 4 ✓
- Clickable grid + hover affordance → Task 5 ✓
- Upload stores description → Task 6 ✓
- `api/gallery/update.php` → Task 7 ✓
- Admin description field + inline edit → Task 8 ✓
- Sitemap project pages → Task 9 ✓
