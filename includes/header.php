<?php
/** Shared page head + navbar. Set $page_title before including. */
$u = current_user();
$b = business_info();
$title = isset($page_title) ? "$page_title — {$b['name']}" : $b['name'];

// SEO: absolute URLs for canonical, social cards, and structured data.
$meta_desc = isset($page_description)
    ? $page_description
    : 'High-end painting & drywall contractor serving the Lehigh Valley and Bucks County, PA. Level 5 smooth-wall finishes, skim coating, fine-finish painting and wall restoration for luxury homes and businesses near Easton, Bethlehem, New Hope and Doylestown. Licensed, insured, free estimates.';
// Note: <meta keywords> is ignored by Google; the real ranking signals are the
// title, description, visible content, and the areaServed cities in JSON-LD below.
$meta_keywords = isset($page_keywords)
    ? $page_keywords
    : 'local painter near Easton, painter Easton PA, Lehigh Valley painter, drywall contractor Lehigh Valley, '
      . 'interior painting Easton, exterior painting Lehigh Valley, drywall repair Easton PA, '
      . 'Bethlehem painter, Allentown painter, Nazareth painter, house painter near me';
$scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'randyspaintdrywall.com';
$origin    = $scheme . '://' . $host;
$canonical = $origin . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
// Preserve the meaningful ?id= param on detail pages (project, blog-post) so each
// canonicalises to its own URL; tracking params (utm_, fbclid, gclid…) drop off.
if (isset($_GET['id']) && ctype_digit((string) $_GET['id'])) {
    $canonical .= '?id=' . (int) $_GET['id'];
}
// Social/SERP image: a page may set $page_image (root-relative via url() or an
// absolute URL); otherwise fall back to the logo.
if (!empty($page_image)) {
    $og_image = preg_match('#^https?://#', $page_image) ? $page_image : $origin . $page_image;
} else {
    $og_image = $origin . url('assets/img/logo.png');
}

// LocalBusiness structured data (JSON-LD) — helps Google show rich local results.
$ld = [
    '@context'    => 'https://schema.org',
    '@type'       => ['HousePainter', 'GeneralContractor'],
    '@id'         => $origin . '/#business',
    'name'        => $b['name'],
    'image'       => $og_image,
    'url'         => $origin . '/',
    'telephone'   => $b['phoneTel'],
    'email'       => $b['email'],
    'founder'     => $b['owner'],
    'priceRange'  => '$$',
    'description' => $meta_desc,
    'openingHours' => 'Mo-Sa 08:00-17:00',
    'hasMap'      => 'https://maps.app.goo.gl/eMAR498zU14UTr4s6',
    'sameAs'      => ['https://maps.app.goo.gl/eMAR498zU14UTr4s6'],
    'geo'         => ['@type' => 'GeoCoordinates', 'latitude' => 40.648803, 'longitude' => -75.5596636],
    'address'     => [
        '@type'           => 'PostalAddress',
        'addressLocality' => 'Easton',
        'addressRegion'   => 'PA',
        'addressCountry'  => 'US',
    ],
    'areaServed'  => [
        ['@type' => 'City', 'name' => 'Easton', 'addressRegion' => 'PA'],
        ['@type' => 'City', 'name' => 'Bethlehem', 'addressRegion' => 'PA'],
        ['@type' => 'City', 'name' => 'Allentown', 'addressRegion' => 'PA'],
        ['@type' => 'City', 'name' => 'Nazareth', 'addressRegion' => 'PA'],
        ['@type' => 'City', 'name' => 'Wilson', 'addressRegion' => 'PA'],
        ['@type' => 'City', 'name' => 'Palmer', 'addressRegion' => 'PA'],
        ['@type' => 'City', 'name' => 'Forks Township', 'addressRegion' => 'PA'],
        ['@type' => 'City', 'name' => 'Phillipsburg', 'addressRegion' => 'NJ'],
        ['@type' => 'City', 'name' => 'New Hope', 'addressRegion' => 'PA'],
        ['@type' => 'City', 'name' => 'Doylestown', 'addressRegion' => 'PA'],
        ['@type' => 'City', 'name' => 'Solebury Township', 'addressRegion' => 'PA'],
        ['@type' => 'City', 'name' => 'Upper Makefield Township', 'addressRegion' => 'PA'],
        ['@type' => 'City', 'name' => 'Newtown', 'addressRegion' => 'PA'],
        ['@type' => 'City', 'name' => 'Yardley', 'addressRegion' => 'PA'],
        ['@type' => 'AdministrativeArea', 'name' => 'Lehigh Valley'],
        ['@type' => 'AdministrativeArea', 'name' => 'Bucks County'],
        [
            '@type'       => 'GeoCircle',
            'geoMidpoint' => ['@type' => 'GeoCoordinates', 'latitude' => 40.6884, 'longitude' => -75.2207],
            'geoRadius'   => 64374,
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="google-site-verification" content="rB030XnwxjgkxfPn7F7474FPRLgchM9LAVA6RjontO8" />
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($meta_desc) ?>">
    <meta name="keywords" content="<?= e($meta_keywords) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta name="robots" content="index, follow">

    <!-- Open Graph (Facebook, Messenger, LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($b['name']) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($meta_desc) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e($og_image) ?>">

    <!-- Twitter / X cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="<?= e($meta_desc) ?>">
    <meta name="twitter:image" content="<?= e($og_image) ?>">

    <!-- LocalBusiness structured data -->
    <script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

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
<header class="site-header">
    <nav class="nav">
        <a class="nav__logo" href="<?= e(url('index.php')) ?>" aria-label="<?= e($b['name']) ?> — home">
            <img src="<?= e(url('assets/img/logo.png')) ?>" alt="<?= e($b['name']) ?>">
        </a>

        <div class="nav__links nav__center">
            <a href="<?= e(url('index.php')) ?>"<?= active('index.php') ?>>Home</a>

            <div class="nav__drop-wrap">
                <a class="nav__drop-trigger" href="<?= e(url('services.php')) ?>"<?= active('services.php') ?>>Services<svg class="nav__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg></a>
                <div class="nav__dropdown">
                    <a href="<?= e(url('services.php')) ?>">Interior Painting</a>
                    <a href="<?= e(url('services.php')) ?>">Exterior Painting</a>
                    <a href="<?= e(url('services.php')) ?>">Drywall Installation</a>
                    <a href="<?= e(url('services.php')) ?>">Drywall Repair</a>
                    <div class="nav__dropdown-divider"></div>
                    <a href="<?= e(url('level-5-drywall.php')) ?>">Level 5 Drywall Finish</a>
                    <a href="<?= e(url('skim-coating.php')) ?>">Skim Coating</a>
                    <a href="<?= e(url('wall-restoration.php')) ?>">Wall Restoration</a>
                    <div class="nav__dropdown-divider"></div>
                    <a href="<?= e(url('locations/index.php')) ?>">Service Areas</a>
                    <div class="nav__dropdown-divider"></div>
                    <a class="nav__dropdown-cta" href="<?= e(url('services.php')) ?>">All Services &rarr;</a>
                </div>
            </div>

            <div class="nav__drop-wrap">
                <a class="nav__drop-trigger" href="<?= e(url('commercial.php')) ?>"<?= active('commercial.php') ?>>Commercial<svg class="nav__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg></a>
                <div class="nav__dropdown">
                    <a href="<?= e(url('commercial.php')) ?>">Office &amp; Retail Painting</a>
                    <a href="<?= e(url('commercial.php')) ?>">Multi-Unit &amp; Condo</a>
                    <a href="<?= e(url('commercial.php')) ?>">Commercial Drywall</a>
                    <div class="nav__dropdown-divider"></div>
                    <a class="nav__dropdown-cta" href="<?= e(url('book.php')) ?>">Get a Commercial Quote &rarr;</a>
                </div>
            </div>

            <a href="<?= e(url('gallery.php')) ?>"<?= active('gallery.php') ?>>Gallery</a>
            <a href="<?= e(url('about.php')) ?>"<?= active('about.php') ?>>About</a>
            <a href="<?= e(url('contact.php')) ?>"<?= active('contact.php') ?>>Contact</a>
        </div>

        <div class="nav__auth desktop">
            <?php if ($u && $u['role'] === 'admin'): ?>
                <a href="<?= e(url('admin/index.php')) ?>">Dashboard</a>
                <span class="who"><?= e($u['email']) ?></span>
                <a class="nav__pill" href="<?= e(url('logout.php')) ?>">Log out</a>
            <?php elseif ($u): ?>
                <a href="<?= e(url('chat.php')) ?>">Chat</a>
                <a href="<?= e(url('book.php')) ?>">Book</a>
                <a href="<?= e(url('bookings.php')) ?>">My Bookings</a>
                <span class="who"><?= e($u['email']) ?></span>
                <a class="nav__pill" href="<?= e(url('logout.php')) ?>">Log out</a>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>">Log in</a>
                <a href="<?= e(url('register.php')) ?>">Register</a>
                <a class="nav__pill" href="<?= e(url('book.php')) ?>">Free Estimate</a>
            <?php endif; ?>
        </div>

        <button class="nav__toggle" type="button" aria-label="Toggle menu" aria-expanded="false" data-nav-toggle>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>

        <div class="nav__panel" data-nav-panel>
            <a href="<?= e(url('index.php')) ?>">Home</a>

            <div class="nav__accordion" data-accordion>
                <button class="nav__accordion-toggle" type="button" aria-expanded="false" data-accordion-toggle>
                    Services
                    <svg class="nav__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="nav__accordion-body" data-accordion-body>
                    <a href="<?= e(url('services.php')) ?>">Interior Painting</a>
                    <a href="<?= e(url('services.php')) ?>">Exterior Painting</a>
                    <a href="<?= e(url('services.php')) ?>">Drywall Installation &amp; Repair</a>
                    <a href="<?= e(url('level-5-drywall.php')) ?>">Level 5 Drywall Finish</a>
                    <a href="<?= e(url('skim-coating.php')) ?>">Skim Coating</a>
                    <a href="<?= e(url('wall-restoration.php')) ?>">Wall Restoration</a>
                    <a href="<?= e(url('locations/index.php')) ?>">Service Areas</a>
                    <a class="nav__panel-cta" href="<?= e(url('services.php')) ?>">All Services &rarr;</a>
                </div>
            </div>

            <div class="nav__accordion" data-accordion>
                <button class="nav__accordion-toggle" type="button" aria-expanded="false" data-accordion-toggle>
                    Commercial
                    <svg class="nav__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="nav__accordion-body" data-accordion-body>
                    <a href="<?= e(url('commercial.php')) ?>">Office &amp; Retail Painting</a>
                    <a href="<?= e(url('commercial.php')) ?>">Multi-Unit &amp; Condo</a>
                    <a href="<?= e(url('commercial.php')) ?>">Commercial Drywall</a>
                    <a class="nav__panel-cta" href="<?= e(url('book.php')) ?>">Get a Commercial Quote &rarr;</a>
                </div>
            </div>

            <a href="<?= e(url('gallery.php')) ?>">Gallery</a>
            <a href="<?= e(url('about.php')) ?>">About</a>
            <a href="<?= e(url('contact.php')) ?>">Contact</a>
            <?php if ($u && $u['role'] === 'admin'): ?>
                <a href="<?= e(url('admin/index.php')) ?>">Dashboard</a>
                <a class="nav__pill" href="<?= e(url('logout.php')) ?>">Log out</a>
            <?php elseif ($u): ?>
                <a href="<?= e(url('chat.php')) ?>">Chat</a>
                <a href="<?= e(url('book.php')) ?>">Book</a>
                <a href="<?= e(url('bookings.php')) ?>">My Bookings</a>
                <a class="nav__pill" href="<?= e(url('logout.php')) ?>">Log out</a>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>">Log in</a>
                <a href="<?= e(url('register.php')) ?>">Register</a>
                <a class="nav__pill" href="<?= e(url('book.php')) ?>">Free Estimate</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
<main class="app-main">
