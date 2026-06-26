<?php
require_once __DIR__ . '/includes/app.php';

$prefill_name    = mb_substr(trim($_GET['name'] ?? ''), 0, 120);
$prefill_service = mb_substr(trim($_GET['service'] ?? ''), 0, 120);
$error = null;
$done  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author = mb_substr(trim($_POST['author'] ?? ''), 0, 120);
    $rating = (int) ($_POST['rating'] ?? 5);
    $body   = mb_substr(trim($_POST['body'] ?? ''), 0, 2000);
    $svc    = mb_substr(trim($_POST['service'] ?? ''), 0, 120);

    if ($author === '') {
        $error = 'Please enter your name.';
    } elseif ($body === '') {
        $error = 'Please write a brief review.';
    } else {
        $rating = max(1, min(5, $rating));
        $meta   = $svc ? $svc . ' · ' . date('Y') : null;
        db()->prepare('INSERT INTO reviews (author, rating, body, meta) VALUES (?, ?, ?, ?)')
            ->execute([$author, $rating, $body, $meta]);
        $done = true;
    }
}

$b = business_info();
$page_title = 'Leave a Review — ' . $b['name'];
require __DIR__ . '/includes/header.php';
?>
<div class="app-wrap">
<?php if ($done): ?>
    <h1 class="app-title">Thank you for your review!</h1>
    <p class="app-sub">Your feedback means the world to us — it helps others find us and helps us keep improving.</p>
    <a class="btn-primary" href="<?= e(url('index.php')) ?>">← Back to home</a>
<?php else: ?>
    <h1 class="app-title">How did we do?</h1>
    <p class="app-sub">Your review takes about 30 seconds and helps <?= e($b['name']) ?> grow. Thank you!</p>

    <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>

    <form method="post" novalidate>
        <input type="hidden" name="service" value="<?= e($prefill_service) ?>">

        <label class="field"><span>Your name</span>
            <input type="text" name="author" value="<?= e($prefill_name) ?>" required maxlength="120">
        </label>

        <div class="field">
            <span>Rating</span>
            <div class="star-picker" role="radiogroup" aria-label="Rating">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>"<?= $i === 5 ? ' checked' : '' ?>>
                <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</label>
                <?php endfor; ?>
            </div>
        </div>

        <label class="field"><span>Your review</span>
            <textarea name="body" rows="4" required maxlength="2000" placeholder="Tell us about your experience…"></textarea>
        </label>

        <button class="btn-primary" type="submit">Submit review</button>
    </form>
<?php endif; ?>
</div>

<style>
.star-picker { display:flex; flex-direction:row-reverse; justify-content:flex-end; gap:.15rem; margin-top:.35rem; }
.star-picker input { display:none; }
.star-picker label { font-size:2rem; color:#ccc; cursor:pointer; transition:color .15s; }
.star-picker input:checked ~ label,
.star-picker label:hover,
.star-picker label:hover ~ label { color:#f5a623; }
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
