<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
$page_title = 'Commercial Painting & Skim Coating | Lehigh Valley & Bucks County, PA';
$page_description = 'Commercial interior and exterior painting, Level 4 & Level 5 skim coating, stucco removal, plaster repair, wallcovering removal, and power washing for properties across the Lehigh Valley and Bucks County, PA. Licensed, insured. Free estimates.';
$page_keywords = 'commercial painting Lehigh Valley, commercial interior painting Easton PA, commercial exterior painting Bucks County, Level 4 skim coating commercial, Level 5 skim coating commercial, stucco removal Lehigh Valley, plaster repair Easton PA, wallcovering removal Bucks County, power washing commercial Lehigh Valley, office painting Bethlehem PA';
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> Commercial</nav>
            <span class="eyebrow">For businesses &amp; property owners</span>
            <h1 style="margin-top:1rem">Commercial painting &amp; skim coating — <span class="ul-brush">built for business</span>.</h1>
            <p>Interior and exterior painting plus Level 4 and Level 5 skim coating for offices, retail, multi-unit buildings, and commercial properties across the Lehigh Valley and Bucks County, PA. Professional results, on your schedule. Licensed, insured, free estimates.</p>
        </div>
    </section>

    <!-- Why choose Randy's for commercial -->
    <section class="section section--tight">
        <div class="container split">
            <div class="split__media"><img src="<?= e(url('assets/img/service-painting.webp')) ?>" alt="Professional commercial interior painting — clean lines on a freshly painted office wall"></div>
            <div class="split__body">
                <span class="eyebrow">Why property owners trust us</span>
                <h2 style="margin-top:1rem">Professional results that protect your investment</h2>
                <p>Commercial properties take more punishment than residential ones — and they represent your business or your tenants' businesses. We treat every commercial job with the same attention to detail we bring to luxury homes: thorough prep, quality materials, and a finish that holds up long-term.</p>
                <ul class="split__list">
                    <li><?= svg_circle_check() ?><div><strong>Licensed &amp; insured</strong><span>Fully covered so your property and your tenants are protected on every job.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Minimal disruption</strong><span>We work around your hours — including evenings and weekends — so business keeps running.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Honest, written quotes</strong><span>Clear pricing upfront. No surprise costs when the job is done.</span></div></li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Core commercial services -->
    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">What we do</span><h2 style="margin-top:1rem">Commercial services we offer</h2><p>One reliable crew covering everything your commercial property needs — from a fresh coat to full surface restoration.</p></div>
            <div class="services-grid">
                <article class="service-card">
                    <div class="service-card__icon"><?= svg_check() ?></div>
                    <h3>Commercial Interior Painting</h3>
                    <p>Walls, ceilings, trim, and accent features finished to a professional standard — with minimal disruption to your tenants, customers, or staff. We work around your hours so business keeps running.</p>
                    <ul>
                        <li><?= svg_check() ?>Offices &amp; corporate spaces</li>
                        <li><?= svg_check() ?>Retail stores &amp; showrooms</li>
                        <li><?= svg_check() ?>Clinics, banks &amp; multi-unit buildings</li>
                    </ul>
                </article>
                <article class="service-card">
                    <div class="service-card__icon"><?= svg_check() ?></div>
                    <h3>Commercial Exterior Painting</h3>
                    <p>Weather-resistant coatings that protect your building and keep it looking sharp — from storefronts and siding to fascia, soffits, and trim. First impressions matter for your business.</p>
                    <ul>
                        <li><?= svg_check() ?>Storefronts &amp; building facades</li>
                        <li><?= svg_check() ?>Siding, stucco &amp; masonry</li>
                        <li><?= svg_check() ?>Trim, fascia &amp; soffits</li>
                    </ul>
                </article>
                <article class="service-card">
                    <div class="service-card__icon"><?= svg_check() ?></div>
                    <h3>Level 4 &amp; Level 5 Skim Coating</h3>
                    <p>For commercial spaces that demand a premium wall finish — lobbies, showrooms, upscale offices, and luxury condos. Glass-smooth walls that stay flawless under bright light and high-sheen paint.</p>
                    <ul>
                        <li><?= svg_check() ?>Level 4 — standard commercial grade</li>
                        <li><?= svg_check() ?>Level 5 — premium smooth-wall finish</li>
                        <li><?= svg_check() ?>Resurface &amp; upgrade existing walls</li>
                    </ul>
                </article>
                <article class="service-card">
                    <div class="service-card__icon"><?= svg_check() ?></div>
                    <h3>Stucco Removal</h3>
                    <p>We remove old, failing, or unwanted stucco from commercial exteriors and interior walls — preparing the surface for a clean, modern finish that lasts.</p>
                    <ul>
                        <li><?= svg_check() ?>Exterior stucco removal</li>
                        <li><?= svg_check() ?>Interior stucco &amp; texture removal</li>
                        <li><?= svg_check() ?>Surface prep for repaint or skim coat</li>
                    </ul>
                </article>
                <article class="service-card">
                    <div class="service-card__icon"><?= svg_check() ?></div>
                    <h3>Plaster Repair &amp; Wallcovering Removal</h3>
                    <p>Cracked or damaged plaster patched and blended seamlessly. Wallpaper and wallcovering stripped cleanly — walls left smooth and ready for paint or skim coat.</p>
                    <ul>
                        <li><?= svg_check() ?>Plaster crack &amp; damage repair</li>
                        <li><?= svg_check() ?>Wallpaper &amp; wallcovering removal</li>
                        <li><?= svg_check() ?>Wall prep for painting or resurfacing</li>
                    </ul>
                </article>
                <article class="service-card">
                    <div class="service-card__icon"><?= svg_check() ?></div>
                    <h3>Power Washing</h3>
                    <p>High-pressure washing for commercial and residential properties — removing dirt, mold, algae, and grime from exteriors, driveways, sidewalks, and siding before painting or as standalone maintenance.</p>
                    <ul>
                        <li><?= svg_check() ?>Building exteriors &amp; siding</li>
                        <li><?= svg_check() ?>Driveways, sidewalks &amp; parking areas</li>
                        <li><?= svg_check() ?>Pre-paint surface preparation</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <!-- Skim coating split -->
    <section class="section section--tight">
        <div class="container split split--reverse">
            <div class="split__media"><img src="<?= e(url('assets/img/service-level5.webp')) ?>" alt="Level 5 smooth skim coat finish on a commercial lobby wall"></div>
            <div class="split__body">
                <span class="eyebrow">Premium wall finishing</span>
                <h2 style="margin-top:1rem">Level 4 &amp; 5 skim coating for commercial spaces</h2>
                <p>Most commercial painters stop at Level 4. Randy's goes further — offering full Level 5 skim coating for commercial spaces where the finish is part of the brand. Lobbies, executive offices, luxury condos, and high-end showrooms all benefit from walls that look flawless, not just freshly painted.</p>
                <ul class="split__list">
                    <li><?= svg_circle_check() ?><div><strong>Level 4 — commercial standard</strong><span>Clean, professional finish suitable for most offices, retail, and tenant spaces.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Level 5 — premium smooth-wall</strong><span>Full skim coat over the entire surface. Flawless under raking light, high-sheen, and dark paint.</span></div></li>
                    <li><?= svg_circle_check() ?><div><strong>Resurface existing walls</strong><span>We can upgrade rough or patched walls to a Level 4 or 5 finish before painting.</span></div></li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Projects / gallery teaser -->
    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">Our work</span><h2 style="margin-top:1rem">Commercial projects we&apos;ve completed</h2><p>See the quality we bring to commercial jobs across the Lehigh Valley and Bucks County.</p></div>
            <div class="center" style="margin-top:2rem">
                <a href="<?= e(url('gallery.php')) ?>" class="btn btn--primary">View our gallery</a>
            </div>
        </div>
    </section>

    <?php mkt_faq_custom([
        ['What types of commercial properties do you paint?', 'We work on offices, retail stores, clinics, banks, apartment buildings, condominiums, lobbies, and HOA-managed properties throughout the Lehigh Valley and Bucks County, PA. Interior and exterior — call us and we\'ll tell you honestly if we\'re the right fit.'],
        ['What is the difference between Level 4 and Level 5 skim coating?', 'Level 4 coats only the taped joints and fasteners — the commercial standard for most offices and retail spaces. Level 5 adds a full skim coat over the entire wall surface, eliminating any texture variation so the finish is glass-smooth. Level 5 is ideal for lobbies, showrooms, executive offices, and anywhere high-sheen or dark paint will be used.'],
        ['Can you skim coat existing commercial walls before painting?', 'Yes. If your walls are rough, patched, or uneven, we can apply a skim coat to bring them up to a Level 4 or Level 5 finish before painting — so the final result looks intentional, not like a repaint.'],
        ['Can you work outside of regular business hours?', 'Yes. We regularly schedule commercial jobs in the evenings or on weekends to minimize disruption to tenants and customers. Let us know your constraints when you request a quote and we\'ll build the schedule around you.'],
        ['Do you offer free estimates for commercial projects?', 'Yes, always. We visit the property, assess the scope, and send a clear written quote — no obligation. Most commercial estimates are returned within 24–48 hours.'],
    ], 'Commercial services — common questions'); ?>

    <?php mkt_service_jsonld('Commercial Painting & Skim Coating', $page_description); ?>

    <section class="section section--tight">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">Related services</span><h2 style="margin-top:1rem">More from Randy&apos;s</h2></div>
            <div class="center" style="display:flex;gap:1rem;flex-wrap:wrap;justify-content:center">
                <a href="<?= e(url('services.php')) ?>" class="btn btn--outline">All Services</a>
                <a href="<?= e(url('level-5-drywall.php')) ?>" class="btn btn--outline">Level 5 Drywall Finish</a>
                <a href="<?= e(url('gallery.php')) ?>" class="btn btn--outline">Project Gallery</a>
            </div>
        </div>
    </section>

    <?php mkt_cta_band('Ready to get a quote?', 'Tell us about your commercial project.', 'Free estimates, honest pricing, and a crew that shows up when they say they will. Serving the Lehigh Valley and Bucks County, PA.'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
