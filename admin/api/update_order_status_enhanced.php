<?php
/**
 * API: Update Order Status (Enhanced with Notifications)
 */
require_once __DIR__ . '/../init_session.php';
require_once __DIR__ . '/../db.php';
init_session();

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
$new_status = trim($_POST['status'] ?? '');

// Validation
if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Order ID is required']);
    exit;
}

$valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    // Get order details
    $order_sql = "SELECT order_id, user_id, order_status FROM orders WHERE order_id = :id";
    $order_stmt = $pdo->prepare($order_sql);
    $order_stmt->execute([':id' => $order_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    $old_status = $order['order_status'];
    
    // Check if status actually changed
    if ($old_status === $new_status) {
        echo json_encode(['success' => true, 'message' => 'Status unchanged']);
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Update order status
    $update_sql = "UPDATE orders SET order_status = :status WHERE order_id = :id";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([
        ':status' => $new_status,
        ':id' => $order_id
    ]);
    
    // Create notification for customer
    $status_messages = [
        'pending' => 'Your order #' . $order_id . ' is now pending.',
        'processing' => 'Your order #' . $order_id . ' is being processed.',
        'shipped' => 'Your order #' . $order_id . ' has been shipped!',
        'delivered' => 'Your order #' . $order_id . ' has been delivered.',
        'cancelled' => 'Your order #' . $order_id . ' has been cancelled.'
    ];
    
    $notif_sql = "INSERT INTO notifications (user_id, type, title, message, related_id, is_read, created_at) 
                  VALUES (:user_id, 'order_status', :title, :message, :related_id, 0, NOW())";
    $notif_stmt = $pdo->prepare($notif_sql);
    $notif_stmt->execute([
        ':user_id' => $order['user_id'],
        ':title' => 'Order Status Updated',
        ':message' => $status_messages[$new_status] ?? 'Your order status has been updated.',
        ':related_id' => $order_id
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'order_id' => $order_id,
        'old_status' => $old_status,
        'new_status' => $new_status
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Update order status error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>
