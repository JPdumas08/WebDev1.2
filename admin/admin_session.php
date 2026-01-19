<?php
/**
 * Admin session utilities
 * - Uses isolated session cookie (different name + path) to avoid conflicts with customer sessions
 * - Adds basic session hardening (user agent check, idle timeout, regeneration)
 */

function admin_session_start(): void
{
    // If already active with our custom name, nothing to do
    if (session_status() === PHP_SESSION_ACTIVE && session_name() === 'JEWELUXE_ADMIN') {
        return;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        // Close any other session to avoid mixing namespaces
        session_write_close();
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $cookiePath = '/admin'; // limit cookie to admin area for isolation

    session_name('JEWELUXE_ADMIN');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath,
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function admin_session_regenerate(): void
{
    // Regenerate the session ID to prevent fixation
    session_regenerate_id(true);
}

function admin_session_guard(): void
{
    admin_session_start();

    // User agent binding
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (!empty($_SESSION['admin_user_id'])) {
        if (isset($_SESSION['admin_user_agent']) && $_SESSION['admin_user_agent'] !== $ua) {
            admin_logout();
            header('Location: login.php?error=session_invalid');
            exit();
        }
        $_SESSION['admin_user_agent'] = $ua;
    }

    // Idle timeout (2 hours)
    $timeout = 7200;
    if (!empty($_SESSION['admin_user_id'])) {
        $last = $_SESSION['admin_last_activity'] ?? time();
        if (time() - $last > $timeout) {
            admin_logout();
            header('Location: login.php?error=session_expired');
            exit();
        }
        $_SESSION['admin_last_activity'] = time();
    }
}

function admin_login(array $adminRow): void
{
    admin_session_start();

    $_SESSION['admin_user_id'] = (int) ($adminRow['id'] ?? $adminRow['admin_id'] ?? 0);
    $_SESSION['admin_user'] = [
        'id' => $_SESSION['admin_user_id'],
        'username' => $adminRow['username'] ?? '',
        'email_address' => $adminRow['email'] ?? $adminRow['email_address'] ?? '',
        'role' => $adminRow['role'] ?? 'admin',
        'last_login_at' => $adminRow['last_login_at'] ?? null,
    ];
    $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['admin_last_activity'] = time();

    admin_session_regenerate();
}

function admin_logged_in(): bool
{
    admin_session_start();
    return !empty($_SESSION['admin_user_id']);
}

function admin_logout(): void
{
    admin_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
