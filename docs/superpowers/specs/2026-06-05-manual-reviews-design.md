# Manual review manager — design

**Date:** 2026-06-05
**Status:** Approved (supersedes the Google Places API approach in
`2026-06-05-google-reviews-design.md`)

## Why the change

The Google Places API requires a Google Cloud project with billing (a credit card),
which the owner does not want. Instead, the admin manages reviews by hand — copying
real Google reviews into the site. No credit card, no external dependency.

## Goal

Admin-managed reviews shown on the homepage "Kind words" section, with the existing
"Leave us a review on Google" button preserved. Falls back to the 3 hardcoded sample
testimonials when no reviews have been entered.

## Storage — new `reviews` table

```sql
CREATE TABLE IF NOT EXISTS reviews (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author     VARCHAR(120) NOT NULL,
  rating     TINYINT UNSIGNED NOT NULL DEFAULT 5,
  body       TEXT NOT NULL,
  meta       VARCHAR(160) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Added to `sql/tables.sql` and executed against the live DB now (setup.php already ran).

## Components

### `includes/reviews.php` (rewrite — drop Google API code)
- `reviews_all(): array` — every review, newest first; each row
  `['author','rating','body','meta']`.
- Remove the Places API fetch/cache/parse functions and the `GOOGLE_REVIEWS_TTL`
  constant — no longer used.

### `includes/marketing.php` (adjust `mkt_testimonials()`)
- Call `reviews_all()`. If non-empty, render those; else render the 3 hardcoded
  samples (unchanged fallback). Star count comes from each review's `rating`,
  avatar = first letter of `author`, `meta` shown as the subtitle when present.
- Keep the "Leave us a review on Google" button driven by
  `google_reviews_review_url` (default = the existing g.page link). The settings key
  name is retained to avoid a needless migration.

### API (mirrors the blog endpoints)
- `api/reviews/list.php` — GET: all reviews (admin and public both get all; there is
  no draft concept). Returns `{ reviews: [{id, author, rating, body, meta, date}] }`.
- `api/reviews/save.php` — POST (admin, JSON body): create/update. Fields: `id?`,
  `author` (required), `rating` (1–5, default 5), `body` (required), `meta?`.
- `api/reviews/delete.php` — POST (admin, JSON `{id}`).
- `api/reviews/settings.php` — trimmed to just the review-button URL (`reviewUrl`
  GET/POST). Drop the apiKey/placeId/cache fields.
- `api/reviews/refresh.php` — deleted.

### Admin (`assets/js/admin.js` → `initReviews`)
- Replace the API-key form with:
  1. A "Leave a review" URL field (Save).
  2. An add/edit review form: Author, Rating (select 1–5), Review text, Meta
     (optional). Save.
  3. A list of existing reviews with Edit and Delete per row.
- Reuse the blog tab's list/edit/reset interaction style.

## Out of scope

- Auto-sync from Google (that was the API approach, now dropped).
- Review photos/avatars beyond the initial-letter circle.
- Ordering controls (newest-first is fixed).

## Testing

- Add a review in admin → it appears on the homepage, samples disappear.
- Edit and delete work and reflect on the homepage.
- With zero reviews → samples show.
- Review button still links to the configured Google URL.
