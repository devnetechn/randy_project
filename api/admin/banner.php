<?php
/** Sitewide announcement banner text. Admin-only: GET reads it, POST saves it. Blank = hidden. */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin_api();
    $payload = read_json();
    $text = trim((string) ($payload['text'] ?? ''));

    if (mb_strlen($text) > 200) {
        json_error('Banner text must be 200 characters or fewer', 422);
    }

    setting_set('site_banner_text', $text);
    json_out(['text' => $text]);
}

require_admin_api();
json_out(['text' => setting_get('site_banner_text', '')]);
