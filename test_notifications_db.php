<?php
require_once 'db.php';

echo "<h2>Notification System Debug</h2>";

// Check notifications count
$count = $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
echo "<p>Total notifications in database: <strong>$count</strong></p>";

// Get recent notifications
echo "<h3>Recent Notifications:</h3>";
$recent = $pdo->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);

if (empty($recent)) {
    echo "<p>No notifications found in database.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Type</th><th>Title</th><th>Message</th><th>Related ID</th><th>Is Read</th><th>Created</th></tr>";
    foreach ($recent as $n) {
        echo "<tr>";
        echo "<td>{$n['notification_id']}</td>";
        echo "<td>{$n['user_id']}</td>";
        echo "<td>{$n['type']}</td>";
        echo "<td>{$n['title']}</td>";
        echo "<td>" . substr($n['message'], 0, 50) . "...</td>";
        echo "<td>{$n['related_id']}</td>";
        echo "<td>" . ($n['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>{$n['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check users
echo "<h3>Users in system:</h3>";
$users = $pdo->query('SELECT user_id, username, email FROM users LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>User ID</th><th>Username</th><th>Email</th></tr>";
foreach ($users as $u) {
    echo "<tr><td>{$u['user_id']}</td><td>{$u['username']}</td><td>{$u['email']}</td></tr>";
}
echo "</table>";

// Check orders
echo "<h3>Recent Orders:</h3>";
$orders = $pdo->query('SELECT order_id, user_id, order_status FROM orders ORDER BY created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Order ID</th><th>User ID</th><th>Status</th></tr>";
foreach ($orders as $o) {
    echo "<tr><td>{$o['order_id']}</td><td>{$o['user_id']}</td><td>{$o['order_status']}</td></tr>";
}
echo "</table>";
?>
