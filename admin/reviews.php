<?php
/**
 * Admin Reviews Management
 * Moderate customer product reviews with filters and status controls
 */
require_once __DIR__ . '/auth.php';

$page_title = 'Reviews';
$action_message = '';
$action_status = 'success';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = (int) ($_POST['review_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $status_map = [
        'approve' => 'approved',
        'hide' => 'hidden',
        'pending' => 'pending',
        'remove' => 'removed'
    ];

    if ($review_id > 0 && isset($status_map[$action])) {
        try {
            $stmt = $pdo->prepare("UPDATE product_reviews SET status = :status, updated_at = NOW() WHERE review_id = :rid LIMIT 1");
            $stmt->execute([
                ':status' => $status_map[$action],
                ':rid' => $review_id
            ]);
            $action_message = 'Review updated successfully.';
        } catch (Exception $e) {
            error_log('Review status update failed: ' . $e->getMessage());
            $action_message = 'Unable to update review right now.';
            $action_status = 'danger';
        }
    } else {
        $action_message = 'Invalid action or review.';
        $action_status = 'danger';
    }
}

// Filters
$status_filter = $_GET['status'] ?? '';
$rating_filter = isset($_GET['rating']) ? (int) $_GET['rating'] : 0;
$product_filter = isset($_GET['product']) ? (int) $_GET['product'] : 0;

$where = [];
$params = [];

if ($status_filter && in_array($status_filter, ['pending','approved','hidden','removed'])) {
    $where[] = 'pr.status = :status';
    $params[':status'] = $status_filter;
}

if ($rating_filter >= 1 && $rating_filter <= 5) {
    $where[] = 'pr.rating = :rating';
    $params[':rating'] = $rating_filter;
}

if ($product_filter > 0) {
    $where[] = 'pr.product_id = :pid';
    $params[':pid'] = $product_filter;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    // Products for filter dropdown
    $products = $pdo->query("SELECT product_id, product_name FROM products ORDER BY product_name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch reviews (limit to keep page light)
    $reviews_sql = "
        SELECT pr.review_id, pr.product_id, pr.user_id, pr.rating, pr.review_content, pr.status, pr.created_at, pr.updated_at, pr.is_verified,
               p.product_name,
               u.first_name, u.last_name
          FROM product_reviews pr
          JOIN products p ON pr.product_id = p.product_id
          JOIN users u ON pr.user_id = u.user_id
          {$where_sql}
      ORDER BY pr.created_at DESC
         LIMIT 150";

    $reviews_stmt = $pdo->prepare($reviews_sql);
    foreach ($params as $key => $value) {
        $reviews_stmt->bindValue($key, $value);
    }
    $reviews_stmt->execute();
    $reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Status counts for quick glance
    $status_counts = $pdo->query("SELECT status, COUNT(*) AS total FROM product_reviews GROUP BY status")
                        ->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
} catch (Exception $e) {
    error_log('Admin reviews fetch error: ' . $e->getMessage());
    $reviews = [];
    $products = [];
    $status_counts = [];
    $action_message = $action_message ?: 'Could not load reviews.';
    $action_status = 'danger';
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Product Reviews</h1>
        <p class="page-subtitle">Moderate customer feedback and keep your catalog trustworthy.</p>
    </div>
    <div class="page-actions">
        <a href="reviews.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-redo"></i> Reset
        </a>
    </div>
</div>

<?php if ($action_message): ?>
    <div class="alert alert-<?php echo htmlspecialchars($action_status); ?>"><?php echo htmlspecialchars($action_message); ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Filters</h2>
            <p class="card-subtitle">Target reviews by product, rating, or status</p>
        </div>
    </div>
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Product</label>
            <select name="product" class="form-control">
                <option value="0">All products</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?php echo (int) $p['product_id']; ?>" <?php echo $product_filter === (int)$p['product_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['product_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
                <option value="">All statuses</option>
                <?php foreach (['pending','approved','hidden','removed'] as $status): ?>
                    <option value="<?php echo $status; ?>" <?php echo $status_filter === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Rating</label>
            <select name="rating" class="form-control">
                <option value="0">All</option>
                <?php for ($r = 5; $r >= 1; $r--): ?>
                    <option value="<?php echo $r; ?>" <?php echo $rating_filter === $r ? 'selected' : ''; ?>><?php echo $r; ?> stars</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-filter"></i> Apply
            </button>
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Reviews</h2>
            <p class="card-subtitle">Pending: <?php echo (int) ($status_counts['pending'] ?? 0); ?> · Approved: <?php echo (int) ($status_counts['approved'] ?? 0); ?> · Hidden: <?php echo (int) ($status_counts['hidden'] ?? 0); ?></p>
        </div>
    </div>

    <?php if (!empty($reviews)): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="min-width: 140px;">Product</th>
                        <th style="min-width: 140px;">Customer</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th style="min-width: 260px;">Review</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $rev): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($rev['product_name']); ?></div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars(trim(($rev['first_name'] ?? '') . ' ' . ($rev['last_name'] ?? ''))); ?></div>
                                <?php if (!empty($rev['is_verified'])): ?>
                                    <span class="badge bg-success">Verified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-warning">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <?php echo $s <= (int)$rev['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-status badge-<?php echo htmlspecialchars($rev['status']); ?>"><?php echo ucfirst($rev['status']); ?></span>
                            </td>
                            <td>
                                <div><?php echo date('M j, Y', strtotime($rev['created_at'])); ?></div>
                                <small style="color: var(--admin-text-muted);">Updated <?php echo date('M j, Y', strtotime($rev['updated_at'])); ?></small>
                            </td>
                            <td>
                                <div class="text-muted small" style="max-width: 320px; white-space: pre-wrap;">
                                    <?php echo nl2br(htmlspecialchars($rev['review_content'])); ?>
                                </div>
                            </td>
                            <td>
                                <form method="POST" class="d-flex flex-column gap-1">
                                    <input type="hidden" name="review_id" value="<?php echo (int)$rev['review_id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                    <button type="submit" name="action" value="pending" class="btn btn-secondary btn-sm">Mark Pending</button>
                                    <button type="submit" name="action" value="hide" class="btn btn-warning btn-sm">Hide</button>
                                    <button type="submit" name="action" value="remove" class="btn btn-danger btn-sm" onclick="return confirm('Remove this review?');">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="p-4 text-center text-muted">
            <i class="fas fa-comments mb-2" style="font-size: 2rem;"></i>
            <p class="mb-0">No reviews found for the current filters.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
