<?php
/**
 * Add descriptive, SEO-friendly captions to existing gallery images.
 *
 * Run from the project root:
 *     C:\xampp\php\php.exe sql\seed-gallery-captions.php
 *
 * Captions double as the <img alt> text on the public gallery, so they help
 * both accessibility and local SEO. Each describes the ACTUAL photo — no
 * invented locations. To boost local SEO further, the owner can append the
 * real town (e.g. " — Bethlehem, PA") to any caption via the admin dashboard.
 *
 * Idempotent and non-destructive: only fills captions that are currently empty,
 * so manual edits made later in the admin are never overwritten.
 */

require_once __DIR__ . '/../includes/db.php';

/** id => caption (max 200 chars). Matched to the uploaded photo. */
$captions = [
    2  => 'Two-story staircase repaint — crisp white walls with bold black-finished railings, balusters and newel posts. Interior painting & trim.',
    3  => 'Upstairs landing with a vibrant orange accent wall and a clean white stair railing — interior painting with a smooth finish.',
    4  => 'Vaulted great room with smooth cathedral ceilings and fresh neutral walls — interior painting and drywall finishing.',
    7  => 'Modern kitchen refresh — smooth grey walls and ceiling with bright white cabinetry and trim. Interior painting.',
    9  => 'Living and dining room repaint — warm greige walls with crisp white trim and crown moulding over refinished hardwood.',
    10 => 'Two-story entry foyer — tall, flawless white walls with detailed trim and crown moulding. Interior painting & smooth-wall finish.',
    5  => 'Exterior stucco repaint in progress — warm tan finish with bright white trim around arched windows. Exterior painting.',
    6  => 'Wraparound porch repaint — cheerful yellow siding, white columns and a welcoming green entry door. Exterior painting & trim.',
    11 => 'Tudor-style home exterior — freshly painted dark trim and timber detailing against classic brick. Exterior painting.',
    12 => 'Bold teal accent wall with a flawless smooth finish — interior painting and premium wall preparation.',
    8  => 'Smooth-finished drywall under soft lavender paint — flawless, even walls ready for a premium finish. Drywall & painting.',
    13 => 'Commercial interior repaint for a restaurant and bar — detailed work around custom ceilings and fixtures. Commercial painting.',
];

/** Photos categorised incorrectly: id => correct category. */
$recategorize = [
    12 => 'interior', // teal accent wall is an interior room, not an exterior shot
];

$db = db();
$setCap = $db->prepare("UPDATE gallery_images SET caption = ? WHERE id = ? AND (caption IS NULL OR caption = '')");
$setCat = $db->prepare('UPDATE gallery_images SET category = ? WHERE id = ? AND category <> ?');

$capCount = 0;
$capSkip  = 0;
foreach ($captions as $id => $caption) {
    $setCap->execute([$caption, $id]);
    if ($setCap->rowCount() > 0) {
        echo "CAPTION #{$id}: set\n";
        $capCount++;
    } else {
        echo "CAPTION #{$id}: skipped (already has one or id missing)\n";
        $capSkip++;
    }
}

$catCount = 0;
foreach ($recategorize as $id => $cat) {
    $setCat->execute([$cat, $id, $cat]);
    if ($setCat->rowCount() > 0) {
        echo "CATEGORY #{$id}: -> {$cat}\n";
        $catCount++;
    }
}

echo "\nDone. Captions set {$capCount}, skipped {$capSkip}. Recategorised {$catCount}.\n";
