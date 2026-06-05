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
