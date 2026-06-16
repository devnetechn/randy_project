<?php
/** Public gallery listing. GET ?category= */
require_once __DIR__ . '/../../includes/app.php';

$category = $_GET['category'] ?? null;
$valid = ['interior', 'exterior', 'drywall', 'commercial', 'other'];

if ($category && in_array($category, $valid, true)) {
    $st = db()->prepare('SELECT * FROM gallery_images WHERE category = ? ORDER BY sort_order ASC, created_at DESC');
    $st->execute([$category]);
} else {
    $st = db()->query('SELECT * FROM gallery_images ORDER BY sort_order ASC, created_at DESC');
}

$images = array_map(function ($img) {
    return [
        'id'          => (int) $img['id'],
        'url'         => url('uploads/gallery/' . $img['filename']),
        'projectUrl'  => url('project.php?id=' . (int) $img['id']),
        'caption'     => $img['caption'],
        'description' => $img['description'] ?? null,
        'category'    => $img['category'],
        'sortOrder'   => (int) $img['sort_order'],
    ];
}, $st->fetchAll());

json_out(['images' => $images]);
