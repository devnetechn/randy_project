# Google Reviews on homepage + review button — design

**Date:** 2026-06-05
**Status:** Approved

## Goal

Show the business's real Google reviews on the homepage "Kind words" section,
auto-fetched from Google, and add a "Leave a review" button that sends visitors to
the Google review page. When Google reviews are unavailable, fall back to the
existing hardcoded sample testimonials so the homepage is never empty.

## Decisions

- **Source:** Google Places API (New) — automatic fetch.
- **Display:** Replace the homepage testimonials (`mkt_testimonials()`).
- **Fallback:** Existing 3 sample testimonials when no real reviews are available.
- **Caching:** Fetch at most once per 24h, cached in the `settings` table, to keep
  API cost near zero and the homepage fast.

## Architecture / data flow

```
Google Places API ──(≤1×/day)──> settings cache (DB) ──> mkt_testimonials()
       ▲                                                      │ null?
   api_key + place_id (admin-set)                             ▼
                                                  hardcoded sample testimonials
```

## Components

### `includes/reviews.php` (new)
- `google_reviews_get(): ?array` — returns cached reviews when `cached_at` is < 24h
  old; otherwise fetches live, caches, and returns. Returns `null` on any failure
  (missing key/place id, HTTP error, no reviews). Never throws to callers.
- `google_reviews_fetch(string $key, string $placeId): ?array` — calls the Places
  API (New) Place Details endpoint and parses the response.
- Endpoint: `GET https://places.googleapis.com/v1/places/{placeId}`
  - Headers: `X-Goog-Api-Key: {key}`,
    `X-Goog-FieldMask: reviews,rating,userRatingCount,displayName`
  - cURL, 15s timeout (mirrors `includes/gemini.php`).
- Parsed shape per review:
  `{ author, rating (int 1-5), text, when (relative string), photo (url|null) }`,
  derived from `reviews[].authorAttribution.displayName`, `reviews[].rating`,
  `reviews[].text.text`, `reviews[].relativePublishTimeDescription`,
  `reviews[].authorAttribution.photoUri`.
- Returns `{ rating, total, reviews: [...] }` so the section can show an aggregate.

### `includes/marketing.php` (modify `mkt_testimonials()`)
- Try `google_reviews_get()`. If it returns reviews, render them in the existing
  `.testi-grid` markup: real star count from each review's rating, author name,
  relative time as the meta line, review text as the quote, avatar = first letter of
  author (or photo if available).
- If `null`, render the current hardcoded `$items` array (unchanged behavior).
- Append a "Leave a review" button below the grid linking to the configured review
  URL (`google_reviews_review_url`), `target="_blank" rel="noopener"`. Button is
  shown whenever a review URL is set, regardless of fallback.

### `api/reviews/settings.php` (new)
- `GET` (admin): returns `{ apiKey, placeId, reviewUrl, cachedAt, count }`.
- `POST` (admin): validates and saves `apiKey`, `placeId`, `reviewUrl`
  (URL validated with `FILTER_VALIDATE_URL` when non-empty). Uses `require_admin_api`,
  `read_json`, `json_out`, `json_error` per existing API conventions.

### `api/reviews/refresh.php` (new)
- `POST` (admin): force a live fetch (bypassing the 24h cache), store it, and return
  `{ ok, count, rating }` or `json_error` with the failure reason.

### `admin/index.php` + `assets/js/admin.js` (modify)
- Add a "Reviews" tab to the admin nav and a matching `[data-panel="reviews"]`.
- `initReviews(panel)` module: a settings form (API key, Place ID, review URL,
  Save) plus a "Refresh now" button that calls `api/reviews/refresh.php` and toasts
  the result (e.g. "Fetched 5 reviews ⭐ 4.9"). Show last-cached time when present.
- Register `reviews: initReviews` in `MODULES`.

### `assets/css/styles.css` (modify)
- Minor additions only: optional avatar-photo variant and the "Leave a review"
  button spacing. Reuse existing `.testi`, `.testi-grid`, `.btn` styles.

## Settings keys (no SQL change — `settings` is key/value)

- `google_reviews_api_key`
- `google_reviews_place_id`
- `google_reviews_review_url` (defaults to the existing Google review share link)
- `google_reviews_cache` (JSON: `{ rating, total, reviews }`)
- `google_reviews_cached_at` (DATETIME string)

## Security

- The API key is used only server-side in PHP; it is never sent to the browser.
- Recommend restricting the key to the Places API in Google Cloud (documented to the
  user, not enforced in code).

## Constraints (acknowledged)

- Google returns at most 5 reviews and chooses which; not selectable.
- Requires a Google Cloud project with billing (API key); free tier typically covers
  one daily call at no cost.
- Reviews are cached with a daily refresh — a practical-standard caching window.

## Out of scope

- Replying to reviews, review moderation, or storing full review history.
- Showing more than the 5 reviews Google returns.
- Per-page review widgets beyond the homepage section.

## Testing

- With a valid key + Place ID: homepage renders real reviews; admin "Refresh now"
  reports the count and rating.
- With key removed/blank: homepage falls back to sample testimonials.
- Simulated API failure (bad key): `google_reviews_get()` returns null → fallback;
  admin refresh surfaces the error.
- Review button: appears and links to the configured Google review URL.
