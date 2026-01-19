<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to reorder.']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int) $input['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit();
}

try {
    // Verify order belongs to user
    $verify_sql = "SELECT order_id FROM orders WHERE order_id = :oid AND user_id = :uid";
    $verify_stmt = $pdo->prepare($verify_sql);
    $verify_stmt->execute([':oid' => $order_id, ':uid' => $user_id]);
    
    if (!$verify_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit();
    }

    // Get all items from the order
    $items_sql = "SELECT oi.product_id, oi.quantity, p.stock_quantity
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.product_id
                  WHERE oi.order_id = :oid";
    $items_stmt = $pdo->prepare($items_sql);
    $items_stmt->execute([':oid' => $order_id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items found in this order.']);
        exit();
    }

    // Check stock availability
    $out_of_stock = [];
    foreach ($items as $item) {
        if ($item['quantity'] > $item['stock_quantity']) {
            $out_of_stock[] = $item['product_id'];
        }
    }

    if (!empty($out_of_stock)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Some items are currently out of stock. Please check product availability.'
        ]);
        exit();
    }

    // Add items to cart
    $added_count = 0;
    
    foreach ($items as $item) {
        $product_id = (int) $item['product_id'];
        $quantity = (int) $item['quantity'];
        
        // Check if item already in cart
        $check_sql = "SELECT cart_id, quantity FROM cart 
                      WHERE user_id = :uid AND product_id = :pid";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update quantity
            $new_quantity = $existing['quantity'] + $quantity;
            // Cap at stock quantity
            $new_quantity = min($new_quantity, $item['stock_quantity']);
            
            $update_sql = "UPDATE cart SET quantity = :qty WHERE cart_id = :cid";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([':qty' => $new_quantity, ':cid' => $existing['cart_id']]);
        } else {
            // Insert new cart item
            $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) 
                          VALUES (:uid, :pid, :qty)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([
                ':uid' => $user_id,
                ':pid' => $product_id,
                ':qty' => $quantity
            ]);
        }
        
        $added_count++;
    }

    echo json_encode([
        'success' => true,
        'message' => "Successfully added {$added_count} item(s) to your cart.",
        'items_added' => $added_count
    ]);

} catch (PDOException $e) {
    error_log("Reorder error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while adding items to cart. Please try again.'
    ]);
}
?>
