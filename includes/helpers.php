<?php
/** Small view + request/response helpers used across pages and the JSON API. */

/** HTML-escape for safe output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * The URL path prefix the app is served from — auto-detected from the web root.
 * Works whether the app sits at the domain root (Hostinger: "") or in a
 * subfolder (XAMPP: "/randy"), so it needs no per-server config and the old
 * `base_path` setting is no longer required.
 */
function app_base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $docroot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
    $approot = str_replace('\\', '/', dirname(__DIR__)); // includes/ -> app root
    if ($docroot !== '' && stripos($approot, $docroot) === 0) {
        return $base = rtrim(substr($approot, strlen($docroot)), '/');
    }
    return $base = '';
}

/**
 * Build an app URL from a root-relative path, honouring the detected base path.
 * Internal page links are returned WITHOUT the ".php" extension (clean URLs like
 * /blog, /services); .htaccess maps them back to the real script. Query strings
 * and fragments are preserved, and "index.php" collapses to the directory root.
 * Asset paths (.css, .js, images) are left untouched since they have no ".php".
 */
function url(string $path = ''): string
{
    $base = app_base_path();
    if ($path === '' || $path === '/') {
        return $base . '/';
    }
    $path = ltrim($path, '/');

    // Split off the query string and fragment so only the path is rewritten.
    $hash = '';
    $query = '';
    if (($h = strpos($path, '#')) !== false) {
        $hash = substr($path, $h);
        $path = substr($path, 0, $h);
    }
    if (($q = strpos($path, '?')) !== false) {
        $query = substr($path, $q);
        $path = substr($path, 0, $q);
    }

    // "index.php" / "admin/index.php" -> directory root; "page.php" -> "page".
    $path = preg_replace('#(^|/)index\.php$#', '$1', $path);
    $path = preg_replace('#\.php$#', '', $path);

    return $base . '/' . $path . $query . $hash;
}

/** Send a JSON response and stop. */
function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/** Send a JSON error and stop. */
function json_error(string $message, int $status = 400): void
{
    json_out(['error' => $message], $status);
}

/** Decode the JSON request body into an associative array. */
function read_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Redirect to an app path and stop. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Mark the current nav link as active. */
function active(string $page): string
{
    $current = basename($_SERVER['SCRIPT_NAME']);
    return $current === $page ? ' aria-current="page"' : '';
}
