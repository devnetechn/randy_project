<?php
/** Admin-managed customer reviews, stored in the `reviews` table. */

/** Every review, newest first. Each row: ['id','author','rating','body','meta','created_at']. */
function reviews_all(): array
{
    return db()->query(
        'SELECT id, author, rating, body, meta, created_at FROM reviews ORDER BY created_at DESC, id DESC'
    )->fetchAll();
}

/** A single review by id, or null. */
function reviews_find(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM reviews WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}
