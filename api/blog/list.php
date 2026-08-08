<?php
/** Blog listing. Public: published only. Admin: all. Optional ?limit=N. */
require_once __DIR__ . '/../../includes/app.php';

$limit = isset($_GET['limit']) ? max(1, min(50, (int) $_GET['limit'])) : null;

if (is_admin()) {
    $sql = 'SELECT id, title, slug, excerpt, image, status, created_at FROM blog_posts ORDER BY created_at DESC';
    $st = $limit !== null ? db()->prepare($sql . ' LIMIT ?') : db()->query($sql);
    if ($limit !== null) { $st->bindValue(1, $limit, PDO::PARAM_INT); $st->execute(); }
} else {
    $sql = 'SELECT id, title, slug, excerpt, image, status, created_at FROM blog_posts WHERE status = \'published\' ORDER BY created_at DESC';
    $st = $limit !== null ? db()->prepare($sql . ' LIMIT ?') : db()->query($sql);
    if ($limit !== null) { $st->bindValue(1, $limit, PDO::PARAM_INT); $st->execute(); }
}

$posts = array_map(function ($p) {
    return [
        'id'      => (int) $p['id'],
        'title'   => $p['title'],
        'slug'    => $p['slug'],
        'excerpt' => $p['excerpt'],
        'image'   => $p['image'] ? url('uploads/blog/' . $p['image']) : null,
        'status'  => $p['status'],
        'date'    => $p['created_at'],
    ];
}, $st->fetchAll());

json_out(['posts' => $posts]);
