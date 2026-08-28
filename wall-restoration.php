<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
$page_title = 'Wall Restoration & Plaster Repair | Lehigh Valley & Bucks County, PA';
$page_description = 'Expert wall restoration and plaster repair across the Lehigh Valley and Bucks County, PA. We diagnose, re-secure, and seamlessly match damaged plaster and drywall in luxury and historic homes — repairs that disappear. Free estimates.';
$page_keywords = 'wall restoration Lehigh Valley, plaster repair Easton PA, drywall restoration specialist Easton PA, historic home plaster repair Bucks County, plaster crack repair Bethlehem PA, water damage wall repair New Hope PA';
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span><a href="<?= e(url('services.php')) ?>">Services</a><span>/</span> Wall Restoration</nav>
            <span class="eyebrow">Restoration &amp; plaster repair</span>
            <h1 style="margin-top:1rem">Wall restoration that <span class="ul-brush">disappears</span></h1>
            <p>From cracked plaster in a historic home to water-damaged drywall, we restore walls so the repair vanishes into the surrounding surface. Serving luxury and period homes across the Lehigh Valley and Bucks County, PA.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container split">
            <div class="split__media"><img src="<?= e(url('assets/img/service-wall-restoration.webp')) ?>" alt="Wall restoration in progress — old wallpaper stripped and the surface prepped for a smooth finish"></div>
            <div class="split__body">
                <span class="eyebrow">What it is</span>
                <h2 style="margin-top:1rem">More than a patch — a proper repair</h2>
                <p>Wall restoration is rarely just filling a hole. It is diagnosing why the wall failed, re-securing loose plaster to the lath, matching the original profile and texture, and finishing it so the repair blends seamlessly. In a high-end or historic home, an invisible repair preserves the very character you are trying to protect.</p>
                <ul class="split__list">
                    <li><?= svg_circle_check() ?><div><strong>Plaster crack &amp; key repair</strong><span>We re-secure separated plaster and bridge cracks so they stay closed.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Water-damage restoration</strong><span>Stains sealed and damaged material rebuilt — not just painted over.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Seamless texture matching</strong><span>New work feathered and matched so it disappears under raking light.</span></div></li>
                </ul>
            </div>
        </div>
    </section>

    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container">
            <div class="section-head"><span class="eyebrow">Signs to watch for</span><h2 style="margin-top:1rem">When a wall needs professional restoration</h2></div>
            <div class="services-grid">
                <article class="service-card"><div class="service-card__icon"><?= svg_check() ?></div><h3>Cracks that keep returning</h3><p>Diagonal cracks from door and window corners, or cracks that reopen after patching, point to movement or a failing repair — not a cosmetic fix.</p></article>
                <article class="service-card"><div class="service-card__icon"><?= svg_check() ?></div><h3>Spongy or hollow plaster</h3><p>If the wall flexes or sounds hollow, the plaster has separated from the lath and must be re-secured before any surface repair will last.</p></article>
                <article class="service-card"><div class="service-card__icon"><?= svg_check() ?></div><h3>Stains &amp; crumbling</h3><p>Brown or yellow staining signals water intrusion; powdery, crumbling plaster has deteriorated beyond a simple patch.</p></article>
            </div>
        </div>
    </section>

    <?php mkt_faq_custom([
        ['Can you repair plaster walls, not just drywall?', 'Yes. We restore both plaster and drywall. Plaster repair often involves re-securing the plaster to the lath, bridging cracks properly, and matching the original texture — work that takes the right materials and an experienced hand, which is why amateur patches so often fail.'],
        ['Will the repair be visible after painting?', 'Done correctly, no. The goal of wall restoration is an invisible repair — we feather and texture-match the new work so it blends into the surrounding wall and disappears under light. For larger areas we may skim coat the wall for a perfectly uniform surface.'],
        ['My wall has a water stain. Can you just paint over it?', 'Painting over a stain without sealing it and addressing the source guarantees it will bleed back through. We seal the stain, restore any damaged material, and only then refinish — so the repair lasts.'],
        ['Do you restore walls in historic homes?', 'Yes. Many of the finest homes across the Lehigh Valley and Bucks County, PA have original plaster, and we restore them with care to preserve their character — from New Hope and Doylestown to Easton and Bethlehem. Estimates are free.'],
    ], 'Wall restoration — common questions'); ?>

    <?php mkt_service_jsonld('Wall Restoration & Plaster Repair', $page_description); ?>

    <section class="section section--tight">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">Related finishes</span><h2 style="margin-top:1rem">Explore our high-end finishing</h2></div>
            <div class="center" style="display:flex;gap:1rem;flex-wrap:wrap;justify-content:center">
                <a href="<?= e(url('level-5-drywall.php')) ?>" class="btn btn--outline">Level 5 Drywall</a>
                <a href="<?= e(url('skim-coating.php')) ?>" class="btn btn--outline">Skim Coating</a>
                <a href="<?= e(url('stucco-removal.php')) ?>" class="btn btn--outline">Stucco Removal</a>
                <a href="<?= e(url('services.php')) ?>" class="btn btn--outline">All Services</a>
            </div>
        </div>
    </section>

    <?php mkt_cta_band('Ready to restore your walls', 'Get a restoration estimate.', 'Free, no-pressure quotes across the Lehigh Valley and Bucks County, PA. Most returned within 24 hours.'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
