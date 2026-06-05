<?php
/** Blog settings. GET: read the external blog URL (public). POST: save it (admin). */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin_api();
    $payload = read_json();
    $url = trim((string) ($payload['blogUrl'] ?? ''));

    if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
        json_error('Please enter a valid URL (including https://)', 400);
    }
    if (mb_strlen($url) > 500) {
        json_error('URL is too long', 400);
    }
    setting_set('blog_url', $url);
    json_out(['blogUrl' => $url]);
}

json_out(['blogUrl' => setting_get('blog_url', '')]);
