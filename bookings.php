<?php
require_once __DIR__ . '/includes/app.php';

$u = require_login();
if ($u['role'] === 'admin') {
    redirect('admin/index.php');
}

// Customer-initiated cancel of their own pending/confirmed booking.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $id = (int) ($_POST['id'] ?? 0);
    $st = db()->prepare('SELECT * FROM appointments WHERE id = ? AND customer_id = ?');
    $st->execute([$id, $u['id']]);
    $appt = $st->fetch();
    if ($appt && in_array($appt['status'], ['pending', 'confirmed'], true)) {
        db()->prepare("UPDATE appointments SET status='cancelled' WHERE id=?")->execute([$id]);
    }
    redirect('bookings.php');
}

$st = db()->prepare('SELECT * FROM appointments WHERE customer_id = ? ORDER BY created_at DESC');
$st->execute([$u['id']]);
$bookings = $st->fetchAll();

$fmt = function ($dt) {
    return $dt ? date('M j, Y, g:i A', strtotime($dt)) : '—';
};

$page_title = 'My bookings';
require __DIR__ . '/includes/header.php';
?>
<div class="app-wrap app-wrap--wide">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem">
        <h1 class="app-title" style="margin:0">My bookings</h1>
        <a class="btn-primary" href="<?= e(url('book.php')) ?>">New booking</a>
    </div>

    <?php if (!$bookings): ?>
        <p class="app-sub">No bookings yet.</p>
    <?php else: ?>
        <ul class="booking-list">
            <?php foreach ($bookings as $b): ?>
                <li class="booking-item">
                    <div class="booking-item__head">
                        <span class="booking-item__title"><?= e($b['service_type']) ?></span>
                        <span class="badge badge--<?= e($b['status']) ?>"><?= e($b['status']) ?></span>
                    </div>
                    <p class="booking-item__meta">
                        Preferred: <?= e($fmt($b['preferred_at'])) ?>
                        <?= $b['scheduled_at'] ? ' · Scheduled: ' . e($fmt($b['scheduled_at'])) : '' ?>
                    </p>
                    <p class="booking-item__meta"><?= e($b['address']) ?></p>
                    <?php if ($b['status'] === 'declined' && $b['decline_reason']): ?>
                        <p class="booking-item__meta">Reason: <?= e($b['decline_reason']) ?></p>
                    <?php endif; ?>
                    <?php if (in_array($b['status'], ['pending', 'confirmed'], true)): ?>
                        <div class="booking-actions">
                            <form method="post" onsubmit="return confirm('Cancel this booking?')">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                <button type="submit" class="link-red" style="background:none;border:0;cursor:pointer;font-size:.9rem">Cancel</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
