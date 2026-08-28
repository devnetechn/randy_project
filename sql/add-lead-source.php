<?php
/**
 * Add the `source` column to appointments (first-touch UTM/gclid/fbclid
 * attribution captured via the `lead_src` cookie — see includes/lead_source.php).
 *
 * Run from the project root:
 *     C:\xampp\php\php.exe sql\add-lead-source.php
 *
 * Idempotent: only adds the column if it is not already present.
 */

require_once __DIR__ . '/../includes/db.php';

$db = db();
$exists = $db->query("SHOW COLUMNS FROM appointments LIKE 'source'")->fetch();
if ($exists) {
    echo "Column `source` already exists — nothing to do.\n";
    exit;
}
$db->exec('ALTER TABLE appointments ADD COLUMN source VARCHAR(255) NULL AFTER notes');
echo "Added `source` column to appointments.\n";
