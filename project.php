<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
require_once __DIR__ . '/includes/blog.php';     // blog_render_body(), blog_date()
require_once __DIR__ . '/includes/gallery.php';  // gallery_find()

$CATS = [
    'interior'   => 'Interior',
    'exterior'   => 'Exterior',
    'drywall'    => 'Drywall',
    'commercial' => 'Commercial',
    'other'      => 'Our work',
];

$id = (int) ($_GET['id'] ?? 0);
$photo = $id ? gallery_find($id) : null;

if (!$photo) {
    http_response_code(404);
    $page_title = 'Project not found';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="mkt">
        <section class="page-hero">
            <div class="page-hero__bg" aria-hidden="true"></div>
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> <a href="<?= e(url('gallery.php')) ?>">Gallery</a><span>/</span> Not found</nav>
                <h1 style="margin-top:1rem">We couldn&apos;t find that project</h1>
                <p>It may have been removed. Browse the full gallery instead.</p>
                <div style="margin-top:2rem"><a href="<?= e(url('gallery.php')) ?>" class="btn btn--slate">Back to the gallery<?= svg_arrow() ?></a></div>
            </div>
        </section>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$caption  = $photo['caption'] ?: 'Recent project';
$catLabel = $CATS[$photo['category']] ?? 'Our work';
$hasBody  = trim((string) ($photo['description'] ?? '')) !== '';

$page_title       = $caption;
$page_description = $hasBody ? mb_substr(trim($photo['description']), 0, 160) : $caption;
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero page-hero--project">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> <a href="<?= e(url('gallery.php')) ?>">Gallery</a><span>/</span> <?= e($catLabel) ?></nav>
            <span class="eyebrow"><?= e($catLabel) ?></span>
            <h1 style="margin-top:1rem"><?= e($caption) ?></h1>
            <p><?= e(blog_date($photo['created_at'])) ?></p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container blog-article">
            <div class="blog-article__img project-figure"><img src="<?= e(url('uploads/gallery/' . $photo['filename'])) ?>" alt="<?= e($caption) ?>"></div>
            <div class="blog-article__body">
                <?php if ($hasBody): ?>
                    <?= blog_render_body($photo['description']) ?>
                <?php else: ?>
                    <p><?= e($caption) ?></p>
                <?php endif; ?>
            </div>
            <div style="margin-top:2rem"><a href="<?= e(url('gallery.php')) ?>" class="btn btn--slate">&larr; Back to the gallery</a></div>
        </div>
    </section>

    <?php mkt_cta_band("Let's talk", 'Ready to start your project?', 'Free estimates across the Lehigh Valley and Bucks County, PA.'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
