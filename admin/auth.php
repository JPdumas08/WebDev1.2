<?php
/**
 * Admin Authentication & Authorization
 * Isolated admin session (separate from customer site) and role check
 */
require_once __DIR__ . '/admin_session.php';
require_once __DIR__ . '/../db.php';

// Enforce isolated admin session
admin_session_guard();

// If not logged in, redirect to dedicated admin login
if (!admin_logged_in()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'index.php'));
    exit();
}

$admin_id = (int) ($_SESSION['admin_user_id'] ?? 0);

// Fetch admin user from dedicated admin_users table
$admin_sql = "SELECT 
        id AS admin_id,
        username,
        email AS email_address,
        role,
        status,
        last_login_at
    FROM admin_users
    WHERE id = :id AND status = 'active'
    LIMIT 1";

$admin_stmt = $pdo->prepare($admin_sql);
$admin_stmt->execute([':id' => $admin_id]);
$admin_user = $admin_stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin_user) {
    // Log unauthorized access attempt and force logout
    error_log("Unauthorized admin access attempt by admin_id: {$admin_id}");
    admin_logout();
    header('HTTP/1.1 403 Forbidden');
    header('Location: login.php?error=unauthorized');
    exit();
}

// Refresh session copy for downstream pages
$_SESSION['admin_user'] = $admin_user;
?>
