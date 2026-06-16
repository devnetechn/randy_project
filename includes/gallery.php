<?php
/** Gallery data helpers — shared by the project detail page and the sitemap. */

require_once __DIR__ . '/db.php';

/** A single gallery photo row, or null if not found. */
function gallery_find(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM gallery_images WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

/** Every gallery photo, ordered the same way as the public grid. */
function gallery_all(): array
{
    return db()->query('SELECT * FROM gallery_images ORDER BY sort_order ASC, created_at DESC')->fetchAll();
}
