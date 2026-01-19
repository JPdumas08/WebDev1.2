<?php
/**
 * Admin Products Management
 * View, add, edit, and delete products with image uploads
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/image_utils.php';

$page_title = 'Products';

// Search
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? 'active'; // active, archived, all
$sort = $_GET['sort'] ?? 'newest';
$page = (int)($_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;

try {
    // Build query with filters
    $where_clauses = [];
    $params = [];

    // Status filter
    if ($status_filter === 'active') {
        $where_clauses[] = "is_archived = 0";
    } elseif ($status_filter === 'archived') {
        $where_clauses[] = "is_archived = 1";
    }
    // 'all' shows both

    if ($search) {
        $where_clauses[] = "(product_name LIKE :search OR product_description LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    if ($category) {
        $where_clauses[] = "category = :category";
        $params[':category'] = $category;
    }

    $where = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    // Determine sort order
    $order_by = 'ORDER BY created_at DESC';
    if ($sort === 'name_asc') {
        $order_by = 'ORDER BY product_name ASC';
    } elseif ($sort === 'name_desc') {
        $order_by = 'ORDER BY product_name DESC';
    } elseif ($sort === 'price_high') {
        $order_by = 'ORDER BY product_price DESC';
    } elseif ($sort === 'price_low') {
        $order_by = 'ORDER BY product_price ASC';
    } elseif ($sort === 'stock_low') {
        $order_by = 'ORDER BY product_stock ASC';
    }

    // Count total
    $count_sql = "SELECT COUNT(*) as total FROM products {$where}";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = (int)$count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $pages = ceil($total / $per_page);

    // Fetch products
    $products_sql = "SELECT product_id, product_name, product_price, 
                            COALESCE(product_stock, 0) as product_stock, 
                            product_image, category, is_archived,
                            COALESCE(created_at, NOW()) as created_at,
                            archived_at
                     FROM products
                     {$where}
                     {$order_by}
                     LIMIT :limit OFFSET :offset";
    
    $products_stmt = $pdo->prepare($products_sql);
    $products_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $products_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $products_stmt->bindValue($key, $value);
    }
    $products_stmt->execute();
    $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get categories
    $categories_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category";
    $categories = $pdo->query($categories_sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Products query error: " . $e->getMessage());
    $products = [];
    $total = 0;
    $pages = 1;
    $categories = [];
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Products Management</h1>
        <p class="page-subtitle">Manage your jewelry inventory</p>
    </div>
    <div class="page-actions">
        <a href="product_edit.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add New Product
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
            placeholder="Search products by name or description..." 
            value="<?php echo htmlspecialchars($search); ?>">
        
        <select name="status" class="form-control">
            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Products</option>
            <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived Products</option>
            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Products</option>
        </select>
        
        <select name="category" class="form-control">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="sort" class="form-control">
            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price (High to Low)</option>
            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price (Low to High)</option>
            <option value="stock_low" <?php echo $sort === 'stock_low' ? 'selected' : ''; ?>>Low Stock</option>
        </select>

        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-search"></i> Search
        </button>
        <a href="products.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-redo"></i> Reset
        </a>
    </form>
</div>

<!-- Products Table -->
<div class="admin-card">
    <div class="card-header">
        <div>
            <h2 class="card-title">All Products</h2>
            <p class="card-subtitle">Total: <?php echo number_format($total); ?> products</p>
        </div>
    </div>

    <?php if (!empty($products)): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th style="min-width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): 
                        $is_archived = (int)$product['is_archived'] === 1;
                        $row_style = $is_archived ? 'opacity: 0.6; background: var(--admin-bg-hover);' : '';
                    ?>
                        <tr style="<?php echo $row_style; ?>" data-product-id="<?php echo (int)$product['product_id']; ?>">
                            <td>
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <img src="<?php echo htmlspecialchars(get_admin_image_path($product['product_image'])); ?>" alt="Product" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--admin-border); background: var(--admin-bg-secondary);" onerror="this.src='../image/placeholder.png'">
                                    <div>
                                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                        <?php if ($is_archived): ?>
                                            <span class="badge-status badge-secondary" style="margin-left: 0.5rem; font-size: 0.7rem;">
                                                <i class="fas fa-archive"></i> ARCHIVED
                                            </span>
                                        <?php endif; ?>
                                        <br><small style="color: var(--admin-text-muted);">ID: <?php echo (int)$product['product_id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($product['category']): ?>
                                    <span class="badge-status badge-info" style="font-size: 0.8rem;">
                                        <?php echo htmlspecialchars($product['category']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--admin-text-muted);">Uncategorized</span>
                                <?php endif; ?>
                            </td>
                            <td><strong>₱<?php echo number_format($product['product_price'], 2); ?></strong></td>
                            <td>
                                <?php 
                                    $stock = (int)$product['product_stock'];
                                    if ($stock < 5):
                                        echo '<span class="badge-status badge-danger">' . $stock . ' units</span>';
                                    elseif ($stock < 20):
                                        echo '<span class="badge-status badge-warning">' . $stock . ' units</span>';
                                    else:
                                        echo '<span class="badge-status badge-success">' . $stock . ' units</span>';
                                    endif;
                                ?>
                            </td>
                            <td>
                                <?php if ($is_archived): ?>
                                    <span class="badge-status badge-secondary">
                                        <i class="fas fa-archive"></i> Archived
                                    </span>
                                <?php elseif ($stock > 0): ?>
                                    <span class="badge-status badge-success">In Stock</span>
                                <?php else: ?>
                                    <span class="badge-status badge-danger">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="product_edit.php?id=<?php echo (int)$product['product_id']; ?>" class="btn btn-secondary btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    
                                    <?php if ($is_archived): ?>
                                        <button 
                                            onclick="toggleArchiveProduct(<?php echo (int)$product['product_id']; ?>, 'unarchive')" 
                                            class="btn btn-success btn-sm" 
                                            title="Restore this product">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                    <?php else: ?>
                                        <button 
                                            onclick="toggleArchiveProduct(<?php echo (int)$product['product_id']; ?>, 'archive')" 
                                            class="btn btn-warning btn-sm" 
                                            title="Archive this product">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                    <?php endif; ?>
                                </div>
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
                        <a href="?page=1&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" class="btn btn-secondary btn-sm">
                            1
                        </a>
                        <?php if ($start > 2): ?>
                            <span style="padding: .5rem .75rem;">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" 
                           class="btn <?php echo $page === $i ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end < $pages): ?>
                        <?php if ($end < $pages - 1): ?>
                            <span style="padding: .5rem .75rem;">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $pages; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" class="btn btn-secondary btn-sm">
                            <?php echo $pages; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="padding: 3rem; text-align: center; color: var(--admin-text-muted);">
            <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <p>No products found matching your criteria.</p>
            <div style="margin-top: 1.5rem;">
                <a href="products.php" class="btn btn-secondary btn-sm" style="margin-right: 1rem;">Clear Filters</a>
                <a href="product_edit.php" class="btn btn-primary btn-sm">Add New Product</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleArchiveProduct(productId, action) {
    const actionText = action === 'archive' ? 'archive' : 'restore';
    const title = action === 'archive' ? '⚠️ Archive Product' : '🔄 Restore Product';
    const confirmMsg = action === 'archive' 
        ? 'Are you sure you want to archive this product? It will be hidden from customers but can be restored later.'
        : 'Are you sure you want to restore this product? It will become visible to customers again.';
    
    AdminModal.show(title, confirmMsg, function() {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('action', action);
        
        fetch('api/archive_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                AdminModal.success(data.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                AdminModal.error(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            AdminModal.error('An error occurred. Please try again.');
        });
    });
}
</script>

<?php include 'includes/footer.php'; ?>
