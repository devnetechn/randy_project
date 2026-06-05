# Blog Feature Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an admin-managed blog (posts with featured images, draft/published status) that visitors reach only through a teaser section on the About page, with each post on its own `blog-post.php?id=X` page.

**Architecture:** Mirror the existing gallery feature exactly — a `blog_posts` MySQL table, a small JSON API under `api/blog/`, a new tab in the admin SPA (`admin/index.php` + `assets/js/admin.js`), and a shared PHP helper (`includes/blog.php`) used by both the About teaser and the single-post page. Session-based auth (`require_admin_api()`), no CSRF token, matching the gallery's conventions.

**Tech Stack:** Vanilla PHP 8 + PDO/MySQL, plain JS (`window.api` helper), CSS in `assets/css/styles.css`. Served by XAMPP at `http://localhost/randy/`.

---

## Environment notes (read before starting)

- **No git:** This project is not a git repository. Each task ends with a **Checkpoint** instead of a commit. If you prefer version control, run `git init` first (Task 0) and replace each Checkpoint with `git add -A && git commit`.
- **No automated test framework:** There is no PHPUnit or test runner in this codebase. "Verify" steps use real HTTP requests (PowerShell `Invoke-RestMethod`) and browser checks against the running XAMPP server. Make sure **Apache + MySQL are running in the XAMPP control panel** before verifying.
- **Admin login for testing:** Admin API routes need an admin session cookie. Test those through the browser while logged in as the admin (the account seeded by `setup.php` / `config.php`), not via raw curl.
- **Base URL:** All examples assume `http://localhost/randy/`. Adjust if your app sits elsewhere.

---

## File structure

| File | Action | Responsibility |
|---|---|---|
| `sql/tables.sql` | Modify | Add `blog_posts` table definition |
| `setup.php` | Modify | Create `uploads/blog/` folder on setup |
| `includes/blog.php` | Create | Shared blog queries + render helpers (DRY for About + post page) |
| `api/blog/list.php` | Create | List posts (published for public, all for admin) |
| `api/blog/get.php` | Create | Fetch one post by id (for admin edit / detail) |
| `api/blog/save.php` | Create | Create or update a post (multipart, admin) |
| `api/blog/delete.php` | Create | Delete a post + its image (admin) |
| `admin/index.php` | Modify | Add "Blog" tab + panel |
| `assets/js/admin.js` | Modify | Add `initBlog` module |
| `about.php` | Modify | Add blog teaser section before CTA band |
| `blog-post.php` | Create | Single-article public page |
| `assets/css/styles.css` | Modify | Blog card + article + admin-list styles |

---

## Task 0 (optional): Initialize git

Skip this entirely if you don't want version control.

- [ ] **Step 1: Init**

```bash
cd /c/xampp/htdocs/randy
git init
git add -A
git commit -m "chore: snapshot before blog feature"
```

---

## Task 1: Database table + upload folder

**Files:**
- Modify: `sql/tables.sql` (append at end)
- Modify: `setup.php:48-53` (the upload-folder block)

- [ ] **Step 1: Add the `blog_posts` table to `sql/tables.sql`**

Append this to the END of `sql/tables.sql`:

```sql

CREATE TABLE IF NOT EXISTS blog_posts (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(200) NOT NULL,
  excerpt      VARCHAR(300) NULL,
  body         MEDIUMTEXT NOT NULL,
  image        VARCHAR(255) NULL,
  status       ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_blog_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 2: Make `setup.php` create the `uploads/blog/` folder**

In `setup.php`, find this block (around lines 48–53):

```php
    // 4) Make sure the upload folder exists.
    $dir = __DIR__ . '/uploads/gallery';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $steps[] = 'Upload folder ready (uploads/gallery).';
```

Replace it with:

```php
    // 4) Make sure the upload folders exist.
    foreach (['uploads/gallery', 'uploads/blog'] as $rel) {
        $dir = __DIR__ . '/' . $rel;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
    $steps[] = 'Upload folders ready (uploads/gallery, uploads/blog).';
```

- [ ] **Step 3: Run setup to apply**

Open `http://localhost/randy/setup.php` in the browser (XAMPP MySQL must be running).
Expected: "Setup complete!" with "Tables created (or already present)." and "Upload folders ready (uploads/gallery, uploads/blog)."

- [ ] **Step 4: Verify the table exists**

Run in PowerShell:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root randy -e "DESCRIBE blog_posts;"
```

Expected: a table listing showing columns `id, title, excerpt, body, image, status, created_at, updated_at`.
(If your DB name isn't `randy`, use the `name` from `config.php`. If MySQL has a password, add `-p`.)

- [ ] **Step 5: Verify the folder exists**

```powershell
Test-Path C:\xampp\htdocs\randy\uploads\blog
```

Expected: `True`.

- [ ] **Step 6: Checkpoint** — table and folder created.

---

## Task 2: Shared blog helper (`includes/blog.php`)

This file centralizes the queries and rendering so `about.php`, `blog-post.php`, and the API don't duplicate logic.

**Files:**
- Create: `includes/blog.php`

- [ ] **Step 1: Create `includes/blog.php`**

```php
<?php
/** Blog queries + render helpers, shared by the public pages and the API. */

/** Latest published posts, newest first. Pass a limit for the teaser. */
function blog_published(?int $limit = null): array
{
    $sql = 'SELECT id, title, excerpt, body, image, status, created_at
            FROM blog_posts WHERE status = \'published\'
            ORDER BY created_at DESC';
    if ($limit !== null) {
        $st = db()->prepare($sql . ' LIMIT ?');
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();
    } else {
        $st = db()->query($sql);
    }
    return $st->fetchAll();
}

/** A single published post, or null. */
function blog_find_published(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM blog_posts WHERE id = ? AND status = \'published\'');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Public URL for a post's featured image, or null when there's none. */
function blog_image_url(?string $filename): ?string
{
    return $filename ? url('uploads/blog/' . $filename) : null;
}

/** Format a stored DATETIME as a human date, e.g. "June 3, 2026". */
function blog_date(?string $dt): string
{
    if (!$dt) {
        return '';
    }
    $ts = strtotime($dt);
    return $ts ? date('F j, Y', $ts) : '';
}

/** Render a plain-text body as safe HTML paragraphs (blank line = new paragraph). */
function blog_render_body(string $body): string
{
    $blocks = preg_split('/\n\s*\n/', trim($body));
    $html = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        $html .= '<p>' . nl2br(e($block)) . '</p>';
    }
    return $html;
}
```

- [ ] **Step 2: Verify it parses**

```powershell
& "C:\xampp\php\php.exe" -l C:\xampp\htdocs\randy\includes\blog.php
```

Expected: `No syntax errors detected in ...\includes\blog.php`.

- [ ] **Step 3: Checkpoint** — helper added.

---

## Task 3: API — list posts (`api/blog/list.php`)

Public callers get only published posts (no body). A logged-in admin gets all posts including drafts.

**Files:**
- Create: `api/blog/list.php`

- [ ] **Step 1: Create `api/blog/list.php`**

```php
<?php
/** Blog listing. Public: published only. Admin: all. Optional ?limit=N. */
require_once __DIR__ . '/../../includes/app.php';

$limit = isset($_GET['limit']) ? max(1, min(50, (int) $_GET['limit'])) : null;

if (is_admin()) {
    $sql = 'SELECT id, title, excerpt, image, status, created_at FROM blog_posts ORDER BY created_at DESC';
    $st = $limit !== null ? db()->prepare($sql . ' LIMIT ?') : db()->query($sql);
    if ($limit !== null) { $st->bindValue(1, $limit, PDO::PARAM_INT); $st->execute(); }
} else {
    $sql = 'SELECT id, title, excerpt, image, status, created_at FROM blog_posts WHERE status = \'published\' ORDER BY created_at DESC';
    $st = $limit !== null ? db()->prepare($sql . ' LIMIT ?') : db()->query($sql);
    if ($limit !== null) { $st->bindValue(1, $limit, PDO::PARAM_INT); $st->execute(); }
}

$posts = array_map(function ($p) {
    return [
        'id'      => (int) $p['id'],
        'title'   => $p['title'],
        'excerpt' => $p['excerpt'],
        'image'   => $p['image'] ? url('uploads/blog/' . $p['image']) : null,
        'status'  => $p['status'],
        'date'    => $p['created_at'],
    ];
}, $st->fetchAll());

json_out(['posts' => $posts]);
```

- [ ] **Step 2: Lint**

```powershell
& "C:\xampp\php\php.exe" -l C:\xampp\htdocs\randy\api\blog\list.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Verify the public response (logged out)**

```powershell
Invoke-RestMethod http://localhost/randy/api/blog/list.php
```

Expected: an object with a `posts` array (empty `{}`/`@()` if no posts yet — no error).

- [ ] **Step 4: Checkpoint** — list endpoint works.

---

## Task 4: API — get one post (`api/blog/get.php`)

Used by the admin Edit action (admin can load drafts). Public callers only get published posts.

**Files:**
- Create: `api/blog/get.php`

- [ ] **Step 1: Create `api/blog/get.php`**

```php
<?php
/** Single blog post by ?id=. Drafts visible to admins only. */
require_once __DIR__ . '/../../includes/app.php';

$id = (int) ($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM blog_posts WHERE id = ?');
$st->execute([$id]);
$post = $st->fetch();

if (!$post || ($post['status'] === 'draft' && !is_admin())) {
    json_error('Post not found', 404);
}

json_out(['post' => [
    'id'      => (int) $post['id'],
    'title'   => $post['title'],
    'excerpt' => $post['excerpt'],
    'body'    => $post['body'],
    'image'   => $post['image'] ? url('uploads/blog/' . $post['image']) : null,
    'status'  => $post['status'],
    'date'    => $post['created_at'],
]]);
```

- [ ] **Step 2: Lint**

```powershell
& "C:\xampp\php\php.exe" -l C:\xampp\htdocs\randy\api\blog\get.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Verify a missing id returns 404**

```powershell
try { Invoke-RestMethod http://localhost/randy/api/blog/get.php?id=999999 } catch { $_.Exception.Response.StatusCode }
```

Expected: `NotFound` (HTTP 404).

- [ ] **Step 4: Checkpoint** — get endpoint works.

---

## Task 5: API — create/update a post (`api/blog/save.php`)

Multipart POST. Creates a new post, or updates when an `id` field is present. Handles the optional featured image like the gallery upload.

**Files:**
- Create: `api/blog/save.php`

- [ ] **Step 1: Create `api/blog/save.php`**

```php
<?php
/** Admin create/update a blog post (multipart). Fields: id?, title, excerpt, body, status, image?. */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$id      = (int) ($_POST['id'] ?? 0);
$title   = trim($_POST['title'] ?? '');
$excerpt = trim($_POST['excerpt'] ?? '');
$body    = trim($_POST['body'] ?? '');
$status  = $_POST['status'] ?? 'draft';

if ($title === '') { json_error('Title is required', 400); }
if ($body === '')  { json_error('Body is required', 400); }
if (!in_array($status, ['draft', 'published'], true)) { $status = 'draft'; }
$title   = mb_substr($title, 0, 200);
$excerpt = $excerpt !== '' ? mb_substr($excerpt, 0, 300) : null;

// Optional featured image (same rules as the gallery).
$newFilename = null;
if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    if ($file['size'] > 5 * 1024 * 1024) {
        json_error('Image must be 5 MB or smaller', 400);
    }
    $extByMime = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($extByMime[$mime])) {
        json_error('Only JPEG, PNG, or WebP images are allowed', 400);
    }
    $dir = __DIR__ . '/../../uploads/blog';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $newFilename = bin2hex(random_bytes(16)) . $extByMime[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $newFilename)) {
        json_error('Could not save the uploaded file', 500);
    }
}

if ($id > 0) {
    // Update existing post.
    $st = db()->prepare('SELECT * FROM blog_posts WHERE id = ?');
    $st->execute([$id]);
    $existing = $st->fetch();
    if (!$existing) { json_error('Post not found', 404); }

    if ($newFilename !== null) {
        // Replace the old image file.
        $old = __DIR__ . '/../../uploads/blog/' . $existing['image'];
        if ($existing['image'] && is_file($old)) { @unlink($old); }
        $image = $newFilename;
    } else {
        $image = $existing['image'];
    }
    db()->prepare('UPDATE blog_posts SET title = ?, excerpt = ?, body = ?, image = ?, status = ? WHERE id = ?')
        ->execute([$title, $excerpt, $body, $image, $status, $id]);
} else {
    // Create new post.
    db()->prepare('INSERT INTO blog_posts (title, excerpt, body, image, status) VALUES (?, ?, ?, ?, ?)')
        ->execute([$title, $excerpt, $body, $newFilename, $status]);
    $id = (int) db()->lastInsertId();
}

$row = db()->prepare('SELECT * FROM blog_posts WHERE id = ?');
$row->execute([$id]);
$post = $row->fetch();

json_out(['post' => [
    'id'      => (int) $post['id'],
    'title'   => $post['title'],
    'excerpt' => $post['excerpt'],
    'body'    => $post['body'],
    'image'   => $post['image'] ? url('uploads/blog/' . $post['image']) : null,
    'status'  => $post['status'],
    'date'    => $post['created_at'],
]], $id ? 200 : 201);
```

- [ ] **Step 2: Lint**

```powershell
& "C:\xampp\php\php.exe" -l C:\xampp\htdocs\randy\api\blog\save.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Verify non-admin is rejected (logged out)**

```powershell
try { Invoke-RestMethod -Method Post http://localhost/randy/api/blog/save.php -Body @{title='x';body='y'} } catch { $_.Exception.Response.StatusCode }
```

Expected: `Unauthorized` (HTTP 401). (Full create/update is exercised through the admin UI in Task 7.)

- [ ] **Step 4: Checkpoint** — save endpoint guarded and parses.

---

## Task 6: API — delete a post (`api/blog/delete.php`)

**Files:**
- Create: `api/blog/delete.php`

- [ ] **Step 1: Create `api/blog/delete.php`**

```php
<?php
/** Admin delete a blog post. Body: { id } */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$id = (int) ($payload['id'] ?? 0);

$st = db()->prepare('SELECT * FROM blog_posts WHERE id = ?');
$st->execute([$id]);
$post = $st->fetch();
if (!$post) {
    json_error('Post not found', 404);
}

if ($post['image']) {
    $path = __DIR__ . '/../../uploads/blog/' . $post['image'];
    if (is_file($path)) { @unlink($path); }
}
db()->prepare('DELETE FROM blog_posts WHERE id = ?')->execute([$id]);

json_out(['ok' => true]);
```

- [ ] **Step 2: Lint**

```powershell
& "C:\xampp\php\php.exe" -l C:\xampp\htdocs\randy\api\blog\delete.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Verify non-admin is rejected**

```powershell
try { Invoke-RestMethod -Method Post -ContentType 'application/json' -Body '{"id":1}' http://localhost/randy/api/blog/delete.php } catch { $_.Exception.Response.StatusCode }
```

Expected: `Unauthorized` (HTTP 401).

- [ ] **Step 4: Checkpoint** — delete endpoint guarded and parses.

---

## Task 7: Admin panel — Blog tab

Adds a tab to the dashboard and a JS module modeled on `initGallery`, with a create/edit form and a managed list (drafts + published).

**Files:**
- Modify: `admin/index.php:12-22`
- Modify: `assets/js/admin.js` (add `initBlog`, register in `MODULES`)

- [ ] **Step 1: Add the tab + panel in `admin/index.php`**

Find the tabs block (lines 12–22):

```php
    <div class="tabs" data-tabs role="tablist">
        <button class="tab is-active" data-tab="overview" role="tab">Overview</button>
        <button class="tab" data-tab="chat" role="tab">Live Chat</button>
        <button class="tab" data-tab="bookings" role="tab">Bookings</button>
        <button class="tab" data-tab="gallery" role="tab">Gallery</button>
    </div>

    <div data-panel="overview"></div>
    <div data-panel="chat" hidden></div>
    <div data-panel="bookings" hidden></div>
    <div data-panel="gallery" hidden></div>
```

Replace it with (adds Blog button + panel):

```php
    <div class="tabs" data-tabs role="tablist">
        <button class="tab is-active" data-tab="overview" role="tab">Overview</button>
        <button class="tab" data-tab="chat" role="tab">Live Chat</button>
        <button class="tab" data-tab="bookings" role="tab">Bookings</button>
        <button class="tab" data-tab="gallery" role="tab">Gallery</button>
        <button class="tab" data-tab="blog" role="tab">Blog</button>
    </div>

    <div data-panel="overview"></div>
    <div data-panel="chat" hidden></div>
    <div data-panel="bookings" hidden></div>
    <div data-panel="gallery" hidden></div>
    <div data-panel="blog" hidden></div>
```

- [ ] **Step 2: Add the `initBlog` module in `assets/js/admin.js`**

Insert this function immediately AFTER the `initGallery` function closes (after its line `refresh.gallery = load; }` near line 137), BEFORE the `/* ---- Live chat ---- */` comment:

```javascript
  /* ----------  Blog  ---------- */
  function initBlog(panel) {
    panel.innerHTML =
      '<form class="app-card" data-blog-form>' +
      '<input type="hidden" name="id" value="">' +
      '<label class="field"><span>Title</span><input type="text" name="title" maxlength="200" required></label>' +
      '<label class="field"><span>Excerpt (short blurb for cards, optional)</span><input type="text" name="excerpt" maxlength="300"></label>' +
      '<label class="field"><span>Body</span><textarea name="body" rows="8" required></textarea></label>' +
      '<label class="field"><span>Status</span><select name="status"><option value="draft">Draft</option><option value="published">Published</option></select></label>' +
      '<label class="field"><span>Featured image (JPEG/PNG/WebP, ≤5MB — optional)</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>' +
      '<div class="booking-actions"><button class="btn-primary" type="submit" data-blog-submit>Save post</button> ' +
      '<button class="btn-soft" type="button" data-blog-reset>New / clear</button></div>' +
      '</form>' +
      '<div class="blog-admin__list" data-blog-list></div>';
    const form = panel.querySelector('[data-blog-form]');
    const listEl = panel.querySelector('[data-blog-list]');

    function clearForm() {
      form.reset();
      form.querySelector('[name="id"]').value = '';
      form.querySelector('[data-blog-submit]').textContent = 'Save post';
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = form.querySelector('[data-blog-submit]');
      btn.disabled = true;
      try {
        await api.upload('api/blog/save.php', new FormData(form));
        clearForm();
        await load();
        toast('Post saved');
      } catch (err) { toast(err.message, 'error'); }
      finally { btn.disabled = false; }
    });

    form.querySelector('[data-blog-reset]').addEventListener('click', clearForm);

    listEl.addEventListener('click', async (e) => {
      const edit = e.target.closest('[data-edit]');
      const del = e.target.closest('[data-del]');
      if (edit) {
        try {
          const p = (await api.get('api/blog/get.php?id=' + edit.dataset.edit)).post;
          form.querySelector('[name="id"]').value = p.id;
          form.querySelector('[name="title"]').value = p.title || '';
          form.querySelector('[name="excerpt"]').value = p.excerpt || '';
          form.querySelector('[name="body"]').value = p.body || '';
          form.querySelector('[name="status"]').value = p.status || 'draft';
          form.querySelector('[name="image"]').value = '';
          form.querySelector('[data-blog-submit]').textContent = 'Update post';
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (err) { toast(err.message, 'error'); }
        return;
      }
      if (del) {
        if (!confirm('Delete this post?')) return;
        try { await api.post('api/blog/delete.php', { id: +del.dataset.del }); await load(); toast('Post deleted'); }
        catch (err) { toast(err.message, 'error'); }
      }
    });

    async function load() {
      try {
        const posts = (await api.get('api/blog/list.php')).posts || [];
        listEl.innerHTML = posts.length ? posts.map((p) =>
          '<div class="blog-admin__item">' +
          (p.image ? '<img src="' + escapeHtml(p.image) + '" alt="">' : '<div class="blog-admin__noimg">No image</div>') +
          '<div class="blog-admin__meta">' +
          '<span class="badge badge--' + (p.status === 'published' ? 'confirmed' : 'pending') + '">' + escapeHtml(p.status) + '</span>' +
          '<div class="blog-admin__title">' + escapeHtml(p.title) + '</div>' +
          '<div class="blog-admin__date">' + fmt(p.date) + '</div></div>' +
          '<div class="blog-admin__actions"><button class="btn-soft" data-edit="' + p.id + '">Edit</button> ' +
          '<button class="btn-soft" data-del="' + p.id + '">Delete</button></div></div>').join('')
          : '<p style="color:var(--muted)">No posts yet.</p>';
      } catch (e) { toast(e.message, 'error'); }
    }
    load();
    refresh.blog = load;
  }
```

- [ ] **Step 3: Register `initBlog` in the MODULES map**

Find (around line 243):

```javascript
  const MODULES = { overview: initOverview, chat: initChat, bookings: initBookings, gallery: initGallery };
```

Replace with:

```javascript
  const MODULES = { overview: initOverview, chat: initChat, bookings: initBookings, gallery: initGallery, blog: initBlog };
```

- [ ] **Step 4: Verify in the browser (manual)**

1. Log in as admin, open `http://localhost/randy/admin/index.php`.
2. Click the **Blog** tab. Expected: the create form + "No posts yet."
3. Fill Title + Body, set Status = **Published**, optionally pick an image, click **Save post**. Expected: toast "Post saved", and the post appears in the list with a green "published" badge.
4. Create a second post with Status = **Draft**. Expected: it appears with a "pending"-styled "draft" badge.
5. Click **Edit** on a post. Expected: the form fills in, button reads "Update post". Change the title, Save. Expected: list updates.

- [ ] **Step 5: Checkpoint** — admin can create, edit, and delete posts.

---

## Task 8: About page teaser section

Shows the 3 latest **published** posts as cards, linking to the post page. Renders nothing when there are no published posts.

**Files:**
- Modify: `about.php:1-6` (add the require) and `about.php:56` (insert section before the CTA)

- [ ] **Step 1: Include the blog helper at the top of `about.php`**

Find lines 1–6:

```php
<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
$page_title = 'About';
require __DIR__ . '/includes/header.php';
?>
```

Replace with:

```php
<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
require_once __DIR__ . '/includes/blog.php';
$page_title = 'About';
$blog_teasers = blog_published(3);
require __DIR__ . '/includes/header.php';
?>
```

- [ ] **Step 2: Insert the teaser section before the CTA band**

Find this line (line 56):

```php
    <?php mkt_cta_band("Let's talk", 'Trusted by your neighbors.', 'See why Easton-area homeowners keep calling us back. Free estimates, always.'); ?>
```

Insert this BEFORE it:

```php
    <?php if ($blog_teasers): ?>
    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">From our blog</span><h2 style="margin-top:1rem">Tips, updates &amp; project stories</h2></div>
            <div class="blog-grid">
                <?php foreach ($blog_teasers as $post): ?>
                    <a class="blog-card" href="<?= e(url('blog-post.php?id=' . (int) $post['id'])) ?>">
                        <div class="blog-card__img">
                            <?php if ($post['image']): ?>
                                <img src="<?= e(blog_image_url($post['image'])) ?>" alt="">
                            <?php else: ?>
                                <div class="ph ph--warm"><span class="ph__tag">Blog</span></div>
                            <?php endif; ?>
                        </div>
                        <div class="blog-card__body">
                            <div class="blog-card__date"><?= e(blog_date($post['created_at'])) ?></div>
                            <h3 class="blog-card__title"><?= e($post['title']) ?></h3>
                            <?php if ($post['excerpt']): ?><p class="blog-card__excerpt"><?= e($post['excerpt']) ?></p><?php endif; ?>
                            <span class="blog-card__more">Read more<?= svg_arrow() ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

```

- [ ] **Step 3: Lint**

```powershell
& "C:\xampp\php\php.exe" -l C:\xampp\htdocs\randy\about.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 4: Verify in the browser**

Open `http://localhost/randy/about.php`. Expected: a "From our blog" section appears above the bottom CTA, showing the published posts you created in Task 7 (drafts must NOT appear). Each card links to `blog-post.php?id=...`.

- [ ] **Step 5: Checkpoint** — teaser renders published posts only.

---

## Task 9: Single-post page (`blog-post.php`)

**Files:**
- Create: `blog-post.php`

- [ ] **Step 1: Create `blog-post.php`**

```php
<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
require_once __DIR__ . '/includes/blog.php';

$id = (int) ($_GET['id'] ?? 0);
$post = $id ? blog_find_published($id) : null;

if (!$post) {
    http_response_code(404);
    $page_title = 'Post not found';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="mkt">
        <section class="page-hero">
            <div class="page-hero__bg" aria-hidden="true"></div>
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> <a href="<?= e(url('about.php')) ?>">About</a><span>/</span> Not found</nav>
                <h1 style="margin-top:1rem">We couldn&apos;t find that post</h1>
                <p>It may have been removed or isn&apos;t published yet.</p>
                <div style="margin-top:2rem"><a href="<?= e(url('about.php')) ?>" class="btn btn--slate">Back to About<?= svg_arrow() ?></a></div>
            </div>
        </section>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $post['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> <a href="<?= e(url('about.php')) ?>">About</a><span>/</span> <?= e($post['title']) ?></nav>
            <span class="eyebrow">From our blog</span>
            <h1 style="margin-top:1rem"><?= e($post['title']) ?></h1>
            <p><?= e(blog_date($post['created_at'])) ?></p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container blog-article">
            <?php if ($post['image']): ?>
                <div class="blog-article__img"><img src="<?= e(blog_image_url($post['image'])) ?>" alt="<?= e($post['title']) ?>"></div>
            <?php endif; ?>
            <div class="blog-article__body">
                <?= blog_render_body($post['body']) ?>
            </div>
            <div style="margin-top:2rem"><a href="<?= e(url('about.php')) ?>" class="btn btn--slate">&larr; Back to About</a></div>
        </div>
    </section>

    <?php mkt_cta_band("Let's talk", 'Ready to start your project?', 'Free estimates within 25 miles of Easton, PA.'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
```

- [ ] **Step 2: Lint**

```powershell
& "C:\xampp\php\php.exe" -l C:\xampp\htdocs\randy\blog-post.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Verify in the browser**

1. From the About page, click a blog card. Expected: the single-post page shows the title, date, featured image (if any), and body paragraphs, with a "Back to About" link and the CTA band.
2. Visit `http://localhost/randy/blog-post.php?id=999999`. Expected: the "We couldn't find that post" page (and HTTP 404).
3. Visit a **draft** post's URL directly (`blog-post.php?id=<draft id>`) while logged out. Expected: the not-found page (drafts aren't public).

- [ ] **Step 4: Checkpoint** — single-post page works, drafts hidden.

---

## Task 10: Styles

Adds blog teaser-card, article, and admin-list styles, reusing existing tokens.

**Files:**
- Modify: `assets/css/styles.css` (append at end)

- [ ] **Step 1: Append blog styles to `assets/css/styles.css`**

Add at the END of the file:

```css
/* ----------  Blog  ---------- */
.blog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}
.blog-card {
  display: flex;
  flex-direction: column;
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(22, 35, 63, .08), 0 8px 24px rgba(22, 35, 63, .06);
  text-decoration: none;
  color: inherit;
  transition: transform .2s ease, box-shadow .2s ease;
}
.blog-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(22, 35, 63, .12), 0 16px 40px rgba(22, 35, 63, .10);
}
.blog-card__img { aspect-ratio: 16 / 10; overflow: hidden; }
.blog-card__img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.blog-card__img .ph { width: 100%; height: 100%; }
.blog-card__body { padding: 1.25rem 1.4rem 1.5rem; display: flex; flex-direction: column; gap: .5rem; }
.blog-card__date { font-size: .8rem; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
.blog-card__title { font-size: 1.15rem; line-height: 1.3; margin: 0; }
.blog-card__excerpt { color: var(--muted); font-size: .95rem; margin: 0; }
.blog-card__more {
  display: inline-flex; align-items: center; gap: .35rem;
  margin-top: .4rem; font-weight: 700; color: var(--brand, #d8322b); font-size: .9rem;
}
.blog-card__more svg { width: 1em; height: 1em; }

/* Single article */
.blog-article { max-width: 760px; }
.blog-article__img { border-radius: 16px; overflow: hidden; margin-bottom: 2rem; }
.blog-article__img img { width: 100%; height: auto; display: block; }
.blog-article__body p { margin: 0 0 1.2rem; line-height: 1.75; font-size: 1.05rem; }

/* Admin blog list */
.blog-admin__list { display: flex; flex-direction: column; gap: .75rem; margin-top: 1.5rem; }
.blog-admin__item {
  display: grid;
  grid-template-columns: 72px 1fr auto;
  gap: 1rem; align-items: center;
  background: #fff; border-radius: 12px; padding: .75rem 1rem;
  box-shadow: 0 1px 3px rgba(22, 35, 63, .08);
}
.blog-admin__item img { width: 72px; height: 56px; object-fit: cover; border-radius: 8px; }
.blog-admin__noimg {
  width: 72px; height: 56px; border-radius: 8px; background: var(--plaster-2, #eef);
  display: flex; align-items: center; justify-content: center;
  font-size: .7rem; color: var(--muted); text-align: center;
}
.blog-admin__title { font-weight: 700; margin-top: .25rem; }
.blog-admin__date { font-size: .8rem; color: var(--muted); }
.blog-admin__actions { white-space: nowrap; }
```

- [ ] **Step 2: Verify in the browser**

Hard-refresh (Ctrl+F5) the About page and a post page. Expected: cards have rounded corners, hover lift, and consistent spacing; the article body is readable with comfortable line height; the admin Blog list rows are tidy with a thumbnail, badge, title, date, and Edit/Delete buttons.

(The stylesheet is cache-busted by `filemtime` in `header.php`, so the new CSS loads automatically.)

- [ ] **Step 3: Checkpoint** — styles applied.

---

## Task 11: End-to-end verification

- [ ] **Step 1: Full flow walkthrough**

1. Admin → Blog tab → create a **published** post with an image. Confirm toast + list entry.
2. Open About → the post shows in "From our blog". Click it → single page renders correctly.
3. Admin → create a **draft** post. Confirm it does NOT appear on About and its direct URL shows the not-found page when logged out.
4. Admin → Edit the published post, change the title and image → save → confirm About + post page reflect the change, and the old image file is gone from `uploads/blog/`.
5. Admin → Delete a post → confirm it disappears from About and its image file is removed.

- [ ] **Step 2: Confirm the blog is NOT in the header**

Check `includes/header.php` — there should be **no** "Blog" link in `.nav__center` or `.nav__panel`. The blog is reachable only via the About page. (This task adds nothing to the header; just confirm.)

- [ ] **Step 3: Verify image cleanup**

```powershell
Get-ChildItem C:\xampp\htdocs\randy\uploads\blog
```

Expected: only image files for posts that still exist (deleted/replaced images are gone).

- [ ] **Step 4: Final checkpoint** — feature complete.

---

## Self-review notes

- **Spec coverage:** DB table (T1), uploads folder (T1), 4 API endpoints (T3–T6), admin Blog tab (T7), About teaser with 3 latest published (T8), single-post page with 404 for missing/draft (T9), styles reusing tokens (T10), not-in-header confirmed (T11). Shared helper (`includes/blog.php`, T2) keeps About + post page DRY.
- **Naming consistency:** API JSON keys (`id, title, excerpt, body, image, status, date`) are identical across `list/get/save`. The admin JS reads exactly those keys. PHP helpers (`blog_published`, `blog_find_published`, `blog_image_url`, `blog_date`, `blog_render_body`) are referenced consistently in `about.php` and `blog-post.php`.
- **Out of scope (YAGNI):** standalone `blog.php` index, slugs, categories, comments, pagination, rich-text editor.
