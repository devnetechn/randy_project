<?php
/** List appointments. Admin: all (optional ?status). Customer: their own. */
require_once __DIR__ . '/../../includes/app.php';
$u = require_login_api();

if ($u['role'] === 'admin') {
    $status = $_GET['status'] ?? null;
    $valid = ['pending', 'confirmed', 'declined', 'cancelled', 'completed'];
    // LEFT JOIN so guest requests (customer_id IS NULL) still appear; fall back
    // to the guest_* columns for their name/email.
    if ($status && in_array($status, $valid, true)) {
        $st = db()->prepare(
            'SELECT a.*,
                    COALESCE(u.full_name, a.guest_name)  AS customer_name,
                    COALESCE(u.email, a.guest_email)     AS customer_email
               FROM appointments a LEFT JOIN users u ON u.id = a.customer_id
              WHERE a.status = ? ORDER BY a.created_at DESC'
        );
        $st->execute([$status]);
    } else {
        $st = db()->query(
            'SELECT a.*,
                    COALESCE(u.full_name, a.guest_name)  AS customer_name,
                    COALESCE(u.email, a.guest_email)     AS customer_email
               FROM appointments a LEFT JOIN users u ON u.id = a.customer_id
              ORDER BY a.created_at DESC'
        );
    }
    json_out(['appointments' => $st->fetchAll()]);
}

$st = db()->prepare('SELECT * FROM appointments WHERE customer_id = ? ORDER BY created_at DESC');
$st->execute([$u['id']]);
json_out(['appointments' => $st->fetchAll()]);
