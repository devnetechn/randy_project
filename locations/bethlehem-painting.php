<?php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/locations.php';
$loc = $LOCATIONS['bethlehem-painting'];
$page_title = $loc['title'];
$page_description = $loc['meta_description'];
$page_keywords = $loc['meta_keywords'];
require __DIR__ . '/../includes/header.php';
location_page('bethlehem-painting');
require __DIR__ . '/../includes/footer.php';
