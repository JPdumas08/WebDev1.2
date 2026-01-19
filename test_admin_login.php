<?php
require_once 'db.php';

echo "<h3>Admin Users Check</h3>";

try {
    // Check if admin_users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "<p style='color:red;'>❌ admin_users table does NOT exist!</p>";
        echo "<p>You need to run the migration: migrations/004_create_admin_users_table.sql</p>";
        exit;
    }
    
    echo "<p style='color:green;'>✅ admin_users table exists</p>";
    
    // Count admin users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total admin users: <strong>{$count['count']}</strong></p>";
    
    // List all admin users
    $stmt = $pdo->query("SELECT id, username, email, role, status, created_at FROM admin_users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p style='color:orange;'>⚠️ No admin users found in database!</p>";
        echo "<p>Creating default admin user...</p>";
        
        // Create default admin
        $default_password = 'Temp123!';
        $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
        
        $insert = $pdo->prepare("INSERT INTO admin_users (username, email, password_hash, role, status) 
                                 VALUES (:username, :email, :hash, :role, 'active')");
        $insert->execute([
            ':username' => 'admin',
            ':email' => 'admin@gmail.com',
            ':hash' => $password_hash,
            ':role' => 'superadmin'
        ]);
        
        echo "<p style='color:green;'>✅ Admin user created!</p>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>Email:</strong> admin@gmail.com</p>";
        echo "<p><strong>Password:</strong> Temp123!</p>";
    } else {
        echo "<h4>Admin Users List:</h4>";
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th></tr>";
        foreach ($users as $user) {
            $statusColor = $user['status'] === 'active' ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td><strong>{$user['username']}</strong></td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td style='color:{$statusColor};'>{$user['status']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test login for first user
    if (!empty($users)) {
        $test_user = $users[0];
        echo "<h4>Test Login Credentials:</h4>";
        echo "<p><strong>Username/Email:</strong> {$test_user['username']} or {$test_user['email']}</p>";
        echo "<p><strong>Password:</strong> Temp123! (or Admin@123 if you haven't changed it)</p>";
        
        // Test password verification
        echo "<h4>Password Hash Check:</h4>";
        $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE username = :u");
        $stmt->execute([':u' => $test_user['username']]);
        $hash_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Stored hash: <code>" . substr($hash_data['password_hash'], 0, 50) . "...</code></p>";
        
        $test_passwords = ['Temp123!', 'Admin@123', 'admin123'];
        echo "<h4>Testing Common Passwords:</h4>";
        foreach ($test_passwords as $pwd) {
            $valid = password_verify($pwd, $hash_data['password_hash']);
            $icon = $valid ? '✅' : '❌';
            $color = $valid ? 'green' : 'red';
            echo "<p style='color:{$color};'>{$icon} Password '{$pwd}': " . ($valid ? 'VALID' : 'Invalid') . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>
