<?php
/** Admin appointment actions. Body: { id, action, scheduledAt?, reason? } */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$id = (int) ($payload['id'] ?? 0);
$action = $payload['action'] ?? '';

$st = db()->prepare('SELECT * FROM appointments WHERE id = ?');
$st->execute([$id]);
$appt = $st->fetch();
if (!$appt) {
    json_error('Appointment not found', 404);
}

/** Normalise a datetime-local / ISO string to MySQL DATETIME. */
$normalize = function (?string $dt): ?string {
    if (!$dt) {
        return null;
    }
    $ts = strtotime($dt);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
};

$pdo = db();
switch ($action) {
    case 'confirm':
        if ($appt['status'] !== 'pending') {
            json_error('Only pending appointments can be confirmed', 409);
        }
        $scheduled = $normalize($payload['scheduledAt'] ?? null) ?: $appt['preferred_at'];
        $pdo->prepare("UPDATE appointments SET status='confirmed', scheduled_at=? WHERE id=?")->execute([$scheduled, $id]);
        break;
    case 'decline':
        if ($appt['status'] !== 'pending') {
            json_error('Only pending appointments can be declined', 409);
        }
        $pdo->prepare("UPDATE appointments SET status='declined', decline_reason=? WHERE id=?")
            ->execute([$payload['reason'] ?? null, $id]);
        break;
    case 'reschedule':
        if ($appt['status'] !== 'confirmed') {
            json_error('Only confirmed appointments can be rescheduled', 409);
        }
        $scheduled = $normalize($payload['scheduledAt'] ?? null);
        if (!$scheduled) {
            json_error('A new date/time is required', 422);
        }
        $pdo->prepare("UPDATE appointments SET scheduled_at=? WHERE id=?")->execute([$scheduled, $id]);
        break;
    case 'complete':
        if ($appt['status'] !== 'confirmed') {
            json_error('Only confirmed appointments can be completed', 409);
        }
        $pdo->prepare("UPDATE appointments SET status='completed' WHERE id=?")->execute([$id]);
        break;
    default:
        json_error('Unknown action', 422);
}

// Re-fetch the updated row (LEFT JOIN so guest appointments — customer_id NULL —
// are returned too, falling back to the guest_* columns for name/email).
$st = db()->prepare(
    'SELECT a.*,
            COALESCE(u.full_name, a.guest_name) AS customer_name,
            COALESCE(u.email, a.guest_email)    AS customer_email
       FROM appointments a LEFT JOIN users u ON u.id = a.customer_id WHERE a.id = ?'
);
$st->execute([$id]);
$updated = $st->fetch();

// Best-effort Google Calendar sync — never blocks the admin action.
require_once __DIR__ . '/../../includes/gcal.php';
if ($updated) {
    if (in_array($action, ['confirm', 'reschedule', 'complete'], true)) {
        gcal_sync_for_appointment($updated);
    } elseif ($action === 'decline') {
        gcal_delete_for_appointment($updated);
    }
}

// Notify the customer by email on confirm or decline.
require_once __DIR__ . '/../../includes/email.php';
if ($updated && in_array($action, ['confirm', 'decline'], true)) {
    send_appointment_status_email($updated, $action);
}

json_out(['appointment' => $updated]);
