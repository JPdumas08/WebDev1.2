<?php
/**
 * Admin Notification Badge Counts
 * Fetch real-time counts for sidebar notifications
 */

if (!function_exists('getAdminNotificationCounts')) {
    function getAdminNotificationCounts(PDO $pdo): array {
        try {
            // Pending/new orders (not delivered or cancelled)
            $pending_orders = (int)$pdo->query(
                "SELECT COUNT(*) FROM orders 
                 WHERE order_status IN ('pending', 'processing') 
                 AND payment_status = 'paid'"
            )->fetchColumn();

            // Unread messages
            $unread_messages = (int)$pdo->query(
                "SELECT COUNT(*) FROM contact_messages WHERE status = 'new'"
            )->fetchColumn();

            // Pending reviews (awaiting approval)
            $pending_reviews = (int)$pdo->query(
                "SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'"
            )->fetchColumn();

            // Pending payments (unpaid orders)
            $pending_payments = (int)$pdo->query(
                "SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'"
            )->fetchColumn();

            // Low stock products (stock <= 5)
            $low_stock = (int)$pdo->query(
                "SELECT COUNT(*) FROM products 
                 WHERE product_stock <= 5 
                 AND (is_archived IS NULL OR is_archived = 0)"
            )->fetchColumn();

            return [
                'orders' => $pending_orders,
                'messages' => $unread_messages,
                'reviews' => $pending_reviews,
                'payments' => $pending_payments,
                'low_stock' => $low_stock,
            ];
        } catch (Exception $e) {
            error_log('Notification counts error: ' . $e->getMessage());
            return [
                'orders' => 0,
                'messages' => 0,
                'reviews' => 0,
                'payments' => 0,
                'low_stock' => 0,
            ];
        }
    }
}

// Fetch notification counts
$notification_counts = getAdminNotificationCounts($pdo);
