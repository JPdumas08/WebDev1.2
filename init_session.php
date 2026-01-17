<?php
// Central session initializer to include at the top of every script
// It re-uses the auth helpers from includes/auth.php and ensures
// $_SESSION['user_id'] is available for legacy code that expects it.
require_once __DIR__ . '/includes/auth.php';

// Ensure session is started and normalized
init_session();

// Session security: Check for session hijacking
if (!empty($_SESSION['user_id'])) {
    // Validate user agent (basic check)
    $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (isset($_SESSION['user_agent'])) {
        if ($_SESSION['user_agent'] !== $current_ua) {
            // Possible session hijacking - log out user
            error_log('Possible session hijacking detected for user_id: ' . $_SESSION['user_id']);
            session_destroy();
            session_start();
            header('Location: login.php?error=session_invalid');
            exit;
        }
    } else {
        // Store user agent on first authenticated request
        $_SESSION['user_agent'] = $current_ua;
    }
    
    // Session timeout check (2 hours of inactivity)
    $timeout = 7200; // 2 hours in seconds
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            // Session expired
            session_destroy();
            session_start();
            header('Location: login.php?error=session_expired');
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}

// Normalize user data into $_SESSION['user_id'] for compatibility
if (!empty($_SESSION['user'])) {
    // Prefer direct user_id if already set
    if (empty($_SESSION['user_id'])) {
        // Extract from nested user array
        $id = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? null;
        if ($id !== null) {
            $_SESSION['user_id'] = (int)$id;
        }
    }
} elseif (!empty($_SESSION['user_id'])) {
    // If user_id exists but user array doesn't, try to populate it
    if (empty($_SESSION['user'])) {
        $_SESSION['user'] = ['id' => (int)$_SESSION['user_id']];
    }
}

// Simple helper used by cart endpoints
function require_login_json()
{
    // caller should have already included this file so session is active
    if (empty($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Please log in to use the cart.']);
        exit;
    }
    return (int) $_SESSION['user_id'];
}