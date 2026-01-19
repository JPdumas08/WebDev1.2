<?php
require_once 'db.php';

echo "<h2>Cleaning Old Notifications</h2>";

// Delete old notifications with invalid types
$delete_sql = "DELETE FROM notifications WHERE type NOT IN ('order_status', 'order_update', 'message_reply', 'system', 'promotion')";
$result = $pdo->exec($delete_sql);

echo "<p>Deleted $result old notification(s) with invalid types.</p>";

// Check remaining notifications
$count = $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
echo "<p>Remaining notifications: <strong>$count</strong></p>";

echo "<hr>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Go to admin panel and update an order status</li>";
echo "<li>Check the customer notifications page</li>";
echo "<li>You should see the new notification appear!</li>";
echo "</ol>";

echo "<p><a href='notifications.php'>Go to Notifications Page</a> | <a href='admin/'>Go to Admin Panel</a></p>";
?>
