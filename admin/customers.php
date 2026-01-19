<?php
/**
 * Admin Customers Management
 * View customer accounts and details
 */
require_once __DIR__ . '/auth.php';

$page_title = 'Customers';

// Search and filter
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Validate sort parameter
$valid_sorts = ['newest', 'oldest', 'name_asc', 'name_desc', 'spent_high', 'spent_low'];
if (!in_array($sort, $valid_sorts)) {
    $sort = 'newest';
}

// Build ORDER BY clause
$order_by = 'u.created_at DESC';
switch ($sort) {
    case 'oldest':
        $order_by = 'u.created_at ASC';
        break;
    case 'name_asc':
        $order_by = 'u.first_name ASC, u.last_name ASC';
        break;
    case 'name_desc':
        $order_by = 'u.first_name DESC, u.last_name DESC';
        break;
    case 'spent_high':
        $order_by = 'total_spent DESC';
        break;
    case 'spent_low':
        $order_by = 'total_spent ASC';
        break;
}

try {
    // Count total customers (non-admin)
    $count_sql = "SELECT COUNT(*) as total FROM users WHERE (is_admin = 0 OR is_admin IS NULL)";
    if ($search) {
        $count_sql .= " AND (first_name LIKE :search OR last_name LIKE :search OR email_address LIKE :search)";
    }
    $count_stmt = $pdo->prepare($count_sql);
    if ($search) {
        $count_stmt->execute([':search' => "%{$search}%"]);
    } else {
        $count_stmt->execute();
    }
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $pages = ceil($total / $per_page);

    // Fetch customers with ordering
    $customers_sql = "SELECT u.user_id, u.first_name, u.last_name, u.email_address, 
                             COALESCE(u.created_at, NOW()) as created_at,
                             COUNT(o.order_id) as total_orders, COALESCE(SUM(o.total_amount), 0) as total_spent
                      FROM users u
                      LEFT JOIN orders o ON u.user_id = o.user_id
                      WHERE u.is_admin = 0 OR u.is_admin IS NULL";
    if ($search) {
        $customers_sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email_address LIKE :search)";
    }
    $customers_sql .= " GROUP BY u.user_id
                       ORDER BY " . $order_by . "
                       LIMIT :limit OFFSET :offset";
    
    $customers_stmt = $pdo->prepare($customers_sql);
    $customers_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $customers_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    if ($search) {
        $customers_stmt->bindValue(':search', "%{$search}%");
    }
    $customers_stmt->execute();
    $customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Customers query error: " . $e->getMessage());
    $customers = [];
    $total = 0;
    $pages = 1;
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Customers</h1>
</div>

<!-- Search and Filter -->
<div class="admin-card">
    <form method="GET" class="search-bar" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 1; min-width: 250px;">
            <label for="search" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Search</label>
            <input type="text" id="search" name="search" class="search-input" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        
        <div style="flex: 0 0 200px;">
            <label for="sort" class="form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Sort By</label>
            <select name="sort" id="sort" class="form-control" style="width: 100%;">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                <option value="spent_high" <?php echo $sort === 'spent_high' ? 'selected' : ''; ?>>Highest Spender</option>
                <option value="spent_low" <?php echo $sort === 'spent_low' ? 'selected' : ''; ?>>Lowest Spender</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
</div>

<!-- Customers Table -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">All Customers (<?php echo number_format($total); ?>)</h2>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Email</th>
                <th>Total Orders</th>
                <th>Total Spent</th>
                <th>Member Since</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td>
                        <div>
                            <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                        </div>
                        <small style="color: var(--admin-text-muted);"><?php echo htmlspecialchars($customer['email_address']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($customer['email_address']); ?></td>
                    <td><?php echo (int)$customer['total_orders']; ?></td>
                    <td>₱<?php echo number_format($customer['total_spent'] ?? 0, 2); ?></td>
                    <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
        <div style="padding: 1.5rem; text-align: center; border-top: 1px solid var(--admin-border);">
            <?php 
            // Smart pagination with ellipsis
            $start = max(1, $page - 2);
            $end = min($pages, $page + 2);
            
            if ($start > 1): ?>
                <a href="?page=1&search=<?php echo urlencode($search); ?>&sort=<?php echo htmlspecialchars($sort); ?>" 
                   class="btn btn-secondary btn-sm" style="margin: 0 .25rem;">1</a>
                <?php if ($start > 2): ?>
                    <span style="padding: 0 .5rem;">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo htmlspecialchars($sort); ?>" 
                   class="btn <?php echo $page === $i ? 'btn-primary' : 'btn-secondary'; ?> btn-sm"
                   style="margin: 0 .25rem;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($end < $pages): ?>
                <?php if ($end < $pages - 1): ?>
                    <span style="padding: 0 .5rem;">...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $pages; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo htmlspecialchars($sort); ?>" 
                   class="btn btn-secondary btn-sm" style="margin: 0 .25rem;"><?php echo $pages; ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
