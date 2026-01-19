<?php
require_once 'db.php';

echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; color: #155724; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; color: #721c24; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; color: #0c5460; }
code { background: #fff; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>";

echo "<h2>🔍 Admin Login Debug Tool</h2>";

// Test credentials
$test_username = 'admin';
$test_password = 'Admin123!';

echo "<div class='info'>";
echo "<strong>Testing with:</strong><br>";
echo "Username: <code>{$test_username}</code><br>";
echo "Password: <code>{$test_password}</code>";
echo "</div>";

try {
    // Step 1: Check if user exists
    echo "<h3>Step 1: Check if admin user exists</h3>";
    $sql = "SELECT id, username, email, password_hash, role, status 
            FROM admin_users 
            WHERE (username = :u1 OR email = :u2) AND status = 'active'
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u1' => $test_username, ':u2' => $test_username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo "<div class='error'>❌ User NOT FOUND with username/email: {$test_username}</div>";
        
        // Check without status filter
        $sql2 = "SELECT username, email, status FROM admin_users WHERE username = :u";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([':u' => $test_username]);
        $user_check = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($user_check) {
            echo "<div class='info'>Found user but status is: <strong>{$user_check['status']}</strong></div>";
        } else {
            echo "<div class='error'>User does not exist at all!</div>";
        }
        exit;
    }
    
    echo "<div class='success'>✅ User found!</div>";
    echo "<pre>";
    echo "ID: {$admin['id']}\n";
    echo "Username: {$admin['username']}\n";
    echo "Email: {$admin['email']}\n";
    echo "Role: {$admin['role']}\n";
    echo "Status: {$admin['status']}\n";
    echo "Password Hash: " . substr($admin['password_hash'], 0, 60) . "...\n";
    echo "</pre>";
    
    // Step 2: Verify password
    echo "<h3>Step 2: Verify password</h3>";
    $verify_result = password_verify($test_password, $admin['password_hash']);
    
    if (!$verify_result) {
        echo "<div class='error'>❌ Password verification FAILED!</div>";
        echo "<p>The password <code>{$test_password}</code> does not match the stored hash.</p>";
        
        // Test multiple common passwords
        echo "<h4>Testing other common passwords:</h4>";
        $common_passwords = ['Admin123!', 'admin123', 'Admin@123', 'Temp123!', 'admin'];
        foreach ($common_passwords as $pwd) {
            $test = password_verify($pwd, $admin['password_hash']);
            $icon = $test ? '✅' : '❌';
            $color = $test ? 'success' : 'error';
            echo "<div class='{$color}'>{$icon} Password: <code>{$pwd}</code> - " . ($test ? 'WORKS!' : 'No match') . "</div>";
        }
        
        // Offer to reset
        echo "<hr>";
        echo "<h3>Reset Password Now</h3>";
        echo "<form method='post' action='debug_admin_login.php'>";
        echo "<input type='hidden' name='action' value='reset_password'>";
        echo "<p>New Password: <input type='text' name='new_password' value='Admin123!' style='padding: 8px; width: 200px;'></p>";
        echo "<button type='submit' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;'>Reset Password</button>";
        echo "</form>";
        
    } else {
        echo "<div class='success'>✅ Password verification SUCCESSFUL!</div>";
        echo "<p><strong>The credentials are correct! You should be able to login with:</strong></p>";
        echo "<ul>";
        echo "<li>Username: <code>{$test_username}</code></li>";
        echo "<li>Password: <code>{$test_password}</code></li>";
        echo "</ul>";
        
        echo "<h3>Step 3: Test Full Login Process</h3>";
        echo "<div class='info'>";
        echo "<p>The credentials work! If you still can't login, there might be an issue with:</p>";
        echo "<ol>";
        echo "<li>Session configuration in <code>admin_session.php</code></li>";
        echo "<li>Cookie path settings</li>";
        echo "<li>Browser cache (try clearing cookies)</li>";
        echo "<li>Rate limiting (try in a new private/incognito window)</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<h4>Quick Login Test</h4>";
        echo "<form method='post' action='admin/login_handler.php' target='_blank'>";
        echo "<input type='hidden' name='username' value='{$test_username}'>";
        echo "<input type='hidden' name='password' value='{$test_password}'>";
        echo "<button type='submit' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>Test Login (opens in new window)</button>";
        echo "</form>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $new_password = $_POST['new_password'] ?? 'Admin123!';
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = :hash WHERE username = 'admin'");
        $stmt->execute([':hash' => $hash]);
        
        echo "<div class='success'>";
        echo "<h3>✅ Password Reset Complete!</h3>";
        echo "<p>New password: <code>{$new_password}</code></p>";
        echo "<p><a href='debug_admin_login.php'>Click here to test again</a></p>";
        echo "</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>Failed to reset: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>
