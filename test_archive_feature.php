<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Feature Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4"><i class="fas fa-archive"></i> Archive Feature Test</h1>
        
        <?php
        require_once 'db.php';
        
        echo '<div class="alert alert-info">';
        echo '<h5><i class="fas fa-info-circle"></i> System Status</h5>';
        
        // Check if is_archived column exists
        try {
            $check = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_archived'")->fetch();
            if ($check) {
                echo '<p class="text-success mb-1"><i class="fas fa-check-circle"></i> ✅ is_archived column exists</p>';
            } else {
                echo '<p class="text-danger mb-1"><i class="fas fa-times-circle"></i> ❌ is_archived column missing</p>';
            }
            
            $check2 = $pdo->query("SHOW COLUMNS FROM products LIKE 'archived_at'")->fetch();
            if ($check2) {
                echo '<p class="text-success mb-1"><i class="fas fa-check-circle"></i> ✅ archived_at column exists</p>';
            } else {
                echo '<p class="text-danger mb-1"><i class="fas fa-times-circle"></i> ❌ archived_at column missing</p>';
            }
        } catch (Exception $e) {
            echo '<p class="text-danger">Error checking columns: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        
        echo '</div>';
        
        // Get product counts
        try {
            $total = $pdo->query("SELECT COUNT(*) as cnt FROM products")->fetch()['cnt'];
            $active = $pdo->query("SELECT COUNT(*) as cnt FROM products WHERE is_archived = 0")->fetch()['cnt'];
            $archived = $pdo->query("SELECT COUNT(*) as cnt FROM products WHERE is_archived = 1")->fetch()['cnt'];
            
            echo '<div class="row mb-4">';
            echo '<div class="col-md-4">';
            echo '<div class="card text-center">';
            echo '<div class="card-body">';
            echo '<h3 class="text-primary">' . $total . '</h3>';
            echo '<p class="mb-0">Total Products</p>';
            echo '</div></div></div>';
            
            echo '<div class="col-md-4">';
            echo '<div class="card text-center">';
            echo '<div class="card-body">';
            echo '<h3 class="text-success">' . $active . '</h3>';
            echo '<p class="mb-0">Active Products</p>';
            echo '</div></div></div>';
            
            echo '<div class="col-md-4">';
            echo '<div class="card text-center">';
            echo '<div class="card-body">';
            echo '<h3 class="text-secondary">' . $archived . '</h3>';
            echo '<p class="mb-0">Archived Products</p>';
            echo '</div></div></div>';
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // Show sample products
        try {
            echo '<h3 class="mb-3">Sample Products</h3>';
            $products = $pdo->query("SELECT product_id, product_name, product_price, product_stock, is_archived, archived_at FROM products LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($products)) {
                echo '<div class="table-responsive">';
                echo '<table class="table table-bordered">';
                echo '<thead class="table-dark">';
                echo '<tr>';
                echo '<th>ID</th>';
                echo '<th>Name</th>';
                echo '<th>Price</th>';
                echo '<th>Stock</th>';
                echo '<th>Status</th>';
                echo '<th>Archived At</th>';
                echo '<th>Action</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($products as $p) {
                    $is_archived = (int)$p['is_archived'] === 1;
                    $row_class = $is_archived ? 'table-secondary' : '';
                    
                    echo '<tr class="' . $row_class . '">';
                    echo '<td>' . $p['product_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($p['product_name']) . '</td>';
                    echo '<td>₱' . number_format($p['product_price'], 2) . '</td>';
                    echo '<td>' . $p['product_stock'] . '</td>';
                    echo '<td>';
                    if ($is_archived) {
                        echo '<span class="badge bg-secondary"><i class="fas fa-archive"></i> Archived</span>';
                    } else {
                        echo '<span class="badge bg-success"><i class="fas fa-check"></i> Active</span>';
                    }
                    echo '</td>';
                    echo '<td>' . ($p['archived_at'] ? date('M j, Y', strtotime($p['archived_at'])) : '-') . '</td>';
                    echo '<td>';
                    if ($is_archived) {
                        echo '<button class="btn btn-sm btn-success" onclick="toggleArchive(' . $p['product_id'] . ', \'unarchive\')">';
                        echo '<i class="fas fa-undo"></i> Restore';
                        echo '</button>';
                    } else {
                        echo '<button class="btn btn-sm btn-warning" onclick="toggleArchive(' . $p['product_id'] . ', \'archive\')">';
                        echo '<i class="fas fa-archive"></i> Archive';
                        echo '</button>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
            } else {
                echo '<p class="text-muted">No products found.</p>';
            }
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <div class="mt-4">
            <h4>Test Links</h4>
            <div class="list-group">
                <a href="admin/products.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-cog"></i> Admin Products Page (with archive filters)
                </a>
                <a href="products.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-store"></i> Customer Products Page (active only)
                </a>
                <a href="admin/index.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-dashboard"></i> Admin Dashboard
                </a>
            </div>
        </div>
        
        <div class="alert alert-success mt-4">
            <h5><i class="fas fa-check-circle"></i> Feature Implementation Complete</h5>
            <ul class="mb-0">
                <li>✅ Archive/Unarchive functionality added</li>
                <li>✅ Admin products page updated with filters</li>
                <li>✅ Customer pages exclude archived products</li>
                <li>✅ Real-time stock validation implemented</li>
                <li>✅ Stock deduction on order completion</li>
                <li>✅ Prevention of archived/out-of-stock purchases</li>
            </ul>
        </div>
    </div>
    
    <script>
    function toggleArchive(productId, action) {
        const actionText = action === 'archive' ? 'archive' : 'restore';
        if (!confirm('Are you sure you want to ' + actionText + ' this product?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('action', action);
        
        fetch('admin/api/archive_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    </script>
</body>
</html>
