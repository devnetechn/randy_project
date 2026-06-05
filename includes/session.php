<?php
/** Session-based authentication (replaces the original JWT + refresh tokens). */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

/** The logged-in user as a small array, or null. */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'        => (int) $_SESSION['user_id'],
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'customer',
        'full_name' => $_SESSION['full_name'] ?? '',
    ];
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

/** Persist a user row (from the DB) into the session. */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---- Page guards (redirect to login) ---- */

function require_login(): array
{
    $u = current_user();
    if ($u === null) {
        redirect('login.php');
    }
    return $u;
}

function require_admin_page(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        redirect('index.php');
    }
    return $u;
}

/* ---- API guards (JSON 401/403) ---- */

function require_login_api(): array
{
    $u = current_user();
    if ($u === null) {
        json_error('Authentication required', 401);
    }
    return $u;
}

function require_admin_api(): array
{
    $u = require_login_api();
    if ($u['role'] !== 'admin') {
        json_error('Forbidden', 403);
    }
    return $u;
}
