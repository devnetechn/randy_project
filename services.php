<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
$page_title = 'Painting & Drywall Services in Easton, PA & the Lehigh Valley';
$page_description = 'Interior & exterior painting, drywall installation, and drywall repair in Easton, PA and across the Lehigh Valley — Bethlehem, Allentown, Nazareth and within 25 miles. Free estimates from a licensed, insured crew.';
$page_keywords = 'interior painting Easton PA, exterior painting Lehigh Valley, drywall installation Lehigh Valley, drywall repair Easton PA, painting contractor Lehigh Valley, commercial painting Easton';
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> Services</nav>
            <span class="eyebrow">What we offer</span>
            <h1 style="margin-top:1rem">Painting &amp; drywall services, done <span class="ul-brush">right</span>.</h1>
            <p>Full-service prep, repair, and finishing for residential and commercial spaces in Easton, PA and across the Lehigh Valley — Bethlehem, Allentown, Nazareth and everywhere within 25 miles. One crew, start to finish.</p>
        </div>
    </section>

    <section class="section section--tight"><div class="container"><?php mkt_service_cards(); ?></div></section>

    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container split">
            <div class="split__media"><div class="ph ph--coral"><span class="ph__tag">Painting project</span></div></div>
            <div class="split__body">
                <span class="eyebrow">Painting</span>
                <h2 style="margin-top:1rem">Interior &amp; exterior painting across the Lehigh Valley</h2>
                <p>Great paint work is mostly preparation. From Easton to Bethlehem and Allentown, we fill, sand, prime, and protect before a brush ever touches your walls — so the color goes on even and lasts for years.</p>
                <ul class="split__list">
                    <li><?= svg_circle_check() ?><div><strong>Thorough surface prep</strong><span>Patching, caulking, sanding, and priming for a flawless base.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Color consultation</strong><span>Not sure on a shade? We'll help you choose with confidence.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Premium paints</strong><span>Trusted brands with low-VOC, washable, long-lasting finishes.</span></div></li>
                </ul>
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container split split--reverse">
            <div class="split__media"><div class="ph ph--cool"><span class="ph__tag">Drywall project</span></div></div>
            <div class="split__body">
                <span class="eyebrow">Drywall</span>
                <h2 style="margin-top:1rem">Drywall installation &amp; repair in Easton &amp; the Lehigh Valley</h2>
                <p>From a single ding to a full basement build-out, we hang, tape, and finish drywall so smoothly that no one will ever know it was touched — serving homes and businesses throughout the Lehigh Valley.</p>
                <ul class="split__list">
                    <li><?= svg_circle_check() ?><div><strong>New installation</strong><span>Additions, basements, garages, and remodels finished to spec.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Invisible patching</strong><span>Texture matched and blended so repairs disappear.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Dust-conscious work</strong><span>We contain and clean up dust to keep your space livable.</span></div></li>
                </ul>
            </div>
        </div>
    </section>

    <?php mkt_faq(); ?>
    <?php mkt_cta_band('No-pressure quote', 'Tell us about your project.', "Share a few details and we'll get back to you with a free estimate."); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
