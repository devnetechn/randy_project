<?php
/** All job applications, newest first, with the current (or snapshot) position title. */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

$applications = db()->query(
    'SELECT a.*, COALESCE(p.title, a.position_title_snapshot) AS position_title
       FROM job_applications a
       LEFT JOIN job_positions p ON p.id = a.position_id
      ORDER BY a.created_at DESC'
)->fetchAll();
json_out(['applications' => $applications]);
