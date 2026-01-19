<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Link Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .test-link { display: inline-block; padding: 10px 20px; background: #d4af37; color: black; text-decoration: none; border-radius: 5px; margin: 10px; }
        .test-link:hover { background: #c4a027; }
    </style>
</head>
<body>
    <h1>Admin Link Navigation Test</h1>
    <p>If you can click these links and navigate, your admin session is active and links work:</p>
    
    <a href="messages.php" class="test-link">Go to Messages</a>
    <a href="index.php" class="test-link">Go to Dashboard</a>
    
    <hr>
    <h2>Current Admin Info:</h2>
    <p>Username: <?php echo htmlspecialchars($_SESSION['admin_user']['username'] ?? 'Not set'); ?></p>
    <p>Admin ID: <?php echo $_SESSION['admin_user_id'] ?? 'Not set'; ?></p>
</body>
</html>
