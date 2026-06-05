<?php
require_once __DIR__ . '/includes/app.php';

// Already logged in? Send them where they belong.
if ($u = current_user()) {
    redirect($u['role'] === 'admin' ? 'admin/index.php' : 'chat.php');
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([$email]);
        $user = $st->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } else {
            login_user($user);
            redirect($user['role'] === 'admin' ? 'admin/index.php' : 'chat.php');
        }
    }
}

$page_title = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<div class="app-wrap">
    <h1 class="app-title">Log in</h1>
    <form method="post" novalidate>
        <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <label class="field"><span>Email</span>
            <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
        </label>
        <label class="field"><span>Password</span>
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn-primary" type="submit">Log in</button>
    </form>
    <p class="form-note">No account? <a href="<?= e(url('register.php')) ?>">Register</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
