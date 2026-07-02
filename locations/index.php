<?php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/locations.php';
$page_title = 'Service Areas | Randy\'s Painting & Drywall Services';
$page_description = 'Painting and drywall repair across the Lehigh Valley: Easton, Bethlehem, and Allentown, PA. Licensed, insured, free estimates.';
require __DIR__ . '/../includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> Service Areas</nav>
            <span class="eyebrow">Where we work</span>
            <h1 style="margin-top:1rem">Painting &amp; drywall across the <span class="ul-brush">Lehigh Valley</span>.</h1>
            <p>Pick your city below for local details, real reviews, and a free estimate.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <div class="services-grid">
                <?php foreach ($LOCATIONS as $slug => $loc): ?>
                    <a class="service-card service-card--link" href="<?= e(url('locations/' . $slug . '.php')) ?>">
                        <div class="service-card__icon"><?= svg_check() ?></div>
                        <h3><?= e($loc['service_label']) ?> — <?= e($loc['city']) ?>, PA</h3>
                        <p><?= e($loc['hero_intro']) ?></p>
                        <span class="textlink">Learn more<?= svg_arrow() ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php mkt_cta_band('Not sure which city?', 'Get a free estimate anywhere in our service area.', 'We serve the Lehigh Valley and Bucks County, PA — reach out and we\'ll confirm coverage for your address.'); ?>
    <style>
        .mkt .service-card--link { display:flex; flex-direction:column; text-decoration:none; color:inherit;
            cursor:pointer; transition:transform .15s ease, box-shadow .15s ease; }
        .mkt .service-card--link:hover { transform:translateY(-3px); box-shadow:0 14px 34px rgba(15,27,51,.12); }
        .mkt .service-card--link .textlink { margin-top:auto; padding-top:1rem; }
    </style>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
