<?php
/** Blog queries + render helpers, shared by the public pages and the API. */

/** Latest published posts, newest first. Pass a limit for the teaser. */
function blog_published(?int $limit = null): array
{
    $sql = 'SELECT id, title, slug, excerpt, body, image, faqs, status, created_at
            FROM blog_posts WHERE status = \'published\'
            ORDER BY created_at DESC';
    if ($limit !== null) {
        $st = db()->prepare($sql . ' LIMIT ?');
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();
    } else {
        $st = db()->query($sql);
    }
    return array_map('blog_decode_faqs', $st->fetchAll());
}

/** A single published post, or null. */
function blog_find_published(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM blog_posts WHERE id = ? AND status = \'published\'');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ? blog_decode_faqs($row) : null;
}

/** Lowercase, hyphenated, URL-safe slug candidate from arbitrary text. */
function blog_slugify(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'post';
}

/** True if some other row already uses this exact slug. */
function blog_slug_taken(string $slug, ?int $excludeId = null): bool
{
    if ($excludeId !== null) {
        $st = db()->prepare('SELECT 1 FROM blog_posts WHERE slug = ? AND id != ? LIMIT 1');
        $st->execute([$slug, $excludeId]);
    } else {
        $st = db()->prepare('SELECT 1 FROM blog_posts WHERE slug = ? LIMIT 1');
        $st->execute([$slug]);
    }
    return (bool) $st->fetchColumn();
}

/** blog_slugify($candidate), with a "-2", "-3", ... suffix appended until unique. */
function blog_unique_slug(string $candidate, ?int $excludeId = null): string
{
    $base = blog_slugify($candidate);
    $slug = $base;
    $n = 2;
    while (blog_slug_taken($slug, $excludeId)) {
        $slug = $base . '-' . $n++;
    }
    return $slug;
}

/** A single published post by slug, or null. */
function blog_find_published_by_slug(string $slug): ?array
{
    $st = db()->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
    $st->execute([$slug]);
    $row = $st->fetch();
    return $row ? blog_decode_faqs($row) : null;
}

/** Decode a post row's `faqs` JSON column into an array of ['q' => ..., 'a' => ...] (empty array if none). */
function blog_decode_faqs(array $post): array
{
    $post['faqs'] = !empty($post['faqs']) ? (json_decode($post['faqs'], true) ?: []) : [];
    return $post;
}

/** Public URL for a post's featured image, or null when there's none. */
function blog_image_url(?string $filename): ?string
{
    return $filename ? url('uploads/blog/' . $filename) : null;
}

/**
 * Render a plain-text body as safe HTML (blank line = new paragraph).
 * A block starting with "## " renders as a section heading instead of a paragraph.
 */
function blog_render_body(string $body): string
{
    $blocks = preg_split('/\n\s*\n/', trim($body));
    $html = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        if (str_starts_with($block, '## ')) {
            $html .= '<h3>' . e(trim(substr($block, 3))) . '</h3>';
            continue;
        }
        $html .= '<p>' . nl2br(e($block)) . '</p>';
    }
    return $html;
}

/** Format a stored DATETIME as a human date, e.g. "June 3, 2026". */
function blog_date(?string $dt): string
{
    if (!$dt) {
        return '';
    }
    $ts = strtotime($dt);
    return $ts ? date('F j, Y', $ts) : '';
}
