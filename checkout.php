<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Get user data
$user_sql = "SELECT first_name, last_name, email_address FROM users WHERE user_id = :uid";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute([':uid' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch saved addresses
$addresses_sql = "SELECT * FROM addresses WHERE user_id = :uid ORDER BY is_default DESC, created_at DESC";
$addr_stmt = $pdo->prepare($addresses_sql);
$addr_stmt->execute([':uid' => $user_id]);
$addresses = $addr_stmt->fetchAll(PDO::FETCH_ASSOC);

// Redirect to address page if no saved addresses
if (empty($addresses)) {
    $_SESSION['checkout_redirect_message'] = 'Please add a shipping address before checking out.';
    header('Location: address.php');
    exit();
}

// Get default address
$default_address = null;
foreach ($addresses as $addr) {
    if ($addr['is_default']) {
        $default_address = $addr;
        break;
    }
}

// If no default, use the first address
if (!$default_address && !empty($addresses)) {
    $default_address = $addresses[0];
}

// Get cart items (PDO)
$cart_sql = "SELECT ci.cart_item_id AS item_id, ci.product_id, ci.quantity, ci.price,
                    p.product_name, p.product_image
             FROM cart_items ci
             JOIN products p ON ci.product_id = p.product_id
             JOIN cart c ON ci.cart_id = c.cart_id
             WHERE c.user_id = :uid";

$stmt = $pdo->prepare($cart_sql);
$stmt->execute([':uid' => $user_id]);
$all_cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if this is a Buy Now checkout (from product detail via URL params)
$buy_now_product_id = null;
$buy_now_quantity = 1;
$is_buy_now_mode = false;

// Debug: log what we receive
error_log('GET params: ' . json_encode($_GET));

if (isset($_GET['buyNow']) && $_GET['buyNow'] == 1 && isset($_GET['productId'])) {
    $is_buy_now_mode = true;
    $buy_now_product_id = (int)$_GET['productId'];
    $buy_now_quantity = isset($_GET['qty']) ? max(1, min(99, (int)$_GET['qty'])) : 1;
    
    error_log('Buy Now Mode Activated - Product ID: ' . $buy_now_product_id . ', Qty: ' . $buy_now_quantity);
    
    // Fetch the product details for Buy Now
    $prod_sql = "SELECT product_id, product_name, product_image, product_price FROM products WHERE product_id = :pid";
    $prod_stmt = $pdo->prepare($prod_sql);
    $prod_stmt->execute([':pid' => $buy_now_product_id]);
    $buy_now_product = $prod_stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log('Product Fetched: ' . json_encode($buy_now_product));
    
    if ($buy_now_product) {
        // Create a temporary cart item for Buy Now (not added to database)
        $buy_now_item = [
            'item_id' => 'buy_now_' . $buy_now_product_id,
            'product_id' => $buy_now_product['product_id'],
            'product_name' => $buy_now_product['product_name'],
            'product_image' => $buy_now_product['product_image'],
            'quantity' => $buy_now_quantity,
            'price' => $buy_now_product['product_price']
        ];
        // Use only the Buy Now item, not the regular cart
        $all_cart_items = [$buy_now_item];
        error_log('Buy Now Item Created: ' . json_encode($buy_now_item));
    }
}


// Filter to only selected items if selectedItems is provided (only in regular cart mode)
$cart_items = $all_cart_items;
$selected_item_ids = [];
if (!$is_buy_now_mode && isset($_POST['selectedItems']) && !empty($_POST['selectedItems'])) {
    $selected_item_ids = array_map('intval', (array)$_POST['selectedItems']);
    $cart_items = array_filter($all_cart_items, function($item) use ($selected_item_ids) {
        return in_array((int)$item['item_id'], $selected_item_ids);
    });
}

// Calculate totals
$subtotal = 0;
$shipping = 150;
foreach ($cart_items as $item) {
    $subtotal += ((float)$item['price']) * ((int)$item['quantity']);
}
$total = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Jewelry Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .order-summary-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .order-summary-card .card-body {
            padding: 0 !important;
            display: flex;
            flex-direction: column;
        }
        .order-summary-card .card-body > * {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }
        .order-summary-card .order-items-container {
            padding: 1rem !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0.875rem !important;
            margin: 0 !important;
            background: #faf9f7 !important;
        }
        .order-summary-card .order-items-container > div {
            margin: 0 !important;
            padding: 1.125rem !important;
            background: #ffffff !important;
            border-radius: 10px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            gap: 1rem !important;
            border: 1px solid #e8e5e0 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
            transition: all 0.2s ease !important;
        }
        .order-summary-card .order-items-container > div[style*="display: none"] {
            display: none !important;
            height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            overflow: hidden !important;
        }
        .order-summary-card .order-items-container > div:hover {
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08) !important;
            border-color: #d4cfc7 !important;
        }
        .order-summary-card .order-item-left {
            display: flex !important;
            gap: 1rem !important;
            align-items: center !important;
            flex: 1 !important;
            min-width: 0 !important;
        }
        .order-summary-card .order-items-container img {
            width: 68px !important;
            height: 68px !important;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #e8e5e0;
            flex-shrink: 0;
            background: #fdfcfb;
        }
        .order-summary-card .order-item-info {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.45rem !important;
            flex: 1 !important;
            min-width: 0 !important;
        }
        .order-summary-card .order-items-container h6 {
            font-size: 0.9375rem;
            font-weight: 600;
            margin: 0 !important;
            color: #2d2a27;
            text-align: left !important;
            line-height: 1.3;
            letter-spacing: -0.01em;
        }
        .order-summary-card .order-item-qty {
            font-size: 0.8125rem !important;
            color: #7a736a !important;
            font-weight: 400 !important;
            letter-spacing: 0.01em !important;
        }
        .order-summary-card .order-items-container .item-price {
            font-weight: 700;
            color: #a67c52;
            font-size: 1.0625rem;
            text-align: right;
            flex-shrink: 0;
            letter-spacing: -0.02em;
        }
        .order-summary-card .order-totals {
            padding: 1.25rem 1rem 1rem !important;
            border-top: 2px solid #e8e5e0 !important;
            margin-top: auto !important;
            background: #ffffff !important;
        }
        
        /* Order Summary Card Styling */
        .order-summary-card .card-header {
            background: #ffffff !important;
            border-bottom: 2px solid #e8e5e0 !important;
            padding: 1.125rem 1.25rem !important;
        }
        
        .order-summary-card .card-header h5 {
            color: #2d2a27 !important;
            font-weight: 600 !important;
            font-size: 1.125rem !important;
            letter-spacing: -0.01em !important;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <button class="btn btn-outline-secondary" onclick="window.history.back();" type="button">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <h2 class="mb-0">Checkout</h2>
                </div>
                
                <!-- Shipping Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <form id="checkoutForm" method="POST" action="process_checkout.php" onsubmit="return false;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="checkout_form" value="1">
                            <input type="hidden" id="addressId" name="addressId" value="<?php echo $default_address ? $default_address['address_id'] : ''; ?>">
                            
                            <div class="mb-4">
                                <h6 class="mb-3">Select Shipping Address</h6>
                                <div class="row g-3" id="savedAddresses">
                                    <?php foreach ($addresses as $addr): ?>
                                        <div class="col-md-6">
                                            <div class="card border cursor-pointer address-option" 
                                                 data-address-id="<?php echo $addr['address_id']; ?>"
                                                 data-full-name="<?php echo htmlspecialchars($addr['full_name']); ?>"
                                                 data-phone="<?php echo htmlspecialchars($addr['phone']); ?>"
                                                 data-address-line1="<?php echo htmlspecialchars($addr['address_line1']); ?>"
                                                 data-address-line2="<?php echo htmlspecialchars($addr['address_line2']); ?>"
                                                 data-city="<?php echo htmlspecialchars($addr['city']); ?>"
                                                 data-state="<?php echo htmlspecialchars($addr['state']); ?>"
                                                 data-postal-code="<?php echo htmlspecialchars($addr['postal_code']); ?>"
                                                 style="cursor: pointer; transition: all 0.3s; <?php echo ($addr['is_default'] || ($default_address && $addr['address_id'] == $default_address['address_id'])) ? 'border: 2px solid #0d6efd !important;' : ''; ?>">
                                                <div class="card-body">
                                                    <?php if ($addr['is_default']): ?>
                                                        <span class="badge bg-primary mb-2">Default</span>
                                                    <?php endif; ?>
                                                    <h6 class="card-title mb-2"><?php echo htmlspecialchars($addr['full_name']); ?></h6>
                                                    <small class="text-muted d-block"><?php echo htmlspecialchars($addr['phone']); ?></small>
                                                    <small class="text-muted d-block"><?php echo htmlspecialchars($addr['address_line1']); ?><?php echo $addr['address_line2'] ? ', ' . htmlspecialchars($addr['address_line2']) : ''; ?></small>
                                                    <small class="text-muted d-block"><?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['state']); ?> <?php echo htmlspecialchars($addr['postal_code']); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3">
                                    <a href="address.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-plus"></i> Manage Addresses
                                    </a>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="orderNotes" class="form-label">Order Notes (Optional)</label>
                                <textarea class="form-control" id="orderNotes" name="orderNotes" rows="3" placeholder="Special instructions for your order..."></textarea>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="cod" value="cod" checked>
                            <label class="form-check-label" for="cod">
                                <strong>Cash on Delivery (COD)</strong>
                                <div class="small text-muted">Pay when you receive your order</div>
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="gcash" value="gcash">
                            <label class="form-check-label" for="gcash">
                                <strong>GCash</strong>
                                <div class="small text-muted">Fast and secure mobile payment</div>
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="paypal" value="paypal">
                            <label class="form-check-label" for="paypal">
                                <strong>PayPal</strong>
                                <div class="small text-muted">Pay securely with your PayPal account</div>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="bank_transfer" value="bank_transfer">
                            <label class="form-check-label" for="bank_transfer">
                                <strong>Bank Transfer</strong>
                                <div class="small text-muted">Direct bank deposit or online transfer</div>
                            </label>
                        </div>

                        <!-- GCash Payment Details (hidden by default) -->
                        <div id="gcashDetails" class="mt-3 p-3 bg-light rounded" style="display: none;">
                            <h6 class="mb-3">GCash Payment Instructions</h6>
                            <div class="alert alert-info mb-3">
                                <strong>Mobile Number:</strong> +63 9XX XXX XXXX
                            </div>
                            <ol class="small mb-0">
                                <li>Open your GCash app</li>
                                <li>Go to Send Money</li>
                                <li>Enter the GCash number above</li>
                                <li>Enter the amount: <strong id="gcashAmount">₱0.00</strong></li>
                                <li>Add reference: Your Order ID (provided after checkout)</li>
                                <li>Complete the transaction</li>
                            </ol>
                        </div>

                        <!-- PayPal Payment Details (hidden by default) -->
                        <div id="paypalDetails" class="mt-3 p-3 bg-light rounded" style="display: none;">
                            <h6 class="mb-3">PayPal Payment Instructions</h6>
                            <div class="alert alert-info mb-3">
                                <strong>PayPal Email:</strong> payments@jeweluxe.com
                            </div>
                            <ol class="small mb-0">
                                <li>Log in to your PayPal account</li>
                                <li>Go to Send & Request</li>
                                <li>Enter the PayPal email above</li>
                                <li>Enter the amount: <strong id="paypalAmount">₱0.00</strong></li>
                                <li>Add reference: Your Order ID (provided after checkout)</li>
                                <li>Complete the transaction</li>
                            </ol>
                        </div>

                        <!-- Bank Transfer Payment Details (hidden by default) -->
                        <div id="bankTransferDetails" class="mt-3 p-3 bg-light rounded" style="display: none;">
                            <h6 class="mb-3">Bank Transfer Instructions</h6>
                            <div class="alert alert-info mb-3">
                                <div class="mb-2"><strong>Bank Name:</strong> BDO Unibank</div>
                                <div class="mb-2"><strong>Account Name:</strong> Jeweluxe Store</div>
                                <div><strong>Account Number:</strong> 1234-5678-9012</div>
                            </div>
                            <ol class="small mb-0">
                                <li>Visit your bank branch or use online banking</li>
                                <li>Transfer to the account above</li>
                                <li>Enter the amount: <strong id="bankTransferAmount">₱0.00</strong></li>
                                <li>Use your Order ID as reference (provided after checkout)</li>
                                <li>Keep your transaction receipt</li>
                                <li>Confirm your payment on the payment page</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-md-4">
                <div class="card order-summary-card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($cart_items) > 0): ?>
                            <div class="order-items-container">
                                <?php foreach ($cart_items as $item): ?>
                                    <div data-item-id="<?php echo $item['item_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>"
                                         <?php if ($is_buy_now_mode): ?>
                                         data-buy-now-product-id="<?php echo $item['product_id']; ?>"
                                         data-buy-now-product-name="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         data-buy-now-product-image="<?php echo htmlspecialchars($item['product_image']); ?>"
                                         data-buy-now-product-price="<?php echo $item['price']; ?>"
                                         data-buy-now-quantity="<?php echo $item['quantity']; ?>"
                                         <?php endif; ?>
                                    >
                                        <div class="order-item-left">
                                            <img src="<?php echo $item['product_image']; ?>" alt="<?php echo $item['product_name']; ?>">
                                            <div class="order-item-info">
                                                <h6><?php echo $item['product_name']; ?></h6>
                                                <span class="order-item-qty">Qty: <?php echo $item['quantity']; ?></span>
                                            </div>
                                        </div>
                                        <span class="item-price">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="order-totals">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span id="orderSubtotal">₱<?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Shipping:</span>
                                    <span id="orderShipping">₱<?php echo number_format($shipping, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Tax:</span>
                                    <span>₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold mb-3">
                                    <span>Total:</span>
                                    <span id="orderTotal">₱<?php echo number_format($total, 2); ?></span>
                                </div>
                                
                                <button type="submit" form="checkoutForm" class="btn btn-primary w-100">
                                    Place Order
                                </button>
                                
                                <div class="text-center mt-3">
                                    <a href="cart.php" class="text-decoration-none">
                                        <small>← Back to Cart</small>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-center">Your cart is empty.</p>
                            <div class="text-center">
                                <a href="products.php" class="btn btn-outline-primary">Continue Shopping</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to recalculate Order Summary from current DOM state
        function recalculateOrderSummary() {
            const selectedItemsJson = sessionStorage.getItem('selectedCartItems');
            const buyNowProductId = sessionStorage.getItem('buyNowProductId');
            
            if (!selectedItemsJson && !buyNowProductId) {
                return;
            }
            
            // Fetch updated cart data from server
            fetch('get_cart.php')
                .then(response => response.json())
                .then(cartData => {
                    if (!cartData || !cartData.items) {
                        console.error('Invalid cart data received');
                        return;
                    }
                    
                    let filterMode = 'itemId';
                    let selectedItems = [];
                    
                    if (buyNowProductId) {
                        filterMode = 'productId';
                        selectedItems = [parseInt(buyNowProductId)];
                    } else if (selectedItemsJson) {
                        filterMode = 'itemId';
                        selectedItems = JSON.parse(selectedItemsJson);
                    }
                    
                    let selectedSubtotal = 0;
                    const itemElements = document.querySelectorAll('[data-item-id]');
                    
                    itemElements.forEach(element => {
                        const itemId = parseInt(element.getAttribute('data-item-id'));
                        const productId = parseInt(element.getAttribute('data-product-id'));
                        
                        let shouldShow = false;
                        let updatedItem = null;
                        
                        if (filterMode === 'productId') {
                            shouldShow = selectedItems.includes(productId);
                            updatedItem = cartData.items.find(item => parseInt(item.product_id) === productId);
                        } else {
                            shouldShow = selectedItems.includes(itemId);
                            updatedItem = cartData.items.find(item => parseInt(item.item_id) === itemId);
                        }
                        
                        if (shouldShow && updatedItem) {
                            // Update quantity display
                            const qtyElement = element.querySelector('.order-item-qty');
                            if (qtyElement) {
                                qtyElement.textContent = 'Qty: ' + updatedItem.quantity;
                            }
                            
                            // Calculate and update price
                            const itemTotal = parseFloat(updatedItem.price) * parseInt(updatedItem.quantity);
                            const priceElement = element.querySelector('.item-price');
                            if (priceElement) {
                                priceElement.textContent = '₱' + itemTotal.toFixed(2);
                                selectedSubtotal += itemTotal;
                            }
                        }
                    });
                    
                    // Update totals
                    const shipping = selectedSubtotal > 0 ? 150 : 0;
                    const total = selectedSubtotal + shipping;
                    
                    const subtotalEl = document.getElementById('orderSubtotal');
                    const shippingEl = document.getElementById('orderShipping');
                    const totalEl = document.getElementById('orderTotal');
                    const gcashAmountEl = document.getElementById('gcashAmount');
                    
                    if (subtotalEl) subtotalEl.textContent = '₱' + selectedSubtotal.toFixed(2);
                    if (shippingEl) shippingEl.textContent = '₱' + shipping.toFixed(2);
                    if (totalEl) totalEl.textContent = '₱' + total.toFixed(2);
                    if (gcashAmountEl) gcashAmountEl.textContent = '₱' + total.toFixed(2);
                    
                    // Update all payment method amounts
                    updatePaymentAmounts();
                })
                .catch(error => {
                    console.error('Error fetching cart data:', error);
                });
        }
        
        // Listen for cart update events
        window.addEventListener('cartUpdated', function(e) {
            console.log('Cart updated event received, recalculating Order Summary...');
            recalculateOrderSummary();
        });
        
        // Filter items based on selected items from cart
        document.addEventListener('DOMContentLoaded', function() {
            const selectedItemsJson = sessionStorage.getItem('selectedCartItems');
            const buyNowProductId = sessionStorage.getItem('buyNowProductId');
            
            // Check if we're in Buy Now mode via URL (newer implementation)
            const urlParams = new URLSearchParams(window.location.search);
            const isBuyNowUrl = urlParams.has('buyNow') && urlParams.get('buyNow') === '1';
            
            console.log('Selected Items JSON:', selectedItemsJson);
            console.log('Buy Now Product ID (sessionStorage):', buyNowProductId);
            console.log('Buy Now via URL:', isBuyNowUrl);
            
            // Skip filtering if Buy Now is via URL (PHP already handled it)
            if (isBuyNowUrl) {
                console.log('Buy Now mode via URL - skipping JS filtering');
                return;
            }
            
            if (selectedItemsJson || buyNowProductId) {
                try {
                    let filterMode = 'itemId'; // 'itemId' or 'productId'
                    let selectedItems = [];
                    
                    if (buyNowProductId) {
                        // Buy Now mode: filter by product ID
                        filterMode = 'productId';
                        selectedItems = [parseInt(buyNowProductId)];
                    } else if (selectedItemsJson) {
                        // Regular cart checkout: filter by item IDs
                        filterMode = 'itemId';
                        selectedItems = JSON.parse(selectedItemsJson);
                    }
                    
                    console.log('Filter mode:', filterMode);
                    console.log('Selected Items Array:', selectedItems);
                    
                    const itemElements = Array.from(document.querySelectorAll('[data-item-id]'));
                    console.log('Found item elements:', itemElements.length);
                    
                    let selectedSubtotal = 0;
                    let visibleItemsCount = 0;
                    const visibleItems = [];
                    const hiddenItems = [];

                    // First pass: categorize items and calculate totals
                    itemElements.forEach(element => {
                        let shouldShow = false;
                        
                        if (filterMode === 'productId') {
                            const productId = parseInt(element.getAttribute('data-product-id'));
                            shouldShow = selectedItems.includes(productId);
                            console.log('Checking product ID:', productId, 'Is selected:', shouldShow);
                        } else {
                            const itemId = parseInt(element.getAttribute('data-item-id'));
                            shouldShow = selectedItems.includes(itemId);
                            console.log('Checking item ID:', itemId, 'Is selected:', shouldShow);
                        }
                        
                        if (shouldShow) {
                            // Get the price from the item-price span
                            const priceElement = element.querySelector('.item-price');
                            if (priceElement) {
                                const priceText = priceElement.textContent.trim();
                                const price = parseFloat(priceText.replace('₱', '').replace(/,/g, ''));
                                console.log('Item price:', price);
                                selectedSubtotal += price;
                            }
                            visibleItems.push({
                                element: element,
                                productId: parseInt(element.getAttribute('data-product-id'))
                            });
                            visibleItemsCount++;
                        } else {
                            hiddenItems.push(element);
                        }
                    });

                    // Sort visible items by product ID for consistent ordering
                    visibleItems.sort((a, b) => a.productId - b.productId);

                    // Get the parent container
                    const container = document.querySelector('.order-items-container');
                    if (container) {
                        // Second pass: reorder and show/hide items
                        visibleItems.forEach((item, index) => {
                            item.element.style.display = 'flex';
                            item.element.style.visibility = 'visible';
                            item.element.style.order = index;
                            container.appendChild(item.element); // Move to end in sorted order
                        });

                        // Hide non-selected items completely
                        hiddenItems.forEach(element => {
                            element.style.display = 'none';
                            element.style.visibility = 'hidden';
                            element.style.height = '0';
                            element.style.padding = '0';
                            element.style.margin = '0';
                            element.style.border = 'none';
                            element.style.overflow = 'hidden';
                        });
                    }

                    console.log('Selected subtotal:', selectedSubtotal);
                    console.log('Visible items:', visibleItemsCount);

                    // Update totals
                    const shipping = selectedSubtotal > 0 ? 150 : 0;
                    const total = selectedSubtotal + shipping;
                    
                    const subtotalEl = document.getElementById('orderSubtotal');
                    const shippingEl = document.getElementById('orderShipping');
                    const totalEl = document.getElementById('orderTotal');
                    const gcashAmountEl = document.getElementById('gcashAmount');
                    
                    if (subtotalEl) subtotalEl.textContent = '₱' + selectedSubtotal.toFixed(2);
                    if (shippingEl) shippingEl.textContent = '₱' + shipping.toFixed(2);
                    if (totalEl) totalEl.textContent = '₱' + total.toFixed(2);
                    if (gcashAmountEl) gcashAmountEl.textContent = '₱' + total.toFixed(2);
                    
                    // Update all payment method amounts
                    updatePaymentAmounts();
                    
                    console.log('Totals updated');
                } catch (error) {
                    console.error('Error parsing selected items:', error);
                }
            } else {
                console.log('No selected items in sessionStorage');
            }
        });

        // Handle payment method changes
        document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const gcashDetails = document.getElementById('gcashDetails');
                const paypalDetails = document.getElementById('paypalDetails');
                const bankTransferDetails = document.getElementById('bankTransferDetails');
                
                // Hide all payment details first
                gcashDetails.style.display = 'none';
                paypalDetails.style.display = 'none';
                bankTransferDetails.style.display = 'none';
                
                // Show the selected payment method details
                if (this.value === 'gcash') {
                    gcashDetails.style.display = 'block';
                } else if (this.value === 'paypal') {
                    paypalDetails.style.display = 'block';
                } else if (this.value === 'bank_transfer') {
                    bankTransferDetails.style.display = 'block';
                }
            });
        });

        // Update payment amounts when total changes (if dynamically updated)
        function updatePaymentAmounts() {
            const totalElement = document.getElementById('orderTotal');
            if (totalElement) {
                const totalText = totalElement.textContent.trim();
                document.getElementById('gcashAmount').textContent = totalText;
                document.getElementById('paypalAmount').textContent = totalText;
                document.getElementById('bankTransferAmount').textContent = totalText;
            }
        }

        // Initial update
        updatePaymentAmounts();

        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            // IMPORTANT: Prevent default form submission immediately
            e.preventDefault();
            e.stopPropagation();
            
            // Check if address is selected
            const addressId = document.getElementById('addressId').value;
            if (!addressId) {
                ToastNotification.error('Please select a shipping address.');
                return false;
            }
            
            // Get form data
            const formData = new FormData(this);
            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
            
            // Add payment method to form data
            formData.append('paymentMethod', paymentMethod);
            
            // Check if this is a Buy Now checkout
            const urlParams = new URLSearchParams(window.location.search);
            const isBuyNow = urlParams.has('buyNow') && urlParams.get('buyNow') === '1';
            
            if (isBuyNow) {
                // Buy Now mode: pass product details to backend
                formData.append('isBuyNow', '1');
                
                // Get Buy Now product details from DOM
                const buyNowProduct = document.querySelector('[data-product-id]');
                if (buyNowProduct) {
                    const productId = buyNowProduct.getAttribute('data-product-id');
                    const productData = document.querySelector('[data-buy-now-product-id="' + productId + '"]');
                    
                    if (productData) {
                        formData.append('buyNowProductId', productData.getAttribute('data-buy-now-product-id') || productId);
                        formData.append('buyNowProductName', productData.getAttribute('data-buy-now-product-name') || '');
                        formData.append('buyNowProductImage', productData.getAttribute('data-buy-now-product-image') || '');
                        formData.append('buyNowPrice', productData.getAttribute('data-buy-now-product-price') || '0');
                        formData.append('buyNowQuantity', productData.getAttribute('data-buy-now-quantity') || '1');
                    }
                }
            } else {
                // Regular cart mode: get selected items from sessionStorage
                const selectedItems = sessionStorage.getItem('selectedCartItems');
                if (selectedItems) {
                    formData.append('selectedItems', selectedItems);
                    // DO NOT clear sessionStorage here - only clear after successful server confirmation
                    // This prevents loss of selection if validation fails and user retries
                }
                formData.append('isBuyNow', '0');
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            }
            
            // Send to process_checkout.php
            fetch('process_checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Clear selectedItems ONLY after successful order creation
                    sessionStorage.removeItem('selectedCartItems');
                    
                    // Redirect to appropriate page based on payment method
                    const redirect_url = data.redirect_url || ('order_confirmation.php?order_id=' + data.order_id);
                    ToastNotification.success('Order placed successfully! Redirecting...');
                    setTimeout(() => {
                        window.location.href = redirect_url;
                    }, 1500);
                } else {
                    // Show error message BUT DO NOT clear selectedItems
                    // User should be able to retry with the same selection
                    ToastNotification.error(data.message || 'An error occurred while processing your order.');
                    // Re-enable button
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Place Order';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // DO NOT clear selectedItems on error
                // User should be able to retry with the same selection
                ToastNotification.error('Network error: ' + error.message);
                // Re-enable button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Place Order';
                }
            });
            
            return false;
        });

        // Handle address selection
        document.querySelectorAll('.address-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove active state from all options
                document.querySelectorAll('.address-option').forEach(o => {
                    o.style.borderColor = '#dee2e6';
                    o.style.borderWidth = '1px';
                });
                
                // Add active state to selected option
                this.style.borderColor = '#0d6efd';
                this.style.borderWidth = '2px';
                
                // Update the hidden addressId input
                const addressId = this.dataset.addressId;
                document.getElementById('addressId').value = addressId;
            });
        });

        // Set initial active state on page load (select default or first address)
        const defaultAddress = document.querySelector('.address-option');
        if (defaultAddress) {
            defaultAddress.click();
        }
    </script>
</body>
</html>