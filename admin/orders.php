<?php
/**
 * Admin Orders Management
 * View, filter, search, and edit orders with status updates
 */
require_once __DIR__ . '/auth.php';

$page_title = 'Orders';

// Search and filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$payment_filter = $_GET['payment'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    // Build query with filters
    $where_clauses = [];
    $params = [];

    if ($search) {
        $where_clauses[] = "(o.order_number LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.email_address LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    if ($status_filter && in_array($status_filter, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
        $where_clauses[] = "o.order_status = :status";
        $params[':status'] = $status_filter;
    }

    if ($payment_filter && in_array($payment_filter, ['pending', 'paid', 'failed', 'refunded'])) {
        $where_clauses[] = "o.payment_status = :payment";
        $params[':payment'] = $payment_filter;
    }

    $where = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    // Determine sort order
    $order_by = 'ORDER BY o.created_at DESC';
    if ($sort === 'oldest') {
        $order_by = 'ORDER BY o.created_at ASC';
    } elseif ($sort === 'amount_high') {
        $order_by = 'ORDER BY o.total_amount DESC';
    } elseif ($sort === 'amount_low') {
        $order_by = 'ORDER BY o.total_amount ASC';
    }

    // Count total
    $count_sql = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.user_id = u.user_id {$where}";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = (int)$count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $pages = ceil($total / $per_page);

    // Fetch orders
    $orders_sql = "SELECT o.order_id, o.order_number, o.total_amount, o.payment_status, o.order_status, 
                          o.payment_method, o.created_at, u.user_id, u.first_name, u.last_name, u.email_address
                   FROM orders o
                   JOIN users u ON o.user_id = u.user_id
                   {$where}
                   {$order_by}
                   LIMIT :limit OFFSET :offset";
    $orders_stmt = $pdo->prepare($orders_sql);
    $orders_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $orders_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $orders_stmt->bindValue($key, $value);
    }
    $orders_stmt->execute();
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Orders query error: " . $e->getMessage());
    $orders = [];
    $total = 0;
    $pages = 1;
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Orders Management</h1>
        <p class="page-subtitle">View and manage all customer orders</p>
    </div>
    <div class="page-actions">
        <a href="orders.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-redo"></i> Reset
        </a>
    </div>
</div>

<!-- Search & Filter Section -->
<div class="admin-card">
    <form method="GET" class="search-bar">
        <input 
            type="text" 
            name="search" 
            class="search-input" 
            placeholder="Search by order #, customer name, or email..." 
            value="<?php echo htmlspecialchars($search); ?>">
        
        <select name="status" class="form-control">
            <option value="">All Order Status</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
            <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>

        <select name="payment" class="form-control">
            <option value="">All Payment Status</option>
            <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="paid" <?php echo $payment_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
            <option value="failed" <?php echo $payment_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
            <option value="refunded" <?php echo $payment_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
        </select>

        <select name="sort" class="form-control">
            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
            <option value="amount_high" <?php echo $sort === 'amount_high' ? 'selected' : ''; ?>>Highest Amount</option>
            <option value="amount_low" <?php echo $sort === 'amount_low' ? 'selected' : ''; ?>>Lowest Amount</option>
        </select>

        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-search"></i> Search
        </button>
    </form>
</div>

<!-- Orders Table -->
<div class="admin-card">
    <div class="card-header">
        <div>
            <h2 class="card-title">All Orders</h2>
            <p class="card-subtitle">Total: <?php echo number_format($total); ?> orders</p>
        </div>
    </div>

    <?php if (!empty($orders)): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                <small style="color: var(--admin-text-muted);"><?php echo htmlspecialchars($order['email_address']); ?></small>
                            </td>
                            <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            <td>
                                <span class="badge-status badge-<?php echo strtolower($order['payment_status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['payment_status'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-status badge-<?php echo strtolower($order['order_status']); ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></small>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
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

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
            <div style="padding: 2rem 1.5rem; text-align: center; border-top: 1px solid var(--admin-border); display: flex; justify-content: center; gap: .5rem; flex-wrap: wrap;">
                <?php 
                    $start = max(1, $page - 2);
                    $end = min($pages, $page + 2);
                    
                    if ($start > 1): ?>
                        <a href="?page=1&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment=<?php echo urlencode($payment_filter); ?>&sort=<?php echo urlencode($sort); ?>" class="btn btn-secondary btn-sm">
                            1
                        </a>
                        <?php if ($start > 2): ?>
                            <span style="padding: .5rem .75rem;">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment=<?php echo urlencode($payment_filter); ?>&sort=<?php echo urlencode($sort); ?>" 
                           class="btn <?php echo $page === $i ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end < $pages): ?>
                        <?php if ($end < $pages - 1): ?>
                            <span style="padding: .5rem .75rem;">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $pages; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment=<?php echo urlencode($payment_filter); ?>&sort=<?php echo urlencode($sort); ?>" class="btn btn-secondary btn-sm">
                            <?php echo $pages; ?>
                        </a>
                    <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="padding: 3rem; text-align: center; color: var(--admin-text-muted);">
            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <p>No orders found matching your criteria.</p>
            <a href="orders.php" class="btn btn-secondary btn-sm" style="margin-top: 1rem;">Clear Filters</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
