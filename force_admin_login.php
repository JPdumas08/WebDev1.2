<?php
// FORCE ADMIN LOGIN - Emergency bypass
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/admin/admin_session.php';

// Start admin session
admin_session_start();

try {
    // Get admin user
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = 'admin' AND status = 'active' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        die("❌ Admin user not found!");
    }
    
    // Update last login
    $update = $pdo->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = ?");
    $update->execute([$admin['id']]);
    
    // Force login
    $_SESSION['admin_user_id'] = (int) $admin['id'];
    $_SESSION['admin_user'] = [
        'id' => (int) $admin['id'],
        'username' => $admin['username'],
        'email_address' => $admin['email'],
        'role' => $admin['role'],
        'last_login_at' => $admin['last_login_at']
    ];
    $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['admin_last_activity'] = time();
    
    // Regenerate session ID
    session_regenerate_id(true);
    
    echo "✅ LOGIN SUCCESSFUL!<br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Admin User ID: " . $_SESSION['admin_user_id'] . "<br>";
    echo "Username: " . $_SESSION['admin_user']['username'] . "<br><br>";
    echo "Redirecting to admin dashboard in 2 seconds...<br><br>";
    echo "<a href='admin/index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Dashboard Now</a>";
    
    // Auto redirect
    header("refresh:2;url=admin/index.php");
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
