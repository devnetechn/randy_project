<?php
/** Reusable marketing sections (ported from the React marketing components). */

require_once __DIR__ . '/reviews.php';

function svg_arrow(): string
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
}
function svg_check(): string
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>';
}
function svg_phone(): string
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
}
function svg_circle_check(): string
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>';
}

function mkt_service_cards(): void
{
    $cards = [
        ['/ 01', 'Interior Painting', 'Walls, ceilings, trim, doors, and cabinets refreshed with a clean, durable finish.', ['Walls & ceilings', 'Trim, doors & baseboards', 'Cabinet refinishing']],
        ['/ 02', 'Exterior Painting', 'Protective, weather-resistant coatings that refresh and shield your property.', ['Siding & stucco', 'Trim, fascia & soffits', 'Decks & fences']],
        ['/ 03', 'Drywall Installation', 'New construction, additions, and remodels hung and finished to a paint-ready surface.', ['Hanging & taping', 'Mudding & sanding', 'Ceilings & soffits']],
        ['/ 04', 'Drywall Repair', 'Cracks, holes, dents, and water damage made invisible with seamless patching.', ['Holes & cracks', 'Water-damage repair', 'Popcorn-ceiling removal']],
        ['/ 05', 'Texture & Finishing', 'Knockdown, orange peel, smooth Level 5 — matched perfectly to your existing walls.', ['Texture matching', 'Skim coating', 'Level 5 smooth finish']],
        ['/ 06', 'Commercial Projects', 'Offices, retail, and rentals painted and repaired on schedule, with minimal disruption.', ['Offices & retail', 'Rental turnovers', 'After-hours scheduling']],
        ['/ 07', 'Level 5 Skim Coating', 'A full skim coat over the entire surface for a glass-smooth, flawless wall — our highest drywall finish.', ['Full-surface skim coat', 'Flawless under raking light', 'Ideal for gloss & dark paints']],
    ];
    echo '<div class="services-grid">';
    foreach ($cards as [$num, $title, $desc, $features]) {
        echo '<article class="service-card">';
        echo '<div class="service-card__num">' . e($num) . '</div>';
        echo '<div class="service-card__icon">' . svg_check() . '</div>';
        echo '<h3>' . e($title) . '</h3><p>' . e($desc) . '</p><ul>';
        foreach ($features as $f) {
            echo '<li>' . svg_check() . e($f) . '</li>';
        }
        echo '</ul></article>';
    }
    echo '</div>';
}

function mkt_stats_band(): void
{
    $stats = [['35', '+', 'Years in business'], ['500', '+', 'Projects completed'], ['100', '%', 'Satisfaction focus'], ['5', '★', 'Average rating']];
    echo '<div class="stats"><div class="stats__grid">';
    foreach ($stats as [$num, $suffix, $lbl]) {
        echo '<div class="stat"><div class="stat__num">' . e($num) . '<span class="suffix">' . e($suffix) . '</span></div><div class="stat__lbl">' . e($lbl) . '</div></div>';
    }
    echo '</div></div>';
}

function mkt_process_steps(): void
{
    $steps = [
        ['Free Estimate', 'We visit, measure, and listen — then send a clear written quote with no obligation.'],
        ['Prep & Protect', 'Furniture covered, surfaces patched and sanded, edges taped for a clean line.'],
        ['Paint & Finish', 'Skilled application with premium materials and careful attention to every detail.'],
        ['Walk-Through', "We clean up fully and review the work together until you're 100% happy."],
    ];
    echo '<div class="process">';
    foreach ($steps as [$t, $d]) {
        echo '<div class="process__step"><h3>' . e($t) . '</h3><p>' . e($d) . '</p></div>';
    }
    echo '</div>';
}

function mkt_testimonials(): void
{
    // Hardcoded samples — the fallback when the admin hasn't entered any real reviews.
    $samples = [
        ['Maria S.', 5, "They repaired our water-damaged ceiling and repainted the whole floor. You honestly can't tell where the patch was.", 'Interior repaint · Bethlehem, PA'],
        ['The Johnsons', 5, "On time, spotless, and the price matched the quote exactly. We've already booked them for the exterior next spring.", 'Full home repaint · Easton, PA'],
        ['Dave R.', 5, "Professional from quote to clean-up. They hung and finished drywall in our basement and it's flawless.", 'Basement drywall · Nazareth, PA'],
    ];

    // Real admin-managed reviews when present; otherwise the samples above.
    $rows = reviews_all();
    if ($rows) {
        $items = array_map(function ($r) {
            return [$r['author'], (int) $r['rating'], $r['body'], $r['meta']];
        }, $rows);
    } else {
        $items = $samples;
    }

    echo '<div class="testi-grid">';
    foreach ($items as [$name, $rating, $quote, $meta]) {
        $rating = max(1, min(5, (int) $rating));
        $initial = mb_strtoupper(mb_substr(trim($name) ?: 'G', 0, 1));
        echo '<figure class="testi"><div class="testi__stars" aria-label="' . $rating . ' out of 5 stars">' . str_repeat(mkt_star(), $rating) . '</div>';
        echo '<blockquote class="testi__quote">&ldquo;' . e($quote) . '&rdquo;</blockquote>';
        echo '<figcaption class="testi__who"><span class="testi__avatar">' . e($initial) . '</span>';
        echo '<div><div class="testi__name">' . e($name) . '</div>';
        if ((string) $meta !== '') {
            echo '<div class="testi__meta">' . e($meta) . '</div>';
        }
        echo '</div></figcaption></figure>';
    }
    echo '</div>';

    // "Leave a review" button — shown whenever a review link is configured.
    $reviewUrl = trim((string) setting_get('google_reviews_review_url', 'https://share.google/jOEA4W8Vst9zQkl5u'));
    if ($reviewUrl !== '') {
        echo '<div class="testi-cta center">';
        echo '<a class="btn btn--outline" href="' . e($reviewUrl) . '" target="_blank" rel="noopener">' . mkt_star() . ' Leave us a review on Google</a>';
        echo '</div>';
    }
}

/** A single filled star glyph, used by the testimonials grid. */
function mkt_star(): string
{
    return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6 6.6.5-5 4.3 1.5 6.4L12 16.9 6 19.2l1.5-6.4-5-4.3 6.6-.5z"/></svg>';
}

function mkt_faq(): void
{
    $items = [
        ['Do you offer free estimates?', "Yes — every estimate is free and comes with no obligation. We'll visit your property, discuss your goals, and send a detailed written quote, usually within 24 hours."],
        ['Are you licensed and insured?', "Absolutely. Randy's Painting & Drywall Services is fully licensed and carries liability insurance, so you're protected on every job."],
        ['How long will my project take?', "It depends on scope, but most single rooms are completed in 1–2 days and full-home repaints in about a week. We'll give you a clear timeline with your quote."],
        ['What kind of paint do you use?', "We use premium, low-VOC paints from trusted brands. If you have a preferred product or color, just let us know and we'll work with it."],
        ['Do you guarantee your work?', "Yes. We stand behind our craftsmanship with a workmanship guarantee. If something isn't right, we'll come back and fix it."],
    ];
    echo '<section class="section"><div class="container">';
    echo '<div class="section-head center"><span class="eyebrow" style="justify-content:center">Good to know</span><h2 style="margin-top:1rem">Frequently asked questions</h2></div>';
    echo '<div class="faq">';
    foreach ($items as $i => [$q, $a]) {
        echo '<div class="faq-item" data-faq>';
        echo '<button class="faq-q" type="button" data-faq-q aria-expanded="false"><span>' . e($q) . '</span><span class="icon" aria-hidden="true"></span></button>';
        echo '<div class="faq-a"><div class="faq-a__inner">' . e($a) . '</div></div>';
        echo '</div>';
    }
    echo '</div></div></section>';
}

/**
 * Render a FAQ section from custom [question, answer] items and emit matching
 * FAQPage structured data. Used by the dedicated service pages. Reuses the same
 * markup/classes as mkt_faq() so the toggle JS in app.js works automatically.
 */
function mkt_faq_custom(array $items, string $heading = 'Frequently asked questions', string $eyebrow = 'Good to know'): void
{
    echo '<section class="section"><div class="container">';
    echo '<div class="section-head center"><span class="eyebrow" style="justify-content:center">' . e($eyebrow) . '</span><h2 style="margin-top:1rem">' . e($heading) . '</h2></div>';
    echo '<div class="faq">';
    foreach ($items as [$q, $a]) {
        echo '<div class="faq-item" data-faq>';
        echo '<button class="faq-q" type="button" data-faq-q aria-expanded="false"><span>' . e($q) . '</span><span class="icon" aria-hidden="true"></span></button>';
        echo '<div class="faq-a"><div class="faq-a__inner">' . e($a) . '</div></div>';
        echo '</div>';
    }
    echo '</div></div></section>';

    $ld = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
    foreach ($items as [$q, $a]) {
        $ld['mainEntity'][] = [
            '@type'          => 'Question',
            'name'           => $q,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
        ];
    }
    echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/**
 * Emit AggregateRating + Review structured data tied to the LocalBusiness, so
 * Google can show star ratings in search results. Uses the SAME @id as the
 * LocalBusiness in header.php so the data merges into one entity.
 *
 * Only outputs when genuine admin-managed reviews exist — never fabricates a
 * rating (fake review markup is a Google policy violation). Call this only on a
 * page that actually displays the reviews (the homepage testimonials section).
 */
function mkt_review_schema(): void
{
    $rows = reviews_all();
    if (!$rows) {
        return; // no real reviews -> no markup
    }

    $b      = business_info();
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'randyspaintdrywall.com';
    $origin = $scheme . '://' . $host;

    $count = count($rows);
    $sum   = 0;
    $reviews = [];
    foreach ($rows as $r) {
        $rating = max(1, min(5, (int) $r['rating']));
        $sum   += $rating;
        $review = [
            '@type'         => 'Review',
            'author'        => ['@type' => 'Person', 'name' => (string) $r['author']],
            'reviewRating'  => ['@type' => 'Rating', 'ratingValue' => (string) $rating, 'bestRating' => '5'],
            'reviewBody'    => (string) $r['body'],
        ];
        if (!empty($r['created_at']) && ($ts = strtotime((string) $r['created_at']))) {
            $review['datePublished'] = date('Y-m-d', $ts);
        }
        $reviews[] = $review;
    }
    $avg = round($sum / $count, 1);

    $ld = [
        '@context'        => 'https://schema.org',
        '@type'           => ['HousePainter', 'GeneralContractor'],
        '@id'             => $origin . '/#business',
        'name'            => $b['name'],
        'aggregateRating' => [
            '@type'       => 'AggregateRating',
            'ratingValue' => (string) $avg,
            'reviewCount' => (string) $count,
            'bestRating'  => '5',
            'worstRating' => '1',
        ],
        'review'          => $reviews,
    ];
    echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/** Emit Service structured data for a dedicated service page (SEO rich results). */
function mkt_service_jsonld(string $name, string $description): void
{
    $b = business_info();
    $ld = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        'serviceType' => $name,
        'name'        => $name,
        'description' => $description,
        'provider'    => ['@type' => 'HousePainter', 'name' => $b['name'], 'telephone' => $b['phoneTel']],
        'areaServed'  => [
            ['@type' => 'AdministrativeArea', 'name' => 'Lehigh Valley'],
            ['@type' => 'AdministrativeArea', 'name' => 'Bucks County'],
        ],
    ];
    echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

function mkt_before_after(): void
{
    $before = url('assets/img/gallery/ba-before.webp');
    $after = url('assets/img/gallery/ba-after.webp');
    echo '<div class="ba" data-ba><div class="ba__slider">';
    echo '<div class="ba__layer ba__layer--before"><img src="' . e($before) . '" alt="Before — project in progress"></div>';
    echo '<div class="ba__layer ba__layer--after" data-ba-after style="clip-path: inset(0 0 0 50%)"><img src="' . e($after) . '" alt="After — finished by Randy\'s Painting & Drywall"></div>';
    echo '<span class="ba__tag ba__tag--before">Before</span><span class="ba__tag ba__tag--after">After</span>';
    echo '<div class="ba__handle" data-ba-handle style="left:50%"></div>';
    echo '<span class="ba__knob" data-ba-knob style="left:50%"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7l-5 5 5 5M16 7l5 5-5 5"/></svg></span>';
    echo '<input type="range" min="0" max="100" value="50" class="ba__range" data-ba-range aria-label="Before and after slider position">';
    echo '</div></div>';
}

/** A reusable dark CTA band. */
function mkt_cta_band(string $eyebrow, string $title, string $text, bool $showQuote = true): void
{
    $b = business_info();
    echo '<section class="section section--tight"><div class="container"><div class="cta-band"><div class="cta-band__bg" aria-hidden="true"></div><div class="cta-band__inner">';
    echo '<div><span class="eyebrow eyebrow--light">' . e($eyebrow) . '</span><h2 style="margin-top:1rem">' . e($title) . '</h2><p>' . e($text) . '</p></div>';
    echo '<div class="cta-band__actions">';
    if ($showQuote) {
        echo '<a href="' . e(url('book.php')) . '" class="btn btn--lg">Get a Free Quote' . svg_arrow() . '</a>';
    }
    echo '<a href="tel:' . e($b['phoneTel']) . '" class="btn btn--ghost-light btn--lg">' . svg_phone() . e($b['phone']) . '</a>';
    echo '</div></div></div></div></section>';
}
