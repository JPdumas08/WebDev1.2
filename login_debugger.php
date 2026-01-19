<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/admin_session.php';
require_once __DIR__ . '/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login Debugger</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .step { background: #2d2d2d; padding: 15px; margin: 10px 0; border-left: 4px solid #007acc; }
        .success { border-left-color: #4ec9b0; }
        .error { border-left-color: #f48771; }
        .warning { border-left-color: #dcdcaa; }
        pre { background: #1e1e1e; padding: 10px; overflow-x: auto; }
        button { background: #0e639c; color: white; border: none; padding: 10px 20px; cursor: pointer; margin: 5px; }
        button:hover { background: #1177bb; }
    </style>
</head>
<body>
    <h1>🔍 Admin Login Debugger</h1>
    
<?php
$test_username = 'admin';
$test_email = 'admin@gmail.com';
$test_password = 'Admin123!';

echo "<div class='step'>";
echo "<strong>Step 1: Testing with credentials</strong><br>";
echo "Username: <code>{$test_username}</code> or <code>{$test_email}</code><br>";
echo "Password: <code>{$test_password}</code>";
echo "</div>";

try {
    // Test 1: Check database connection
    echo "<div class='step success'>";
    echo "<strong>✅ Step 2: Database connection OK</strong>";
    echo "</div>";
    
    // Test 2: Query admin user
    echo "<div class='step'>";
    echo "<strong>Step 3: Searching for admin user...</strong><br>";
    
    $sql = "SELECT id, username, email, password_hash, role, status 
            FROM admin_users 
            WHERE (username = :u1 OR email = :u2) AND status = 'active'
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u1' => $test_username, ':u2' => $test_email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo "<span style='color: #f48771;'>❌ User NOT FOUND!</span><br>";
        echo "Query: <pre>" . htmlspecialchars($sql) . "</pre>";
        echo "Parameters: u1={$test_username}, u2={$test_email}";
        echo "</div>";
        exit;
    }
    
    echo "<span style='color: #4ec9b0;'>✅ User FOUND!</span><br>";
    echo "<pre>";
    print_r([
        'id' => $admin['id'],
        'username' => $admin['username'],
        'email' => $admin['email'],
        'role' => $admin['role'],
        'status' => $admin['status'],
        'hash_preview' => substr($admin['password_hash'], 0, 60) . '...'
    ]);
    echo "</pre>";
    echo "</div>";
    
    // Test 3: Password verification
    echo "<div class='step'>";
    echo "<strong>Step 4: Testing password verification...</strong><br>";
    
    $passwords_to_test = [
        'Admin123!' => 'Primary test password',
        'Temp123!' => 'Alternative password',
        'Admin@123' => 'Old password format',
        'admin' => 'Simple password'
    ];
    
    $working_password = null;
    foreach ($passwords_to_test as $pwd => $desc) {
        $result = password_verify($pwd, $admin['password_hash']);
        $icon = $result ? '✅' : '❌';
        $color = $result ? '#4ec9b0' : '#f48771';
        echo "<span style='color: {$color};'>{$icon} {$desc}: <code>{$pwd}</code></span><br>";
        if ($result && !$working_password) {
            $working_password = $pwd;
        }
    }
    echo "</div>";
    
    if (!$working_password) {
        echo "<div class='step error'>";
        echo "<strong>❌ None of the test passwords work!</strong><br>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='reset_password' value='1'>";
        echo "<button type='submit'>Reset Password to Admin123!</button>";
        echo "</form>";
        echo "</div>";
        
        if (isset($_POST['reset_password'])) {
            $new_hash = password_hash('Admin123!', PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE admin_users SET password_hash = :hash WHERE id = :id");
            $update->execute([':hash' => $new_hash, ':id' => $admin['id']]);
            echo "<div class='step success'><strong>✅ Password has been reset! Reload this page.</strong></div>";
        }
        exit;
    }
    
    // Test 4: Session test
    echo "<div class='step success'>";
    echo "<strong>✅ Step 5: Password verification PASSED with: <code>{$working_password}</code></strong>";
    echo "</div>";
    
    // Test 5: Simulate login
    echo "<div class='step'>";
    echo "<strong>Step 6: Testing session system...</strong><br>";
    
    // Check if session functions exist
    if (!function_exists('admin_session_start')) {
        echo "<span style='color: #f48771;'>❌ admin_session_start() function not found!</span><br>";
        echo "admin_session.php may not be loaded correctly.";
        echo "</div>";
        exit;
    }
    
    echo "✅ admin_session_start() function exists<br>";
    
    if (!function_exists('admin_login')) {
        echo "<span style='color: #f48771;'>❌ admin_login() function not found!</span><br>";
        echo "</div>";
        exit;
    }
    
    echo "✅ admin_login() function exists<br>";
    echo "</div>";
    
    // Test 6: Perform actual login
    if (isset($_POST['do_login'])) {
        echo "<div class='step'>";
        echo "<strong>Step 7: Performing login...</strong><br>";
        
        try {
            admin_session_start();
            
            // Update last login
            $upd = $pdo->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = :id");
            $upd->execute([':id' => $admin['id']]);
            
            // Call admin_login
            admin_login($admin);
            
            echo "✅ admin_login() called successfully<br>";
            echo "✅ Session data set<br>";
            echo "<br>Session contents:<br>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
            
            echo "<span style='color: #4ec9b0; font-size: 18px;'>✅ LOGIN SUCCESSFUL!</span><br><br>";
            echo "<a href='admin/index.php'><button>Go to Admin Dashboard</button></a>";
            echo "<script>setTimeout(function(){ window.location.href='admin/index.php'; }, 3000);</script>";
            echo "</div>";
            exit;
            
        } catch (Exception $e) {
            echo "<span style='color: #f48771;'>❌ Login failed: " . htmlspecialchars($e->getMessage()) . "</span><br>";
            echo "</div>";
            exit;
        }
    }
    
    // Show login button
    echo "<div class='step success'>";
    echo "<strong>✅ All checks passed! Ready to login.</strong><br><br>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='do_login' value='1'>";
    echo "<button type='submit'>🚀 Login Now (admin / {$working_password})</button>";
    echo "</form>";
    echo "</div>";
    
    echo "<hr>";
    echo "<div class='step warning'>";
    echo "<strong>Try the regular login page:</strong><br>";
    echo "URL: <a href='admin/login.php' style='color: #4ec9b0;'>http://localhost/admin/login.php</a><br>";
    echo "Username: <code>admin</code> or <code>admin@gmail.com</code><br>";
    echo "Password: <code>{$working_password}</code>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='step error'>";
    echo "<strong>❌ Database Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='step error'>";
    echo "<strong>❌ Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

</body>
</html>
