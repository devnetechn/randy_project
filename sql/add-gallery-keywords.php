<?php
/**
 * Add the `keywords` column to gallery_images (SEO meta + image alt text on
 * project detail pages).
 *
 * Run from the project root:
 *     C:\xampp\php\php.exe sql\add-gallery-keywords.php
 *
 * Idempotent: only adds the column if it is not already present.
 */

require_once __DIR__ . '/../includes/db.php';

$db = db();
$exists = $db->query("SHOW COLUMNS FROM gallery_images LIKE 'keywords'")->fetch();
if ($exists) {
    echo "Column `keywords` already exists — nothing to do.\n";
    exit;
}
$db->exec('ALTER TABLE gallery_images ADD COLUMN keywords VARCHAR(300) NULL AFTER description');
echo "Added `keywords` column to gallery_images.\n";
