<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';

$positions = db()->query("SELECT * FROM job_positions WHERE status = 'open' ORDER BY created_at DESC")->fetchAll();

$EMPLOYMENT_LABELS = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract'];

$error = null;
$sent = isset($_GET['applied']);
$selectedPosition = (int) ($_GET['position'] ?? 0);
$form = [
    'position_id' => $selectedPosition,
    'name' => '', 'email' => '', 'phone' => '',
    'years_experience' => '', 'availability' => '', 'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['position_id']      = (int) ($_POST['position_id'] ?? 0);
    $form['name']             = trim($_POST['name'] ?? '');
    $form['email']            = trim($_POST['email'] ?? '');
    $form['phone']            = trim($_POST['phone'] ?? '');
    $form['years_experience'] = trim($_POST['years_experience'] ?? '');
    $form['availability']     = trim($_POST['availability'] ?? '');
    $form['message']          = trim($_POST['message'] ?? '');

    $position = null;
    foreach ($positions as $p) {
        if ((int) $p['id'] === $form['position_id']) { $position = $p; break; }
    }

    if (!$position) {
        $error = 'Please choose a valid open position.';
    } elseif ($form['name'] === '' || $form['email'] === '' || $form['phone'] === '') {
        $error = 'Please provide your name, email, and phone.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please attach your resume as a PDF.';
    } elseif ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
        $error = 'Resume must be 5 MB or smaller.';
    } elseif (mime_content_type($_FILES['resume']['tmp_name']) !== 'application/pdf') {
        $error = 'Resume must be a PDF file.';
    } else {
        $dir = __DIR__ . '/uploads/resumes';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $filename = bin2hex(random_bytes(16)) . '.pdf';
        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $dir . '/' . $filename)) {
            $error = 'Could not save your resume — please try again.';
        } else {
            db()->prepare(
                'INSERT INTO job_applications
                    (position_id, position_title_snapshot, name, email, phone, years_experience, availability, message, resume_path)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $position['id'], $position['title'], $form['name'], $form['email'], $form['phone'],
                $form['years_experience'] ?: null, $form['availability'] ?: null, $form['message'] ?: null,
                $filename,
            ]);

            require_once __DIR__ . '/includes/email.php';
            $id = (int) db()->lastInsertId();
            $st = db()->prepare('SELECT * FROM job_applications WHERE id = ?');
            $st->execute([$id]);
            if ($application = $st->fetch()) {
                send_job_application_notification($application);
            }

            redirect('careers.php?applied=1');
        }
    }
}

$page_title = 'Careers — Join Our Team';
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> Careers</nav>
            <span class="eyebrow">Join our team</span>
            <h1 style="margin-top:1rem">Build your career with <span class="ul-brush">Randy's</span>.</h1>
            <p>We're always looking for skilled, reliable people to join our painting and drywall crew across the Lehigh Valley and Bucks County, PA.</p>
        </div>
    </section>

    <?php if ($sent): ?>
    <section class="section section--tight">
        <div class="container">
            <div class="form-card" style="max-width:36rem;margin-inline:auto;text-align:center">
                <h2>Thanks for applying!</h2>
                <p style="color:var(--muted);margin-top:.5rem">We've received your application and will reach out if it's a good fit.</p>
                <p style="margin-top:1.5rem"><a href="<?= e(url('careers.php')) ?>">&larr; Back to open positions</a></p>
            </div>
        </div>
    </section>
    <?php else: ?>

    <section class="section section--tight">
        <div class="container">
            <?php if (!$positions): ?>
                <p style="color:var(--muted);text-align:center">No open positions right now — please check back soon.</p>
            <?php else: ?>
            <div class="services-grid">
                <?php foreach ($positions as $p): ?>
                <div class="service-card">
                    <h3><?= e($p['title']) ?></h3>
                    <p style="color:var(--muted);font-size:.85rem;margin-bottom:.5rem"><?= e($EMPLOYMENT_LABELS[$p['employment_type']] ?? $p['employment_type']) ?><?= $p['pay_range'] ? ' · ' . e($p['pay_range']) : '' ?></p>
                    <p><?= nl2br(e($p['description'])) ?></p>
                    <?php if ($p['requirements']): ?><p style="margin-top:.75rem"><strong>Requirements:</strong><br><?= nl2br(e($p['requirements'])) ?></p><?php endif; ?>
                    <p style="margin-top:1rem"><a class="textlink" href="<?= e(url('careers.php?position=' . $p['id'])) ?>#apply">Apply for this position<?= svg_arrow() ?></a></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($positions): ?>
    <section class="section section--tight" id="apply">
        <div class="container">
            <div class="form-card" style="max-width:36rem;margin-inline:auto">
                <h2 style="margin-bottom:1.5rem">Apply now</h2>
                <form method="post" enctype="multipart/form-data" novalidate>
                    <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
                    <label class="field"><span>Position</span>
                        <select name="position_id" required>
                            <?php foreach ($positions as $p): ?>
                                <option value="<?= (int) $p['id'] ?>"<?= $p['id'] === $form['position_id'] ? ' selected' : '' ?>><?= e($p['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field"><span>Your name</span>
                        <input type="text" name="name" value="<?= e($form['name']) ?>" required>
                    </label>
                    <label class="field"><span>Email</span>
                        <input type="email" name="email" value="<?= e($form['email']) ?>" required>
                    </label>
                    <label class="field"><span>Phone</span>
                        <input type="tel" name="phone" value="<?= e($form['phone']) ?>" required>
                    </label>
                    <label class="field"><span>Years of experience</span>
                        <input type="text" name="years_experience" value="<?= e($form['years_experience']) ?>" placeholder="e.g. 3 years">
                    </label>
                    <label class="field"><span>Availability</span>
                        <input type="text" name="availability" value="<?= e($form['availability']) ?>" placeholder="e.g. Immediately, weekdays">
                    </label>
                    <label class="field"><span>Why are you interested? (optional)</span>
                        <textarea name="message" rows="4"><?= e($form['message']) ?></textarea>
                    </label>
                    <label class="field"><span>Resume (PDF, up to 5MB)</span>
                        <input type="file" name="resume" accept="application/pdf" required>
                    </label>
                    <button class="btn-primary" type="submit">Submit application</button>
                </form>
            </div>
        </div>
    </section>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
