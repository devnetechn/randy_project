<?php
/** Update a job application's status. Body: { id, status } */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$id = (int) ($payload['id'] ?? 0);
$status = $payload['status'] ?? '';
$valid = ['new', 'reviewed', 'hired', 'rejected'];

if ($id <= 0) {
    json_error('A valid application id is required', 422);
}
if (!in_array($status, $valid, true)) {
    json_error('Invalid status', 422);
}

$st = db()->prepare('SELECT id FROM job_applications WHERE id = ?');
$st->execute([$id]);
if (!$st->fetch()) {
    json_error('Application not found', 404);
}

db()->prepare('UPDATE job_applications SET status = ? WHERE id = ?')->execute([$status, $id]);

$st = db()->prepare(
    'SELECT a.*, COALESCE(p.title, a.position_title_snapshot) AS position_title
       FROM job_applications a
       LEFT JOIN job_positions p ON p.id = a.position_id
      WHERE a.id = ?'
);
$st->execute([$id]);
$application = $st->fetch();

require_once __DIR__ . '/../../includes/email.php';
send_job_application_status_email($application, $status);

json_out(['application' => $application]);
