<?php
require_once __DIR__ . '/includes/app.php';

if ($u = current_user()) {
    redirect($u['role'] === 'admin' ? 'admin/index.php' : 'chat.php');
}

$error = null;
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'Name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $st = db()->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $error = 'That email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            db()->prepare(
                "INSERT INTO users (email, password_hash, full_name, phone, role) VALUES (?, ?, ?, ?, 'customer')"
            )->execute([$email, $hash, $fullName, $phone ?: null]);
            $st = db()->prepare('SELECT * FROM users WHERE id = ?');
            $st->execute([(int) db()->lastInsertId()]);
            login_user($st->fetch());
            redirect('chat.php');
        }
    }
}

$page_title = 'Register';
require __DIR__ . '/includes/header.php';
?>
<div class="app-wrap">
    <h1 class="app-title">Create your account</h1>
    <form method="post" novalidate>
        <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <label class="field"><span>Full name</span>
            <input type="text" name="full_name" value="<?= e($fullName) ?>" required autocomplete="name">
        </label>
        <label class="field"><span>Email</span>
            <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
        </label>
        <label class="field"><span>Phone (optional)</span>
            <input type="tel" name="phone" autocomplete="tel">
        </label>
        <label class="field"><span>Password</span>
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </label>
        <button class="btn-primary" type="submit">Create account</button>
    </form>
    <p class="form-note">Already have an account? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
