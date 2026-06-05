<?php
/**
 * One-time setup. Open http://your-site/setup.php in your browser.
 * Works on XAMPP (auto-creates the DB) and on shared hosting like Hostinger
 * (where the DB is created in the control panel first). Safe to re-run.
 */
require_once __DIR__ . '/includes/app.php';

$steps = [];
$error = null;

try {
    $c = config('db');
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    $dsnWithDb = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";

    // 1) Connect to the configured database. If it doesn't exist yet (typical on
    //    local XAMPP), try to create it. On shared hosting the DB already exists
    //    (made in the control panel), so this just connects.
    try {
        $pdo = new PDO($dsnWithDb, $c['user'], $c['password'], $opts);
        $steps[] = "Connected to database '{$c['name']}'.";
    } catch (PDOException $e) {
        $dsnNoDb = "mysql:host={$c['host']};port={$c['port']};charset={$c['charset']}";
        $root = new PDO($dsnNoDb, $c['user'], $c['password'], $opts);
        $root->exec("CREATE DATABASE IF NOT EXISTS `{$c['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = new PDO($dsnWithDb, $c['user'], $c['password'], $opts);
        $steps[] = "Created and connected to database '{$c['name']}'.";
    }

    // 2) Create the tables (no CREATE DATABASE inside — safe on shared hosting).
    $pdo->exec(file_get_contents(__DIR__ . '/sql/tables.sql'));
    $steps[] = 'Tables created (or already present).';

    // 3) Seed the admin account.
    $a = config('admin');
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute([$a['email']]);
    if ($st->fetch()) {
        $steps[] = "Admin account already exists ({$a['email']}).";
    } else {
        $hash = password_hash($a['password'], PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, 'admin')")
            ->execute([$a['email'], $hash, $a['full_name']]);
        $steps[] = "Admin account created: {$a['email']} (password from config.php).";
    }

    // 4) Make sure the upload folders exist.
    foreach (['uploads/gallery', 'uploads/blog'] as $rel) {
        $dir = __DIR__ . '/' . $rel;
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new RuntimeException("Could not create directory: $rel");
        }
    }
    $steps[] = 'Upload folders ready (uploads/gallery, uploads/blog).';
} catch (Throwable $ex) {
    $error = $ex->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup — Randy's Painting &amp; Drywall</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 4rem auto; padding: 0 1.5rem; color: #16233f; }
        h1 { color: #1f56c4; }
        .ok { color: #137a3a; } .err { color: #d8322b; }
        li { margin: .4rem 0; } code { background: #eef; padding: .1rem .35rem; border-radius: 4px; }
        a.btn { display: inline-block; margin-top: 1.5rem; background: #d8322b; color: #fff; padding: .7rem 1.4rem; border-radius: 999px; text-decoration: none; font-weight: 700; }
        pre { background:#f4f4fb; padding:1rem; border-radius:8px; overflow:auto; }
    </style>
</head>
<body>
    <h1>Setup</h1>
    <?php if ($error): ?>
        <p class="err"><strong>Setup failed:</strong> <?= e($error) ?></p>
        <p>Check the <code>db</code> settings in <code>config.php</code>:</p>
        <ul>
            <li>On <strong>Hostinger</strong>: create the database + user in hPanel first, then put the
                exact <code>name</code>, <code>user</code>, <code>password</code> (host is usually <code>localhost</code>) into <code>config.php</code>.</li>
            <li>On <strong>XAMPP</strong>: make sure MySQL is running (default user <code>root</code>, blank password).</li>
        </ul>
    <?php else: ?>
        <p class="ok"><strong>Setup complete!</strong></p>
        <ul>
            <?php foreach ($steps as $s): ?><li><?= e($s) ?></li><?php endforeach; ?>
        </ul>
        <p>You can now delete <code>setup.php</code> for production. Log in with the admin
           account, then change the password.</p>
        <a class="btn" href="<?= e(url('index.php')) ?>">Go to the website →</a>
    <?php endif; ?>
</body>
</html>
