<?php
/**
 * Add the `featured` column to gallery_images (marks a project for the
 * magazine-style portfolio page).
 *
 * Run from the project root:
 *     C:\xampp\php\php.exe sql\add-gallery-featured.php
 *
 * Idempotent: only adds the column if it is not already present.
 */

require_once __DIR__ . '/../includes/db.php';

$db = db();
$exists = $db->query("SHOW COLUMNS FROM gallery_images LIKE 'featured'")->fetch();
if ($exists) {
    echo "Column `featured` already exists — nothing to do.\n";
    exit;
}
$db->exec('ALTER TABLE gallery_images ADD COLUMN featured TINYINT(1) NOT NULL DEFAULT 0 AFTER sort_order');
echo "Added `featured` column to gallery_images.\n";
