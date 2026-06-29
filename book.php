<?php
require_once __DIR__ . '/includes/app.php';

// No login required — anyone can request a quote. Logged-in customers get a
// booking tied to their account; guests just leave their contact details.
$u = current_user();
if ($u && $u['role'] === 'admin') {
    redirect('admin/index.php');
}
$is_guest = !$u;

$SERVICES = [
    'Painting'              => ['Interior painting', 'Exterior painting', 'Cabinet refinishing'],
    'Commercial'            => ['Commercial interior painting', 'Commercial exterior painting'],
    'Drywall & Finishing'   => ['Drywall installation', 'Drywall repair', 'Level 4 & 5 skim coating', 'Texture & finishing', 'Popcorn ceiling removal'],
    'Surface Restoration'   => ['Stucco removal', 'Plaster repair', 'Wallcovering removal'],
    'Exterior Services'     => ['Power washing'],
    'Other'                 => ['Free estimate / not sure yet'],
];
// Flat list for validation
$SERVICES_FLAT = array_merge(...array_values($SERVICES));

$error = null;
$sent  = isset($_GET['sent']);
$form = [
    'name' => '', 'email' => '', 'serviceType' => $SERVICES_FLAT[0],
    'preferredAt' => '', 'address' => '', 'phone' => '', 'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name']        = trim($_POST['name'] ?? '');
    $form['email']       = trim($_POST['email'] ?? '');
    $form['serviceType'] = $_POST['service_type'] ?? $SERVICES[0];
    $form['preferredAt'] = trim($_POST['preferred_at'] ?? '');
    $form['address']     = trim($_POST['address'] ?? '');
    $form['phone']       = trim($_POST['phone'] ?? '');
    $form['notes']       = trim($_POST['notes'] ?? '');

    if (!in_array($form['serviceType'], $SERVICES_FLAT, true)) {
        $error = 'Please choose a valid service.';
    } elseif ($form['preferredAt'] === '' || $form['address'] === '') {
        $error = 'Service, preferred date/time, and address are required.';
    } elseif ($is_guest && ($form['name'] === '' || $form['email'] === '' || $form['phone'] === '')) {
        $error = 'Please provide your name, email, and phone so we can reach you.';
    } elseif ($is_guest && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // datetime-local "2026-06-01T14:30" -> MySQL DATETIME "2026-06-01 14:30:00"
        $preferred = str_replace('T', ' ', $form['preferredAt']);
        if (strlen($preferred) === 16) {
            $preferred .= ':00';
        }
        db()->prepare(
            'INSERT INTO appointments
                (customer_id, guest_name, guest_email, guest_phone, service_type, preferred_at, address, phone, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $is_guest ? null : $u['id'],
            $is_guest ? $form['name'] : null,
            $is_guest ? $form['email'] : null,
            $is_guest ? $form['phone'] : null,
            $form['serviceType'], $preferred, $form['address'],
            $form['phone'] ?: null, $form['notes'] ?: null,
        ]);

        // Best-effort notifications — none of these can block or fail the booking.
        require_once __DIR__ . '/includes/email.php';
        require_once __DIR__ . '/includes/sms.php';
        require_once __DIR__ . '/includes/gcal.php';
        $st = db()->prepare('SELECT * FROM appointments WHERE id = ?');
        $st->execute([(int) db()->lastInsertId()]);
        if ($appt = $st->fetch()) {
            send_booking_notification($appt);   // email the business
            notify_owner_sms($appt);            // text Randy
            gcal_sync_for_appointment($appt);   // add to Google Calendar
        }

        // Guests have no "My Bookings" page — show a thank-you instead.
        redirect($is_guest ? 'book.php?sent=1' : 'bookings.php');
    }
}

$page_title = 'Book an appointment';
require __DIR__ . '/includes/header.php';
?>
<div class="app-wrap">
    <?php if ($sent): $b = business_info(); ?>
        <h1 class="app-title">Thanks — request received!</h1>
        <p class="app-sub">We've got your details and will reach out shortly to confirm your free estimate. Want to talk sooner? Call us about your free estimate.</p>
        <p style="margin:1.25rem 0">
            <a class="btn-primary" href="tel:<?= e($b['phoneTel']) ?>">📞 Call us now — <?= e($b['phone']) ?></a>
        </p>
        <a href="<?= e(url('index.php')) ?>">← Back to home</a>
    <?php else: ?>
    <h1 class="app-title">Request a free estimate</h1>
    <p class="app-sub">Tell us what you need and your preferred time — we'll confirm shortly. No account required; estimates are free.</p>
    <form method="post" novalidate>
        <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if ($is_guest): ?>
        <label class="field"><span>Your name</span>
            <input type="text" name="name" value="<?= e($form['name']) ?>" required>
        </label>
        <label class="field"><span>Email</span>
            <input type="email" name="email" value="<?= e($form['email']) ?>" required>
        </label>
        <?php endif; ?>
        <label class="field"><span>Service</span>
            <select name="service_type">
                <?php foreach ($SERVICES as $group => $options): ?>
                    <optgroup label="<?= e($group) ?>">
                        <?php foreach ($options as $s): ?>
                            <option value="<?= e($s) ?>"<?= $s === $form['serviceType'] ? ' selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field"><span>Preferred date &amp; time</span>
            <input type="datetime-local" name="preferred_at" value="<?= e($form['preferredAt']) ?>" required>
        </label>
        <label class="field"><span>Service address</span>
            <input type="text" name="address" value="<?= e($form['address']) ?>" required>
        </label>
        <label class="field"><span>Phone<?= $is_guest ? '' : ' (optional)' ?></span>
            <input type="tel" name="phone" value="<?= e($form['phone']) ?>"<?= $is_guest ? ' required' : '' ?>>
        </label>
        <label class="field"><span>Notes (optional)</span>
            <textarea name="notes" rows="3"><?= e($form['notes']) ?></textarea>
        </label>
        <button class="btn-primary" type="submit">Request appointment</button>
    </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
