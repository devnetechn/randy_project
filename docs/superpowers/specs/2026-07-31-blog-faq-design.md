# Blog post FAQs — design

## Purpose

Let the admin attach an optional list of FAQ (question/answer) pairs to individual blog posts, editable from the admin panel, and shown on the public post page in the same accordion FAQ style already used on the services/commercial pages.

## Data layer

- New column `blog_posts.faqs JSON NULL` — an array of `{"q": "...", "a": "..."}` objects, stored in display order. `NULL` or an empty array means the post has no FAQ section.
- `sql/tables.sql`: add `faqs JSON NULL` to the `blog_posts` table definition, so fresh installs get it via `CREATE TABLE IF NOT EXISTS`.
- `setup.php`: add a guarded migration block (same `colExists()` pattern used for the other blog_posts/appointments columns) that runs `ALTER TABLE blog_posts ADD COLUMN faqs JSON NULL` on existing installs that don't have the column yet.

No new table. A per-post FAQ list is small and doesn't need independent querying — it's stored and read alongside the rest of the post, the same way `body` already is.

## API changes

- `api/blog/save.php`:
  - Read `$_POST['faqs']` as a JSON string (sent by the admin form).
  - `json_decode` it; if invalid or absent, treat as no FAQs.
  - Drop any pair where question or answer is blank after trimming.
  - Cap the list at 20 pairs (defensive limit; not a real product constraint, just guards against abuse).
  - Re-encode the cleaned array (or `NULL` if empty) and include it in the `INSERT`/`UPDATE` of `blog_posts`.
  - Include `faqs` (decoded back to an array) in the JSON response for the saved post, matching how `image` etc. are already returned.
- `api/blog/get.php`: include `faqs` (json_decode'd) in the returned post object.
- `includes/blog.php`:
  - `blog_published()` and `blog_find_published()`: select `faqs` along with the other columns; decode it to an array (or `[]`) before returning, so callers never have to deal with the raw JSON string.

## Admin UI (`assets/js/admin.js`, blog panel)

- Add a "FAQs" section inside the existing blog post form (`[data-blog-form]`), below the Status/Image fields and above the save button:
  - A container (`[data-blog-faq-list]`) holding one row per FAQ pair — each row has a question `<input>`, an answer `<textarea>`, and a "Remove" button.
  - An "Add FAQ" button that appends a new empty row to the container.
- **Loading for edit:** when an existing post is loaded into the form (`data-edit` click handler), clear the FAQ rows and rebuild them from `p.faqs` (one row per entry). For a new/blank post, start with zero rows.
- **On submit:** before building `FormData(form)`, read all current rows, filter out any with a blank question or answer, serialize the rest to a JSON string, and set it on a hidden `<input type="hidden" name="faqs">` inside the form so it's picked up by `FormData` automatically.
- **On clear/reset:** the existing `clearForm()` also empties the FAQ row container back to zero rows.

No new admin API calls — FAQs travel with the existing `save.php`/`get.php`/`list.php` (list doesn't need FAQ data, only the single-post fetch and the save round-trip do).

## Public display (`blog-post.php`)

- After the existing post body block, if `$post['faqs']` is a non-empty array, map it to `[question, answer]` pairs and call the existing `mkt_faq_custom($pairs, 'Frequently asked questions')` helper from `includes/marketing.php`.
  - This reuses the site's existing accordion markup/JS (`data-faq`, `data-faq-q`) and automatically emits `FAQPage` JSON-LD structured data, consistent with how services/commercial pages already do it.
- If `faqs` is empty/null, nothing extra renders — the call is simply skipped.

## Out of scope / explicitly not doing

- No rich text/markdown in FAQ answers — plain text only, consistent with the existing `mkt_faq`/`mkt_faq_custom` usage across the site (answers are `e()`-escaped, not HTML-rendered).
- No reordering via drag-and-drop — order is just the order the rows are in when saved (admin can remove and re-add to reorder if needed).
- No separate "FAQ library" reused across posts — each post's FAQs are independent, entered fresh per post.
