<?php
/**
 * Admin Payments / Transactions
 * View payment and transaction records
 */
require_once __DIR__ . '/auth.php';

$page_title = 'Payments';

// Filter
$method_filter = $_GET['method'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 25;
$offset = ($page - 1) * $per_page;

try {
    // Build filters
    $where_clauses = [];
    $params = [];

    if ($method_filter && in_array($method_filter, ['cod', 'gcash', 'paypal', 'bank_transfer'])) {
        $where_clauses[] = "o.payment_method = :method";
        $params[':method'] = $method_filter;
    }

    if ($status_filter && in_array($status_filter, ['pending', 'paid', 'failed', 'refunded'])) {
        $where_clauses[] = "o.payment_status = :status";
        $params[':status'] = $status_filter;
    }

    $where = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    // Count total
    $count_sql = "SELECT COUNT(*) as total FROM orders o {$where}";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $pages = ceil($total / $per_page);

    // Fetch payments
    $payments_sql = "SELECT o.order_id, o.order_number, o.total_amount, o.payment_method, o.payment_status,
                            o.created_at, u.first_name, u.last_name, u.email_address
                     FROM orders o
                     JOIN users u ON o.user_id = u.user_id
                     {$where}
                     ORDER BY o.created_at DESC
                     LIMIT :limit OFFSET :offset";
    $payments_stmt = $pdo->prepare($payments_sql);
    $payments_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $payments_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $payments_stmt->bindValue($key, $value);
    }
    $payments_stmt->execute();
    $payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Payments query error: " . $e->getMessage());
    $payments = [];
    $total = 0;
    $pages = 1;
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Payments</h1>
</div>

<!-- Filters -->
<div class="admin-card">
    <form method="GET" class="search-bar">
        <select name="method" class="form-control" style="max-width: 200px;">
            <option value="">All Methods</option>
            <option value="cod" <?php echo $method_filter === 'cod' ? 'selected' : ''; ?>>Cash on Delivery</option>
            <option value="gcash" <?php echo $method_filter === 'gcash' ? 'selected' : ''; ?>>GCash</option>
            <option value="paypal" <?php echo $method_filter === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
            <option value="bank_transfer" <?php echo $method_filter === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
        </select>

        <select name="status" class="form-control" style="max-width: 200px;">
            <option value="">All Statuses</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
            <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
            <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
        </select>

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </form>
</div>

<!-- Payments Table -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">All Payments (<?php echo number_format($total); ?>)</h2>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><strong>#<?php echo htmlspecialchars($payment['order_number']); ?></strong></td>
                    <td>
                        <div><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></div>
                        <small style="color: var(--admin-text-muted);"><?php echo htmlspecialchars($payment['email_address']); ?></small>
                    </td>
                    <td><strong>₱<?php echo number_format($payment['total_amount'], 2); ?></strong></td>
                    <td>
                        <?php 
                            $method = $payment['payment_method'];
                            if ($method === 'cod') echo 'Cash on Delivery';
                            elseif ($method === 'gcash') echo 'GCash';
                            elseif ($method === 'paypal') echo 'PayPal';
                            elseif ($method === 'bank_transfer') echo 'Bank Transfer';
                            else echo ucfirst($method);
                        ?>
                    </td>
                    <td>
                        <span class="badge-status badge-<?php echo strtolower($payment['payment_status']); ?>">
                            <?php echo ucfirst($payment['payment_status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
        <div style="padding: 1.5rem; text-align: center; border-top: 1px solid var(--admin-border);">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&method=<?php echo urlencode($method_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                   class="btn <?php echo $page === $i ? 'btn-primary' : 'btn-secondary'; ?> btn-sm"
                   style="margin: 0 .25rem;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
