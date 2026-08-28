<?php
/** Admin gallery upload (multipart): fields `image`, `caption`, `category`, `featured`. */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    json_error('Image file is required', 400);
}

$file = $_FILES['image'];
if ($file['size'] > 5 * 1024 * 1024) {
    json_error('Image must be 5 MB or smaller', 400);
}

$extByMime = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp'];
$mime = mime_content_type($file['tmp_name']);
if (!isset($extByMime[$mime])) {
    json_error('Only JPEG, PNG, or WebP images are allowed', 400);
}

$category = $_POST['category'] ?? 'other';
$valid = ['interior', 'exterior', 'drywall', 'commercial', 'other'];
if (!in_array($category, $valid, true)) {
    $category = 'other';
}
$caption = trim($_POST['caption'] ?? '');
$caption = $caption !== '' ? mb_substr($caption, 0, 200) : null;

$description = trim($_POST['description'] ?? '');
$description = $description !== '' ? $description : null;

$keywords = trim($_POST['keywords'] ?? '');
$keywords = $keywords !== '' ? mb_substr($keywords, 0, 300) : null;

$featured = !empty($_POST['featured']) ? 1 : 0;

$dir = __DIR__ . '/../../uploads/gallery';
if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
}
$filename = bin2hex(random_bytes(16)) . $extByMime[$mime];
if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
    json_error('Could not save the uploaded file', 500);
}

db()->prepare('INSERT INTO gallery_images (filename, caption, description, keywords, category, featured) VALUES (?, ?, ?, ?, ?, ?)')
    ->execute([$filename, $caption, $description, $keywords, $category, $featured]);
$id = (int) db()->lastInsertId();

json_out([
    'image' => [
        'id'          => $id,
        'url'         => url('uploads/gallery/' . $filename),
        'projectUrl'  => url('project.php?id=' . $id),
        'caption'     => $caption,
        'description' => $description,
        'keywords'    => $keywords,
        'category'    => $category,
        'featured'    => (bool) $featured,
    ],
], 201);
