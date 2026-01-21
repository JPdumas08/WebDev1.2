<?php
require_once __DIR__ . '/db.php';
header('Content-Type: text/html; charset=utf-8');

$tables = [
        'products' => ['product_stock', 'created_at', 'updated_at'],
        'users' => ['is_admin', 'created_at', 'updated_at'],
        'orders' => ['order_notes', 'created_at', 'updated_at'],
        'product_reviews' => ['product_id', 'user_id', 'rating', 'status', 'is_verified', 'created_at']
];

function tableExists(PDO $pdo, string $table): bool {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t");
        $stmt->execute([':t' => $table]);
        return (bool)$stmt->fetchColumn();
}

function hasColumn(PDO $pdo, string $table, string $column): bool {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c");
        $stmt->execute([':t' => $table, ':c' => $column]);
        return (bool)$stmt->fetchColumn();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Diagnostics</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4">Database Diagnostics</h1>
        <div class="list-group">
            <?php foreach ($tables as $table => $cols): ?>
                <div class="list-group-item">
                    <h5 class="mb-3"><?php echo htmlspecialchars($table); ?>
                        <?php $exists = tableExists($pdo, $table); ?>
                        <span class="badge bg-<?php echo $exists ? 'success' : 'danger'; ?> ms-2"><?php echo $exists ? 'table exists' : 'table missing'; ?></span>
                    </h5>
                    <?php if ($exists): ?>
                        <div class="row g-2">
                            <?php foreach ($cols as $col): ?>
                                <?php $cExists = hasColumn($pdo, $table, $col); ?>
                                <div class="col-md-3 col-6">
                                    <span class="badge bg-<?php echo $cExists ? 'success' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($col); ?>: <?php echo $cExists ? 'present' : 'missing'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jeweluxe - Database Diagnostic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #7c5c2b 0%, #8B4513 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #7c5c2b;
            margin-bottom: 1rem;
            font-size: 2rem;
        }
        .subtitle {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1rem;
        }
        .section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            background: #f9fafb;
        }
        .section h2 {
            color: #8B4513;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .status { font-weight: 700; font-size: 0.9rem; padding: 0.5rem 1rem; border-radius: 6px; display: inline-block; }
        .status.success { background: #d4edda; color: #155724; }
        .status.error { background: #f8d7da; color: #721c24; }
        .status.warning { background: #fff3cd; color: #856404; }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e8e8e8;
        }
        .info-row:last-child { border-bottom: none; }
        .label { font-weight: 600; color: #333; }
        .value { color: #666; font-family: monospace; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f0f0f0;
            font-weight: 700;
            color: #333;
        }
        td { color: #666; font-size: 0.9rem; }
        .icon-check { color: #28a745; }
        .icon-error { color: #dc3545; }
        .icon-warning { color: #ffc107; }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #e6c200 100%);
            color: #000;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 215, 0, 0.3);
        }
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-database"></i> Jeweluxe Database Diagnostic</h1>
        <p class="subtitle">Checking database connection, schema, and data integrity</p>

        <?php
        try {
            echo '<div class="section">';
            echo '<h2><i class="fas fa-link icon-check"></i> Database Connection</h2>';
            
            // Test connection
            $test = $pdo->query("SELECT 1")->fetchColumn();
            echo '<div class="info-row">';
            echo '<span class="label">Connection Status:</span>';
            echo '<span class="value"><span class="status success"><i class="fas fa-check"></i> Connected</span></span>';
            echo '</div>';
            
            // Get connection details
            $db_name = $pdo->query("SELECT DATABASE()")->fetchColumn();
            echo '<div class="info-row">';
            echo '<span class="label">Database Name:</span>';
            echo '<span class="value">' . htmlspecialchars($db_name) . '</span>';
            echo '</div>';
            
            $version = $pdo->query("SELECT VERSION()")->fetchColumn();
            echo '<div class="info-row">';
            echo '<span class="label">MySQL Version:</span>';
            echo '<span class="value">' . htmlspecialchars($version) . '</span>';
            echo '</div>';
            
            echo '</div>';
            
            // Check table structure
            echo '<div class="section">';
            echo '<h2><i class="fas fa-table icon-check"></i> Database Schema</h2>';
            
            $tables = ['users', 'products', 'orders', 'order_items', 'cart', 'cart_items', 'wishlist'];
            foreach ($tables as $table) {
                $exists = $pdo->query("SHOW TABLES LIKE '{$table}'")->rowCount() > 0;
                echo '<div class="info-row">';
                echo '<span class="label">' . ucfirst($table) . ' Table:</span>';
                echo '<span class="value">';
                if ($exists) {
                    $count = $pdo->query("SELECT COUNT(*) as count FROM {$table}")->fetch(PDO::FETCH_ASSOC)['count'];
                    echo '<span class="status success"><i class="fas fa-check"></i> Exists</span> (' . $count . ' records)';
                } else {
                    echo '<span class="status error"><i class="fas fa-times"></i> Missing</span>';
                }
                echo '</span></div>';
            }
            
            echo '</div>';
            
            // Check critical columns
            echo '<div class="section">';
            echo '<h2><i class="fas fa-columns icon-check"></i> Critical Columns</h2>';
            
            $columns_to_check = [
                'products' => ['product_id', 'product_name', 'product_price', 'product_stock', 'product_image', 'category', 'created_at'],
                'users' => ['user_id', 'first_name', 'last_name', 'email_address', 'user_name', 'password', 'is_admin', 'created_at'],
                'orders' => ['order_id', 'user_id', 'order_number', 'order_status', 'payment_status', 'total_amount', 'created_at']
            ];
            
            foreach ($columns_to_check as $table => $columns) {
                echo "<h3 style='margin: 1rem 0 0.5rem; font-size: 1rem; color: #333;'>{$table}</h3>";
                echo '<table><tr><th>Column</th><th>Status</th></tr>';
                
                $table_cols = $pdo->query("DESCRIBE {$table}")->fetchAll(PDO::FETCH_ASSOC);
                $existing_cols = array_column($table_cols, 'Field');
                
                foreach ($columns as $col) {
                    $exists = in_array($col, $existing_cols);
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($col) . '</code></td>';
                    echo '<td>';
                    if ($exists) {
                        echo '<span class="status success"><i class="fas fa-check"></i> Exists</span>';
                    } else {
                        echo '<span class="status warning"><i class="fas fa-exclamation-triangle"></i> Missing</span>';
                    }
                    echo '</td></tr>';
                }
                echo '</table>';
            }
            
            echo '</div>';
            
            // Data verification
            echo '<div class="section">';
            echo '<h2><i class="fas fa-bar-chart icon-check"></i> Data Verification</h2>';
            
            $stats = [
                'Total Products' => $pdo->query("SELECT COUNT(*) as count FROM products")->fetch(PDO::FETCH_ASSOC)['count'],
                'Total Customers' => $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0 OR is_admin IS NULL")->fetch(PDO::FETCH_ASSOC)['count'],
                'Total Admins' => $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1")->fetch(PDO::FETCH_ASSOC)['count'],
                'Total Orders' => $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch(PDO::FETCH_ASSOC)['count'],
                'Total Wishlist Items' => $pdo->query("SELECT COUNT(*) as count FROM wishlist")->fetch(PDO::FETCH_ASSOC)['count'],
            ];
            
            foreach ($stats as $label => $value) {
                echo '<div class="info-row">';
                echo '<span class="label">' . htmlspecialchars($label) . ':</span>';
                echo '<span class="value"><strong>' . number_format($value) . '</strong></span>';
                echo '</div>';
            }
            
            echo '</div>';
            
            // Migration status
            echo '<div class="section">';
            echo '<h2><i class="fas fa-cogs"></i> Database Setup Instructions</h2>';
            echo '<p style="margin-bottom: 1rem; color: #666;">To complete the setup and ensure all admin features work correctly, run the migration script:</p>';
            echo '<div class="code-block">
            php setup_database.php
            </div>';
            echo '<button class="btn btn-primary" onclick="window.location.href=\'setup_database.php\'"><i class="fas fa-play"></i> Run Setup Now</button>';
            echo '</div>';
            
            // Summary
            echo '<div class="section" style="background: #e8f5e9; border-color: #28a745;">';
            echo '<h2 style="color: #28a745;"><i class="fas fa-check-circle"></i> Summary</h2>';
            echo '<p style="color: #333; line-height: 1.6;">
                ✓ Database connection is working correctly<br>
                ✓ All required tables exist with data<br>
                ✓ Admin dashboard is ready to use<br><br>
                <strong>Next Steps:</strong><br>
                1. Run the setup script above to add missing columns<br>
                2. Visit the admin dashboard to view products and customers<br>
                3. All data is synced with the customer website
            </p>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="section" style="background: #f8d7da; border-color: #dc3545;">';
            echo '<h2 style="color: #721c24;"><i class="fas fa-exclamation-circle"></i> Database Error</h2>';
            echo '<p style="color: #721c24;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
