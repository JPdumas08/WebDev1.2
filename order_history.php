<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php?redirect=order_history');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Get user's name and email from database
$user_sql = "SELECT first_name, last_name, email_address FROM users WHERE user_id = :uid";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute([':uid' => $user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

$user_name = $user_data ? ($user_data['first_name'] . ' ' . $user_data['last_name']) : ($_SESSION['user_name'] ?? 'Valued Customer');
$user_email = $user_data ? $user_data['email_address'] : ($_SESSION['user_email'] ?? '');

// Get user's orders (PDO)
$orders_sql = "SELECT o.*, COUNT(oi.order_item_id) as item_count
               FROM orders o
               LEFT JOIN order_items oi ON o.order_id = oi.order_id
               WHERE o.user_id = :uid
               GROUP BY o.order_id
               ORDER BY o.created_at DESC";


$stmt = $pdo->prepare($orders_sql);
if (!$stmt->execute([':uid' => $user_id])) {
    $errorInfo = $stmt->errorInfo();
    die('Database error: ' . htmlspecialchars($errorInfo[2]));
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepared statement for quick product preview per order
$item_preview_stmt = $pdo->prepare(
    "SELECT oi.quantity, oi.unit_price, p.product_name, p.product_image
     FROM order_items oi
     JOIN products p ON oi.product_id = p.product_id
     WHERE oi.order_id = :oid
     ORDER BY oi.order_item_id ASC
     LIMIT 1"
);
?>

<?php
$pageTitle = 'Order History - Jeweluxe';
include 'includes/header.php';
?>
<link rel="stylesheet" href="styles.css">
<body class="order-history-page">

    <section class="orders-hero">
        <div class="container-xl">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light btn-sm" onclick="window.history.back();" type="button" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h1 class="mb-0 text-white">Order History</h1>
            </div>
        </div>
    </section>

    <div class="orders-wrapper py-5">
        <div class="container-xl">
            <div class="row g-4">
                <?php 
                $active_page = 'order_history';
                include 'includes/account_sidebar.php'; 
                ?>

                <main class="col-lg-9">
                    <div class="card shadow-sm border-0 rounded-4 orders-panel">
                        <div class="card-body pb-0">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                                <div>
                                    <div class="small text-muted mb-1">Order Center</div>
                                    <h4 class="mb-0">My Orders</h4>
                                </div>
                                <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                            </div>

                            <ul class="nav nav-pills orders-filter mb-3" id="ordersFilter" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" data-filter="all" type="button">All</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-filter="shipped" type="button">Shipped</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-filter="delivered" type="button">Delivered</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-filter="cancelled" type="button">Cancelled</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-filter="returned" type="button">Returned</button>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body pt-0">
                            <?php if (count($orders) > 0): ?>
                                <div class="orders-list">
                                    <?php foreach ($orders as $order): ?>
                                        <?php
                                        $item_preview_stmt->execute([':oid' => $order['order_id']]);
                                        $preview = $item_preview_stmt->fetch(PDO::FETCH_ASSOC) ?: null;

                                        $status_key = strtolower((string) $order['order_status']);
                                        $status_labels = [
                                            'pending' => ['Pending', 'pill pending'],
                                            'processing' => ['Processing', 'pill processing'],
                                            'shipped' => ['Shipped', 'pill shipped'],
                                            'delivered' => ['Delivered', 'pill delivered'],
                                            'cancelled' => ['Cancelled', 'pill cancelled'],
                                            'returned' => ['Returned', 'pill returned'],
                                        ];
                                        $status_label = $status_labels[$status_key][0] ?? ucfirst($status_key ?: 'Unknown');
                                        $status_class = $status_labels[$status_key][1] ?? 'pill default';

                                        $payment_key = strtolower((string) $order['payment_status']);
                                        $payment_map = [
                                            'paid' => ['Paid', 'pill paid'],
                                            'pending' => ['Pending', 'pill pending'],
                                            'failed' => ['Failed', 'pill cancelled'],
                                        ];
                                        $payment_label = $payment_map[$payment_key][0] ?? ucfirst($payment_key ?: 'Unknown');
                                        $payment_class = $payment_map[$payment_key][1] ?? 'pill default';
                                        ?>

                                        <div class="order-card-luxury" data-status="<?php echo $status_key; ?>" data-order-id="<?php echo $order['order_id']; ?>">
                                            <!-- Card Header -->
                                            <div class="order-card-header">
                                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                                    <span class="order-status-badge status-<?php echo $status_key; ?>">
                                                        <i class="fas fa-<?php echo $status_key === 'delivered' ? 'check-circle' : ($status_key === 'shipped' ? 'truck' : ($status_key === 'cancelled' ? 'times-circle' : 'clock')); ?>"></i>
                                                        <?php echo htmlspecialchars($status_label); ?>
                                                    </span>
                                                    <div class="small text-muted">
                                                        <i class="fas fa-hashtag" style="font-size: 10px;"></i> 
                                                        <?php echo htmlspecialchars($order['order_number']); ?>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="small text-muted">Order Date</div>
                                                    <div class="fw-semibold"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                                </div>
                                            </div>

                                            <!-- Card Body -->
                                            <div class="order-card-body">
                                                <div class="d-flex gap-3 align-items-center flex-wrap">
                                                    <div class="order-product-thumb">
                                                        <?php if ($preview && !empty($preview['product_image'])): ?>
                                                            <img src="<?php echo htmlspecialchars($preview['product_image']); ?>" alt="<?php echo htmlspecialchars($preview['product_name']); ?>">
                                                        <?php else: ?>
                                                            <div class="thumb-placeholder">
                                                                <i class="fas fa-gem fs-3 text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="flex-grow-1">
                                                        <div class="fw-bold mb-1 text-dark" style="font-size: 16px;">
                                                            <?php echo $preview ? htmlspecialchars($preview['product_name']) : 'Order Items'; ?>
                                                        </div>
                                                        <div class="d-flex gap-3 flex-wrap align-items-center mb-2">
                                                            <span class="text-muted small">
                                                                <i class="fas fa-box me-1"></i>
                                                                <?php echo (int) $order['item_count']; ?> item<?php echo $order['item_count'] > 1 ? 's' : ''; ?>
                                                            </span>
                                                            <span class="status-pill <?php echo $payment_class; ?>">
                                                                <?php echo htmlspecialchars($payment_label); ?>
                                                            </span>
                                                            <span class="text-muted small">
                                                                <i class="fas fa-credit-card me-1"></i>
                                                                <?php echo ucfirst(htmlspecialchars($order['payment_method'])); ?>
                                                            </span>
                                                        </div>
                                                        <div class="fw-bold text-dark" style="font-size: 20px;">
                                                            ₱<?php echo number_format($order['total_amount'], 2); ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex flex-column gap-2">
                                                        <?php if ($status_key === 'delivered'): ?>
                                                            <button class="btn-review-order" onclick="openReviewModal(<?php echo $order['order_id']; ?>)">
                                                                <i class="fas fa-star"></i>
                                                                Review Order
                                                            </button>
                                                            <button class="btn-reorder" onclick="reorderItems(<?php echo $order['order_id']; ?>)">
                                                                <i class="fas fa-redo"></i>
                                                                Reorder
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($status_key === 'pending' || $status_key === 'processing'): ?>
                                                            <button class="btn-order-danger" onclick="cancelOrder(<?php echo $order['order_id']; ?>)">
                                                                <i class="fas fa-times"></i>
                                                                Cancel Order
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn-order-primary" onclick="toggleOrderDetails(<?php echo $order['order_id']; ?>)">
                                                            <span>View Details</span>
                                                            <i class="fas fa-chevron-down expand-toggle" data-order="<?php echo $order['order_id']; ?>"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Expandable Order Details -->
                                        <div id="order-details-<?php echo $order['order_id']; ?>" class="order-details-expandable">
                                            <div class="order-details-content">
                                                <?php
                                                // Order Timeline - show for shipped/delivered orders
                                                if ($status_key === 'shipped' || $status_key === 'delivered'):
                                                ?>
                                                <div class="order-timeline">
                                                    <div class="timeline-step completed">
                                                        <div class="timeline-icon">
                                                            <i class="fas fa-check"></i>
                                                        </div>
                                                        <div class="timeline-content">
                                                            <div class="timeline-title">Order Placed</div>
                                                            <div class="timeline-date"><?php echo date('M j, g:i A', strtotime($order['created_at'])); ?></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="timeline-step completed">
                                                        <div class="timeline-icon">
                                                            <i class="fas fa-box"></i>
                                                        </div>
                                                        <div class="timeline-content">
                                                            <div class="timeline-title">Packed</div>
                                                            <div class="timeline-date"><?php echo date('M j', strtotime($order['created_at'] . ' +1 day')); ?></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="timeline-step <?php echo $status_key === 'shipped' ? 'active' : 'completed'; ?>">
                                                        <div class="timeline-icon">
                                                            <i class="fas fa-truck"></i>
                                                        </div>
                                                        <div class="timeline-content">
                                                            <div class="timeline-title">Out for Delivery</div>
                                                            <div class="timeline-date"><?php echo $status_key === 'shipped' ? 'In transit' : date('M j', strtotime($order['updated_at'])); ?></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="timeline-step <?php echo $status_key === 'delivered' ? 'completed' : ''; ?>">
                                                        <div class="timeline-icon">
                                                            <i class="fas fa-home"></i>
                                                        </div>
                                                        <div class="timeline-content">
                                                            <div class="timeline-title">Delivered</div>
                                                            <div class="timeline-date"><?php echo $status_key === 'delivered' ? date('M j', strtotime($order['updated_at'])) : 'Est. ' . date('M j', strtotime($order['created_at'] . ' +5 days')); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>

                                                <?php
                                                // Estimated delivery for processing/pending orders
                                                if ($status_key === 'processing' || $status_key === 'pending'):
                                                    $est_delivery = date('l, F j, Y', strtotime($order['created_at'] . ' +5 days'));
                                                ?>
                                                <div class="delivery-estimate">
                                                    <div class="delivery-icon">
                                                        <i class="fas fa-calendar-check"></i>
                                                    </div>
                                                    <div class="delivery-info">
                                                        <div class="delivery-label">Estimated Delivery</div>
                                                        <div class="delivery-date"><?php echo $est_delivery; ?></div>
                                                        <div class="delivery-time">Between 10:00 AM - 5:00 PM</div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>

                                                <?php
                                                $items_sql = "SELECT oi.*, p.product_name, p.product_image
                                                              FROM order_items oi
                                                              JOIN products p ON oi.product_id = p.product_id
                                                              WHERE oi.order_id = :oid";
                                                $stmt_items = $pdo->prepare($items_sql);
                                                $stmt_items->execute([':oid' => $order['order_id']]);
                                                $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
                                                ?>

                                                <?php if (count($order_items) > 0): ?>
                                                    <div class="mb-4">
                                                        <h6 class="fw-bold mb-3">
                                                            <i class="fas fa-shopping-bag me-2"></i>
                                                            All Items (<?php echo count($order_items); ?>)
                                                        </h6>
                                                        <?php foreach ($order_items as $item): ?>
                                                            <div class="row align-items-center mb-3 pb-3 border-bottom">
                                                                <div class="col-md-2 col-3">
                                                                    <div class="order-thumb rounded-3 overflow-hidden bg-light">
                                                                        <?php if (!empty($item['product_image'])): ?>
                                                                            <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                                        <?php else: ?>
                                                                            <div class="thumb-placeholder">
                                                                                <i class="fas fa-image"></i>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6 col-9">
                                                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                                    <p class="text-muted mb-0 small">
                                                                        <i class="fas fa-cubes me-1"></i>
                                                                        Quantity: <?php echo (int) $item['quantity']; ?>
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-4 text-md-end mt-2 mt-md-0">
                                                                    <p class="mb-0 text-muted small">₱<?php echo number_format($item['unit_price'], 2); ?> each</p>
                                                                    <strong class="text-dark">₱<?php echo number_format($item['total_price'], 2); ?></strong>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="row g-3">
                                                    <div class="col-md-7">
                                                        <?php if (!empty($order['shipping_address'])): ?>
                                                            <div class="p-3 rounded-3 bg-white border h-100">
                                                                <div class="fw-bold mb-2">
                                                                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                                                    Shipping Address
                                                                </div>
                                                                <p class="mb-0 small text-muted"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="order-summary-luxury">
                                                            <div class="order-summary-row">
                                                                <div class="order-summary-label">
                                                                    <div class="order-summary-label-icon">
                                                                        <i class="fas fa-shopping-cart"></i>
                                                                    </div>
                                                                    Subtotal
                                                                </div>
                                                                <div class="order-summary-value">₱<?php echo number_format($order['subtotal'], 2); ?></div>
                                                            </div>
                                                            
                                                            <div class="order-summary-row">
                                                                <div class="order-summary-label">
                                                                    <div class="order-summary-label-icon">
                                                                        <i class="fas fa-truck"></i>
                                                                    </div>
                                                                    Shipping
                                                                </div>
                                                                <div class="order-summary-value">₱<?php echo number_format($order['shipping_cost'], 2); ?></div>
                                                            </div>
                                                            
                                                            <div class="order-summary-row">
                                                                <div class="order-summary-label">
                                                                    <div class="order-summary-label-icon">
                                                                        <i class="fas fa-calculator"></i>
                                                                    </div>
                                                                    Tax
                                                                </div>
                                                                <div class="order-summary-value">₱<?php echo number_format($order['tax'], 2); ?></div>
                                                            </div>
                                                            
                                                            <div class="order-summary-divider"></div>
                                                            
                                                            <div class="order-summary-row total-row">
                                                                <div class="order-summary-label">
                                                                    <i class="fas fa-money-bill-wave me-2"></i>
                                                                    Total
                                                                </div>
                                                                <div class="order-summary-value">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <div class="empty-icon mb-3">📦</div>
                                    <h4 class="mb-1">No Orders Yet</h4>
                                    <p class="text-muted mb-3">You have not placed any orders yet. Start shopping to see them here.</p>
                                    <a href="products.php" class="btn btn-primary">Start Shopping</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <style>
        /* Review Modal Styles */
        .review-item-card {
            background: #fafbfc;
            transition: all 0.3s ease;
        }
        .review-item-card:hover {
            background: #f3f4f6;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .rating-stars {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        .rating-stars .star-rating {
            font-size: 28px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        .rating-stars .star-rating:hover {
            transform: scale(1.15);
        }
        .rating-stars .star-rating.active {
            color: #ffd700;
            text-shadow: 0 2px 4px rgba(255, 215, 0, 0.4);
        }
        .rating-stars .star-rating.fas {
            color: #ffd700;
        }
        .rating-stars .star-rating.far {
            color: #ddd;
        }
    </style>

    <script>
        // Toggle order details with smooth expand/collapse
        function toggleOrderDetails(orderId) {
            const detailsEl = document.getElementById('order-details-' + orderId);
            const toggleIcon = document.querySelector(`[data-order="${orderId}"] .expand-toggle`);
            
            if (!detailsEl) return;
            
            if (detailsEl.classList.contains('expanded')) {
                detailsEl.classList.remove('expanded');
                if (toggleIcon) toggleIcon.classList.remove('expanded');
            } else {
                // Close all other expanded orders for clean UX
                document.querySelectorAll('.order-details-expandable.expanded').forEach(el => {
                    el.classList.remove('expanded');
                });
                document.querySelectorAll('.expand-toggle.expanded').forEach(icon => {
                    icon.classList.remove('expanded');
                });
                
                detailsEl.classList.add('expanded');
                if (toggleIcon) toggleIcon.classList.add('expanded');
                
                // Smooth scroll to details
                setTimeout(() => {
                    detailsEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        }

        // Cancel order function
        function cancelOrder(orderId) {
            ConfirmModal.show(
                '⚠️ Cancel Order',
                'Are you sure you want to cancel this order? This action cannot be undone.',
                function() {
                    fetch('cancel_order.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ order_id: orderId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            ToastNotification.success('Order cancelled successfully.');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            ToastNotification.error(data.message || 'Failed to cancel order.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        ToastNotification.error('An error occurred while cancelling the order.');
                    });
                }
            );
        }

        // Reorder items function
        function reorderItems(orderId) {
            ConfirmModal.show(
                '🛍️ Reorder Items',
                'Add all items from this order to your cart?',
                function() {
                    fetch('reorder.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order_id: orderId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            ToastNotification.success('Items added to cart!');
                            setTimeout(() => window.location.href = 'cart.php', 1500);
                        } else {
                            ToastNotification.error(data.message || 'Failed to reorder items.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        ToastNotification.error('An error occurred while adding items to cart.');
                    });
                }
            );
        }

        // Register filter buttons
        (function registerFilters() {
            const buttons = document.querySelectorAll('#ordersFilter .nav-link');
            const cards = document.querySelectorAll('.order-card-luxury, .order-card-ui');
            
            buttons.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Update active state
                    buttons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    
                    const filter = btn.dataset.filter;
                    
                    // Filter cards
                    cards.forEach(card => {
                        if (!filter || filter === 'all') {
                            card.style.display = '';
                            return;
                        }
                        const match = card.dataset.status === filter;
                        card.style.display = match ? '' : 'none';
                    });
                });
            });
        })();

        // Review Order Modal Function
        function openReviewModal(orderId) {
            // Fetch order items for review
            fetch('get_order_items_for_review.php?order_id=' + orderId, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    // Check if response is OK
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    // Check if response is actually JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        // If redirected to login page, we'll get HTML
                        if (response.redirected || response.url.includes('login.php')) {
                            throw new Error('Session expired. Please log in again.');
                        }
                        return response.text().then(text => {
                            console.error('Non-JSON response:', text.substring(0, 200));
                            throw new Error('Invalid response format. Please try again.');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.items) {
                        showReviewModal(orderId, data.items);
                    } else {
                        ToastNotification.error(data.message || 'Unable to load order items for review.');
                    }
                })
                .catch(error => {
                    console.error('Review modal error:', error);
                    ToastNotification.error(error.message || 'An error occurred while loading order items. Please try again.');
                });
        }

        function showReviewModal(orderId, items) {
            // Create modal HTML
            let modalHTML = `
                <div class="modal fade" id="reviewOrderModal" tabindex="-1" aria-labelledby="reviewOrderModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header" style="background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); border-bottom: none;">
                                <h5 class="modal-title fw-bold" id="reviewOrderModalLabel">
                                    <i class="fas fa-star me-2"></i>Review Your Order
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                                <p class="text-muted mb-4">Share your experience! Your feedback helps other customers and improves our service.</p>
                                <div id="reviewItemsContainer">
            `;

            items.forEach((item, index) => {
                const hasReview = item.has_review || false;
                modalHTML += `
                    <div class="review-item-card mb-4 p-4 border rounded-3" data-product-id="${item.product_id}">
                        <div class="d-flex gap-3 mb-3">
                            <img src="${item.product_image}" alt="${item.product_name}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold">${item.product_name}</h6>
                                <p class="text-muted small mb-0">Quantity: ${item.quantity}</p>
                                ${hasReview ? '<span class="badge bg-success mt-2"><i class="fas fa-check me-1"></i>Already Reviewed</span>' : ''}
                            </div>
                        </div>
                        ${!hasReview ? `
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label>
                            <div class="rating-stars" data-product-id="${item.product_id}">
                                <i class="far fa-star star-rating" data-rating="1" data-product="${item.product_id}"></i>
                                <i class="far fa-star star-rating" data-rating="2" data-product="${item.product_id}"></i>
                                <i class="far fa-star star-rating" data-rating="3" data-product="${item.product_id}"></i>
                                <i class="far fa-star star-rating" data-rating="4" data-product="${item.product_id}"></i>
                                <i class="far fa-star star-rating" data-rating="5" data-product="${item.product_id}"></i>
                            </div>
                            <input type="hidden" class="product-rating" data-product-id="${item.product_id}" value="0">
                        </div>
                        <div class="mb-3">
                            <label for="review-${item.product_id}" class="form-label fw-semibold">Your Review <span class="text-danger">*</span></label>
                            <textarea class="form-control product-review" id="review-${item.product_id}" data-product-id="${item.product_id}" rows="3" placeholder="Share your thoughts about this product..." required></textarea>
                        </div>
                        ` : '<div class="alert alert-success mb-0"><i class="fas fa-check-circle me-2"></i>You have already reviewed this product.</div>'}
                    </div>
                `;
            });

            modalHTML += `
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="submitOrderReviews(${orderId})">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Reviews
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal if any
            const existingModal = document.getElementById('reviewOrderModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Initialize star ratings
            initializeStarRatings();

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('reviewOrderModal'));
            modal.show();
        }

        function initializeStarRatings() {
            document.querySelectorAll('.star-rating').forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    const productId = this.dataset.product;
                    const stars = document.querySelectorAll(`[data-product="${productId}"].star-rating`);
                    const ratingInput = document.querySelector(`.product-rating[data-product-id="${productId}"]`);

                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.add('active');
                            s.classList.remove('far');
                            s.classList.add('fas');
                            s.style.color = '#ffd700';
                        } else {
                            s.classList.remove('active');
                            s.classList.remove('fas');
                            s.classList.add('far');
                            s.style.color = '#ddd';
                        }
                    });

                    if (ratingInput) {
                        ratingInput.value = rating;
                    }
                });

                star.addEventListener('mouseenter', function() {
                    const rating = parseInt(this.dataset.rating);
                    const productId = this.dataset.product;
                    const stars = document.querySelectorAll(`[data-product="${productId}"].star-rating`);

                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.style.color = '#ffd700';
                        } else {
                            s.style.color = '#ddd';
                        }
                    });
                });

                // Add mouseleave handler to rating container
                const ratingContainer = star.closest('.rating-stars');
                if (ratingContainer && !ratingContainer.dataset.mouseleaveHandler) {
                    ratingContainer.dataset.mouseleaveHandler = 'true';
                    ratingContainer.addEventListener('mouseleave', function() {
                        const productId = this.dataset.productId;
                        const stars = document.querySelectorAll(`[data-product="${productId}"].star-rating`);
                        const ratingInput = document.querySelector(`.product-rating[data-product-id="${productId}"]`);
                        const currentRating = ratingInput ? parseInt(ratingInput.value) : 0;

                        stars.forEach((s, index) => {
                            if (index < currentRating) {
                                s.style.color = '#ffd700';
                            } else {
                                s.style.color = '#ddd';
                            }
                        });
                    });
                }
            });
        }

        function submitOrderReviews(orderId) {
            const reviewItems = document.querySelectorAll('.review-item-card[data-product-id]');
            const reviews = [];
            const errors = [];

            reviewItems.forEach(item => {
                const productId = item.dataset.productId;
                const ratingInput = item.querySelector(`.product-rating[data-product-id="${productId}"]`);
                const reviewTextarea = item.querySelector(`.product-review[data-product-id="${productId}"]`);

                // Skip if already reviewed
                if (!ratingInput || !reviewTextarea) {
                    return;
                }

                const rating = parseInt(ratingInput.value);
                const review = reviewTextarea.value.trim();

                if (rating === 0) {
                    errors.push(`Please provide a rating for ${item.querySelector('h6').textContent}`);
                } else if (review.length === 0) {
                    errors.push(`Please write a review for ${item.querySelector('h6').textContent}`);
                } else if (review.length < 10) {
                    errors.push(`Review for ${item.querySelector('h6').textContent} must be at least 10 characters`);
                } else {
                    reviews.push({
                        product_id: parseInt(productId),
                        rating: rating,
                        review_content: review
                    });
                }
            });

            if (errors.length > 0) {
                ToastNotification.error(errors[0]);
                return;
            }

            if (reviews.length === 0) {
                ToastNotification.error('Please provide at least one rating and review, or all items have already been reviewed.');
                return;
            }

            // Disable submit button
            const submitBtn = document.querySelector('#reviewOrderModal .btn-primary');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
            }

            // Submit each review
            let submitted = 0;
            let failed = 0;
            const totalReviews = reviews.length;

            reviews.forEach((review, index) => {
                const formData = new FormData();
                formData.append('product_id', review.product_id);
                formData.append('order_id', orderId);
                formData.append('rating', review.rating);
                formData.append('review_content', review.review_content);

                fetch('review_submit.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        submitted++;
                    } else {
                        failed++;
                        console.error('Review submission failed:', data.message);
                    }

                    // Check if all reviews are processed
                    if (submitted + failed === totalReviews) {
                        if (submitted > 0) {
                            ToastNotification.success(`${submitted} review${submitted > 1 ? 's' : ''} submitted successfully!`);
                            // Close modal and reload page after a delay
                            setTimeout(() => {
                                const modalElement = document.getElementById('reviewOrderModal');
                                if (modalElement) {
                                    const modal = bootstrap.Modal.getInstance(modalElement);
                                    if (modal) modal.hide();
                                }
                                setTimeout(() => location.reload(), 500);
                            }, 1500);
                        }
                        if (failed > 0) {
                            ToastNotification.error(`${failed} review${failed > 1 ? 's' : ''} failed to submit. Please try again.`);
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Reviews';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Review submission error:', error);
                    failed++;
                    if (submitted + failed === totalReviews) {
                        ToastNotification.error('Some reviews failed to submit. Please try again.');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Reviews';
                        }
                    }
                });
            });
        }
    </script>