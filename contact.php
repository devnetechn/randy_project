<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';
$page_title = 'Contact — Free Painting & Drywall Estimate in Easton, PA & the Lehigh Valley';
$page_description = 'Contact Randy\'s Painting & Drywall Services for a free estimate. Local painter and drywall contractor serving Easton, PA, Bethlehem, Allentown, Nazareth and the Lehigh Valley. Call (484) 546-3660.';
$page_keywords = 'painter near Easton PA, free painting estimate Lehigh Valley, drywall contractor near me, contact painter Easton, Lehigh Valley painter quote';
$b = business_info();
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> Contact</nav>
            <span class="eyebrow">Get in touch</span>
            <h1 style="margin-top:1rem">Contact — Let's get you a <span class="ul-brush">free quote</span>.</h1>
            <p>Tell us about your painting or drywall project anywhere in Easton, PA or the Lehigh Valley and we'll get back to you — usually within 24 hours.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container contact-grid">
            <aside class="contact-aside">
                <div class="contact-card"><span class="contact-card__icon"><?= svg_phone() ?></span><div><h3>Call or text</h3><p><a href="tel:<?= e($b['phoneTel']) ?>"><?= e($b['phone']) ?></a></p></div></div>
                <div class="contact-card"><span class="contact-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg></span><div><h3>Email</h3><p><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></p></div></div>
                <div class="contact-card"><span class="contact-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span><div><h3>Website</h3><p><a href="https://<?= e($b['website']) ?>" target="_blank" rel="noopener noreferrer"><?= e($b['website']) ?></a></p></div></div>
                <div class="contact-card"><span class="contact-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><div><h3>Hours &amp; area</h3><p>Mon–Sat, 8am–5pm<br>Sun: By appointment<br>Easton, PA &amp; the Lehigh Valley (within 25 miles)</p></div></div>
            </aside>

            <div class="form-card">
                <h2 style="font-size:clamp(1.6rem,1.3rem+1vw,2.1rem);margin-bottom:.5rem">Request a free estimate</h2>
                <p style="color:var(--muted);margin-bottom:1.75rem">Ready to get started? Book online and we'll send you a detailed written quote within 24 hours — no pressure, no obligation.</p>
                <div style="text-align:center;padding:2rem 1rem"><a href="<?= e(url('book.php')) ?>" class="btn btn--lg">Request a Free Estimate<?= svg_arrow() ?></a></div>
                <ul style="margin-top:1.5rem;list-style:none;padding:0;display:flex;flex-direction:column;gap:.75rem">
                    <?php foreach (['Free, no-obligation estimates', 'Usually quoted within 24 hours', 'Licensed & insured crew', 'Workmanship guarantee'] as $item): ?>
                        <li style="display:flex;align-items:center;gap:.5rem;font-size:.9rem"><span style="display:inline-flex;width:1rem;height:1rem;color:var(--brand)"><?= svg_check() ?></span><?= e($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container"><div class="cta-band"><div class="cta-band__bg" aria-hidden="true"></div><div class="cta-band__inner">
            <div><span class="eyebrow eyebrow--light">Prefer to talk?</span><h2 style="margin-top:1rem">We're a quick call away.</h2><p>Reach us directly and we'll answer your questions and schedule a visit.</p></div>
            <div class="cta-band__actions"><a href="tel:<?= e($b['phoneTel']) ?>" class="btn btn--lg"><?= svg_phone() ?>Call <?= e($b['phone']) ?></a></div>
        </div></div></div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
