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
$stmt->execute([':uid' => $user_id]);
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
    </script>