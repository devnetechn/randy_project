<?php
/** Delete a job position. Body: { id }. Applications keep their position_title_snapshot. */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$id = (int) ($payload['id'] ?? 0);
if ($id <= 0) {
    json_error('A valid position id is required', 422);
}

db()->prepare('DELETE FROM job_positions WHERE id = ?')->execute([$id]);
json_out(['success' => true]);
