<?php
/**
 * First-touch ad-campaign attribution. If the current URL carries campaign
 * params, stash them in a first-party cookie so book.php can attach them to
 * a lead even if the visitor browses a few pages before submitting.
 */
if (!isset($_COOKIE['lead_src'])) {
    $keys  = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid'];
    $parts = [];
    foreach ($keys as $key) {
        if (!empty($_GET[$key])) {
            $parts[] = $key . '=' . preg_replace('/[^A-Za-z0-9._-]/', '', (string) $_GET[$key]);
        }
    }
    if ($parts) {
        setcookie('lead_src', implode('&', $parts), time() + 90 * 86400, '/', '', false, true);
    }
}
