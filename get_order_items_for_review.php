<?php
// Set JSON header FIRST to prevent any HTML redirects
header('Content-Type: application/json');

// Initialize session manually to avoid redirects in init_session.php
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication without redirecting
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to review orders.']);
    exit();
}

// Now include database connection
require_once __DIR__ . '/db.php';

$user_id = (int) $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit();
}

try {
    // Verify order belongs to user and is delivered
    $order_sql = "SELECT order_id, order_status FROM orders WHERE order_id = :oid AND user_id = :uid LIMIT 1";
    $order_stmt = $pdo->prepare($order_sql);
    $order_stmt->execute([':oid' => $order_id, ':uid' => $user_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit();
    }

    if (strtolower($order['order_status']) !== 'delivered') {
        echo json_encode(['success' => false, 'message' => 'Reviews can only be submitted for delivered orders.']);
        exit();
    }

    // Get all order items with review status
    // Check if user has already reviewed this product (from any order, but prefer this order)
    $items_sql = "
        SELECT 
            oi.product_id,
            oi.quantity,
            p.product_name,
            p.product_image,
            CASE 
                WHEN pr.review_id IS NOT NULL THEN 1 
                ELSE 0 
            END as has_review
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN product_reviews pr ON pr.product_id = oi.product_id 
            AND pr.user_id = :uid 
            AND (pr.order_id = :order_id_review OR pr.order_id IS NULL)
        WHERE oi.order_id = :oid
        ORDER BY oi.order_item_id ASC
    ";
    
    $items_stmt = $pdo->prepare($items_sql);
    $items_stmt->execute([':oid' => $order_id, ':uid' => $user_id, ':order_id_review' => $order_id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items found for this order.']);
        exit();
    }

    // Format items for frontend
    $formatted_items = array_map(function($item) {
        return [
            'product_id' => (int) $item['product_id'],
            'product_name' => $item['product_name'],
            'product_image' => $item['product_image'] ?: 'images/placeholder.jpg',
            'quantity' => (int) $item['quantity'],
            'has_review' => (bool) $item['has_review']
        ];
    }, $items);

    echo json_encode([
        'success' => true,
        'items' => $formatted_items,
        'order_id' => $order_id
    ]);

} catch (PDOException $e) {
    error_log('Get order items for review PDO error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]); // DEBUG: show actual error
} catch (Exception $e) {
    error_log('Get order items for review error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]); // DEBUG: show actual error
}
