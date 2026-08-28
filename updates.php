<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
require_once __DIR__ . '/includes/gallery.php';

$page_title = 'Project Updates';
$page_description = 'Recently completed painting and drywall projects from Randy\'s Painting & Drywall Services, across the Lehigh Valley and Bucks County, PA.';
require __DIR__ . '/includes/header.php';

$photos = gallery_recent();
$category_labels = ['interior' => 'Interior', 'exterior' => 'Exterior', 'drywall' => 'Drywall', 'commercial' => 'Commercial', 'other' => 'Project'];
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> Project Updates</nav>
            <span class="eyebrow">Fresh off the job site</span>
            <h1 style="margin-top:1rem">Our latest <span class="ul-brush">project updates</span>.</h1>
            <p>Every completed project, newest first — real homes and businesses across the Lehigh Valley and Bucks County, PA.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <?php if (!$photos): ?>
                <p class="center" style="color:var(--muted)">No project updates yet. Check back soon.</p>
            <?php else: ?>
                <div class="blog-grid">
                    <?php foreach ($photos as $p): ?>
                        <a class="blog-card" href="<?= e(url('project.php?id=' . (int) $p['id'])) ?>">
                            <div class="blog-card__img">
                                <img src="<?= e(url('uploads/gallery/' . $p['filename'])) ?>" alt="<?= e($p['caption'] ?: 'Completed project photo') ?>" loading="lazy">
                            </div>
                            <div class="blog-card__body">
                                <div class="blog-card__date"><?= e(date('F j, Y', strtotime($p['created_at']))) ?> &middot; <?= e($category_labels[$p['category']] ?? 'Project') ?></div>
                                <h2 class="blog-card__title"><?= e($p['caption'] ?: 'Recent project') ?></h2>
                                <?php if ($p['description']): ?><p class="blog-card__excerpt"><?= e(mb_strimwidth($p['description'], 0, 160, '…')) ?></p><?php endif; ?>
                                <span class="blog-card__more">View project<?= svg_arrow() ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php mkt_cta_band('Your project next', 'See your home or business here.', 'Free estimates across the Lehigh Valley and Bucks County, PA.'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
