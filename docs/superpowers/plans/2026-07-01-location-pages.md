# Location Pages — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add five indexable city+service landing pages (Painting Contractor — Easton/Bethlehem/Allentown, PA; Drywall Repair — Easton/Bethlehem, PA) plus a `/locations` hub, wired into nav, footer, and the sitemap.

**Architecture:** One data+render helper (`includes/locations.php`) holds a `$LOCATIONS` array (unique copy per city+service) and a shared `location_page()` renderer that reuses the existing dedicated-service-page layout (hero, split intro, feature grid, testimonials, FAQ, related links, CTA) and existing components (`mkt_faq_custom`, `mkt_cta_band`, `mkt_service_jsonld`, gallery/review helpers). Five thin `/locations/*.php` entry files plus a `/locations/index.php` hub follow the same one-file-per-page pattern as `level-5-drywall.php` etc. No new database tables — content is static PHP, matching how other dedicated service pages are authored.

**Tech Stack:** Vanilla PHP 8 + PDO/MySQL (XAMPP), plain JS/CSS (no framework, no build step). **No test framework exists** — verification is via `php -l` (lint), short PHP CLI scripts run with `C:\xampp\php\php.exe`, and browser/HTTP checks against `http://localhost/randy/...`.

**Conventions to follow:**
- Pages bootstrap with `require_once __DIR__ . '/includes/app.php';` (or `../includes/app.php` for files inside a subfolder).
- Output escaping: `e($value)`. URLs: `url('path.php')` (strips `.php` for clean links; `.htaccess` already maps any nested clean path like `/locations/easton-painting` back to `locations/easton-painting.php` via its generic rewrite rule — no `.htaccess` changes needed).
- Marketing helpers live in `includes/marketing.php` (`mkt_faq_custom`, `mkt_cta_band`, `mkt_service_jsonld`, `svg_circle_check`, `mkt_star`, etc.).
- `reviews_all()` (`includes/reviews.php`) and `gallery_all()` / `gallery_images.category` (`includes/gallery.php`) are the existing data sources reused here — no schema changes.
- Commit after each task.

---

## File Structure

| File | Responsibility | Action |
|------|----------------|--------|
| `includes/marketing.php` | `mkt_service_jsonld()` | Modify (optional `$areaServed` override) |
| `includes/locations.php` | `$LOCATIONS` data + `location_page()` + `location_testimonials()` + `location_gallery_preview()` | Create |
| `locations/easton-painting.php` | Thin entry page | Create |
| `locations/bethlehem-painting.php` | Thin entry page | Create |
| `locations/allentown-painting.php` | Thin entry page | Create |
| `locations/easton-drywall-repair.php` | Thin entry page | Create |
| `locations/bethlehem-drywall-repair.php` | Thin entry page | Create |
| `locations/index.php` | Service-areas hub page | Create |
| `includes/header.php` | Desktop + mobile nav | Modify (add "Service Areas" link) |
| `includes/footer.php` | Footer Company column | Modify (add "Service Areas" link) |
| `sitemap.php` | Dynamic XML sitemap | Modify (add 6 new URLs + missing `commercial.php`) |

---

## Task 1: Extend `mkt_service_jsonld()` with an optional `areaServed` override

**Files:**
- Modify: `includes/marketing.php:234-250`

- [ ] **Step 1: Add the optional parameter**

In `includes/marketing.php`, replace the existing `mkt_service_jsonld()` function with:

```php
/** Emit Service structured data for a dedicated service page (SEO rich results). */
function mkt_service_jsonld(string $name, string $description, ?array $areaServed = null): void
{
    $b = business_info();
    $ld = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        'serviceType' => $name,
        'name'        => $name,
        'description' => $description,
        'provider'    => ['@type' => 'HousePainter', 'name' => $b['name'], 'telephone' => $b['phoneTel']],
        'areaServed'  => $areaServed ?? [
            ['@type' => 'AdministrativeArea', 'name' => 'Lehigh Valley'],
            ['@type' => 'AdministrativeArea', 'name' => 'Bucks County'],
        ],
    ];
    echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l includes/marketing.php`
Expected: `No syntax errors detected in includes/marketing.php`

- [ ] **Step 3: Verify existing callers are unaffected**

```
C:\xampp\php\php.exe -l level-5-drywall.php
C:\xampp\php\php.exe -l skim-coating.php
C:\xampp\php\php.exe -l wall-restoration.php
C:\xampp\php\php.exe -l commercial.php
```
Expected: `No syntax errors detected` for all four (their existing 2-arg calls still work since the third parameter is optional).

- [ ] **Step 4: Commit**

```bash
git add includes/marketing.php
git commit -m "feat(seo): allow mkt_service_jsonld() to override areaServed"
```

---

## Task 2: Create `includes/locations.php` (data + render helpers)

**Files:**
- Create: `includes/locations.php`

- [ ] **Step 1: Create the file**

Create `includes/locations.php`:

```php
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
        'title'                => "Painting Contractor in Easton, PA | Randy's Painting & Drywall Services",
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
        'related_slugs'        => ['bethlehem-painting', 'allentown-painting'],
    ],

    'bethlehem-painting' => [
        'title'                => "Painting Contractor in Bethlehem, PA | Randy's Painting & Drywall Services",
        'meta_description'     => "Interior and exterior painting for Bethlehem, PA homes and businesses — from historic rowhomes to South Side lofts. Licensed, insured, free estimates.",
        'meta_keywords'        => 'painting contractor Bethlehem PA, house painter Bethlehem PA, interior painting Bethlehem PA, exterior painting Bethlehem PA, South Side Bethlehem painter',
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
        'title'                => "Painting Contractor in Allentown, PA | Randy's Painting & Drywall Services",
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
        'title'                => "Drywall Repair in Easton, PA | Randy's Painting & Drywall Services",
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
        'title'                => "Drywall Repair in Bethlehem, PA | Randy's Painting & Drywall Services",
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
    ?>
    <div class="mkt">
        <section class="page-hero">
            <div class="page-hero__bg" aria-hidden="true"></div>
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> <a href="<?= e(url('locations/index.php')) ?>">Service Areas</a><span>/</span> <?= e($loc['city']) ?> <?= e($loc['service_label']) ?></nav>
                <span class="eyebrow"><?= e($loc['service_label']) ?> in <?= e($loc['city']) ?>, PA</span>
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
        <?php mkt_service_jsonld($loc['service_name'], $loc['meta_description'], [['@type' => 'City', 'name' => $loc['city'] . ', PA']]); ?>

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
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l includes/locations.php`
Expected: `No syntax errors detected in includes/locations.php`

- [ ] **Step 3: Verify the renderer produces output for every slug**

Run:
```
C:\xampp\php\php.exe -r "require 'includes/app.php'; require 'includes/locations.php'; foreach (array_keys($LOCATIONS) as $slug) { ob_start(); location_page($slug); $html = ob_get_clean(); echo $slug . ': len=' . strlen($html) . ' hasFAQ=' . (strpos($html, 'FAQPage') !== false ? 'Y' : 'N') . ' hasService=' . (strpos($html, '\"@type\":\"Service\"') !== false ? 'Y' : 'N') . PHP_EOL; }"
```
Expected: 5 lines, one per slug, each with `len=` well over 2000, `hasFAQ=Y`, `hasService=Y`.

- [ ] **Step 4: Commit**

```bash
git add includes/locations.php
git commit -m "feat(seo): add location pages data + shared renderer"
```

---

## Task 3: Create the 5 location entry pages

**Files:**
- Create: `locations/easton-painting.php`
- Create: `locations/bethlehem-painting.php`
- Create: `locations/allentown-painting.php`
- Create: `locations/easton-drywall-repair.php`
- Create: `locations/bethlehem-drywall-repair.php`

- [ ] **Step 1: Create `locations/easton-painting.php`**

```php
<?php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/locations.php';
$loc = $LOCATIONS['easton-painting'];
$page_title = $loc['title'];
$page_description = $loc['meta_description'];
$page_keywords = $loc['meta_keywords'];
require __DIR__ . '/../includes/header.php';
location_page('easton-painting');
require __DIR__ . '/../includes/footer.php';
```

- [ ] **Step 2: Create `locations/bethlehem-painting.php`**

```php
<?php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/locations.php';
$loc = $LOCATIONS['bethlehem-painting'];
$page_title = $loc['title'];
$page_description = $loc['meta_description'];
$page_keywords = $loc['meta_keywords'];
require __DIR__ . '/../includes/header.php';
location_page('bethlehem-painting');
require __DIR__ . '/../includes/footer.php';
```

- [ ] **Step 3: Create `locations/allentown-painting.php`**

```php
<?php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/locations.php';
$loc = $LOCATIONS['allentown-painting'];
$page_title = $loc['title'];
$page_description = $loc['meta_description'];
$page_keywords = $loc['meta_keywords'];
require __DIR__ . '/../includes/header.php';
location_page('allentown-painting');
require __DIR__ . '/../includes/footer.php';
```

- [ ] **Step 4: Create `locations/easton-drywall-repair.php`**

```php
<?php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/locations.php';
$loc = $LOCATIONS['easton-drywall-repair'];
$page_title = $loc['title'];
$page_description = $loc['meta_description'];
$page_keywords = $loc['meta_keywords'];
require __DIR__ . '/../includes/header.php';
location_page('easton-drywall-repair');
require __DIR__ . '/../includes/footer.php';
```

- [ ] **Step 5: Create `locations/bethlehem-drywall-repair.php`**

```php
<?php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/locations.php';
$loc = $LOCATIONS['bethlehem-drywall-repair'];
$page_title = $loc['title'];
$page_description = $loc['meta_description'];
$page_keywords = $loc['meta_keywords'];
require __DIR__ . '/../includes/header.php';
location_page('bethlehem-drywall-repair');
require __DIR__ . '/../includes/footer.php';
```

- [ ] **Step 6: Lint all 5**

Run:
```
C:\xampp\php\php.exe -l locations/easton-painting.php
C:\xampp\php\php.exe -l locations/bethlehem-painting.php
C:\xampp\php\php.exe -l locations/allentown-painting.php
C:\xampp\php\php.exe -l locations/easton-drywall-repair.php
C:\xampp\php\php.exe -l locations/bethlehem-drywall-repair.php
```
Expected: `No syntax errors detected` for all 5.

- [ ] **Step 7: Verify each page loads (200) with clean URLs**

In PowerShell:
```powershell
foreach ($slug in @('easton-painting','bethlehem-painting','allentown-painting','easton-drywall-repair','bethlehem-drywall-repair')) {
    $r = Invoke-WebRequest "http://localhost/randy/locations/$slug" -UseBasicParsing
    "$slug -> $($r.StatusCode)"
}
```
Expected: `200` for all 5 (confirms the `.htaccess` clean-URL rewrite maps `/locations/<slug>` to `locations/<slug>.php` with no extra config).

- [ ] **Step 8: Spot-check content on one page**

```powershell
$h = (Invoke-WebRequest "http://localhost/randy/locations/easton-painting" -UseBasicParsing).Content
$h -match '<h1' ; $h -match 'College Hill' ; $h -match 'FAQPage' ; $h -match '"@type":"City"'
```
Expected: all `True`.

- [ ] **Step 9: Commit**

```bash
git add locations/easton-painting.php locations/bethlehem-painting.php locations/allentown-painting.php locations/easton-drywall-repair.php locations/bethlehem-drywall-repair.php
git commit -m "feat(seo): add 5 location landing pages"
```

---

## Task 4: Create the `/locations` hub page

**Files:**
- Create: `locations/index.php`

- [ ] **Step 1: Create the hub page**

```php
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
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l locations/index.php`
Expected: `No syntax errors detected in locations/index.php`

- [ ] **Step 3: Verify it loads and lists all 5**

```powershell
$r = Invoke-WebRequest "http://localhost/randy/locations" -UseBasicParsing
$r.StatusCode
($r.Content -split 'service-card--link').Count - 1
```
Expected: `200`, then `5` (5 cards).

- [ ] **Step 4: Commit**

```bash
git add locations/index.php
git commit -m "feat(seo): add /locations service-areas hub page"
```

---

## Task 5: Add "Service Areas" to primary navigation

**Files:**
- Modify: `includes/header.php:133-146` (desktop dropdown)
- Modify: `includes/header.php:189-203` (mobile accordion)

- [ ] **Step 1: Add the link to the desktop Services dropdown**

In `includes/header.php`, inside the Services `nav__dropdown` (after the `wall-restoration.php` link and its divider, before the `nav__dropdown-cta`), the block currently reads:

```html
                    <a href="<?= e(url('level-5-drywall.php')) ?>">Level 5 Drywall Finish</a>
                    <a href="<?= e(url('skim-coating.php')) ?>">Skim Coating</a>
                    <a href="<?= e(url('wall-restoration.php')) ?>">Wall Restoration</a>
                    <div class="nav__dropdown-divider"></div>
                    <a class="nav__dropdown-cta" href="<?= e(url('services.php')) ?>">All Services &rarr;</a>
```

Replace it with:

```html
                    <a href="<?= e(url('level-5-drywall.php')) ?>">Level 5 Drywall Finish</a>
                    <a href="<?= e(url('skim-coating.php')) ?>">Skim Coating</a>
                    <a href="<?= e(url('wall-restoration.php')) ?>">Wall Restoration</a>
                    <div class="nav__dropdown-divider"></div>
                    <a href="<?= e(url('locations/index.php')) ?>">Service Areas</a>
                    <div class="nav__dropdown-divider"></div>
                    <a class="nav__dropdown-cta" href="<?= e(url('services.php')) ?>">All Services &rarr;</a>
```

- [ ] **Step 2: Add the link to the mobile Services accordion**

The mobile accordion body currently reads:

```html
                <div class="nav__accordion-body" data-accordion-body>
                    <a href="<?= e(url('services.php')) ?>">Interior Painting</a>
                    <a href="<?= e(url('services.php')) ?>">Exterior Painting</a>
                    <a href="<?= e(url('services.php')) ?>">Drywall Installation &amp; Repair</a>
                    <a href="<?= e(url('level-5-drywall.php')) ?>">Level 5 Drywall Finish</a>
                    <a href="<?= e(url('skim-coating.php')) ?>">Skim Coating</a>
                    <a href="<?= e(url('wall-restoration.php')) ?>">Wall Restoration</a>
                    <a class="nav__panel-cta" href="<?= e(url('services.php')) ?>">All Services &rarr;</a>
                </div>
```

Replace it with:

```html
                <div class="nav__accordion-body" data-accordion-body>
                    <a href="<?= e(url('services.php')) ?>">Interior Painting</a>
                    <a href="<?= e(url('services.php')) ?>">Exterior Painting</a>
                    <a href="<?= e(url('services.php')) ?>">Drywall Installation &amp; Repair</a>
                    <a href="<?= e(url('level-5-drywall.php')) ?>">Level 5 Drywall Finish</a>
                    <a href="<?= e(url('skim-coating.php')) ?>">Skim Coating</a>
                    <a href="<?= e(url('wall-restoration.php')) ?>">Wall Restoration</a>
                    <a href="<?= e(url('locations/index.php')) ?>">Service Areas</a>
                    <a class="nav__panel-cta" href="<?= e(url('services.php')) ?>">All Services &rarr;</a>
                </div>
```

- [ ] **Step 3: Lint**

Run: `C:\xampp\php\php.exe -l includes/header.php`
Expected: `No syntax errors detected in includes/header.php`

- [ ] **Step 4: Verify in the browser**

Open `http://localhost/randy/` (homepage). Confirm:
- Desktop: hovering/clicking "Services" shows a "Service Areas" link (after a divider, before "All Services →") that navigates to `/locations`.
- Mobile width (or the hamburger menu): the Services accordion shows the same "Service Areas" link.

- [ ] **Step 5: Commit**

```bash
git add includes/header.php
git commit -m "feat(nav): add Service Areas link to Services dropdown"
```

---

## Task 6: Add "Service Areas" to the footer

**Files:**
- Modify: `includes/footer.php:34-43` (Services footer column)

- [ ] **Step 1: Add the link**

In `includes/footer.php`, the Services footer column currently reads:

```html
                <div class="footer-col">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="<?= e(url('level-5-drywall.php')) ?>">Level 5 Drywall Finish</a></li>
                        <li><a href="<?= e(url('skim-coating.php')) ?>">Skim Coating</a></li>
                        <li><a href="<?= e(url('wall-restoration.php')) ?>">Wall Restoration</a></li>
                        <li><a href="<?= e(url('services.php')) ?>">Painting &amp; Drywall</a></li>
                        <li><a href="<?= e(url('commercial.php')) ?>">Commercial Work</a></li>
                    </ul>
                </div>
```

Replace it with:

```html
                <div class="footer-col">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="<?= e(url('level-5-drywall.php')) ?>">Level 5 Drywall Finish</a></li>
                        <li><a href="<?= e(url('skim-coating.php')) ?>">Skim Coating</a></li>
                        <li><a href="<?= e(url('wall-restoration.php')) ?>">Wall Restoration</a></li>
                        <li><a href="<?= e(url('services.php')) ?>">Painting &amp; Drywall</a></li>
                        <li><a href="<?= e(url('commercial.php')) ?>">Commercial Work</a></li>
                        <li><a href="<?= e(url('locations/index.php')) ?>">Service Areas</a></li>
                    </ul>
                </div>
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l includes/footer.php`
Expected: `No syntax errors detected in includes/footer.php`

- [ ] **Step 3: Verify in the browser**

Open any page, scroll to the footer. Confirm the Services column now has a "Service Areas" link that navigates to `/locations`.

- [ ] **Step 4: Commit**

```bash
git add includes/footer.php
git commit -m "feat(footer): add Service Areas link"
```

---

## Task 7: Add location pages to the sitemap (and fix the missing `commercial.php`)

**Files:**
- Modify: `sitemap.php:18-28` (static `$pages` array)

- [ ] **Step 1: Add the hub + 5 location pages, and `commercial.php`**

In `sitemap.php`, the static `$pages` array currently reads:

```php
$pages = [
    ['index.php',          'weekly',  '1.0', $today],
    ['services.php',       'monthly', '0.9', $today],
    ['level-5-drywall.php','monthly', '0.9', $today],
    ['skim-coating.php',   'monthly', '0.9', $today],
    ['wall-restoration.php','monthly','0.9', $today],
    ['gallery.php',        'weekly',  '0.8', $today],
    ['about.php',          'monthly', '0.7', $today],
    ['blog.php',           'weekly',  '0.7', $today],
    ['contact.php',        'monthly', '0.8', $today],
];
```

Replace it with:

```php
$pages = [
    ['index.php',          'weekly',  '1.0', $today],
    ['services.php',       'monthly', '0.9', $today],
    ['level-5-drywall.php','monthly', '0.9', $today],
    ['skim-coating.php',   'monthly', '0.9', $today],
    ['wall-restoration.php','monthly','0.9', $today],
    ['commercial.php',     'monthly', '0.8', $today],
    ['gallery.php',        'weekly',  '0.8', $today],
    ['about.php',          'monthly', '0.7', $today],
    ['blog.php',           'weekly',  '0.7', $today],
    ['contact.php',        'monthly', '0.8', $today],
    ['locations/index.php',                    'monthly', '0.8', $today],
    ['locations/easton-painting.php',          'monthly', '0.8', $today],
    ['locations/bethlehem-painting.php',       'monthly', '0.8', $today],
    ['locations/allentown-painting.php',       'monthly', '0.8', $today],
    ['locations/easton-drywall-repair.php',    'monthly', '0.8', $today],
    ['locations/bethlehem-drywall-repair.php', 'monthly', '0.8', $today],
];
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l sitemap.php`
Expected: `No syntax errors detected in sitemap.php`

- [ ] **Step 3: Verify the new URLs appear**

```powershell
$x = (Invoke-WebRequest "http://localhost/randy/sitemap.php" -UseBasicParsing).Content
$x -match 'commercial'
([regex]::Matches($x, 'locations/')).Count
```
Expected: `True`, then `6` (hub + 5 location pages).

- [ ] **Step 4: Commit**

```bash
git add sitemap.php
git commit -m "feat(seo): add location pages to sitemap; fix missing commercial.php"
```

---

## Final verification

- [ ] Lint everything touched/created in one pass:
  `C:\xampp\php\php.exe -l includes/marketing.php; C:\xampp\php\php.exe -l includes/locations.php; C:\xampp\php\php.exe -l locations/index.php; C:\xampp\php\php.exe -l locations/easton-painting.php; C:\xampp\php\php.exe -l locations/bethlehem-painting.php; C:\xampp\php\php.exe -l locations/allentown-painting.php; C:\xampp\php\php.exe -l locations/easton-drywall-repair.php; C:\xampp\php\php.exe -l locations/bethlehem-drywall-repair.php; C:\xampp\php\php.exe -l includes/header.php; C:\xampp\php\php.exe -l includes/footer.php; C:\xampp\php\php.exe -l sitemap.php`
- [ ] Browse all 5 location pages + the hub in a browser; confirm breadcrumbs, related-links section, and CTA band render correctly and no PHP warnings/notices appear on the page.
- [ ] Confirm nav (desktop + mobile) and footer both link to `/locations`.
- [ ] Confirm `/sitemap.xml` includes the hub, all 5 location pages, and `commercial.php`.
- [ ] Paste the rendered HTML of one location page into Google's Rich Results Test (or view-source and manually check the two `<script type="application/ld+json">` blocks) to confirm the `Service` and `FAQPage` JSON-LD are well-formed.

---

## Spec coverage check

- Five location pages (Painting: Easton/Bethlehem/Allentown; Drywall Repair: Easton/Bethlehem) → Task 3 ✓
- `/locations` hub page → Task 4 ✓
- `Service` + `FAQPage` schema with city-specific `areaServed` → Task 1 (schema override) + Task 2 (`mkt_faq_custom` + `mkt_service_jsonld` calls inside `location_page()`) ✓
- Real (non-fabricated) testimonials filtered by city → Task 2 (`location_testimonials()`) ✓
- Existing category-filtered gallery photos, no new DB field → Task 2 (`location_gallery_preview()`) ✓
- Reachable from nav, footer, sitemap (no orphan pages) → Tasks 5, 6, 7 ✓
- `commercial.php` sitemap gap fixed → Task 7 ✓
