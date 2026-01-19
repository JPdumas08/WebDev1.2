<?php
/**
 * Admin Order Detail Page
 * View full order details, items, and update status
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/image_utils.php';

$page_title = 'Order Detail';
$order_id = (int)($_GET['id'] ?? 0);
$success_message = '';
$error_message = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status' && isset($_POST['order_id'], $_POST['status'])) {
        try {
            $order_id_update = (int)$_POST['order_id'];
            $new_status = $_POST['status'];
            
            // Get order details including user_id and old status
            $order_check_sql = "SELECT user_id, order_status FROM orders WHERE order_id = :oid";
            $order_check_stmt = $pdo->prepare($order_check_sql);
            $order_check_stmt->execute([':oid' => $order_id_update]);
            $order_info = $order_check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order_info) {
                $old_status = $order_info['order_status'];
                
                // Update order status
                $update_sql = "UPDATE orders SET order_status = :status, updated_at = NOW() WHERE order_id = :oid";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([
                    ':status' => $new_status,
                    ':oid' => $order_id_update
                ]);
                
                // Create notification for customer if status changed
                if ($old_status !== $new_status && $order_info['user_id']) {
                    $status_messages = [
                        'pending' => 'Your order #' . $order_id_update . ' is now pending.',
                        'processing' => 'Your order #' . $order_id_update . ' is being processed.',
                        'shipped' => 'Great news! Your order #' . $order_id_update . ' has been shipped!',
                        'delivered' => 'Your order #' . $order_id_update . ' has been delivered.',
                        'cancelled' => 'Your order #' . $order_id_update . ' has been cancelled.'
                    ];
                    
                    // Check if related_id column exists
                    $columns = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'related_id'")->fetch();
                    
                    if ($columns) {
                        // New schema with related_id
                        $notif_sql = "INSERT INTO notifications (user_id, type, title, message, related_id, is_read, created_at) 
                                      VALUES (:user_id, 'order_status', :title, :message, :related_id, 0, NOW())";
                        $notif_stmt = $pdo->prepare($notif_sql);
                        $notif_stmt->execute([
                            ':user_id' => $order_info['user_id'],
                            ':title' => 'Order Status Updated',
                            ':message' => $status_messages[$new_status] ?? 'Your order status has been updated.',
                            ':related_id' => $order_id_update
                        ]);
                    } else {
                        // Old schema without related_id
                        $notif_sql = "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                                      VALUES (:user_id, 'order_status', :title, :message, 0, NOW())";
                        $notif_stmt = $pdo->prepare($notif_sql);
                        $notif_stmt->execute([
                            ':user_id' => $order_info['user_id'],
                            ':title' => 'Order Status Updated',
                            ':message' => $status_messages[$new_status] ?? 'Your order status has been updated.'
                        ]);
                    }
                }
                
                $success_message = "Order status updated successfully!";
            }
        } catch (Exception $e) {
            error_log("Update status error: " . $e->getMessage());
            $error_message = "Failed to update order status. Please try again.";
        }
    } elseif ($_POST['action'] === 'update_payment' && isset($_POST['order_id'], $_POST['payment_status'])) {
        try {
            $update_sql = "UPDATE orders SET payment_status = :status, updated_at = NOW() WHERE order_id = :oid";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                ':status' => $_POST['payment_status'],
                ':oid' => (int)$_POST['order_id']
            ]);
            $success_message = "Payment status updated successfully!";
        } catch (Exception $e) {
            error_log("Update payment error: " . $e->getMessage());
            $error_message = "Failed to update payment status. Please try again.";
        }
    }
}

if ($order_id === 0) {
    header('Location: orders.php');
    exit();
}

try {
    // Fetch order with all details
    $order_sql = "SELECT o.*, u.user_id, u.first_name, u.last_name, u.email_address
                  FROM orders o
                  JOIN users u ON o.user_id = u.user_id
                  WHERE o.order_id = :oid";
    $order_stmt = $pdo->prepare($order_sql);
    $order_stmt->execute([':oid' => $order_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('Location: orders.php');
        exit();
    }

    // Fetch order items with product details
    $items_sql = "SELECT oi.*, p.product_id, p.product_name, p.product_image
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.product_id
                  WHERE oi.order_id = :oid
                  ORDER BY oi.order_item_id";
    $items_stmt = $pdo->prepare($items_sql);
    $items_stmt->execute([':oid' => $order_id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Order detail error: " . $e->getMessage());
    header('Location: orders.php');
    exit();
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
        <p class="page-subtitle">Detailed order information and management</p>
    </div>
    <div class="page-actions">
        <a href="orders.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;">
        <i class="fas fa-check-circle"></i>
        <div><?php echo htmlspecialchars($success_message); ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
        <i class="fas fa-exclamation-circle"></i>
        <div><?php echo htmlspecialchars($error_message); ?></div>
    </div>
<?php endif; ?>

<!-- Order Header with Status Badges -->
<div class="admin-card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Order Status</h2>
            <p class="card-subtitle">Ordered on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; padding: 1.5rem 0;">
        <div>
            <label class="form-label" style="margin-bottom: 1rem;">Order Status</label>
            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                <span class="badge-status badge-<?php echo strtolower($order['order_status']); ?>" style="font-size: 0.95rem; padding: 0.6rem 0.875rem;">
                    <i class="fas fa-box"></i> <?php echo ucfirst($order['order_status']); ?>
                </span>
                <form method="POST" style="flex: 1;">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                    <div style="display: flex; gap: 0.5rem;">
                        <select name="status" class="form-control" style="flex: 1; padding: 0.5rem;" required>
                            <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                    </div>
                </form>
            </div>
        </div>

        <div>
            <label class="form-label" style="margin-bottom: 1rem;">Payment Status</label>
            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                <span class="badge-status badge-<?php echo strtolower($order['payment_status']); ?>" style="font-size: 0.95rem; padding: 0.6rem 0.875rem;">
                    <i class="fas fa-credit-card"></i> <?php echo ucfirst($order['payment_status']); ?>
                </span>
                <form method="POST" style="flex: 1;">
                    <input type="hidden" name="action" value="update_payment">
                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                    <div style="display: flex; gap: 0.5rem;">
                        <select name="payment_status" class="form-control" style="flex: 1; padding: 0.5rem;" required>
                            <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                    </div>
                </form>
            </div>
        </div>

        <div>
            <label class="form-label" style="margin-bottom: 1rem;">Payment Method</label>
            <div style="padding: 0.6rem 0.875rem; background: var(--admin-bg); border-radius: 6px; font-weight: 600;">
                <?php 
                    $methods = [
                        'cod' => 'Cash on Delivery',
                        'gcash' => 'GCash',
                        'paypal' => 'PayPal',
                        'bank_transfer' => 'Bank Transfer'
                    ];
                    echo htmlspecialchars($methods[$order['payment_method']] ?? ucfirst(str_replace('_', ' ', $order['payment_method'])));
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Customer & Shipping Information -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 2.5rem;">
    <!-- Customer Info -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Customer Information</h2>
        </div>
        <div>
            <p style="margin: 0 0 1rem 0;">
                <strong style="font-size: 1.05rem;"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
            </p>
            <p style="margin: 0; color: var(--admin-text-muted); word-break: break-all;">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['email_address']); ?>
            </p>
            <div style="margin-top: 1.5rem;">
                <a href="customers.php?id=<?php echo (int)$order['user_id']; ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-user"></i> View Customer
                </a>
            </div>
        </div>
    </div>

    <!-- Shipping Address -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Shipping Address</h2>
        </div>
        <div style="line-height: 1.8; color: var(--admin-text);">
            <?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? 'Not provided')); ?>
        </div>
    </div>

    <!-- Billing Address -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Billing Address</h2>
        </div>
        <div style="line-height: 1.8; color: var(--admin-text);">
            <?php echo nl2br(htmlspecialchars($order['billing_address'] ?? 'Same as shipping address')); ?>
        </div>
    </div>
</div>

<!-- Order Items -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Order Items</h2>
        <p class="card-subtitle"><?php echo count($items); ?> product(s)</p>
    </div>

    <?php if (!empty($items)): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <img src="<?php echo htmlspecialchars(get_admin_image_path($item['product_image'])); ?>" alt="Product" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--admin-border); background: var(--admin-bg-secondary);" onerror="this.src='../image/placeholder.png'">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                        <br><small style="color: var(--admin-text-muted);">ID: <?php echo (int)$item['product_id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><code><?php echo htmlspecialchars($item['order_item_id']); ?></code></td>
                            <td><?php echo (int)$item['quantity']; ?> unit(s)</td>
                            <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td><strong>₱<?php echo number_format($item['total_price'], 2); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="padding: 2rem; text-align: center; color: var(--admin-text-muted);">
            <p>No items in this order.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Order Summary -->
<div class="admin-card" style="max-width: 500px;">
    <div class="card-header">
        <h2 class="card-title">Order Summary</h2>
    </div>

    <div style="display: flex; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid var(--admin-border);">
        <span>Subtotal:</span>
        <strong>₱<?php echo number_format($order['subtotal'], 2); ?></strong>
    </div>
    <div style="display: flex; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid var(--admin-border);">
        <span>Shipping Cost:</span>
        <strong>₱<?php echo number_format($order['shipping_cost'], 2); ?></strong>
    </div>
    <div style="display: flex; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid var(--admin-border);">
        <span>Tax:</span>
        <strong>₱<?php echo number_format($order['tax'], 2); ?></strong>
    </div>
    <div style="display: flex; justify-content: space-between; padding: 1.5rem 0; color: var(--accent-gold); font-size: 1.3rem; font-weight: 700;">
        <span>Total Amount:</span>

<?php include 'includes/footer.php'; ?>
