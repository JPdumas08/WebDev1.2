<?php
require_once 'db.php';

echo "<h2>Admin Password Reset Tool</h2>";

// Set new password
$new_password = 'Admin123!';
$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // Update the admin user's password
    $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = :hash WHERE username = 'admin'");
    $stmt->execute([':hash' => $password_hash]);
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Password Reset Successful!</h3>";
    echo "<p style='font-size: 18px;'><strong>Username:</strong> admin</p>";
    echo "<p style='font-size: 18px;'><strong>Email:</strong> admin@gmail.com</p>";
    echo "<p style='font-size: 18px;'><strong>New Password:</strong> <code style='background: #fff; padding: 5px 10px; border-radius: 3px; font-size: 20px; color: #d9534f;'>Admin123!</code></p>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;'>";
    echo "<h4 style='color: #856404; margin-top: 0;'>Next Steps:</h4>";
    echo "<ol>";
    echo "<li>Go to <a href='admin/login.php' style='color: #0056b3;'>Admin Login Page</a></li>";
    echo "<li>Use username: <strong>admin</strong></li>";
    echo "<li>Use password: <strong>Admin123!</strong></li>";
    echo "<li>After logging in, change your password in settings</li>";
    echo "</ol>";
    echo "</div>";
    
    // Test the password
    $verify = password_verify($new_password, $password_hash);
    echo "<p style='color: " . ($verify ? 'green' : 'red') . ";'>Password verification test: " . ($verify ? '✅ PASSED' : '❌ FAILED') . "</p>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24;'>❌ Error</h3>";
    echo "<p>Could not reset password: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
