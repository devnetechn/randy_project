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

$isNew = ($id === 0);

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
]], $isNew ? 201 : 200);
