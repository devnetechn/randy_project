# Index "We're Hiring" job title cards

## Problem
The homepage's "We're hiring" CTA band (`index.php` lines 131-149) currently shows generic
copy ("Join our crew") and one button linking to `careers.php`. It doesn't surface which
positions are actually open. The owner wants visitors to see the actual open job title(s)
right on the homepage, and clicking a title should go straight to the careers page.

## Change
In `index.php`:

- Replace the count-only query (`SELECT COUNT(*) FROM job_positions WHERE status = 'open'`)
  with one that fetches `id, title` for each open position, ordered by `created_at DESC`.
- Keep the section gated on there being at least one open position (same as today).
- Keep the dark `cta-band` styling, the "We're hiring" eyebrow, the "Join our crew" heading,
  and the short intro paragraph.
- Replace the single "View open positions" button with a row/grid of cards — one per open
  position — each showing only the job title. The entire card is a link (`<a>`) to
  `careers.php` (the general careers page, not a specific position anchor).

## Styling
Add a small new CSS rule (e.g. `.hiring-card`) in `assets/css/styles.css`, scoped under `.mkt`,
styled to sit on the dark `cta-band` background (translucent/white card, hover lift, small
arrow icon) — consistent with existing hover patterns like `.service-card` and `.textlink`.

## Out of scope
- No changes to `careers.php`, `job_positions`/`job_applications` schema, or any API endpoint.
- No per-position deep link (all cards point to the same `careers.php` URL).
