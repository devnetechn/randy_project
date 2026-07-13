<?php
/** All job positions (open + closed), newest first. */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

$positions = db()->query('SELECT * FROM job_positions ORDER BY created_at DESC')->fetchAll();
json_out(['positions' => $positions]);
