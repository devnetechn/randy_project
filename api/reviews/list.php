<?php
/** All reviews, newest first. Public + admin see the same list. */
require_once __DIR__ . '/../../includes/app.php';
require_once __DIR__ . '/../../includes/reviews.php';

$reviews = array_map(function ($r) {
    return [
        'id'     => (int) $r['id'],
        'author' => $r['author'],
        'rating' => (int) $r['rating'],
        'body'   => $r['body'],
        'meta'   => $r['meta'],
        'date'   => $r['created_at'],
    ];
}, reviews_all());

json_out(['reviews' => $reviews]);
