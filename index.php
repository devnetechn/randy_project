<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
$page_title = 'High-End Painting & Level 5 Drywall Finishes | Lehigh Valley & Bucks County, PA';
$hero = url('assets/img/gallery/gallery-10.webp');
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="hero">
        <div class="hero__bg" aria-hidden="true"></div>
        <div class="container hero__grid">
            <div class="hero__copy">
                <span class="eyebrow">Licensed &amp; Insured · 35+ Years · Lehigh Valley &amp; Bucks County, PA</span>
                <h1>Walls done<br>with a <span class="mark mark--ink">flawless</span> finish.</h1>
                <p class="hero__slogan">&ldquo;We build our reputation, one coat at a time.&rdquo;</p>
                <p class="hero__lead">Premium painting and drywall craftsmanship for discerning homeowners and businesses &mdash; including Level&nbsp;5 smooth-wall finishes, skim coating, and fine-finish painting. With 35+ years near Easton, PA, Randy&apos;s Painting &amp; Drywall Services delivers meticulous, on-time work across the Lehigh Valley and Bucks County, PA.</p>
                <div class="hero__cta">
                    <a href="<?= e(url('book.php')) ?>" class="btn btn--lg">Get a Free Quote<?= svg_arrow() ?></a>
                    <a href="<?= e(url('gallery.php')) ?>" class="btn btn--outline btn--lg">See Our Work</a>
                </div>
                <ul class="hero__trust">
                    <li class="t"><?= svg_circle_check() ?>Free estimates</li>
                    <li class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Licensed &amp; insured</li>
                    <li class="t"><svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 2l2.9 6 6.6.5-5 4.3 1.5 6.4L12 16.9 6 19.2l1.5-6.4-5-4.3 6.6-.5z"/></svg>5-star rated</li>
                </ul>
            </div>
            <div class="hero__visual">
                <div class="hero__frame"><img src="<?= e($hero) ?>" alt="Freshly painted interior by Randy's Painting & Drywall"></div>
                <div class="hero__chips" aria-hidden="true">
                    <span style="background:#d8322b"></span><span style="background:#1f56c4"></span><span style="background:#123075"></span><span style="background:#f3f6fc"></span>
                </div>
                <div class="hero__badge"><span class="num">35<span style="color:var(--coral)">+</span></span><span class="lbl">Years of<br>experience</span></div>
            </div>
        </div>
    </section>

    <div class="marquee" aria-hidden="true">
        <div class="marquee__track">
            <?php for ($i = 0; $i < 2; $i++): ?>
                <span class="m">Interior Painting</span><span class="m">Fine-Finish Painting</span><span class="m">Level 5 Drywall</span>
                <span class="m">Skim Coating</span><span class="m">Wall Restoration</span><span class="m">Drywall Repair</span>
                <span class="m">Exterior Painting</span><span class="m">Commercial Projects</span>
            <?php endfor; ?>
        </div>
    </div>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">What we do</span>
                <h2 style="margin-top:1rem">Two trades, <span class="ul-brush">one crew</span> you can trust.</h2>
                <p>Whether you need a single room refreshed or a full repaint with drywall repair, we handle prep, patch, and paint — all under one roof.</p>
            </div>
            <?php mkt_service_cards(); ?>
            <div class="center" style="margin-top:2.5rem"><a href="<?= e(url('services.php')) ?>" class="textlink">Explore all services<?= svg_arrow() ?></a></div>
        </div>
    </section>

    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container split">
            <div class="split__media"><img src="<?= e(url('assets/img/front.jpg')) ?>" alt="Covered outdoor lanai with a stained tongue-and-groove wood ceiling and crisp white columns by Randy's Painting &amp; Drywall"></div>
            <div class="split__body">
                <span class="eyebrow">Why Randy&apos;s</span>
                <h2 style="margin-top:1rem">Tidy job sites. Honest pricing. Work that lasts.</h2>
                <p>We treat your home like our own — drop cloths down, edges taped, and a full clean-up before we leave. No surprise fees, no rushed corners.</p>
                <ul class="split__list">
                    <li><?= svg_circle_check() ?><div><strong>Detailed written estimates</strong><span>Know the full scope and cost before we start — no hidden extras.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Premium, low-VOC materials</strong><span>Durable finishes that look great and are safer for your family.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Workmanship guarantee</strong><span>We stand behind every project and make it right if it isn't.</span></div></li>
                </ul>
                <div style="margin-top:2rem"><a href="<?= e(url('about.php')) ?>" class="btn btn--slate">More about us<?= svg_arrow() ?></a></div>
            </div>
        </div>
    </section>

    <section class="section section--tight"><div class="container"><?php mkt_stats_band(); ?></div></section>

    <section class="section section--tight">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">How it works</span><h2 style="margin-top:1rem">A simple, four-step process</h2></div>
            <?php mkt_process_steps(); ?>
        </div>
    </section>

    <section class="section" style="background:var(--plaster-2)">
        <div class="container">
            <div class="section-head"><span class="eyebrow">Recent work</span><h2 style="margin-top:1rem">Before &amp; after, side by side.</h2><p>Drag the slider to see the transformation. Real results from real Easton-area homes.</p></div>
            <?php mkt_before_after(); ?>
            <div class="center" style="margin-top:2.5rem"><a href="<?= e(url('gallery.php')) ?>" class="btn btn--slate">View full gallery<?= svg_arrow() ?></a></div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">Kind words</span><h2 style="margin-top:1rem">Homeowners &amp; businesses who trust us</h2></div>
            <?php mkt_testimonials(); ?>
            <?php mkt_review_schema(); ?>
        </div>
    </section>

    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container">
            <div class="section-head center">
                <span class="eyebrow" style="justify-content:center">Areas we serve</span>
                <h2 style="margin-top:1rem">Serving the Lehigh Valley &amp; Bucks County, PA</h2>
                <p>Based in Easton, PA, Randy&apos;s Painting &amp; Drywall Services brings premium interior &amp; exterior painting, Level&nbsp;5 drywall finishes, and wall restoration to homeowners and businesses across the Lehigh Valley and the affluent communities of Bucks County &mdash; from Northampton and Lehigh County to New Hope, Doylestown, and Upper Makefield.</p>
            </div>
            <ul class="areas">
                <?php foreach ([
                    'Easton, PA', 'Bethlehem, PA', 'Allentown, PA', 'Nazareth, PA',
                    'Wilson, PA', 'Palmer Township, PA', 'Forks Township, PA', 'Hellertown, PA',
                    'New Hope, PA', 'Doylestown, PA', 'Solebury Township, PA', 'Upper Makefield, PA',
                    'Newtown, PA', 'Yardley, PA', 'Wind Gap, PA', 'Phillipsburg, NJ',
                ] as $area): ?>
                    <li class="area"><?= svg_circle_check() ?><?= e($area) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="center" style="margin-top:2rem;color:var(--muted)">Don&apos;t see your town? If you&apos;re in the Lehigh Valley or Bucks County, we&apos;ve likely got you covered &mdash; <a href="<?= e(url('contact.php')) ?>" class="textlink">just ask<?= svg_arrow() ?></a></p>
        </div>
    </section>
    <style>
        .mkt .areas { list-style:none; margin:0; padding:0; display:grid; gap:.75rem;
            grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); }
        .mkt .area { display:flex; align-items:center; gap:.6rem; background:#fff;
            border:1px solid var(--plaster-3); border-radius:12px; padding:.85rem 1rem;
            font-weight:600; color:var(--ink); }
        .mkt .area svg { width:20px; height:20px; flex:none; color:var(--coral); }
    </style>

    <?php mkt_cta_band('Ready when you are', "Let's give your walls a flawless finish.", 'Free, no-pressure estimates across the Lehigh Valley and Bucks County, PA. Most quotes returned within 24 hours.'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
