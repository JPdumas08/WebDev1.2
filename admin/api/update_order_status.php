<?php
/**
 * API: Update Order Status
 * Real-time sync with main database
 */
require_once __DIR__ . '/../init_session.php';
require_once __DIR__ . '/../db.php';

// Check admin auth
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$user_id = (int)$_SESSION['user_id'];
$user_sql = "SELECT is_admin FROM users WHERE user_id = :uid";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute([':uid' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['is_admin']) {
    http_response_code(403);
    exit('Unauthorized');
}

// Process request
$order_id = (int)($_POST['order_id'] ?? 0);
$status = $_POST['status'] ?? '';

// Validate status
$valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!$order_id || !in_array($status, $valid_statuses)) {
    http_response_code(400);
    exit('Invalid request');
}

try {
    // Update order status in database
    $update_sql = "UPDATE orders SET order_status = :status WHERE order_id = :oid";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([
        ':status' => $status,
        ':oid' => $order_id
    ]);

    // Log the change
    error_log("Order {$order_id} status updated to {$status} by admin {$user_id}");

    // Redirect back with success
    $_SESSION['message'] = 'Order status updated successfully';
    header('Location: ../orders.php');
    exit();
} catch (Exception $e) {
    error_log("Order status update error: " . $e->getMessage());
    http_response_code(500);
    exit('Error updating order');
}
?>
