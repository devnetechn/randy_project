<?php
/**
 * Import a batch of new project photos into the gallery.
 *
 * Run from the project root:
 *     C:\xampp\php\php.exe sql\import-new-photos-2026-06.php
 *
 * Copies each source photo into uploads/gallery/ (under a fresh hashed
 * filename, matching the admin upload convention) and inserts a row into
 * gallery_images with an SEO-friendly caption and the right category.
 *
 * Captions double as the <img alt> text on the public gallery, so each one
 * describes the ACTUAL photo — no invented locations. The owner can append a
 * real town (e.g. " — Bethlehem, PA") to any caption from the admin dashboard.
 *
 * Idempotent: each photo gets a deterministic destination filename derived
 * from its source name, and the row is only inserted if that filename isn't
 * already in the table. Re-running will not create duplicates.
 */

require_once __DIR__ . '/../includes/db.php';

/** Where the original photos live (absolute path). */
$srcDir = 'C:\\Users\\EDP-BARS\\Downloads\\boss randy\\new picture';

/** Destination upload folder for the public gallery. */
$destDir = __DIR__ . '/../uploads/gallery';

/**
 * source basename (no extension) => [category, caption]
 * Categories: interior | exterior | drywall | commercial | other
 */
$photos = [
    '03a860dd-ab40-4e3c-9606-4708b266846f' => ['interior',
        'Open-concept living room with a white coffered ceiling, warm neutral walls and a refinished fireplace surround over new wood flooring — interior painting and trim.'],
    '08e274dd-1c5a-4131-b301-a0532206dc9c' => ['interior',
        'Dining room feature wall in a rich copper tone framed by crisp white crown moulding and trim — interior painting with a smooth finish.'],
    '38c466ce-1f4c-48f9-b768-7a1b8b2130fc' => ['interior',
        'Sage-green room with a stained wood fireplace mantel, floors and surround carefully masked for a clean finish — interior painting and trim.'],
    '56c98ab5-133c-470f-808b-4ccd90e04842' => ['interior',
        'Spacious great room with a freshly painted sage coffered ceiling and matching walls, fully draped and in progress — interior painting.'],
    '73b523c1-5813-4e40-bbfa-034ea2655d64' => ['drywall',
        'Bright dining room with smooth-finished walls and detailed white trim after a seamless drywall patch and repair — drywall and interior painting.'],
    'b0b100a6-1422-49c2-94ff-6ad6cbac4a41' => ['interior',
        'Custom white fireplace mantel with a flawless painted finish over a black-tiled surround and refinished red-oak floor — interior trim and painting.'],
    'cd3d5cdd-86d4-48db-bf5d-8176c484a7ea' => ['interior',
        'Bedroom repaint in a soft greige with a crisp white ceiling and trim — furniture fully masked to protect every surface. Interior painting.'],
    'd98d1adc-4d9e-411a-a0a0-9c8ae5420d2b' => ['interior',
        'Bedroom with warm dusty-rose walls, a bright white ceiling and freshly painted doors and trim — interior painting with a smooth finish.'],
    'db45d3ef-a5dc-454f-ae87-74dc0a75f6e1' => ['interior',
        'Warm beige room crowned by a white coffered ceiling with a dark stained fireplace mantel — interior painting and detailed trim work.'],
    'e1dd0326-0c3d-4818-912b-4b4cc8f4975f' => ['interior',
        'Open living and kitchen space with a white coffered ceiling and black statement pendants, fully draped and masked for painting — interior painting in progress.'],
    'e99fb70d-bf5e-4c4f-a431-11b6a580badb' => ['commercial',
        'Large curved commercial building exterior freshly coated in a clean, uniform white — full-scale commercial exterior painting.'],
    'ebf3add1-bb2a-4035-b262-da5d7473f26b' => ['interior',
        'Living room with a rich aubergine fireplace mantel beneath a soft coffered ceiling, draped and in progress — interior painting and trim.'],
    'fd32dbeb-167f-4595-a566-ef4b1d0d92f3' => ['exterior',
        'Covered outdoor lanai with a warm stained tongue-and-groove ceiling and bright white beams and columns — exterior painting and woodwork.'],
];

if (!is_dir($destDir) && !mkdir($destDir, 0775, true)) {
    fwrite(STDERR, "Could not create destination: $destDir\n");
    exit(1);
}

$db = db();
$exists = $db->prepare('SELECT id FROM gallery_images WHERE filename = ? LIMIT 1');
$insert = $db->prepare('INSERT INTO gallery_images (filename, caption, category) VALUES (?, ?, ?)');

$added = 0;
$skipped = 0;
$missing = 0;

foreach ($photos as $base => [$category, $caption]) {
    $src = $srcDir . DIRECTORY_SEPARATOR . $base . '.jpg';
    if (!is_file($src)) {
        echo "MISSING source: {$base}.jpg\n";
        $missing++;
        continue;
    }

    // Deterministic destination filename so re-runs are idempotent.
    $filename = 'batch2026' . substr(sha1('randy-gallery:' . $base), 0, 23) . '.jpg';

    $exists->execute([$filename]);
    if ($exists->fetch()) {
        echo "SKIP (already imported): {$base}\n";
        $skipped++;
        continue;
    }

    if (!copy($src, $destDir . DIRECTORY_SEPARATOR . $filename)) {
        echo "FAILED to copy: {$base}\n";
        continue;
    }

    $insert->execute([$filename, $caption, $category]);
    $id = (int) $db->lastInsertId();
    echo "ADDED #{$id} [{$category}]: {$filename}\n";
    $added++;
}

echo "\nDone. Added {$added}, skipped {$skipped}, missing {$missing}.\n";
