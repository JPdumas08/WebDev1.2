<?php
// Clear all items from the logged-in user's cart (used after checkout)
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$user_id = require_login_json();

try {
    // Find the cart
    $s = $pdo->prepare('SELECT cart_id FROM cart WHERE user_id = :user_id LIMIT 1');
    $s->execute([':user_id' => $user_id]);
    $cart = $s->fetch();
    if (!$cart) {
        echo json_encode(['success' => true, 'message' => 'no_cart']);
        exit;
    }
    $cart_id = (int)$cart['cart_id'];

    // Delete items from this cart
    $d = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
    $result = $d->execute([':cart_id' => $cart_id]);

    echo json_encode(['success' => true, 'message' => 'cart_cleared']);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'server_error', 'message' => $e->getMessage()]);
    exit;
}

?>
