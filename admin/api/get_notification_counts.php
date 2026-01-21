<?php
/**
 * Admin Notification Badge API
 * Real-time endpoint for AJAX polling
 */
require_once __DIR__ . '/../auth.php';
header('Content-Type: application/json');

try {
    // Pending/new orders
    $pending_orders = (int)$pdo->query(
        "SELECT COUNT(*) FROM orders 
         WHERE order_status IN ('pending', 'processing') 
         AND payment_status = 'paid'"
    )->fetchColumn();

    // Unread messages
    $unread_messages = (int)$pdo->query(
        "SELECT COUNT(*) FROM contact_messages WHERE status = 'new'"
    )->fetchColumn();

    // Pending reviews
    $pending_reviews = (int)$pdo->query(
        "SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'"
    )->fetchColumn();

    // Pending payments
    $pending_payments = (int)$pdo->query(
        "SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'"
    )->fetchColumn();

    // Low stock products
    $low_stock = (int)$pdo->query(
        "SELECT COUNT(*) FROM products 
         WHERE product_stock <= 5 
         AND (is_archived IS NULL OR is_archived = 0)"
    )->fetchColumn();

    echo json_encode([
        'success' => true,
        'counts' => [
            'orders' => $pending_orders,
            'messages' => $unread_messages,
            'reviews' => $pending_reviews,
            'payments' => $pending_payments,
            'low_stock' => $low_stock,
        ],
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    error_log('Notification API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to fetch notification counts'
    ]);
}
