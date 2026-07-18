<?php
/**
 * Slim head/body opener for the admin dashboard only.
 * No marketing nav, no SEO meta/JSON-LD — the admin panel isn't a public page.
 * Expects $u (from require_admin_page()) and $page_title to already be set
 * by the including page.
 */
$b = business_info();
$title = isset($page_title) ? "$page_title — {$b['name']}" : $b['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(url('assets/img/favicon-32.png')) ?>">
    <link rel="icon" href="<?= e(url('assets/img/favicon.ico')) ?>" sizes="any">
    <link rel="apple-touch-icon" href="<?= e(url('assets/img/apple-touch-icon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php $css_v = @filemtime(__DIR__ . '/../assets/css/styles.css') ?: 1; ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/styles.css')) ?>?v=<?= $css_v ?>">
    <script>window.BASE_URL = <?= json_encode(url('')) ?>;</script>
</head>
<body>
