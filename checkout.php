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
$cart_sql = "SELECT ci.cart_item_id AS item_id, ci.cart_id, ci.product_id, ci.quantity, ci.price,
                    p.product_name, p.product_image
             FROM cart_items ci
             JOIN cart c ON ci.cart_id = c.cart_id
             JOIN products p ON ci.product_id = p.product_id
             WHERE c.user_id = :uid";

$stmt = $pdo->prepare($cart_sql);
$stmt->execute([':uid' => $user_id]);
$all_cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter to only selected items if selectedItems is provided
$cart_items = $all_cart_items;
$selected_item_ids = [];
if (isset($_POST['selectedItems']) && !empty($_POST['selectedItems'])) {
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
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="gcash" value="gcash">
                            <label class="form-check-label" for="gcash">
                                <strong>GCash</strong>
                                <div class="small text-muted">Fast and secure mobile payment</div>
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
                                    <div data-item-id="<?php echo $item['item_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>">
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
            console.log('Selected Items JSON:', selectedItemsJson);
            console.log('Buy Now Product ID:', buyNowProductId);
            
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
                if (this.value === 'gcash') {
                    gcashDetails.style.display = 'block';
                } else {
                    gcashDetails.style.display = 'none';
                }
            });
        });

        // Update GCash amount when total changes (if dynamically updated)
        function updateGCashAmount() {
            const totalElement = document.querySelector('[id*="Total"]');
            if (totalElement) {
                const totalText = totalElement.textContent.replace('₱', '').trim();
                document.getElementById('gcashAmount').textContent = '₱' + totalText;
            }
        }

        // Initial update
        updateGCashAmount();

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
            
            // Get selected items from sessionStorage
            const selectedItems = sessionStorage.getItem('selectedCartItems');
            if (selectedItems) {
                formData.append('selectedItems', selectedItems);
                sessionStorage.removeItem('selectedCartItems'); // Clear after using
            }
            
            // Clear Buy Now mode if set
            sessionStorage.removeItem('buyNowProductId');
            
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
                    // Redirect to appropriate page based on payment method
                    const redirect_url = data.redirect_url || ('order_confirmation.php?order_id=' + data.order_id);
                    ToastNotification.success('Order placed successfully! Redirecting...');
                    setTimeout(() => {
                        window.location.href = redirect_url;
                    }, 1500);
                } else {
                    // Show error message
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