<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
require_once __DIR__ . '/includes/gallery.php';

$page_title = 'Portfolio';
$page_description = 'A curated look at our best painting and drywall work — Level 5 finishes, historic restorations, and premium commercial projects across the Lehigh Valley and Bucks County, PA.';
require __DIR__ . '/includes/header.php';

$featured = array_values(array_filter(gallery_recent(), fn($p) => (int) $p['featured'] === 1));
$category_labels = ['interior' => 'Interior Painting', 'exterior' => 'Exterior Painting', 'drywall' => 'Drywall & Plaster', 'commercial' => 'Commercial', 'other' => 'Project'];
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> Portfolio</nav>
            <span class="eyebrow">Our best work</span>
            <h1 style="margin-top:1rem">A closer look at <span class="ul-brush">the craft</span>.</h1>
            <p>A hand-picked selection of our favorite projects — the ones that best show what careful prep, premium materials, and a steady hand can do. See the full archive in our <a href="<?= e(url('gallery.php')) ?>">project gallery</a>.</p>
        </div>
    </section>

    <?php if (!$featured): ?>
    <section class="section section--tight">
        <div class="container">
            <p class="center" style="color:var(--muted)">Nothing marked as featured yet — check back soon, or browse the full <a href="<?= e(url('gallery.php')) ?>">gallery</a>.</p>
        </div>
    </section>
    <?php else: ?>
    <section class="section">
        <div class="container">
            <?php foreach ($featured as $i => $p): ?>
                <div class="split<?= $i % 2 === 1 ? ' split--reverse' : '' ?>">
                    <div class="split__media">
                        <img src="<?= e(url('uploads/gallery/' . $p['filename'])) ?>" alt="<?= e($p['caption'] ?: 'Featured project') ?>">
                    </div>
                    <div class="split__body">
                        <span class="eyebrow"><?= sprintf('%02d', $i + 1) ?> / <?= e($category_labels[$p['category']] ?? 'Project') ?></span>
                        <h2 style="margin-top:1rem"><?= e($p['caption'] ?: 'Featured project') ?></h2>
                        <?php if ($p['description']): ?><p><?= e($p['description']) ?></p><?php endif; ?>
                        <p style="margin-top:1.5rem"><a href="<?= e(url('project.php?id=' . (int) $p['id'])) ?>" class="btn btn--outline">View full project<?= svg_arrow() ?></a></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php mkt_cta_band('Your project next', 'See your home or business featured here.', 'Free estimates across the Lehigh Valley and Bucks County, PA.'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
