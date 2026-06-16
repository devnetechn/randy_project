<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
$page_title = 'Gallery';
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> Gallery</nav>
            <span class="eyebrow">Our work</span>
            <h1 style="margin-top:1rem">Proof in every <span class="ul-brush">coat</span>.</h1>
            <p>A look at recent painting and drywall projects across the Lehigh Valley and Bucks County, PA. Real results from real homes and businesses.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <?php mkt_before_after(); ?>
            <p class="center" style="margin-top:1rem;color:var(--muted);font-size:.92rem">Drag the handle to compare · Living room repaint — coffered ceiling, walls &amp; trim</p>
        </div>
    </section>

    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">Project gallery</span><h2 style="margin-top:1rem">Our Work — Browse by category</h2></div>
            <div class="gallery-filters" data-gallery-filters>
                <?php foreach (['all' => 'All', 'interior' => 'Interior', 'exterior' => 'Exterior', 'drywall' => 'Drywall', 'commercial' => 'Commercial'] as $k => $label): ?>
                    <button type="button" class="filter-btn<?= $k === 'all' ? ' is-active' : '' ?>" data-filter="<?= e($k) ?>"><?= e($label) ?></button>
                <?php endforeach; ?>
            </div>
            <div class="gallery-grid" data-gallery-grid></div>
            <p class="center" data-gallery-empty style="color:var(--muted);display:none">No photos yet. Check back soon for our latest projects.</p>
        </div>
    </section>

    <?php mkt_cta_band('Your project next', 'Picture your space, transformed.', "Let's add your home or business to the gallery. Free estimates across the Lehigh Valley and Bucks County, PA."); ?>
</div>
<script src="<?= e(url('assets/js/gallery.js')) ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
