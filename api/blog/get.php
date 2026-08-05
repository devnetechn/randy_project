<?php
/** Single blog post by ?id=. Drafts visible to admins only. */
require_once __DIR__ . '/../../includes/app.php';

$id = (int) ($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM blog_posts WHERE id = ?');
$st->execute([$id]);
$post = $st->fetch();

if (!$post || ($post['status'] === 'draft' && !is_admin())) {
    json_error('Post not found', 404);
}

json_out(['post' => [
    'id'      => (int) $post['id'],
    'title'   => $post['title'],
    'slug'    => $post['slug'],
    'excerpt' => $post['excerpt'],
    'body'    => $post['body'],
    'image'   => $post['image'] ? url('uploads/blog/' . $post['image']) : null,
    'status'  => $post['status'],
    'faqs'    => $post['faqs'] ? json_decode($post['faqs'], true) : [],
    'date'    => $post['created_at'],
]]);
