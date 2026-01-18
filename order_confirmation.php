<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';

// Ensure session is active and user is logged in
if (empty($_SESSION['user_id'])) {
    $redir = 'login.php?redirect=order_confirmation&order_id=' . urlencode($_GET['order_id'] ?? '');
    header('Location: ' . $redir);
    exit();
}

$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
$user_id = (int) $_SESSION['user_id'];

if ($order_id === 0) {
    header('Location: products.php');
    exit();
}

// Fetch order details with PDO (matches schema in WEBDEV-MAIN.sql)
$order_sql = "SELECT o.*, u.first_name, u.last_name, u.email_address
              FROM orders o
              JOIN users u ON o.user_id = u.user_id
              WHERE o.order_id = :oid AND o.user_id = :uid";
$order_stmt = $pdo->prepare($order_sql);
$order_stmt->execute([':oid' => $order_id, ':uid' => $user_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Order not found.</div></div>";
    exit();
}

// Fetch order items
$items_sql = "SELECT oi.*, p.product_name, p.product_image
              FROM order_items oi
              JOIN products p ON oi.product_id = p.product_id
              WHERE oi.order_id = :oid";
$items_stmt = $pdo->prepare($items_sql);
$items_stmt->execute([':oid' => $order_id]);
$order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Jeweluxe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --gold: var(--accent-gold);
            --gold-100: #F8F3E7;
            --text-900: #1F1F1F;
            --text-600: #5B5B5B;
            --border: #E9ECEF;
            --success: #28A745;
            --danger: #DC3545;
        }

        body { color: var(--text-900); }

        /* Page Header */
        .page-header { margin-bottom: 1.5rem; }
            .page-title { font-size: 1.85rem; font-weight: 800; position: relative; }
            .page-title::after { content: ""; display: block; width: 80px; height: 2px; background: linear-gradient(90deg, var(--gold), transparent); margin-top: .4rem; }
            .page-sub { color: var(--text-600); margin-top: .25rem; }
            .check-icon { color: var(--success); font-size: 1.4rem; }

        /* Meta Row */
        .meta-row { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; margin-bottom: 1.5rem; }
            .meta-row { display: flex; gap: .6rem; flex-wrap: wrap; align-items: center; margin-bottom: 1.25rem; }
            .meta-item { color: var(--text-600); }
            .badge-soft { border: 1px solid var(--border); color: var(--text-900) !important; background: #fff; font-weight: 600; border-radius: 10px; padding: .35rem .6rem; display: inline-flex; align-items: center; gap: .35rem; }
        .badge-gold { border-color: var(--gold); color: var(--primary-dark) !important; background: var(--gold-100); }
        .badge-paid { border-color: #B8E0C2; color: #1E6F2E !important; background: #EAF7EF; }
        .badge-pending { border-color: #FFE7B8; color: #8A6D1A !important; background: #FFF8DB; }
        .badge-failed { border-color: #F5C6CB; color: #842029 !important; background: #FDE2E4; }
        .badge-refunded { border-color: #BEE5EB; color: #0C5460 !important; background: #E6F4F7; }

        /* Timeline */
        .timeline { margin: .75rem 0 1.5rem; }
        .timeline-line { height: 2px; background: var(--border); position: relative; border-radius: 2px; }
        .timeline-fill { height: 100%; background: var(--gold); width: 25%; transition: width .3s; border-radius: 2px; }
        .timeline-steps { display: flex; justify-content: space-between; margin-top: .5rem; }
        .timeline-step { text-align: center; color: var(--text-900); font-size: .9rem; }
        .timeline-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--border); margin: 0 auto .45rem; box-shadow: inset 0 0 0 2px #fff; }
        .timeline-step.active .timeline-dot { background: var(--gold); box-shadow: 0 0 0 3px rgba(212,175,55,.25); }

        /* Sections */
        .section { padding: 1.25rem 0; border-top: 1px solid var(--border); }
        .section:first-of-type { border-top: 0; }
            .section-title { font-weight: 800; font-size: 1rem; margin-bottom: .6rem; position: relative; }
            .section-title::after { content: ""; position: absolute; left: 0; bottom: -6px; width: 28px; height: 2px; background: var(--gold); border-radius: 2px; }

        /* Items Table */
            .items-header, .item-row { display: grid; grid-template-columns: 64px 1fr 80px 110px 110px; gap: 1rem; align-items: center; }
        .items-header { color: var(--text-600); font-size: .9rem; border-bottom: 1px solid var(--border); padding-bottom: .5rem; }
            .item-row { padding: .85rem 0; border-bottom: 1px solid var(--border); }
            .item-img { width: 64px; height: 64px; object-fit: cover; border-radius: 10px; box-shadow: var(--shadow-sm); }
            .item-name { font-weight: 700; letter-spacing: .2px; }
        .item-price, .item-total, .item-qty { text-align: right; }
            .item-sub { color: var(--text-600); font-size: .85rem; }

        /* Summary */
            .summary-card { border: 1px solid var(--border); border-radius: 12px; padding: 1rem; background: linear-gradient(180deg, #fff, #fff), #fff; box-shadow: var(--shadow-sm); border-top: 2px solid var(--gold); }
            .summary-row { display: flex; justify-content: space-between; padding: .6rem 0; }
        .summary-row.total { border-top: 1px solid var(--border); margin-top: .5rem; padding-top: .75rem; font-weight: 700; }
            .summary-row.total .value { color: var(--gold); font-size: 1.35rem; font-weight: 800; }

        /* Buttons */
            .btn-primary-gold { background: var(--gold); color: #111; border: 1px solid var(--gold); box-shadow: 0 2px 8px rgba(212,175,55,.25); }
            .btn-primary-gold:hover { filter: brightness(1.06); transform: translateY(-1px); }
        .btn-outline-gold { border: 1px solid var(--gold); color: var(--gold); }
        .btn-outline-gold:hover { background: var(--gold); color: #111; }
        .btn-link-danger { color: var(--danger); }

        /* Responsive */
        @media (max-width: 768px) {
            .items-header, .item-row { grid-template-columns: 50px 1fr 60px 90px 90px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <!-- Header -->
                <div class="page-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-check-circle check-icon"></i>
                        <h1 class="page-title mb-0">Thank you! Your order is confirmed</h1>
                    </div>
                    <div class="page-sub">A confirmation email has been sent to <?php echo htmlspecialchars($order['email_address']); ?>.</div>
                </div>

                <!-- Meta Row -->
                <div class="meta-row">
                    <span class="badge badge-soft badge-gold"><i class="fas fa-hashtag"></i> Order #<?php echo htmlspecialchars($order['order_number']); ?></span>
                    <span class="badge badge-soft"><i class="fas fa-credit-card"></i>
                        <?php 
                            $method = htmlspecialchars($order['payment_method']);
                            if ($method === 'cod') echo 'Cash on Delivery';
                            elseif ($method === 'gcash') echo 'GCash';
                            elseif ($method === 'paypal') echo 'PayPal';
                            elseif ($method === 'bank_transfer') echo 'Bank Transfer';
                            else echo ucfirst($method);
                        ?>
                    </span>
                    <?php $pstat = strtolower($order['payment_status']); ?>
                    <span class="badge badge-soft <?php echo $pstat === 'paid' ? 'badge-paid' : ($pstat === 'failed' ? 'badge-failed' : ($pstat === 'refunded' ? 'badge-refunded' : 'badge-pending')); ?>">
                        <i class="<?php echo $pstat === 'paid' ? 'fas fa-circle-check' : ($pstat === 'failed' ? 'fas fa-circle-xmark' : ($pstat === 'refunded' ? 'fas fa-rotate-left' : 'fas fa-hourglass-half')); ?>"></i>
                        <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                    </span>
                    <span class="badge badge-soft"><i class="far fa-calendar"></i> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></span>
                </div>

                <!-- Timeline -->
                <div class="timeline">
                    <div class="timeline-line">
                        <div class="timeline-fill" style="width: <?php 
                            $status = strtolower($order['order_status']);
                            if ($status === 'pending') echo '25%';
                            elseif ($status === 'processing') echo '50%';
                            elseif ($status === 'shipped') echo '75%';
                            elseif ($status === 'delivered') echo '100%';
                            elseif ($status === 'cancelled') echo '0%';
                            else echo '25%';
                        ?>;"></div>
                    </div>
                    <div class="timeline-steps">
                        <div class="timeline-step active">
                            <div class="timeline-dot"></div>
                            Placed
                        </div>
                        <div class="timeline-step <?php echo in_array($status, ['processing','shipped','delivered']) ? 'active' : ''; ?>">
                            <div class="timeline-dot"></div>
                            Processing
                        </div>
                        <div class="timeline-step <?php echo in_array($status, ['shipped','delivered']) ? 'active' : ''; ?>">
                            <div class="timeline-dot"></div>
                            Shipped
                        </div>
                        <div class="timeline-step <?php echo $status === 'delivered' ? 'active' : ''; ?>">
                            <div class="timeline-dot"></div>
                            Delivered
                        </div>
                    </div>
                </div>

                <!-- Two-column layout -->
                <div class="row g-4">
                    <!-- Left: Details + Items -->
                    <div class="col-lg-8">
                        <div class="section">
                            <div class="section-title">Order Overview</div>
                            <div class="row gy-2">
                                <div class="col-sm-6"><span class="text-muted">Status:</span> <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?></div>
                                <div class="col-sm-6"><span class="text-muted">Customer:</span> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                <div class="col-sm-6"><span class="text-muted">Email:</span> <?php echo htmlspecialchars($order['email_address']); ?></div>
                                <div class="col-sm-6"><span class="text-muted">Payment:</span> <?php 
                                    $method = htmlspecialchars($order['payment_method']);
                                    if ($method === 'cod') echo 'Cash on Delivery';
                                    elseif ($method === 'gcash') echo 'GCash';
                                    elseif ($method === 'paypal') echo 'PayPal';
                                    elseif ($method === 'bank_transfer') echo 'Bank Transfer';
                                    else echo ucfirst($method);
                                ?></div>
                            </div>
                        </div>

                        <?php if (!empty($order['shipping_address'])): ?>
                        <div class="section">
                            <div class="section-title">Shipping Address</div>
                            <div><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($order['order_notes'])): ?>
                        <div class="section">
                            <div class="section-title">Order Notes</div>
                            <div><?php echo nl2br(htmlspecialchars($order['order_notes'])); ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="section">
                            <div class="section-title">Items</div>
                            <div class="items-header">
                                <div></div>
                                <div>Item</div>
                                <div class="item-qty">Qty</div>
                                <div class="item-price">Price</div>
                                <div class="item-total">Total</div>
                            </div>
                            <?php foreach ($order_items as $item): ?>
                                <div class="item-row">
                                    <img class="item-img" src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    <div>
                                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="item-sub">Qty <?php echo (int)$item['quantity']; ?> × ₱<?php echo number_format($item['unit_price'], 2); ?></div>
                                    </div>
                                    <div class="item-qty"><?php echo (int)$item['quantity']; ?></div>
                                    <div class="item-price">₱<?php echo number_format($item['unit_price'], 2); ?></div>
                                    <div class="item-total">₱<?php echo number_format($item['total_price'], 2); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Right: Summary -->
                    <div class="col-lg-4">
                        <div class="summary-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold">Order Summary</span>
                            </div>
                            <div class="summary-row">
                                <div>Subtotal</div>
                                <div>₱<?php echo number_format($order['subtotal'], 2); ?></div>
                            </div>
                            <div class="summary-row">
                                <div>Shipping</div>
                                <div>₱<?php echo number_format($order['shipping_cost'], 2); ?></div>
                            </div>
                            <div class="summary-row">
                                <div>Tax</div>
                                <div>₱<?php echo number_format($order['tax'], 2); ?></div>
                            </div>
                            <div class="summary-row total">
                                <div>Total</div>
                                <div class="value">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                            </div>

                            <div class="mt-3 d-grid gap-2">
                                <a href="products.php" class="btn btn-primary-gold">Continue Shopping</a>
                                <a href="order_history.php" class="btn btn-outline-gold">View Orders</a>
                                <button type="button" class="btn btn-link btn-link-danger text-start px-0" id="cancelOrderBtn">Cancel Order</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Handle cancel order functionality
        document.addEventListener('DOMContentLoaded', function() {
            const cancelBtn = document.getElementById('cancelOrderBtn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    ConfirmModal.show(
                        '⚠️ Cancel Order',
                        'Are you sure you want to cancel this order? This action cannot be undone.',
                        function() {
                            // Get order_id from URL
                            const urlParams = new URLSearchParams(window.location.search);
                            const orderId = urlParams.get('order_id');
                            
                            if (!orderId) {
                                ToastNotification.error('Order ID not found.');
                                return;
                            }
                            
                            // Disable button during request
                            cancelBtn.disabled = true;
                            cancelBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cancelling...';
                            
                            // Send cancel request
                            fetch('cancel_order.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ order_id: parseInt(orderId) })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    ToastNotification.success('Order cancelled successfully.');
                                    setTimeout(() => {
                                        window.location.href = 'order_history.php';
                                    }, 1500);
                                } else {
                                    ToastNotification.error(data.message || 'Failed to cancel order.');
                                    cancelBtn.disabled = false;
                                    cancelBtn.innerHTML = '<i class="fas fa-times-circle"></i> Cancel Order';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                ToastNotification.error('An error occurred while cancelling the order.');
                                cancelBtn.disabled = false;
                                cancelBtn.innerHTML = '<i class="fas fa-times-circle"></i> Cancel Order';
                            });
                        }
                    );
                });
            }
        });
    </script>
</body>
</html>