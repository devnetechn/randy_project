<?php
/** Review-button URL. GET: read it (public). POST: save it (admin). */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin_api();
    $payload = read_json();
    $url = trim((string) ($payload['reviewUrl'] ?? ''));

    if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
        json_error('Please enter a valid review URL (including https://)', 400);
    }
    if (mb_strlen($url) > 500) {
        json_error('URL is too long', 400);
    }
    setting_set('google_reviews_review_url', $url);
    json_out(['reviewUrl' => $url]);
}

json_out(['reviewUrl' => setting_get('google_reviews_review_url', 'https://share.google/jOEA4W8Vst9zQkl5u')]);
