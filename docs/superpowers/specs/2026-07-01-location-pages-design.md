# Location Pages (Phase 1 of SEO/Content Expansion)

**Date:** 2026-07-01
**Status:** Approved (design)

## Summary

This is phase 1 of a larger SEO/content roadmap the owner requested:

1. **Location pages** (this spec)
2. Dedicated FAQ page
3. Before/after project page depth (expand `project.php`)
4. Internal linking pass across services/locations/blog
5. Blog content calendar (ongoing content creation, not a build)

Each phase gets its own spec/plan cycle. This spec covers only phase 1: five
new city+service landing pages, reusing the existing dedicated-service-page
pattern (`level-5-drywall.php`, `skim-coating.php`, `wall-restoration.php`).

## Goals

- Five indexable, unique-content pages targeting city+service search intent:
  - Painting Contractor — Easton, PA
  - Painting Contractor — Bethlehem, PA
  - Painting Contractor — Allentown, PA
  - Drywall Repair — Easton, PA
  - Drywall Repair — Bethlehem, PA
- A `/locations/index.php` hub page so the five pages aren't orphaned, and so
  future cities have somewhere to be listed.
- Correct `Service` + `FAQPage` structured data per page, with `areaServed` set
  to the specific city rather than the site-wide Lehigh Valley/Bucks County pair.
- Reachable from primary nav, footer, and the sitemap (no orphan pages).

## Non-Goals (YAGNI)

- No `locations` database table or admin UI — content is static PHP, matching
  the existing pattern for dedicated service pages (also hardcoded).
- No new gallery "tag by city" field — location pages reuse existing
  category-filtered gallery images (interior/exterior for painting pages,
  drywall for drywall-repair pages), not a per-city photo query.
- No fabricated reviews — testimonials are pulled from real admin-entered
  `reviews` rows, filtered by matching the city name in the existing `meta`
  text field, falling back to unfiltered real reviews if none match. Never
  invent a review or re-attribute one to a city it didn't mention.
- No deep cross-linking edits to existing service pages (`level-5-drywall.php`,
  `skim-coating.php`, etc.) beyond what's needed to avoid orphaning the new
  pages. Broader cross-linking is phase 4.
- No `.htaccess` changes — the existing generic rewrite rules
  (`^(.+?)/?$ $1.php`) already clean-URL anything under a subfolder.

## Architecture

### New: `includes/locations.php`

- `$LOCATIONS` — an associative array keyed by slug
  (`easton-painting`, `bethlehem-painting`, `allentown-painting`,
  `easton-drywall-repair`, `bethlehem-drywall-repair`). Each entry holds:
  `title`, `meta_description`, `meta_keywords`, `city`, `service_label`,
  `hero_heading`, `hero_intro`, `feature_cards` (3x `[heading, body]`),
  `gallery_category` (maps to existing `gallery_images.category`), `faq`
  (array of `[question, answer]`), `related_slugs` (sibling location slugs to
  cross-link).
- `location_page(string $slug): void` — renders the shared layout: breadcrumb
  (Home > Service Areas > {City} {Service}) → hero → intro split section →
  3-card feature grid → testimonials (city-filtered via `reviews_all()` meta
  match, fallback to unfiltered) → `mkt_faq_custom($faq, ...)` → related-links
  section (sibling cities + relevant service page + `/locations` hub) →
  `mkt_cta_band(...)` → `mkt_service_jsonld($name, $description, $areaServed)`.

### New: `/locations/{slug}.php` (5 files)

Thin entry points, e.g.:

```php
<?php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/marketing.php';
require_once __DIR__ . '/../includes/locations.php';
$loc = $LOCATIONS['easton-painting'];
$page_title = $loc['title'];
$page_description = $loc['meta_description'];
$page_keywords = $loc['meta_keywords'];
require __DIR__ . '/../includes/header.php';
location_page('easton-painting');
require __DIR__ . '/../includes/footer.php';
```

### New: `/locations/index.php`

Hub page, styled like `services.php`'s grid: one card per entry in
`$LOCATIONS`, linking to each page. Written so adding a new city later is
"add an array entry + one thin file + one card."

### Edit: `includes/marketing.php`

Extend `mkt_service_jsonld()` to accept an optional `$areaServed` array,
defaulting to the current hardcoded Lehigh Valley/Bucks County pair so
existing callers (`level-5-drywall.php`, etc.) are unaffected:

```php
function mkt_service_jsonld(string $name, string $description, ?array $areaServed = null): void
```

### Edit: `includes/header.php`

Add a "Service Areas" link to the Services dropdown (desktop) and the
Services accordion (mobile), pointing to `/locations/index.php`.

### Edit: `includes/footer.php`

Add Service Areas hub link to the Company footer column.

### Edit: `sitemap.php`

Add the hub page and all 5 location pages to the static `$pages` array.
Also add `commercial.php`, which is missing from the current sitemap
(pre-existing gap, fixed while touching this file).

## Content Plan (per page, ~500-1000 words)

Drafted by Claude, following the site's existing luxury/high-end positioning
and Lehigh Valley + Bucks County expansion tone (per prior repositioning).
Each page: local framing (what homes/buildings look like in that city, common
issues), 3 differentiators, real filtered testimonials, 4-5 city-specific FAQ
items, related links, CTA. No fabricated stats or claims about
city-specific project counts.

## Testing

- Manual: load each of the 5 pages + hub, confirm clean URLs resolve
  (`/locations/easton-painting`, no `.php` needed), nav/footer links work,
  breadcrumbs correct.
- Validate JSON-LD (Service + FAQPage) via Google's Rich Results Test for one
  page.
- Confirm `/sitemap.xml` includes all 6 new URLs plus `commercial.php`.
