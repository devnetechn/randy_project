<?php
/**
 * Location landing pages: city + service combinations. Static content array
 * (no DB table) rendered through one shared layout, matching how the other
 * dedicated service pages (level-5-drywall.php, etc.) are authored.
 */

require_once __DIR__ . '/marketing.php';
require_once __DIR__ . '/gallery.php';

$LOCATIONS = [
    'easton-painting' => [
        'title'                => 'Painting Contractor in Easton, PA',
        'meta_description'     => "High-end interior and exterior painting for Easton, PA homes — from College Hill Victorians to new builds in the West Ward. Licensed, insured, free estimates.",
        'meta_keywords'        => 'painting contractor Easton PA, house painter Easton PA, interior painting Easton PA, exterior painting Easton PA, historic home painter Lehigh Valley',
        'city'                 => 'Easton',
        'service_label'        => 'Painting Contractor',
        'service_name'         => 'Interior & Exterior Painting',
        'hero_heading'         => 'Easton\'s painting contractor for <span class="ul-brush">flawless</span>, lasting results.',
        'hero_intro'           => "Based right here in Easton, we've painted the homes and storefronts that make this city what it is — from the Victorian and Colonial homes on College Hill to newer construction across the West Ward and beyond. Free, no-pressure estimates for interior and exterior work.",
        'intro_heading'        => 'Local knowledge, careful craftsmanship',
        'intro_body'           => "Easton's housing stock spans more than a century, and it shows — plaster walls, layered old paint, ornate trim, and brick exteriors that all need a different touch than a builder-grade new build. We've worked on homes throughout College Hill, the West Ward, and South Side Easton, and we know how to prep and paint each one the right way, protecting original character while giving it a fresh, modern finish.",
        'feature_cards'        => [
            ['Historic home experience', 'College Hill and downtown Easton are full of century-old homes with plaster walls and detailed trim. We prep and paint them without damaging original character.'],
            ['Weather-ready exteriors', 'Lehigh Valley winters and humid summers are hard on exterior paint. We use coatings built to hold their color and seal through every season.'],
            ['Minimal disruption', 'Furniture covered, dust and drips contained, and a clean job site every day — so your home stays livable while we work.'],
        ],
        'faq'                  => [
            ['How much does interior painting cost in Easton, PA?', 'It depends on square footage, ceiling height, and surface condition — a small bedroom and a whole-home repaint aren\'t priced the same way. Most single rooms start in the low hundreds; we\'ll give you an exact number after a free, no-obligation on-site estimate.'],
            ['Do you paint historic homes in Easton?', 'Yes — many of our Easton projects are on College Hill and downtown, in homes with original plaster, trim, and woodwork. We take extra care with prep so repairs and painting protect that character instead of covering it up.'],
            ['How long does exterior painting take on a typical Easton home?', 'A standard single-family exterior usually takes 3-5 days depending on size, prep needed, and weather. We\'ll walk you through the schedule with your estimate.'],
            ['Do you offer free estimates in Easton?', 'Always. We\'re based in Easton, so scheduling an in-person estimate is easy — most quotes are returned within 24 hours, with no obligation.'],
        ],
        'cta_eyebrow'          => 'Local & ready to help',
        'cta_title'            => 'Get a painting estimate in Easton',
        'cta_text'             => 'Free, no-pressure quotes for homes and businesses throughout Easton, PA. Most returned within 24 hours.',
        'hero_image'           => 'assets/img/service-painting.webp',
        'hero_image_alt'       => 'Interior painting and trim work in an Easton, PA home',
        'gallery_categories'   => ['interior', 'exterior'],
        'related_service_slug' => 'services.php',
        'related_service_label'=> 'All Painting Services',
        'related_slugs'        => ['college-hill-painting', 'bethlehem-painting', 'allentown-painting'],
    ],

    'bethlehem-painting' => [
        'title'                => 'Painting Contractor in Bethlehem, PA',
        'meta_description'     => "Interior and exterior painting for Bethlehem, PA homes and businesses — from historic rowhomes to South Side lofts. Licensed, insured, free estimates.",
        'meta_keywords'        => 'painting contractor Bethlehem PA, house painter Bethlehem PA, interior painting Bethlehem PA, exterior painting Bethlehem PA, South Side Bethlehem painter, Bethlehem Painters',
        'city'                 => 'Bethlehem',
        'service_label'        => 'Painting Contractor',
        'service_name'         => 'Interior & Exterior Painting',
        'hero_heading'         => 'Bethlehem painting, done with <span class="ul-brush">precision</span>.',
        'hero_intro'           => 'From the historic rowhomes near Historic Bethlehem and the Moravian district to converted lofts on the South Side, we bring careful prep and premium paint to every Bethlehem project. Free estimates, no pressure.',
        'intro_heading'        => "Painting that respects Bethlehem's character",
        'intro_body'           => "Bethlehem's neighborhoods each bring their own painting challenges: tight rowhomes with shared walls and narrow trim, older plaster in the Historic District, and industrial-turned-residential spaces on the South Side with brick, concrete, and exposed surfaces. We adjust our approach to the building, not the other way around — matching prep, primer, and finish to what the surface actually needs.",
        'feature_cards'        => [
            ['Rowhome & townhome experience', 'Shared walls, narrow stairwells, and tight trim lines are second nature to us — we protect neighboring units and finish clean, straight lines every time.'],
            ['South Side loft & industrial finishes', 'Exposed brick, concrete, and steel need different prep and paint than a typical drywall room. We know how to prime and coat them so the finish holds.'],
            ['Scheduling that fits your life', 'Owner-occupied or tenant-occupied, we work around your schedule and keep the job site clean and contained throughout.'],
        ],
        'faq'                  => [
            ['How much does interior painting cost in Bethlehem, PA?', 'Cost depends on the size of the space, ceiling height, and how much prep the walls need. Most single rooms start in the low hundreds — we\'ll confirm the exact price with a free on-site estimate.'],
            ['Do you paint rowhomes and townhomes in Bethlehem?', 'Yes, regularly. We know how to work in tight spaces, protect shared walls, and keep dust and drips contained in close quarters.'],
            ['Can you paint exposed brick or concrete in a South Side loft?', 'Yes — these surfaces need different priming and paint than standard drywall, and we have the products and experience to get lasting adhesion on brick, block, and concrete.'],
            ['Do you offer free estimates in Bethlehem?', 'Yes, always free and no obligation. We serve all of Bethlehem, from the Historic District to the South Side, with most quotes returned within 24 hours.'],
        ],
        'cta_eyebrow'          => 'Serving all of Bethlehem',
        'cta_title'            => 'Get a painting estimate in Bethlehem',
        'cta_text'             => 'Free, no-pressure quotes for homes and businesses throughout Bethlehem, PA. Most returned within 24 hours.',
        'hero_image'           => 'assets/img/service-painting.webp',
        'hero_image_alt'       => 'Freshly painted interior in a Bethlehem, PA home',
        'gallery_categories'   => ['interior', 'exterior'],
        'related_service_slug' => 'services.php',
        'related_service_label'=> 'All Painting Services',
        'related_slugs'        => ['easton-painting', 'allentown-painting'],
    ],

    'allentown-painting' => [
        'title'                => 'Painting Contractor in Allentown, PA',
        'meta_description'     => 'Interior and exterior painting for Allentown, PA homes, rentals, and businesses — twins, rowhomes, and downtown properties. Licensed, insured, free estimates.',
        'meta_keywords'        => 'painting contractor Allentown PA, house painter Allentown PA, interior painting Allentown PA, exterior painting Allentown PA, rental painting Allentown',
        'city'                 => 'Allentown',
        'service_label'        => 'Painting Contractor',
        'service_name'         => 'Interior & Exterior Painting',
        'hero_heading'         => 'Allentown painting contractor, built on <span class="ul-brush">trust</span>.',
        'hero_intro'           => 'From twins and rowhomes in the West End Historic District to rental turnovers and downtown revitalization projects, we bring the same careful prep and premium paint to every Allentown property. Free estimates, no pressure.',
        'intro_heading'        => 'One crew for homes, rentals & businesses',
        'intro_body'           => "Allentown's mix of century-old twins, rental properties, and a fast-growing downtown means no two jobs look alike. We've painted owner-occupied homes in the West End Historic District, turned over units for landlords between tenants, and refreshed commercial spaces downtown — all with the same attention to prep, clean lines, and a durable finish that holds up to daily use.",
        'feature_cards'        => [
            ['West End & historic twins', 'Century-old twin homes need careful trim work and surface prep. We match that level of care on every property.'],
            ['Fast, reliable rental turnovers', 'Landlords and property managers count on us for quick, consistent turnovers between tenants — on schedule, every time.'],
            ['Downtown commercial-ready', 'Offices, retail, and mixed-use buildings get scheduling that minimizes disruption to tenants and customers.'],
        ],
        'faq'                  => [
            ['How much does interior painting cost in Allentown, PA?', 'Pricing depends on square footage, ceiling height, and how much prep the walls need. Most single rooms start in the low hundreds — a free on-site estimate gets you an exact number.'],
            ['Do you handle rental property turnovers in Allentown?', 'Yes — we work regularly with landlords and property managers on turnover painting between tenants, with scheduling built around move-in/move-out dates.'],
            ['Can you paint commercial spaces downtown?', 'Yes. We paint offices, retail, and mixed-use buildings throughout downtown Allentown, with after-hours and weekend scheduling available to avoid disrupting your business.'],
            ['Do you offer free estimates in Allentown?', 'Always, with no obligation. We serve all of Allentown — from the West End to downtown — and most quotes are returned within 24 hours.'],
        ],
        'cta_eyebrow'          => 'Serving all of Allentown',
        'cta_title'            => 'Get a painting estimate in Allentown',
        'cta_text'             => 'Free, no-pressure quotes for homes, rentals, and businesses throughout Allentown, PA. Most returned within 24 hours.',
        'hero_image'           => 'assets/img/service-painting.webp',
        'hero_image_alt'       => 'Exterior painting on a twin home in Allentown, PA',
        'gallery_categories'   => ['interior', 'exterior'],
        'related_service_slug' => 'services.php',
        'related_service_label'=> 'All Painting Services',
        'related_slugs'        => ['easton-painting', 'bethlehem-painting'],
    ],

    'easton-drywall-repair' => [
        'title'                => 'Drywall Repair in Easton, PA',
        'meta_description'     => 'Seamless drywall and plaster repair for Easton, PA homes — cracks, holes, water damage, and popcorn ceiling removal. Licensed, insured, free estimates.',
        'meta_keywords'        => 'drywall repair Easton PA, plaster repair Easton PA, drywall patch Easton PA, popcorn ceiling removal Easton, water damage repair Easton PA',
        'city'                 => 'Easton',
        'service_label'        => 'Drywall Repair',
        'service_name'         => 'Drywall Repair',
        'hero_heading'         => 'Drywall repair in Easton that <span class="ul-brush">disappears</span>.',
        'hero_intro'           => "Cracks, holes, water stains, and aging plaster are common in Easton's older homes. We patch, blend, and finish repairs so seamlessly that you won't be able to find them once the paint is dry.",
        'intro_heading'        => 'From a single crack to a full ceiling',
        'intro_body'           => "Many Easton homes — especially on College Hill and downtown — still have original plaster walls and ceilings, which crack and settle differently than modern drywall. We're experienced with both: matching plaster texture where walls are original, and blending drywall patches invisibly where a home has been updated. Popcorn ceilings, water-damaged sections, and settling cracks are some of the most common repairs we handle in Easton.",
        'feature_cards'        => [
            ['Plaster & drywall, both handled right', 'Original plaster and modern drywall crack and repair differently. We match the right material and technique to your walls.'],
            ['Water damage & popcorn ceiling removal', 'Stains, sagging, and rough texture from age or leaks are repaired and finished smooth — ready for paint.'],
            ['Invisible texture matching', 'We blend new patches into the existing texture so repairs disappear, not stand out.'],
        ],
        'faq'                  => [
            ['How much does drywall repair cost in Easton, PA?', 'It depends on the size and cause of the damage — a small nail hole costs far less than a full section replaced after a leak. Most small patches start in the low hundreds; we\'ll quote the exact cost after a free on-site look.'],
            ['What are the signs I need drywall repair?', 'Visible cracks (especially near corners or doorways), soft or bulging spots, water stains, popped nails or screws, and sagging ceilings are all signs worth having looked at before they get worse.'],
            ['Do you repair plaster as well as drywall?', 'Yes. Many Easton homes still have original plaster, and we match repairs to whichever material your walls are actually made of, not just drywall.'],
            ['How long does drywall repair take?', 'A small patch is often finished same-day; larger repairs with texture matching and multiple coats of compound may take 2-4 days to fully dry, sand, and blend before painting.'],
        ],
        'cta_eyebrow'          => 'Repairs that disappear',
        'cta_title'            => 'Get a drywall repair estimate in Easton',
        'cta_text'             => 'Free, no-pressure quotes for drywall and plaster repair throughout Easton, PA. Most returned within 24 hours.',
        'hero_image'           => 'assets/img/service-drywall.webp',
        'hero_image_alt'       => 'Seamless drywall patch repair in an Easton, PA home',
        'gallery_categories'   => ['drywall'],
        'related_service_slug' => 'wall-restoration.php',
        'related_service_label'=> 'Wall Restoration & Plaster Repair',
        'related_slugs'        => ['bethlehem-drywall-repair', 'easton-painting'],
    ],

    'bethlehem-drywall-repair' => [
        'title'                => 'Drywall Repair in Bethlehem, PA',
        'meta_description'     => 'Seamless drywall and plaster repair for Bethlehem, PA homes and South Side lofts — cracks, holes, water damage, texture matching. Licensed, insured, free estimates.',
        'meta_keywords'        => 'drywall repair Bethlehem PA, plaster repair Bethlehem PA, drywall patch Bethlehem PA, water damage repair Bethlehem, ceiling repair Bethlehem PA',
        'city'                 => 'Bethlehem',
        'service_label'        => 'Drywall Repair',
        'service_name'         => 'Drywall Repair',
        'hero_heading'         => "Bethlehem drywall repair that's truly <span class=\"ul-brush\">seamless</span>.",
        'hero_intro'           => "From cracked plaster in a Historic District rowhome to a damaged wall in a South Side loft conversion, we repair and finish drywall and plaster so the patch is impossible to find once it's painted.",
        'intro_heading'        => "Repairs matched to Bethlehem's building stock",
        'intro_body'           => "Bethlehem's rowhomes and older buildings often carry original plaster, prone to hairline cracking and settling over the decades — while newer conversions and additions use standard drywall. We repair both, matching texture, thickness, and finish so the patched area blends into the surrounding wall instead of standing out under paint or light.",
        'feature_cards'        => [
            ['Historic District plaster repair', 'Cracked or crumbling plaster in older rowhomes is patched and blended to match the surrounding wall texture.'],
            ['South Side loft & renovation repairs', 'Newer drywall in converted and renovated spaces gets clean, invisible patching — including holes, dents, and seam repair.'],
            ['Water damage & ceiling repair', 'Stained, sagging, or damaged ceilings from leaks are cut out, replaced, and finished smooth and paint-ready.'],
        ],
        'faq'                  => [
            ['How much does drywall repair cost in Bethlehem, PA?', 'It depends on the size and cause — a small hole or crack costs far less than replacing a water-damaged section. Most small patches start in the low hundreds; a free on-site estimate gets you an exact number.'],
            ['Do you repair plaster in older Bethlehem rowhomes?', 'Yes. Original plaster cracks and settles differently than drywall, and we match our repair technique and texture to what\'s actually on your walls.'],
            ['Can you fix a water-damaged ceiling?', 'Yes — we remove the damaged section, address the texture, and finish it smooth and paint-ready, matching the surrounding ceiling.'],
            ['How long does a typical drywall repair take in Bethlehem?', 'Small patches are often same-day. Larger repairs need time for compound to dry between coats, so plan on 2-4 days before the area is ready to paint.'],
        ],
        'cta_eyebrow'          => 'Repairs that disappear',
        'cta_title'            => 'Get a drywall repair estimate in Bethlehem',
        'cta_text'             => 'Free, no-pressure quotes for drywall and plaster repair throughout Bethlehem, PA. Most returned within 24 hours.',
        'hero_image'           => 'assets/img/service-drywall.webp',
        'hero_image_alt'       => 'Drywall ceiling repair in a Bethlehem, PA home',
        'gallery_categories'   => ['drywall'],
        'related_service_slug' => 'wall-restoration.php',
        'related_service_label'=> 'Wall Restoration & Plaster Repair',
        'related_slugs'        => ['easton-drywall-repair', 'bethlehem-painting'],
    ],

    'upper-makefield-painting' => [
        'title'                => 'Painting Contractor in Upper Makefield, PA',
        'meta_description'     => 'Fine-finish interior and exterior painting for Upper Makefield Township, PA — estate homes, historic stone farmhouses, and Level 5 smooth walls. Licensed, insured, free estimates.',
        'meta_keywords'        => 'painting contractor Upper Makefield PA, house painter Upper Makefield, interior painting Bucks County PA, estate home painter Bucks County, Level 5 painting Upper Makefield, Washington Crossing painter',
        'city'                 => 'Upper Makefield',
        // Township, not a city — spelled out in headings and structured data.
        'city_line'            => 'Upper Makefield Township, PA',
        'service_label'        => 'Painting Contractor',
        'service_name'         => 'Interior & Exterior Painting',
        'hero_heading'         => 'Upper Makefield painting at an <span class="ul-brush">estate-level</span> standard.',
        'hero_intro'           => 'From the stone farmhouses around Washington Crossing to newer estate homes off Eagle and Dolington Roads, we bring fine-finish painting and Level 5 smooth walls to Upper Makefield Township. Free, no-pressure estimates.',
        'intro_heading'        => 'Finishes that hold up to close inspection',
        'intro_body'           => "Upper Makefield homes are built and detailed at a level where an average paint job shows immediately — tall foyers with raking light, custom millwork, coffered ceilings, and dark or high-sheen colors that expose every flaw underneath. We prep accordingly: walls skimmed smooth where they need it, trim sanded and caulked properly, and coatings chosen for the surface rather than whatever is quickest to spray.",
        'feature_cards'        => [
            ['Estate & custom home experience', 'Tall foyers, custom millwork, and coffered ceilings need patient prep and steady hands. That is the work we do every week.'],
            ['Level 5 smooth-wall finishes', 'Where dark paint, high sheen, or big windows will show every ripple, we skim the full surface first so the finish stays flawless.'],
            ['Historic stone farmhouse care', 'Plaster, original trim, and old woodwork near Washington Crossing are prepped to preserve the character, not bury it.'],
        ],
        'faq'                  => [
            ['How much does interior painting cost in Upper Makefield, PA?', 'It comes down to square footage, ceiling height, millwork detail, and how much wall prep is needed — an estate foyer and a spare bedroom are not priced the same way. We give you an exact number after a free, no-obligation walkthrough.'],
            ['Do you do Level 5 smooth-wall finishes in Bucks County?', 'Yes — Level 5 is one of our specialties. If you have dark or high-sheen paint planned, or big windows throwing raking light across a wall, a full skim coat is what keeps the finish looking flawless.'],
            ['Can you paint a historic stone farmhouse?', 'Yes. Older Upper Makefield homes often have original plaster and woodwork that need gentler prep and the right primer. We match the approach to what the surface actually is.'],
            ['Do you offer free estimates in Upper Makefield?', 'Always, with no obligation. We serve Upper Makefield Township and the surrounding Bucks County communities — Washington Crossing, Newtown, and Yardley included — with most quotes returned within 24 hours.'],
        ],
        'cta_eyebrow'          => 'Serving Bucks County',
        'cta_title'            => 'Get a painting estimate in Upper Makefield',
        'cta_text'             => 'Free, no-pressure quotes for homes throughout Upper Makefield Township and Bucks County, PA. Most returned within 24 hours.',
        'hero_image'           => 'assets/img/service-level5.webp',
        'hero_image_alt'       => 'Level 5 smooth-wall paint finish in an Upper Makefield, PA estate home',
        'gallery_categories'   => ['interior', 'exterior'],
        'related_service_slug' => 'level-5-drywall.php',
        'related_service_label'=> 'Level 5 Drywall Finish',
        'related_slugs'        => ['college-hill-painting', 'easton-painting'],
    ],

    'college-hill-painting' => [
        'title'                => 'Painting Contractor in College Hill, Easton, PA',
        'meta_description'     => 'Interior and exterior painting for College Hill in Easton, PA — Victorians, Colonials, porches, and original trim near Lafayette College. Licensed, insured, free estimates.',
        'meta_keywords'        => 'painting contractor College Hill Easton PA, College Hill painter, Victorian home painter Easton PA, historic home painting Easton, exterior painting College Hill, Lafayette College area painter',
        'city'                 => 'College Hill',
        // Neighborhood of Easton — kept explicit so headings and schema read correctly.
        'city_line'            => 'College Hill, Easton, PA',
        'area_type'            => 'Place',
        'service_label'        => 'Painting Contractor',
        'service_name'         => 'Interior & Exterior Painting',
        'hero_heading'         => 'College Hill painting that respects the <span class="ul-brush">original</span> details.',
        'hero_intro'           => "We're based in Easton, and College Hill is our own backyard — Victorians, Colonials, and Four Squares with plaster walls, deep porches, and trim worth saving. Free, no-pressure estimates for interior and exterior work.",
        'intro_heading'        => 'A century-old home needs a different approach',
        'intro_body'           => "Most of College Hill was built well before modern drywall, and it shows: lath-and-plaster walls that crack along the seams, layered old paint on ornate trim, wood porches and railings that take the full brunt of the weather, and window sashes nobody wants replaced. Rushing the prep on a home like that is how you end up with peeling in two years. We take the time — scrape, sand, patch, prime — so the finish lasts and the details still look like themselves.",
        'feature_cards'        => [
            ['Victorian & Colonial trim work', 'Ornate trim, brackets, and window sashes get hand prep and careful cut-in — no sprayed-over detail, no filled-in profiles.'],
            ['Plaster prep before paint', 'Settling cracks and old patchwork are repaired and blended first, so the paint lands on a wall that is actually ready for it.'],
            ['Porches built for the weather', 'Porch floors, ceilings, columns, and railings take the worst of the Lehigh Valley seasons. We prep and coat them to hold.'],
        ],
        'faq'                  => [
            ['How much does it cost to paint a College Hill Victorian?', 'Older homes with detailed trim take more prep hours than a modern build of the same size, so the range is wider. We walk the house, count what actually needs hand work, and give you an exact written number for free.'],
            ['Will you damage the original trim or woodwork?', 'No — protecting it is the point. We hand-sand and prep detailed trim rather than blasting or over-filling it, so the profiles and character stay intact under the new finish.'],
            ['Do you repair plaster cracks before painting?', 'Yes. Painting over a live crack just hides it until next winter. We patch and blend plaster first, and can handle larger restoration work on the same visit.'],
            ['Do you serve the rest of Easton too?', 'Yes — College Hill, the West Ward, downtown, and South Side Easton. We are based right here, so scheduling a free on-site estimate is easy and most quotes come back within 24 hours.'],
        ],
        'cta_eyebrow'          => 'Right here in Easton',
        'cta_title'            => 'Get a painting estimate on College Hill',
        'cta_text'             => 'Free, no-pressure quotes for homes throughout College Hill and the rest of Easton, PA. Most returned within 24 hours.',
        'hero_image'           => 'assets/img/service-painting.webp',
        'hero_image_alt'       => 'Freshly painted trim and porch on a College Hill Victorian in Easton, PA',
        'gallery_categories'   => ['interior', 'exterior'],
        'related_service_slug' => 'wall-restoration.php',
        'related_service_label'=> 'Wall Restoration & Plaster Repair',
        'related_slugs'        => ['easton-painting', 'easton-drywall-repair'],
    ],
];

/**
 * Real, admin-entered testimonials mentioning the given city (matched against
 * the free-text `meta` field, e.g. "Bethlehem, PA"). Falls back to the
 * unfiltered review pool if fewer than 2 match; renders nothing if there are
 * no reviews at all yet. Never fabricates a review.
 */
function location_testimonials(string $city): void
{
    $rows = reviews_all();
    $matched = array_values(array_filter($rows, function ($r) use ($city) {
        return stripos((string) $r['meta'], $city) !== false;
    }));
    $pool = count($matched) >= 2 ? $matched : $rows;
    if (!$pool) {
        return;
    }
    $items = array_slice($pool, 0, 3);

    echo '<div class="testi-grid">';
    foreach ($items as $r) {
        $rating = max(1, min(5, (int) $r['rating']));
        $initial = mb_strtoupper(mb_substr(trim($r['author']) ?: 'G', 0, 1));
        echo '<figure class="testi"><div class="testi__stars" aria-label="' . $rating . ' out of 5 stars">' . str_repeat(mkt_star(), $rating) . '</div>';
        echo '<blockquote class="testi__quote">&ldquo;' . e($r['body']) . '&rdquo;</blockquote>';
        echo '<figcaption class="testi__who"><span class="testi__avatar">' . e($initial) . '</span>';
        echo '<div><div class="testi__name">' . e($r['author']) . '</div>';
        if ((string) $r['meta'] !== '') {
            echo '<div class="testi__meta">' . e($r['meta']) . '</div>';
        }
        echo '</div></figcaption></figure>';
    }
    echo '</div>';
}

/**
 * Up to $limit real gallery photos matching any of the given categories,
 * each linking to its project detail page. Renders nothing if no photos in
 * those categories exist yet.
 */
function location_gallery_preview(array $categories, int $limit = 3): void
{
    $matched = array_values(array_filter(gallery_all(), function ($p) use ($categories) {
        return in_array($p['category'], $categories, true);
    }));
    if (!$matched) {
        return;
    }
    $items = array_slice($matched, 0, $limit);

    echo '<div class="gallery-grid">';
    foreach ($items as $img) {
        $caption = $img['caption'] ?: 'Recent project';
        echo '<a class="gallery-item" href="' . e(url('project.php?id=' . (int) $img['id'])) . '">';
        echo '<img src="' . e(url('uploads/gallery/' . $img['filename'])) . '" alt="' . e($caption) . '" loading="lazy">';
        echo '<span class="gallery-item__cap">' . e($caption) . ' <span>' . e($img['category']) . '</span></span>';
        echo '<span class="gallery-item__view">View project &rarr;</span>';
        echo '</a>';
    }
    echo '</div>';
}

/** Render a full location landing page for the given $LOCATIONS slug. */
function location_page(string $slug): void
{
    global $LOCATIONS;
    $loc = $LOCATIONS[$slug];
    // Most entries are plain cities ("Easton" → "Easton, PA"); townships and
    // neighborhoods override the full line and the schema.org type.
    $city_line = $loc['city_line'] ?? $loc['city'] . ', PA';
    $area_type = $loc['area_type'] ?? 'City';
    ?>
    <div class="mkt">
        <section class="page-hero">
            <div class="page-hero__bg" aria-hidden="true"></div>
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> <a href="<?= e(url('locations/index.php')) ?>">Service Areas</a><span>/</span> <?= e($loc['city']) ?> <?= e($loc['service_label']) ?></nav>
                <span class="eyebrow"><?= e($loc['service_label']) ?> in <?= e($city_line) ?></span>
                <h1 style="margin-top:1rem"><?= $loc['hero_heading'] ?></h1>
                <p><?= e($loc['hero_intro']) ?></p>
            </div>
        </section>

        <section class="section section--tight">
            <div class="container split">
                <div class="split__media"><img src="<?= e(url($loc['hero_image'])) ?>" alt="<?= e($loc['hero_image_alt']) ?>"></div>
                <div class="split__body">
                    <span class="eyebrow">Why <?= e($loc['city']) ?> homeowners choose us</span>
                    <h2 style="margin-top:1rem"><?= e($loc['intro_heading']) ?></h2>
                    <p><?= e($loc['intro_body']) ?></p>
                    <ul class="split__list">
                        <?php foreach ($loc['feature_cards'] as [$h, $b]): ?>
                            <li><?= svg_circle_check() ?><div><strong><?= e($h) ?></strong><span><?= e($b) ?></span></div></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>

        <section class="section section--tight" style="background:var(--plaster-2)">
            <div class="container">
                <div class="section-head center"><span class="eyebrow" style="justify-content:center">Recent work</span><h2 style="margin-top:1rem">Projects in <?= e($loc['city']) ?> &amp; nearby</h2></div>
                <?php location_gallery_preview($loc['gallery_categories']); ?>
            </div>
        </section>

        <section class="section section--tight">
            <div class="container">
                <div class="section-head center"><span class="eyebrow" style="justify-content:center">What neighbors say</span><h2 style="margin-top:1rem">Real reviews from real customers</h2></div>
                <?php location_testimonials($loc['city']); ?>
            </div>
        </section>

        <?php mkt_faq_custom($loc['faq'], $loc['service_label'] . ' in ' . $loc['city'] . ' — common questions'); ?>
        <?php mkt_service_jsonld($loc['service_name'], $loc['meta_description'], [['@type' => $area_type, 'name' => $city_line]]); ?>

        <section class="section section--tight" style="background:var(--plaster-2)">
            <div class="container">
                <div class="section-head center"><span class="eyebrow" style="justify-content:center">Explore more</span><h2 style="margin-top:1rem">Related services &amp; areas</h2></div>
                <div class="center" style="display:flex;gap:1rem;flex-wrap:wrap;justify-content:center">
                    <a href="<?= e(url($loc['related_service_slug'])) ?>" class="btn btn--outline"><?= e($loc['related_service_label']) ?></a>
                    <?php foreach ($loc['related_slugs'] as $sib): ?>
                        <a href="<?= e(url('locations/' . $sib . '.php')) ?>" class="btn btn--outline"><?= e($LOCATIONS[$sib]['city']) ?> <?= e($LOCATIONS[$sib]['service_label']) ?></a>
                    <?php endforeach; ?>
                    <a href="<?= e(url('locations/index.php')) ?>" class="btn btn--outline">All Service Areas</a>
                </div>
            </div>
        </section>

        <?php mkt_cta_band($loc['cta_eyebrow'], $loc['cta_title'], $loc['cta_text']); ?>
    </div>
    <?php
}
