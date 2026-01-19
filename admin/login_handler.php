<?php
require_once __DIR__ . '/admin_session.php';
require_once __DIR__ . '/../db.php';

// Basic rate limiting using session counter
admin_session_start();
if (!isset($_SESSION['admin_login_attempts'])) {
    $_SESSION['admin_login_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$redirect = $_POST['redirect'] ?? 'index.php';

if ($username === '' || $password === '') {
    header('Location: login.php?error=invalid');
    exit();
}

// Throttle after 5 attempts per session
if ($_SESSION['admin_login_attempts'] >= 5) {
    header('Location: login.php?error=invalid');
    exit();
}

try {
    $sql = "SELECT id, username, email, password_hash, role, status, last_login_at
            FROM admin_users
            WHERE (username = :u1 OR email = :u2) AND status = 'active'
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u1' => $username, ':u2' => $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_login_attempts']++;
        header('Location: login.php?error=invalid');
        exit();
    }

    // Successful login: reset attempts
    $_SESSION['admin_login_attempts'] = 0;

    // Update last_login_at (best effort)
    $upd = $pdo->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = :id");
    $upd->execute([':id' => $admin['id']]);

    admin_login($admin);

    // Use relative path since DocumentRoot is already set to WebDev1.2
    $redirectPath = ($redirect && $redirect !== '') ? $redirect : 'index.php';
    
    header('Location: ' . $redirectPath);
    exit();
} catch (Exception $e) {
    error_log('Admin login error: ' . $e->getMessage());
    header('Location: login.php?error=invalid');
    exit();
}
