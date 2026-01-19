<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Ensure session is active
init_session();

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    error_log('Checkout failed: No user_id in session. Session data: ' . json_encode($_SESSION));
    echo json_encode(['success' => false, 'message' => 'Please log in to continue.']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Validate POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Verify CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit();
}

// Check if addressId is provided (required for saved address checkout)
if (!isset($_POST['addressId']) || empty($_POST['addressId'])) {
    echo json_encode(['success' => false, 'message' => 'Please select a shipping address.']);
    exit();
}

$address_id = (int)$_POST['addressId'];
$paymentMethod = isset($_POST['paymentMethod']) ? trim($_POST['paymentMethod']) : '';
$orderNotes = isset($_POST['orderNotes']) ? trim($_POST['orderNotes']) : '';

// Validate payment method
if (empty($paymentMethod)) {
    echo json_encode(['success' => false, 'message' => 'Please select a payment method.']);
    exit();
}

// Validate payment method is one of the allowed options
$allowed_payment_methods = ['cod', 'gcash', 'paypal', 'bank_transfer'];
if (!in_array($paymentMethod, $allowed_payment_methods)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method selected.']);
    exit();
}

// Fetch the selected address
$address_sql = "SELECT * FROM addresses WHERE address_id = :aid AND user_id = :uid";
$address_stmt = $pdo->prepare($address_sql);
$address_stmt->execute([':aid' => $address_id, ':uid' => $user_id]);
$address = $address_stmt->fetch(PDO::FETCH_ASSOC);

if (!$address) {
    echo json_encode(['success' => false, 'message' => 'Invalid address selected. Please select a valid shipping address.']);
    exit();
}

// Check if this is a Buy Now checkout (Buy Now item is passed via POST)
$is_buy_now_checkout = isset($_POST['isBuyNow']) && $_POST['isBuyNow'] === '1';

// Get cart items (PDO)
$cart_sql = "SELECT ci.cart_item_id AS item_id, ci.cart_id, ci.product_id, ci.quantity, ci.price,
                    p.product_name, p.product_image
             FROM cart_items ci
             JOIN cart c ON ci.cart_id = c.cart_id
             JOIN products p ON ci.product_id = p.product_id
             WHERE c.user_id = :uid";

$stmt = $pdo->prepare($cart_sql);
$stmt->execute([':uid' => $user_id]);
$all_cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine which items to process for checkout
$cart_items = [];

if ($is_buy_now_checkout) {
    // Buy Now mode: get the product from POST data
    if (isset($_POST['buyNowProductId']) && isset($_POST['buyNowQuantity'])) {
        $buy_product_id = (int)$_POST['buyNowProductId'];
        $buy_quantity = (int)$_POST['buyNowQuantity'];
        $buy_price = isset($_POST['buyNowPrice']) ? (float)$_POST['buyNowPrice'] : 0;
        
        if ($buy_product_id > 0 && $buy_quantity > 0) {
            // Create a temporary cart item for Buy Now processing
            $cart_items = [[
                'item_id' => 'buy_now_' . $buy_product_id,
                'cart_id' => null,  // No actual cart_id for Buy Now items
                'product_id' => $buy_product_id,
                'quantity' => $buy_quantity,
                'price' => $buy_price,
                'product_name' => $_POST['buyNowProductName'] ?? '',
                'product_image' => $_POST['buyNowProductImage'] ?? ''
            ]];
        }
    }
} else {
    // Regular cart checkout: filter to only selected items if selectedItems is provided
    // First, try to get selectedItems from POST (comes from client sessionStorage)
    $selected_items_source = 'post';
    if (isset($_POST['selectedItems']) && !empty($_POST['selectedItems'])) {
        $selected_items_json = $_POST['selectedItems'];
        // BACKUP: Also store in SESSION for persistence across retries
        $_SESSION['checkout_selected_items'] = $selected_items_json;
    } elseif (isset($_SESSION['checkout_selected_items']) && !empty($_SESSION['checkout_selected_items'])) {
        // FALLBACK: If POST selectedItems missing, use SESSION backup (client sessionStorage might have been cleared)
        $selected_items_json = $_SESSION['checkout_selected_items'];
        $selected_items_source = 'session';
        error_log('Using SESSION backup for selectedItems (client sessionStorage may have been cleared)');
    } else {
        $selected_items_json = null;
    }
    
    // Process selected items
    $cart_items = $all_cart_items;
    if ($selected_items_json) {
        $selected_items = json_decode($selected_items_json, true);
        if (is_array($selected_items)) {
            $selected_item_ids = array_map('intval', $selected_items);
            $cart_items = array_filter($all_cart_items, function($item) use ($selected_item_ids) {
                return in_array((int)$item['item_id'], $selected_item_ids);
            });
            error_log('Cart checkout using ' . $selected_items_source . ': Processing ' . count($selected_item_ids) . ' selected items out of ' . count($all_cart_items) . ' total cart items');
        }
    }
}

if (count($cart_items) === 0) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty or no items selected.']);
    exit();
}

// Validate all products before processing (real-time stock check)
$validation_errors = [];
foreach ($cart_items as $item) {
    $product_id = (int)$item['product_id'];
    $requested_qty = (int)$item['quantity'];
    
    // Fetch current product stock and archive status
    $check_sql = "SELECT product_stock, is_archived, product_name FROM products WHERE product_id = :pid";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':pid' => $product_id]);
    $product_check = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product_check) {
        $validation_errors[] = "Product " . ($item['product_name'] ?? $product_id) . " no longer exists.";
        continue;
    }
    
    // Check if archived
    if (isset($product_check['is_archived']) && $product_check['is_archived'] == 1) {
        $validation_errors[] = $product_check['product_name'] . " is no longer available.";
        continue;
    }
    
    // Check stock availability
    $current_stock = isset($product_check['product_stock']) ? (int)$product_check['product_stock'] : 0;
    if ($current_stock < $requested_qty) {
        if ($current_stock == 0) {
            $validation_errors[] = $product_check['product_name'] . " is out of stock.";
        } else {
            $validation_errors[] = $product_check['product_name'] . " only has " . $current_stock . " units available (you requested " . $requested_qty . ").";
        }
    }
}

// If any validation errors, return them
if (!empty($validation_errors)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Some products are unavailable:',
        'errors' => $validation_errors
    ]);
    exit();
}

// Calculate order totals
$subtotal = 0;
$shipping = 150;
$tax = 0;

foreach ($cart_items as $item) {
    $subtotal += ((float)$item['price']) * ((int)$item['quantity']);
}
$total = $subtotal + $shipping + $tax;

// Build shipping address string from saved address
$shipping_address = sprintf(
    "%s\n%s\n%s, %s %s\n%s",
    $address['full_name'],
    $address['address_line1'] . ($address['address_line2'] ? ', ' . $address['address_line2'] : ''),
    $address['city'],
    $address['state'],
    $address['postal_code'],
    $address['phone']
);

// Begin transaction
$pdo->beginTransaction();

try {
    // Generate unique order number
    $order_number = 'ORD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Set payment status based on payment method
    // All payment methods start as 'pending'
    // Status will be updated to 'paid' after user confirms payment on respective payment pages
    $payment_status = 'pending';

    // Insert order (align with schema columns that exist in WEBDEV-MAIN.sql)
    $order_sql = "INSERT INTO orders (user_id, order_number, subtotal, shipping_cost, tax, total_amount, payment_method, payment_status, shipping_address, billing_address)
                  VALUES (:uid, :order_number, :subtotal, :shipping, :tax, :total, :payment_method, :payment_status, :shipping_address, :billing_address)";

    $order_stmt = $pdo->prepare($order_sql);
    $order_stmt->execute([
        ':uid' => $user_id,
        ':order_number' => $order_number,
        ':subtotal' => $subtotal,
        ':shipping' => $shipping,
        ':tax' => $tax,
        ':total' => $total,
        ':payment_method' => $paymentMethod,
        ':payment_status' => $payment_status,
        ':shipping_address' => $shipping_address,
        ':billing_address' => $shipping_address
    ]);

    $order_id = (int)$pdo->lastInsertId();

    // Insert order items
    $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price)
                 VALUES (:order_id, :product_id, :quantity, :unit_price, :total_price)";
    $item_stmt = $pdo->prepare($item_sql);

    foreach ($cart_items as $item) {
        $item_total = ((float)$item['price']) * ((int)$item['quantity']);
        $item_stmt->execute([
            ':order_id' => $order_id,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':unit_price' => $item['price'],
            ':total_price' => $item_total
        ]);
        
        // Deduct stock from products table
        $update_stock_sql = "UPDATE products SET product_stock = product_stock - :qty WHERE product_id = :pid AND product_stock >= :qty_check";
        $update_stock_stmt = $pdo->prepare($update_stock_sql);
        $stock_updated = $update_stock_stmt->execute([
            ':qty' => $item['quantity'],
            ':qty_check' => $item['quantity'],
            ':pid' => $item['product_id']
        ]);
        
        // Verify stock was actually updated
        if ($update_stock_stmt->rowCount() === 0) {
            throw new Exception("Failed to update stock for product ID " . $item['product_id'] . ". Product may be out of stock.");
        }
    }

    // Insert payment record
    $payment_sql = "INSERT INTO payments (order_id, payment_method, amount, status)
                    VALUES (:order_id, :payment_method, :amount, :status)";
    $pay_stmt = $pdo->prepare($payment_sql);
    $pay_stmt->execute([
        ':order_id' => $order_id,
        ':payment_method' => $paymentMethod,
        ':amount' => $total,
        ':status' => 'pending' // All payments start as pending
    ]);

    // Only clear cart items if this is NOT a Buy Now checkout
    // For regular cart checkout, only delete the items that were selected for purchase
    if (!$is_buy_now_checkout && !empty($cart_items)) {
        // Get the cart IDs from the items being purchased
        $items_to_delete = array_filter($cart_items, function($item) {
            return !strpos($item['item_id'], 'buy_now_'); // Only delete actual cart items, not Buy Now items
        });
        
        if (!empty($items_to_delete)) {
            // Delete only the selected cart items that were included in the purchase
            $item_ids_to_delete = array_values(array_map(function($item) { return (int)$item['item_id']; }, $items_to_delete));
            
            if (!empty($item_ids_to_delete)) {
                $placeholders = implode(',', array_fill(0, count($item_ids_to_delete), '?'));
                $delete_sql = "DELETE FROM cart_items WHERE cart_item_id IN (" . $placeholders . ")";
                $delete_stmt = $pdo->prepare($delete_sql);
                $delete_stmt->execute($item_ids_to_delete);
            }
        }
    }
    // Note: Buy Now checkout does NOT delete from cart_items table since it doesn't use the cart database
    // The order is created from the Buy Now product data passed in POST

    $pdo->commit();

    // Clear session backup only after successful order creation
    unset($_SESSION['checkout_selected_items']);

    // Determine redirect based on payment method
    // Redirect to respective payment instruction/confirmation pages
    $redirect_url = 'order_confirmation.php?order_id=' . $order_id;
    
    if ($paymentMethod === 'gcash') {
        $redirect_url = 'gcash_payment.php?order_id=' . $order_id;
    } elseif ($paymentMethod === 'paypal') {
        $redirect_url = 'paypal_payment.php?order_id=' . $order_id;
    } elseif ($paymentMethod === 'bank_transfer') {
        $redirect_url = 'bank_transfer_payment.php?order_id=' . $order_id;
    }

    // Log checkout success for debugging
    error_log('Order created successfully. Order ID: ' . $order_id . ', Payment Method: ' . $paymentMethod . ', Is Buy Now: ' . ($is_buy_now_checkout ? 'yes' : 'no'));

    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'order_number' => $order_number,
        'payment_method' => $paymentMethod,
        'payment_status' => $payment_status,
        'redirect_url' => $redirect_url,
        'message' => 'Order placed successfully!'
    ]);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Checkout error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your order. Please try again.', 'debug' => $e->getMessage()]);
    exit();
}
?>