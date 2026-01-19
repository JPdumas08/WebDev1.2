<?php
require_once __DIR__ . '/admin/admin_session.php';
require_once __DIR__ . '/db.php';

// Direct login bypass for testing
$username = 'admin';
$password = 'Admin123!';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Admin Quick Login</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .box { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; color: #721c24; }
        button { background: #007bff; color: white; border: none; padding: 12px 24px; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class='box'>
        <h2>🔐 Admin Quick Login</h2>";

try {
    // Get admin user
    $sql = "SELECT id, username, email, password_hash, role, status 
            FROM admin_users 
            WHERE (username = :u1 OR email = :u2) AND status = 'active'
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u1' => $username, ':u2' => $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo "<div class='error'>❌ Admin user not found!</div>";
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $admin['password_hash'])) {
        echo "<div class='error'>❌ Password does not match!</div>";
        echo "<p>Current password in DB doesn't match <code>Admin123!</code></p>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='reset' value='1'>";
        echo "<button type='submit'>Reset Password to Admin123!</button>";
        echo "</form>";
        
        // Handle password reset
        if (isset($_POST['reset'])) {
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE admin_users SET password_hash = :hash WHERE id = :id");
            $update->execute([':hash' => $new_hash, ':id' => $admin['id']]);
            echo "<div class='success'>✅ Password reset! Refresh this page.</div>";
        }
        exit;
    }
    
    echo "<div class='success'>✅ Credentials verified!</div>";
    
    // Handle auto-login
    if (isset($_POST['auto_login'])) {
        admin_session_start();
        
        // Update last login
        $upd = $pdo->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = :id");
        $upd->execute([':id' => $admin['id']]);
        
        // Set session
        admin_login($admin);
        
        echo "<div class='success'>";
        echo "<h3>✅ Login Successful!</h3>";
        echo "<p>Redirecting to admin dashboard...</p>";
        echo "<script>setTimeout(function(){ window.location.href = 'admin/index.php'; }, 2000);</script>";
        echo "</div>";
        exit;
    }
    
    // Show login form
    echo "<p><strong>Username:</strong> <code>{$username}</code></p>";
    echo "<p><strong>Password:</strong> <code>{$password}</code></p>";
    echo "<p><strong>Status:</strong> ✅ Credentials are valid!</p>";
    echo "<hr>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='auto_login' value='1'>";
    echo "<button type='submit'>🚀 Login to Admin Dashboard</button>";
    echo "</form>";
    echo "<hr>";
    echo "<p><small>Or try the regular login page: <a href='admin/login.php'>admin/login.php</a></small></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></body></html>";
?>
