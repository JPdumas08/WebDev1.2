<?php
require_once __DIR__ . '/admin_session.php';

// Confirm logout action
if (!isset($_GET['confirm'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Logout</title>
        <style>
            body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #0f172a; color: white; }
            .logout-box { background: #1e293b; padding: 40px; border-radius: 10px; text-align: center; }
            .btn { padding: 10px 30px; margin: 10px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
            .btn-danger { background: #ef4444; color: white; }
            .btn-secondary { background: #64748b; color: white; }
        </style>
    </head>
    <body>
        <div class="logout-box">
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to logout?</p>
            <a href="logout.php?confirm=yes" class="btn btn-danger">Yes, Logout</a>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

admin_logout();
header('Location: login.php');
exit();
