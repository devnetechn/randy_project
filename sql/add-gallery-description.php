<?php
/**
 * Add the `description` column to gallery_images (powers project detail pages).
 *
 * Run from the project root:
 *     C:\xampp\php\php.exe sql\add-gallery-description.php
 *
 * Idempotent: only adds the column if it is not already present.
 */

require_once __DIR__ . '/../includes/db.php';

$db = db();
$exists = $db->query("SHOW COLUMNS FROM gallery_images LIKE 'description'")->fetch();
if ($exists) {
    echo "Column `description` already exists — nothing to do.\n";
    exit;
}
$db->exec('ALTER TABLE gallery_images ADD COLUMN description TEXT NULL AFTER caption');
echo "Added `description` column to gallery_images.\n";
