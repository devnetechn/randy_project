<?php
/** Admin create/update a review. JSON body: { id?, author, rating, body, meta? }. */
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/reviews.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$id     = (int) ($payload['id'] ?? 0);
$author = trim((string) ($payload['author'] ?? ''));
$rating = (int) ($payload['rating'] ?? 5);
$body   = trim((string) ($payload['body'] ?? ''));
$meta   = trim((string) ($payload['meta'] ?? ''));

if ($author === '') { json_error('Author name is required', 400); }
if ($body === '')   { json_error('Review text is required', 400); }
$rating = max(1, min(5, $rating));
$author = mb_substr($author, 0, 120);
$meta   = $meta !== '' ? mb_substr($meta, 0, 160) : null;

$isNew = ($id === 0);
if ($id > 0) {
    if (!reviews_find($id)) { json_error('Review not found', 404); }
    db()->prepare('UPDATE reviews SET author = ?, rating = ?, body = ?, meta = ? WHERE id = ?')
        ->execute([$author, $rating, $body, $meta, $id]);
} else {
    db()->prepare('INSERT INTO reviews (author, rating, body, meta) VALUES (?, ?, ?, ?)')
        ->execute([$author, $rating, $body, $meta]);
    $id = (int) db()->lastInsertId();
}

$r = reviews_find($id);
json_out(['review' => [
    'id'     => (int) $r['id'],
    'author' => $r['author'],
    'rating' => (int) $r['rating'],
    'body'   => $r['body'],
    'meta'   => $r['meta'],
    'date'   => $r['created_at'],
]], $isNew ? 201 : 200);
