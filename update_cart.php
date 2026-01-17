<?php
// Update quantity of an existing cart item. If quantity <= 0 the item is removed.
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$user_id = require_login_json();

$input = $_POST;
if (empty($input) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
}

$item_id = isset($input['item_id']) ? (int)$input['item_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : null;

if ($item_id <= 0 || $quantity === null) {
    echo json_encode(['success' => false, 'error' => 'invalid_input']);
    exit;
}

try {
    // Verify ownership: item must belong to user's cart
    $q = "SELECT ci.cart_item_id AS item_id, ci.cart_id, ci.product_id, ci.quantity
        FROM cart_items ci
        JOIN cart c ON c.cart_id = ci.cart_id
        WHERE ci.cart_item_id = :item_id AND c.user_id = :user_id LIMIT 1";
    $stmt = $pdo->prepare($q);
    $stmt->execute([':item_id' => $item_id, ':user_id' => $user_id]);
    $row = $stmt->fetch();
    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'not_found']);
        exit;
    }

    if ($quantity <= 0) {
        // delete the item
        $d = $pdo->prepare('DELETE FROM cart_items WHERE cart_item_id = :item_id');
        $d->execute([':item_id' => $item_id]);
        echo json_encode(['success' => true, 'removed' => true]);
        exit;
    }

    // No stock column in products table; treat available as unlimited
    $available = PHP_INT_MAX;
    $useQty = $quantity > $available ? $available : $quantity;

    $u = $pdo->prepare('UPDATE cart_items SET quantity = :quantity WHERE cart_item_id = :item_id');
    $u->execute([':quantity' => $useQty, ':item_id' => $item_id]);

    // Get updated price and calculate line total
    $priceStmt = $pdo->prepare('SELECT ci.price FROM cart_items ci WHERE ci.cart_item_id = :item_id');
    $priceStmt->execute([':item_id' => $item_id]);
    $priceRow = $priceStmt->fetch();
    $price = $priceRow ? (float)$priceRow['price'] : 0;
    $lineTotal = $price * $useQty;

    echo json_encode([
        'success' => true, 
        'item_id' => $item_id, 
        'quantity' => $useQty,
        'price' => $price,
        'line_total' => $lineTotal
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'server_error', 'message' => $e->getMessage()]);
    exit;
}

?>
