<?php
/**
 * Archive/Unarchive Product API
 * Handles archiving and unarchiving products
 */
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';

if (!$product_id || !in_array($action, ['archive', 'unarchive'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Check if product exists
    $check_sql = "SELECT product_id, product_name, is_archived FROM products WHERE product_id = :id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':id' => $product_id]);
    $product = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Update archive status
    if ($action === 'archive') {
        $sql = "UPDATE products SET is_archived = 1, archived_at = NOW() WHERE product_id = :id";
        $message = 'Product archived successfully';
    } else {
        $sql = "UPDATE products SET is_archived = 0, archived_at = NULL WHERE product_id = :id";
        $message = 'Product unarchived successfully';
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $product_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'product_id' => $product_id,
        'product_name' => $product['product_name'],
        'is_archived' => ($action === 'archive') ? 1 : 0
    ]);
    
} catch (Exception $e) {
    error_log("Archive product error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
