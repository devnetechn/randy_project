<?php
/** Create or update a job position. Body: { id?, title, description, requirements, employmentType, payRange, status } */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$title          = trim((string) ($payload['title'] ?? ''));
$description    = trim((string) ($payload['description'] ?? ''));
$requirements   = trim((string) ($payload['requirements'] ?? ''));
$employmentType = $payload['employmentType'] ?? 'full_time';
$payRange       = trim((string) ($payload['payRange'] ?? ''));
$status         = $payload['status'] ?? 'open';

if ($title === '' || $description === '') {
    json_error('Title and description are required', 422);
}
if (!in_array($employmentType, ['full_time', 'part_time', 'contract'], true)) {
    json_error('Invalid employment type', 422);
}
if (!in_array($status, ['open', 'closed'], true)) {
    json_error('Invalid status', 422);
}

$id = (int) ($payload['id'] ?? 0);
$isNew = $id <= 0;

if ($isNew) {
    db()->prepare(
        'INSERT INTO job_positions (title, description, requirements, employment_type, pay_range, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$title, $description, $requirements ?: null, $employmentType, $payRange ?: null, $status]);
    $id = (int) db()->lastInsertId();
} else {
    $st = db()->prepare('SELECT id FROM job_positions WHERE id = ?');
    $st->execute([$id]);
    if (!$st->fetch()) {
        json_error('Position not found', 404);
    }
    db()->prepare(
        'UPDATE job_positions SET title = ?, description = ?, requirements = ?, employment_type = ?, pay_range = ?, status = ? WHERE id = ?'
    )->execute([$title, $description, $requirements ?: null, $employmentType, $payRange ?: null, $status, $id]);
}

$st = db()->prepare('SELECT * FROM job_positions WHERE id = ?');
$st->execute([$id]);
json_out(['position' => $st->fetch()], $isNew ? 201 : 200);
