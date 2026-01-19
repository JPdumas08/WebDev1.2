<?php
/**
 * Admin Dashboard Overview
 * Real-time statistics and key metrics with improved UI
 */
require_once __DIR__ . '/auth.php';

$page_title = 'Dashboard';

// Fetch key metrics from database with prepared statements
try {
    // Total orders
    $orders_sql = "SELECT COUNT(*) as total FROM orders";
    $total_orders = (int)$pdo->query($orders_sql)->fetch(PDO::FETCH_ASSOC)['total'];

    // Pending orders
    $pending_sql = "SELECT COUNT(*) as total FROM orders WHERE order_status = 'pending'";
    $pending_orders = (int)$pdo->query($pending_sql)->fetch(PDO::FETCH_ASSOC)['total'];

    // Processing orders
    $processing_sql = "SELECT COUNT(*) as total FROM orders WHERE order_status = 'processing'";
    $processing_orders = (int)$pdo->query($processing_sql)->fetch(PDO::FETCH_ASSOC)['total'];

    // Total revenue (paid orders)
    $revenue_sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid'";
    $total_revenue = (float)$pdo->query($revenue_sql)->fetch(PDO::FETCH_ASSOC)['total'];

    // This month revenue
    $month_revenue_sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders 
                          WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
    $month_revenue = (float)$pdo->query($month_revenue_sql)->fetch(PDO::FETCH_ASSOC)['total'];

    // Total products
    $products_sql = "SELECT COUNT(*) as total FROM products";
    $total_products = (int)$pdo->query($products_sql)->fetch(PDO::FETCH_ASSOC)['total'];

    // Low stock products
    $low_stock_sql = "SELECT COUNT(*) as total FROM products WHERE product_stock < 10";
    $low_stock_products = (int)$pdo->query($low_stock_sql)->fetch(PDO::FETCH_ASSOC)['total'];

    // Total customers
    $customers_sql = "SELECT COUNT(*) as total FROM users WHERE is_admin = 0";
    $total_customers = (int)$pdo->query($customers_sql)->fetch(PDO::FETCH_ASSOC)['total'];

    // New customers this month
    $new_customers_sql = "SELECT COUNT(*) as total FROM users WHERE is_admin = 0 AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
    $new_customers = (int)$pdo->query($new_customers_sql)->fetch(PDO::FETCH_ASSOC)['total'];

    // Unpaid orders
    $unpaid_sql = "SELECT COUNT(*) as total FROM orders WHERE payment_status IN ('pending', 'failed')";
    $unpaid_orders = (int)$pdo->query($unpaid_sql)->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent orders
    $recent_sql = "SELECT o.order_id, o.order_number, o.total_amount, o.payment_status, o.order_status, 
                          o.created_at, u.first_name, u.last_name
                   FROM orders o
                   JOIN users u ON o.user_id = u.user_id
                   ORDER BY o.created_at DESC
                   LIMIT 10";
    $recent_orders = $pdo->query($recent_sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $total_orders = $pending_orders = $processing_orders = $total_revenue = $month_revenue = 0;
    $total_products = $low_stock_products = $total_customers = $new_customers = $unpaid_orders = 0;
    $recent_orders = [];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome to Jeweluxe Admin - Real-time store analytics and management</p>
    </div>
    <button class="btn btn-secondary btn-sm" onclick="location.reload()">
        <i class="fas fa-sync-alt"></i> Refresh
    </button>
</div>

<!-- Key Metrics Grid -->
<div class="stats-grid">
    <!-- Total Orders Card -->
    <div class="stat-card">
        <i class="fas fa-shopping-bag stat-icon"></i>
        <div class="stat-label">Total Orders</div>
        <div class="stat-value"><?php echo number_format($total_orders); ?></div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            <span><?php echo $pending_orders; ?> pending, <?php echo $processing_orders; ?> processing</span>
        </div>
    </div>

    <!-- Revenue Card -->
    <div class="stat-card">
        <i class="fas fa-coins stat-icon"></i>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value stat-value-accent">₱<?php echo number_format($total_revenue, 2); ?></div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            <span>This month: ₱<?php echo number_format($month_revenue, 2); ?></span>
        </div>
    </div>

    <!-- Products Card -->
    <div class="stat-card">
        <i class="fas fa-gem stat-icon"></i>
        <div class="stat-label">Active Products</div>
        <div class="stat-value"><?php echo number_format($total_products); ?></div>
        <div class="stat-change <?php echo $low_stock_products > 0 ? 'negative' : 'neutral'; ?>">
            <i class="fas fa-<?php echo $low_stock_products > 0 ? 'alert-circle' : 'check-circle'; ?>"></i>
            <span><?php echo $low_stock_products > 0 ? $low_stock_products . ' low stock items' : 'All items well stocked'; ?></span>
        </div>
    </div>

    <!-- Customers Card -->
    <div class="stat-card">
        <i class="fas fa-users stat-icon"></i>
        <div class="stat-label">Total Customers</div>
        <div class="stat-value"><?php echo number_format($total_customers); ?></div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            <span>+<?php echo $new_customers; ?> new this month</span>
        </div>
    </div>

    <!-- Pending Payments Card -->
    <div class="stat-card">
        <i class="fas fa-credit-card stat-icon"></i>
        <div class="stat-label">Pending Payments</div>
        <div class="stat-value"><?php echo number_format($unpaid_orders); ?></div>
        <div class="stat-change <?php echo $unpaid_orders > 0 ? 'negative' : 'neutral'; ?>">
            <i class="fas fa-<?php echo $unpaid_orders > 0 ? 'exclamation-circle' : 'check-circle'; ?>"></i>
            <span><?php echo $unpaid_orders > 0 ? 'Action needed' : 'No pending payments'; ?></span>
        </div>
    </div>
</div>

<!-- Recent Orders Section -->
<div class="admin-card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Recent Orders</h2>
            <p class="card-subtitle"><?php echo count($recent_orders); ?> latest orders from your store</p>
        </div>
        <div class="card-actions">
            <a href="orders.php" class="btn btn-primary btn-sm">
                <i class="fas fa-arrow-right"></i> View All Orders
            </a>
        </div>
    </div>

    <?php if (!empty($recent_orders)): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                            <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            <td>
                                <span class="badge-status badge-<?php echo strtolower($order['payment_status']); ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-status badge-<?php echo strtolower($order['order_status']); ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y \a\t h:i A', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="order_detail.php?id=<?php echo (int)$order['order_id']; ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="padding: 2rem; text-align: center; color: var(--admin-text-muted);">
            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <p>No orders yet. When customers place orders, they will appear here.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
