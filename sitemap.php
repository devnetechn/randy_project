<?php
/**
 * Dynamic XML sitemap. Lists the static marketing pages plus every published
 * blog post pulled live from the database, so new posts appear automatically.
 * Served at /sitemap.xml via the rewrite rule in .htaccess.
 */
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/blog.php';
require_once __DIR__ . '/includes/gallery.php';

// Absolute origin so <loc> values are fully-qualified on whatever host we run.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'randyspaintdrywall.com';
$origin = $scheme . '://' . $host;
$today  = date('Y-m-d');

/** Static pages: [path, changefreq, priority, lastmod]. */
$pages = [
    ['index.php',          'weekly',  '1.0', $today],
    ['services.php',       'monthly', '0.9', $today],
    ['level-5-drywall.php','monthly', '0.9', $today],
    ['skim-coating.php',   'monthly', '0.9', $today],
    ['wall-restoration.php','monthly','0.9', $today],
    ['commercial.php',     'monthly', '0.8', $today],
    ['gallery.php',        'weekly',  '0.8', $today],
    ['about.php',          'monthly', '0.7', $today],
    ['blog.php',           'weekly',  '0.7', $today],
    ['contact.php',        'monthly', '0.8', $today],
    ['careers.php',        'monthly', '0.6', $today],
    ['locations/index.php',                    'monthly', '0.8', $today],
    ['locations/easton-painting.php',          'monthly', '0.8', $today],
    ['locations/bethlehem-painting.php',       'monthly', '0.8', $today],
    ['locations/allentown-painting.php',       'monthly', '0.8', $today],
    ['locations/easton-drywall-repair.php',    'monthly', '0.8', $today],
    ['locations/bethlehem-drywall-repair.php', 'monthly', '0.8', $today],
];

$urls = [];
foreach ($pages as [$path, $freq, $priority, $lastmod]) {
    $urls[] = ['loc' => $origin . url($path), 'lastmod' => $lastmod, 'changefreq' => $freq, 'priority' => $priority];
}

// Every published blog post, newest first.
foreach (blog_published() as $post) {
    $ts = strtotime((string) ($post['created_at'] ?? ''));
    $urls[] = [
        'loc'        => $origin . url('blog-post.php?id=' . (int) $post['id']),
        'lastmod'    => $ts ? date('Y-m-d', $ts) : $today,
        'changefreq' => 'monthly',
        'priority'   => '0.6',
    ];
}

// Every gallery photo gets a project detail page.
foreach (gallery_all() as $photo) {
    $ts = strtotime((string) ($photo['created_at'] ?? ''));
    $urls[] = [
        'loc'        => $origin . url('project.php?id=' . (int) $photo['id']),
        'lastmod'    => $ts ? date('Y-m-d', $ts) : $today,
        'changefreq' => 'monthly',
        'priority'   => '0.6',
    ];
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . e($u['loc']) . "</loc>\n";
    echo '    <lastmod>' . e($u['lastmod']) . "</lastmod>\n";
    echo '    <changefreq>' . e($u['changefreq']) . "</changefreq>\n";
    echo '    <priority>' . e($u['priority']) . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
