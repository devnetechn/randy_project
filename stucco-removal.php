<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
$page_title = 'Stucco Removal | Lehigh Valley & Bucks County, PA';
$page_description = 'Professional stucco removal across the Lehigh Valley and Bucks County, PA. Exterior and interior stucco removal service and texture removal, with the surface prepped clean for a modern repaint or skim coat. Free estimates.';
$page_keywords = 'stucco removal Lehigh Valley, stucco removal Easton PA, stucco removal service Nazareth PA, stucco texture removal Upper Makefield PA, exterior stucco removal Bucks County, interior texture removal Bethlehem PA';
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span><a href="<?= e(url('services.php')) ?>">Services</a><span>/</span> Stucco Removal</nav>
            <span class="eyebrow">Exterior &amp; interior stucco removal</span>
            <h1 style="margin-top:1rem">Stucco removal, <span class="ul-brush">cleanly</span> done</h1>
            <p>Old, failing, or unwanted stucco removed from exteriors and interior walls, with the surface prepped for a clean, modern finish. Serving homes and businesses across the Lehigh Valley and Bucks County, PA.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container split">
            <div class="split__media"><img src="<?= e(url('assets/img/service-wall-restoration.webp')) ?>" alt="Stucco removal in progress — old exterior texture stripped back to a clean, paint-ready surface"></div>
            <div class="split__body">
                <span class="eyebrow">What it is</span>
                <h2 style="margin-top:1rem">Full removal, not just a patch over it</h2>
                <p>Stucco removal means stripping old, cracked, or dated stucco texture back to a clean substrate — exterior siding or interior wall — so it can be repainted, resurfaced, or skim coated into a modern smooth finish. Done right, it also means checking what is underneath and repairing any damage stucco has been hiding before the new finish goes on.</p>
                <ul class="split__list">
                    <li><?= svg_circle_check() ?><div><strong>Exterior stucco removal</strong><span>Failing or unwanted exterior stucco stripped back for a modern siding or paint finish.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Interior texture removal</strong><span>Dated stucco or heavy texture removed from interior walls and ceilings.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Surface prep included</strong><span>Substrate cleaned and repaired so it's ready for repaint or a skim coat.</span></div></li>
                </ul>
            </div>
        </div>
    </section>

    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container">
            <div class="section-head"><span class="eyebrow">Why remove it</span><h2 style="margin-top:1rem">When stucco removal makes sense</h2></div>
            <div class="services-grid">
                <article class="service-card"><div class="service-card__icon"><?= svg_check() ?></div><h3>Cracking or failing stucco</h3><p>Stucco that's cracked, chipped, or pulling away from the substrate traps moisture and gets worse every season — removal stops the damage from spreading.</p></article>
                <article class="service-card"><div class="service-card__icon"><?= svg_check() ?></div><h3>Dated exterior texture</h3><p>Heavy stucco texture can read as dated next to modern siding, trim, and paint colors. Removing it opens up a cleaner, more current look.</p></article>
                <article class="service-card"><div class="service-card__icon"><?= svg_check() ?></div><h3>Prepping for a new finish</h3><p>Whether you're switching to a smooth exterior coating or skim coating an interior wall, stucco has to come off first for the new finish to bond and last.</p></article>
            </div>
        </div>
    </section>

    <?php mkt_faq_custom([
        ['Do you remove stucco from both exteriors and interior walls?', 'Yes. We handle exterior stucco removal from siding and walls, as well as interior stucco and heavy-texture removal, prepping either surface for whatever finish comes next.'],
        ['What happens to the wall after the stucco is removed?', 'We clean and repair the substrate underneath — patching any damage the stucco was hiding — so it\'s ready for repaint, resurfacing, or a full skim coat.'],
        ['Is stucco removal messy?', 'It generates dust and debris, so we contain the work area and clean up thoroughly each day to keep the rest of your property livable.'],
        ['Do you offer stucco removal across the Lehigh Valley and Bucks County?', 'Yes. We serve homes and businesses throughout the region, including Easton, Nazareth, Bethlehem, and Upper Makefield. Estimates are always free.'],
    ], 'Stucco removal — common questions'); ?>

    <?php mkt_service_jsonld('Stucco Removal', $page_description); ?>

    <section class="section section--tight">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">Related finishes</span><h2 style="margin-top:1rem">Explore our high-end finishing</h2></div>
            <div class="center" style="display:flex;gap:1rem;flex-wrap:wrap;justify-content:center">
                <a href="<?= e(url('skim-coating.php')) ?>" class="btn btn--outline">Skim Coating</a>
                <a href="<?= e(url('wall-restoration.php')) ?>" class="btn btn--outline">Wall Restoration</a>
                <a href="<?= e(url('services.php')) ?>" class="btn btn--outline">All Services</a>
            </div>
        </div>
    </section>

    <?php mkt_cta_band('Ready to remove old stucco', 'Get a stucco removal estimate.', 'Free, no-pressure quotes across the Lehigh Valley and Bucks County, PA. Most returned within 24 hours.'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
