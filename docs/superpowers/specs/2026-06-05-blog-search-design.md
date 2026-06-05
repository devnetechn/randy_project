# Blog live search — design

**Date:** 2026-06-05
**Status:** Approved

## Goal

Let visitors quickly find blog posts on the public Blog page by typing a keyword,
filtering the visible posts instantly without a page reload.

## Scope

- **Changes:** `blog.php` only.
- **No changes:** `blog_posts` schema, admin SPA, blog API endpoints, SEO meta keywords.

## Behavior

1. A search input is rendered at the top of the posts section (inside
   `.section--tight`, before `.blog-grid`), styled to match the existing `.mkt`
   marketing styles. Placeholder: e.g. "Search posts… (e.g. drywall, ceiling)".
2. Each `.blog-card` carries a `data-search` attribute containing the lowercased,
   HTML-stripped concatenation of title + excerpt + body. This is what the filter
   matches against, so non-visible body text is still searchable.
3. Client-side JS filters live as the user types:
   - Split the query into whitespace-separated terms.
   - A card stays visible only if **all** terms appear in its `data-search`.
   - Matching is case-insensitive; leading/trailing whitespace trimmed.
4. When no card matches, a "No posts match …" message is shown.
5. When the query is empty, all posts are shown again.
6. Graceful degradation: with JS disabled, all posts render normally and the
   search box simply does nothing (it is purely additive).

## Edge cases

- Empty/whitespace-only query → show all.
- No published posts at all → existing empty-state message stays; search box may be
  hidden when there are zero posts.
- Long bodies → acceptable page weight; the local blog has few posts.

## Out of scope

- Server-side search / `?q=` URL params.
- Per-post dedicated keywords field.
- Tag/category filtering.
