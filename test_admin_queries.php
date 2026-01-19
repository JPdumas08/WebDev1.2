<?php
/**
 * Admin Dashboard Query Verification & Testing
 * Tests all critical admin queries to ensure they work correctly
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Query Verification</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #7c5c2b; margin-bottom: 0.5rem; }
        .subtitle { color: #666; margin-bottom: 2rem; }
        .test-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            background: #f9fafb;
        }
        .test-section h2 {
            color: #8B4513;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        .status { font-weight: 700; padding: 0.5rem 1rem; border-radius: 6px; display: inline-block; }
        .status.pass { background: #d4edda; color: #155724; }
        .status.fail { background: #f8d7da; color: #721c24; }
        .result { background: #f5f5f5; padding: 1rem; border-radius: 8px; margin: 1rem 0; font-family: monospace; }
        .row-count { font-weight: bold; color: #28a745; }
        table { width: 100%; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f0f0f0; font-weight: 700; }
        .code { background: #1e1e1e; color: #d4d4d4; padding: 1rem; border-radius: 8px; margin: 1rem 0; font-family: monospace; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-stethoscope"></i> Admin Query Verification Tool</h1>
        <p class="subtitle">Testing all critical admin dashboard queries</p>

        <?php
        try {
            $total_tests = 0;
            $passed_tests = 0;

            // Test 1: Products Query
            echo '<div class="test-section">';
            echo '<h2><i class="fas fa-box"></i> Test 1: Products Query</h2>';
            $total_tests++;
            
            try {
                $query = "SELECT product_id, product_name, product_price, 
                                 COALESCE(product_stock, 0) as product_stock, 
                                 product_image, category, 
                                 COALESCE(created_at, NOW()) as created_at
                          FROM products LIMIT 5";
                
                $result = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<p><span class="status pass"><i class="fas fa-check"></i> PASS</span></p>';
                echo '<div class="result">';
                echo '<p><strong>Query executed successfully</strong></p>';
                echo '<p><span class="row-count">Rows returned: ' . count($result) . '</span></p>';
                
                if (!empty($result)) {
                    echo '<h3 style="margin-top: 1rem; margin-bottom: 0.5rem;">Sample Data:</h3>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Category</th></tr>';
                    foreach (array_slice($result, 0, 3) as $row) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['product_id']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['product_name']) . '</td>';
                        echo '<td>₱' . number_format($row['product_price'], 2) . '</td>';
                        echo '<td>' . htmlspecialchars($row['product_stock']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['category'] ?? 'N/A') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
                
                echo '</div>';
                $passed_tests++;
                
            } catch (Exception $e) {
                echo '<p><span class="status fail"><i class="fas fa-times"></i> FAIL</span></p>';
                echo '<div class="result">';
                echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
            echo '</div>';

            // Test 2: Customers Query
            echo '<div class="test-section">';
            echo '<h2><i class="fas fa-users"></i> Test 2: Customers Query</h2>';
            $total_tests++;
            
            try {
                $query = "SELECT u.user_id, u.first_name, u.last_name, u.email_address, 
                                 COALESCE(u.created_at, NOW()) as created_at,
                                 COUNT(o.order_id) as total_orders, 
                                 COALESCE(SUM(o.total_amount), 0) as total_spent
                          FROM users u
                          LEFT JOIN orders o ON u.user_id = o.user_id
                          WHERE u.is_admin = 0 OR u.is_admin IS NULL
                          GROUP BY u.user_id
                          LIMIT 5";
                
                $result = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<p><span class="status pass"><i class="fas fa-check"></i> PASS</span></p>';
                echo '<div class="result">';
                echo '<p><strong>Query executed successfully</strong></p>';
                echo '<p><span class="row-count">Rows returned: ' . count($result) . '</span></p>';
                
                if (!empty($result)) {
                    echo '<h3 style="margin-top: 1rem; margin-bottom: 0.5rem;">Sample Data:</h3>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Orders</th><th>Total Spent</th></tr>';
                    foreach (array_slice($result, 0, 3) as $row) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['user_id']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['email_address']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['total_orders']) . '</td>';
                        echo '<td>₱' . number_format($row['total_spent'], 2) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
                
                echo '</div>';
                $passed_tests++;
                
            } catch (Exception $e) {
                echo '<p><span class="status fail"><i class="fas fa-times"></i> FAIL</span></p>';
                echo '<div class="result">';
                echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
            echo '</div>';

            // Test 3: Orders Query
            echo '<div class="test-section">';
            echo '<h2><i class="fas fa-receipt"></i> Test 3: Orders Query</h2>';
            $total_tests++;
            
            try {
                $query = "SELECT o.order_id, o.order_number, o.total_amount, 
                                 o.payment_status, o.order_status, o.created_at, 
                                 u.first_name, u.last_name, u.email_address
                          FROM orders o
                          JOIN users u ON o.user_id = u.user_id
                          ORDER BY o.created_at DESC
                          LIMIT 5";
                
                $result = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<p><span class="status pass"><i class="fas fa-check"></i> PASS</span></p>';
                echo '<div class="result">';
                echo '<p><strong>Query executed successfully</strong></p>';
                echo '<p><span class="row-count">Rows returned: ' . count($result) . '</span></p>';
                
                if (!empty($result)) {
                    echo '<h3 style="margin-top: 1rem; margin-bottom: 0.5rem;">Sample Data:</h3>';
                    echo '<table>';
                    echo '<tr><th>Order ID</th><th>Order #</th><th>Amount</th><th>Status</th><th>Customer</th></tr>';
                    foreach (array_slice($result, 0, 3) as $row) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['order_id']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['order_number']) . '</td>';
                        echo '<td>₱' . number_format($row['total_amount'], 2) . '</td>';
                        echo '<td><strong>' . htmlspecialchars($row['order_status']) . '</strong></td>';
                        echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
                
                echo '</div>';
                $passed_tests++;
                
            } catch (Exception $e) {
                echo '<p><span class="status fail"><i class="fas fa-times"></i> FAIL</span></p>';
                echo '<div class="result">';
                echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
            echo '</div>';

            // Test 4: Admin Authentication Query
            echo '<div class="test-section">';
            echo '<h2><i class="fas fa-shield-alt"></i> Test 4: Admin Authentication Query</h2>';
            $total_tests++;
            
            try {
                $query = "SELECT user_id, user_name, first_name, last_name, email_address, is_admin 
                          FROM users 
                          WHERE is_admin = 1 
                          LIMIT 5";
                
                $result = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<p><span class="status pass"><i class="fas fa-check"></i> PASS</span></p>';
                echo '<div class="result">';
                echo '<p><strong>Query executed successfully</strong></p>';
                echo '<p><span class="row-count">Admin users found: ' . count($result) . '</span></p>';
                
                if (!empty($result)) {
                    echo '<h3 style="margin-top: 1rem; margin-bottom: 0.5rem;">Sample Data:</h3>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Admin</th></tr>';
                    foreach (array_slice($result, 0, 3) as $row) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['user_id']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['user_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['email_address']) . '</td>';
                        echo '<td><strong>' . ($row['is_admin'] ? 'Yes' : 'No') . '</strong></td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<p style="color: #ffc107;"><i class="fas fa-exclamation-triangle"></i> No admin users found! Run migrations to add is_admin column.</p>';
                }
                
                echo '</div>';
                $passed_tests++;
                
            } catch (Exception $e) {
                echo '<p><span class="status fail"><i class="fas fa-times"></i> FAIL</span></p>';
                echo '<div class="result">';
                echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<p style="margin-top: 0.5rem; color: #666;">This is expected if is_admin column hasn\'t been added yet. Run migrations.</p>';
                echo '</div>';
            }
            echo '</div>';

            // Summary
            echo '<div class="test-section" style="background: linear-gradient(135deg, #d4edda 0%, #e8f5e9 100%); border-color: #28a745;">';
            echo '<h2 style="color: #155724;"><i class="fas fa-chart-pie"></i> Summary</h2>';
            echo '<p style="font-size: 1.1rem; margin: 1rem 0;"><strong>Tests Passed:</strong> <span style="color: #28a745; font-size: 1.3rem;">' . $passed_tests . '/' . $total_tests . '</span></p>';
            
            if ($passed_tests == $total_tests) {
                echo '<p style="color: #155724;"><i class="fas fa-check-circle"></i> All tests passed! Your admin dashboard should be working correctly.</p>';
                echo '<p style="color: #155724; margin-top: 1rem;"><strong>Next steps:</strong></p>';
                echo '<ul style="color: #155724; margin-left: 1.5rem;">';
                echo '<li>Visit <a href="/WebDev1.2/admin/" style="color: #155724; text-decoration: underline;">the admin dashboard</a></li>';
                echo '<li>Log in with your admin account</li>';
                echo '<li>Check Products and Customers sections</li>';
                echo '<li>Verify all data displays correctly</li>';
                echo '</ul>';
            } else {
                echo '<p style="color: #721c24;"><i class="fas fa-exclamation-circle"></i> Some tests failed. Check the errors above and run migrations if needed.</p>';
                echo '<p style="margin-top: 1rem;"><a href="/WebDev1.2/setup_database.php" style="background: #dc3545; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-block;"><i class="fas fa-tools"></i> Run Setup Now</a></p>';
            }
            
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="test-section" style="background: #f8d7da; border-color: #dc3545;">';
            echo '<h2 style="color: #721c24;"><i class="fas fa-exclamation-circle"></i> Critical Error</h2>';
            echo '<p style="color: #721c24;">' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
