<?php
/** Admin gallery delete. Body: { id } */
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

$path = __DIR__ . '/../../uploads/gallery/' . $img['filename'];
if (is_file($path)) {
    @unlink($path);
}
db()->prepare('DELETE FROM gallery_images WHERE id = ?')->execute([$id]);

json_out(['ok' => true]);
