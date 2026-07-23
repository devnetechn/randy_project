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

$keywords = trim((string) ($payload['keywords'] ?? ''));
$keywords = $keywords !== '' ? mb_substr($keywords, 0, 300) : null;

db()->prepare('UPDATE gallery_images SET caption = ?, description = ?, keywords = ?, category = ? WHERE id = ?')
    ->execute([$caption, $description, $keywords, $category, $id]);

json_out(['image' => [
    'id'          => $id,
    'url'         => url('uploads/gallery/' . $img['filename']),
    'projectUrl'  => url('project.php?id=' . $id),
    'caption'     => $caption,
    'description' => $description,
    'keywords'    => $keywords,
    'category'    => $category,
]]);
