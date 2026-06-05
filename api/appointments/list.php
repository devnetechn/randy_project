<?php
/** List appointments. Admin: all (optional ?status). Customer: their own. */
require_once __DIR__ . '/../../includes/app.php';
$u = require_login_api();

if ($u['role'] === 'admin') {
    $status = $_GET['status'] ?? null;
    $valid = ['pending', 'confirmed', 'declined', 'cancelled', 'completed'];
    if ($status && in_array($status, $valid, true)) {
        $st = db()->prepare(
            'SELECT a.*, u.full_name AS customer_name, u.email AS customer_email
               FROM appointments a JOIN users u ON u.id = a.customer_id
              WHERE a.status = ? ORDER BY a.created_at DESC'
        );
        $st->execute([$status]);
    } else {
        $st = db()->query(
            'SELECT a.*, u.full_name AS customer_name, u.email AS customer_email
               FROM appointments a JOIN users u ON u.id = a.customer_id
              ORDER BY a.created_at DESC'
        );
    }
    json_out(['appointments' => $st->fetchAll()]);
}

$st = db()->prepare('SELECT * FROM appointments WHERE customer_id = ? ORDER BY created_at DESC');
$st->execute([$u['id']]);
json_out(['appointments' => $st->fetchAll()]);
