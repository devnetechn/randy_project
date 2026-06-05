<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
require_once __DIR__ . '/includes/blog.php';
$page_title = 'About';
$blog_teasers = blog_published(3);
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> About</nav>
            <span class="eyebrow">Who we are</span>
            <h1 style="margin-top:1rem">About Randy&apos;s Painting &amp; Drywall</h1>
            <p>A family-run painting and drywall company built on tidy job sites, fair quotes, and finishes we're proud to put our name on.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container split">
            <div class="split__media"><div class="ph ph--warm"><span class="ph__tag">Our team</span></div></div>
            <div class="split__body">
                <span class="eyebrow">Our story</span>
                <h2 style="margin-top:1rem">Built on word-of-mouth since 2009</h2>
                <p>What started as one painter — Randy Peay — with a ladder and a reputation for clean work has grown into a trusted crew serving Easton, PA and everywhere within 25 miles. We've stayed small enough to care about every detail and skilled enough to handle any job, from a single patch to a whole-building repaint.</p>
                <p style="margin-top:1rem">We believe the best advertising is a job done right, which is why most of our work comes from referrals and repeat customers.</p>
                <div style="margin-top:2rem"><a href="<?= e(url('contact.php')) ?>" class="btn btn--slate">Work with us<?= svg_arrow() ?></a></div>
            </div>
        </div>
    </section>

    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">What we stand for</span><h2 style="margin-top:1rem">The standards behind every job</h2></div>
            <div class="values-grid">
                <div class="value"><div class="value__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg></div><h3>Integrity</h3><p>Honest quotes, no upsells, and the same price we promised. What we say is what you pay.</p></div>
                <div class="value"><div class="value__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3l7 7-9 9-7-7z"/><path d="M11 6l4 4"/></svg></div><h3>Craftsmanship</h3><p>Proper prep, premium materials, and a sharp eye for detail on every square foot.</p></div>
                <div class="value"><div class="value__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div><h3>Reliability</h3><p>We show up on time, keep you updated, and finish when we said we would.</p></div>
                <div class="value"><div class="value__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l2 5 4-12 2 7h6"/></svg></div><h3>Respect</h3><p>Your home is protected, your space is left spotless, and your time is valued.</p></div>
            </div>
        </div>
    </section>

    <section class="section section--tight"><div class="container"><?php mkt_stats_band(); ?></div></section>

    <section class="section section--tight">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">The crew</span><h2 style="margin-top:1rem">The people behind the brush</h2></div>
            <div class="team-grid">
                <article class="team-card"><div class="team-card__photo"><div class="ph ph--cool"><span class="ph__tag">Photo</span></div></div><div class="team-card__body"><h3>Randy Peay</h3><div class="role">Owner &amp; Lead Painter</div><p>With over 35 years in the trade, Randy built this company on the simple belief that every job deserves the same care — whether it's a single patch or a full-building repaint. He personally oversees every project.</p></div></article>
                <article class="team-card"><div class="team-card__photo"><div class="ph ph--sage"><span class="ph__tag">Photo</span></div></div><div class="team-card__body"><h3>Mike T.</h3><div class="role">Drywall Specialist</div><p>Mike's expertise in hanging, taping, and texture matching means repairs truly disappear. He handles everything from small holes to full basement build-outs.</p></div></article>
                <article class="team-card"><div class="team-card__photo"><div class="ph ph--warm"><span class="ph__tag">Photo</span></div></div><div class="team-card__body"><h3>Sarah L.</h3><div class="role">Project Coordinator</div><p>Sarah keeps every project on schedule and every customer in the loop. She's your first point of contact and makes sure nothing falls through the cracks.</p></div></article>
            </div>
        </div>
    </section>

    <?php if ($blog_teasers): ?>
    <section class="section section--tight" style="background:var(--plaster-2)">
        <div class="container">
            <div class="section-head center"><span class="eyebrow" style="justify-content:center">From our blog</span><h2 style="margin-top:1rem">Tips, updates &amp; project stories</h2></div>
            <div class="blog-grid">
                <?php foreach ($blog_teasers as $post): ?>
                    <a class="blog-card" href="<?= e(url('blog-post.php?id=' . (int) $post['id'])) ?>">
                        <div class="blog-card__img">
                            <?php if ($post['image']): ?>
                                <img src="<?= e(blog_image_url($post['image'])) ?>" alt="">
                            <?php else: ?>
                                <div class="ph ph--warm"><span class="ph__tag">Blog</span></div>
                            <?php endif; ?>
                        </div>
                        <div class="blog-card__body">
                            <div class="blog-card__date"><?= e(blog_date($post['created_at'])) ?></div>
                            <h3 class="blog-card__title"><?= e($post['title']) ?></h3>
                            <?php if ($post['excerpt']): ?><p class="blog-card__excerpt"><?= e($post['excerpt']) ?></p><?php endif; ?>
                            <span class="blog-card__more">Read more<?= svg_arrow() ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="center" style="margin-top:2.5rem"><a href="<?= e(url('blog.php')) ?>" class="btn btn--slate">View all posts<?= svg_arrow() ?></a></div>
        </div>
    </section>
    <?php endif; ?>

    <section class="section section--tight">
        <div class="container">
            <div class="section-head center">
                <span class="eyebrow" style="justify-content:center">Find us</span>
                <h2 style="margin-top:1rem">Serving Easton &amp; the Lehigh Valley</h2>
                <p>Based in Easton, PA, we cover the whole Lehigh Valley within a 25-mile radius. Find us on the map below or get directions.</p>
            </div>
            <div class="map-embed">
                <iframe
                    src="https://www.google.com/maps?q=Randy's+Painting+and+Drywall+Services+LLC&z=11&output=embed"
                    title="Randy's Painting and Drywall Services — service area map"
                    loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen></iframe>
            </div>
            <div class="center" style="margin-top:1.75rem">
                <a href="https://maps.app.goo.gl/eMAR498zU14UTr4s6" target="_blank" rel="noopener noreferrer" class="btn btn--slate">Open in Google Maps<?= svg_arrow() ?></a>
            </div>
        </div>
    </section>
    <style>
        .mkt .map-embed { position:relative; width:100%; aspect-ratio:16/9; border-radius:16px;
            overflow:hidden; border:1px solid var(--plaster-3); box-shadow:0 10px 30px rgba(15,27,51,.08); }
        .mkt .map-embed iframe { position:absolute; inset:0; width:100%; height:100%; border:0; }
        @media (max-width:640px){ .mkt .map-embed { aspect-ratio:4/3; } }
    </style>

    <?php mkt_cta_band("Let's talk", 'Trusted by your neighbors.', 'See why Easton-area homeowners keep calling us back. Free estimates, always.'); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
