<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
$page_title = 'Painting, Drywall & Restoration Services | Lehigh Valley & Bucks County, PA';
$page_description = 'Interior & exterior painting, Level 4 & 5 skim coating, drywall installation & repair, stucco removal, plaster repair, wallcovering removal, and power washing across the Lehigh Valley and Bucks County, PA. Licensed, insured. Free estimates.';
$page_keywords = 'high-end residential painting Lehigh Valley, luxury home painter Bucks County, Level 5 drywall finish, skim coating Lehigh Valley, stucco removal Easton PA, plaster repair Lehigh Valley, wallcovering removal Bucks County, power washing Lehigh Valley, drywall contractor Easton PA, painter Bethlehem PA, architect painting partner Lehigh Valley, interior designer painting Bucks County, general contractor painting subcontractor, facility management painting services';
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> Services</nav>
            <span class="eyebrow">What we offer</span>
            <h1 style="margin-top:1rem">Painting &amp; drywall services, done <span class="ul-brush">right</span>.</h1>
            <p>Full-service prep, repair, and high-end finishing for residential and commercial spaces — including Level&nbsp;5 smooth-wall finishes, skim coating, and fine-finish painting. Serving the Lehigh Valley and Bucks County, PA, from Easton and Bethlehem to New Hope and Doylestown. One crew, start to finish.</p>
            <p>We also partner with architects, interior designers, general contractors, and facility management teams — bringing the same prep, precision, and finish quality to specified projects and ongoing maintenance work.</p>
        </div>
    </section>

    <section class="section section--tight"><div class="container"><?php mkt_service_cards(); ?></div></section>

    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">Signature finishes</span><h2 style="margin-top:1rem">High-end finishing, done by a specialist</h2><p>For luxury homes, the finish is the difference. Explore the premium work that sets our walls apart.</p></div>
            <div class="services-grid">
                <a class="service-card service-card--link" href="<?= e(url('level-5-drywall.php')) ?>"><div class="service-card__icon"><?= svg_check() ?></div><h3>Level 5 Drywall Finish</h3><p>A full skim coat for glass-smooth walls that stay flawless under bright light, gloss, and dark paint.</p><span class="textlink">Learn more<?= svg_arrow() ?></span></a>
                <a class="service-card service-card--link" href="<?= e(url('skim-coating.php')) ?>"><div class="service-card__icon"><?= svg_check() ?></div><h3>Skim Coating</h3><p>Resurface rough, patched, or textured walls into smooth, modern, paint-ready surfaces.</p><span class="textlink">Learn more<?= svg_arrow() ?></span></a>
                <a class="service-card service-card--link" href="<?= e(url('wall-restoration.php')) ?>"><div class="service-card__icon"><?= svg_check() ?></div><h3>Wall Restoration</h3><p>Plaster and drywall repairs that disappear — ideal for luxury and historic homes.</p><span class="textlink">Learn more<?= svg_arrow() ?></span></a>
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container split">
            <div class="split__media"><img src="<?= e(url('assets/img/service-painting.webp')) ?>" alt="Interior painting and trim — crisp white walls with black-finished staircase railings"></div>
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

    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container split split--reverse">
            <div class="split__media"><img src="<?= e(url('assets/img/service-drywall.webp')) ?>" alt="Smooth, flawless drywall finish on a freshly painted room"></div>
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
    <style>
        .mkt .service-card--link { display:flex; flex-direction:column; text-decoration:none; color:inherit;
            cursor:pointer; transition:transform .15s ease, box-shadow .15s ease; }
        .mkt .service-card--link:hover { transform:translateY(-3px); box-shadow:0 14px 34px rgba(15,27,51,.12); }
        .mkt .service-card--link .textlink { margin-top:auto; padding-top:1rem; }
    </style>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
