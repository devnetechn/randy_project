<?php
require_once __DIR__ . '/includes/app.php';

$u = require_login();
if ($u['role'] === 'admin') {
    redirect('admin/index.php');
}

$SERVICES = [
    'Interior painting', 'Exterior painting', 'Drywall installation', 'Drywall repair',
    'Cabinet refinishing', 'Popcorn ceiling removal', 'Pressure washing', 'Free estimate',
];

$error = null;
$form = ['serviceType' => $SERVICES[0], 'preferredAt' => '', 'address' => '', 'phone' => '', 'notes' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['serviceType'] = $_POST['service_type'] ?? $SERVICES[0];
    $form['preferredAt'] = trim($_POST['preferred_at'] ?? '');
    $form['address']     = trim($_POST['address'] ?? '');
    $form['phone']       = trim($_POST['phone'] ?? '');
    $form['notes']       = trim($_POST['notes'] ?? '');

    if (!in_array($form['serviceType'], $SERVICES, true)) {
        $error = 'Please choose a valid service.';
    } elseif ($form['preferredAt'] === '' || $form['address'] === '') {
        $error = 'Service, preferred date/time, and address are required.';
    } else {
        // datetime-local "2026-06-01T14:30" -> MySQL DATETIME "2026-06-01 14:30:00"
        $preferred = str_replace('T', ' ', $form['preferredAt']);
        if (strlen($preferred) === 16) {
            $preferred .= ':00';
        }
        db()->prepare(
            'INSERT INTO appointments (customer_id, service_type, preferred_at, address, phone, notes)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $u['id'], $form['serviceType'], $preferred, $form['address'],
            $form['phone'] ?: null, $form['notes'] ?: null,
        ]);

        // Best-effort email alert to the business — never blocks the booking.
        require_once __DIR__ . '/includes/email.php';
        $st = db()->prepare('SELECT * FROM appointments WHERE id = ?');
        $st->execute([(int) db()->lastInsertId()]);
        if ($appt = $st->fetch()) {
            send_booking_notification($appt);
        }

        redirect('bookings.php');
    }
}

$page_title = 'Book an appointment';
require __DIR__ . '/includes/header.php';
?>
<div class="app-wrap">
    <h1 class="app-title">Book an appointment</h1>
    <p class="app-sub">Tell us what you need and your preferred time — we'll confirm shortly. Estimates are free.</p>
    <form method="post" novalidate>
        <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <label class="field"><span>Service</span>
            <select name="service_type">
                <?php foreach ($SERVICES as $s): ?>
                    <option value="<?= e($s) ?>"<?= $s === $form['serviceType'] ? ' selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Preferred date &amp; time</span>
            <input type="datetime-local" name="preferred_at" value="<?= e($form['preferredAt']) ?>" required>
        </label>
        <label class="field"><span>Service address</span>
            <input type="text" name="address" value="<?= e($form['address']) ?>" required>
        </label>
        <label class="field"><span>Phone (optional)</span>
            <input type="tel" name="phone" value="<?= e($form['phone']) ?>">
        </label>
        <label class="field"><span>Notes (optional)</span>
            <textarea name="notes" rows="3"><?= e($form['notes']) ?></textarea>
        </label>
        <button class="btn-primary" type="submit">Request appointment</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
