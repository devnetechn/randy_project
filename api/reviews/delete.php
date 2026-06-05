<?php
/** Admin delete a review. JSON body: { id }. */
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/reviews.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$id = (int) ($payload['id'] ?? 0);

if (!reviews_find($id)) {
    json_error('Review not found', 404);
}
db()->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);

json_out(['ok' => true]);
