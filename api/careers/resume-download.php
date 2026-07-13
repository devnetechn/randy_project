<?php
/**
 * Stream an applicant's resume PDF (admin only). GET ?id=<application_id>.
 * Not a JSON endpoint on success — streams the raw file with a download header.
 */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    json_error('A valid application id is required', 422);
}

$st = db()->prepare('SELECT name, resume_path FROM job_applications WHERE id = ?');
$st->execute([$id]);
$app = $st->fetch();
if (!$app) {
    json_error('Application not found', 404);
}

$path = __DIR__ . '/../../uploads/resumes/' . $app['resume_path'];
if (!is_file($path)) {
    json_error('Resume file not found', 404);
}

$safeName = preg_replace('/[^A-Za-z0-9 _-]/', '', $app['name']) ?: 'resume';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $safeName . ' - resume.pdf"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
