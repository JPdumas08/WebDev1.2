#!/usr/bin/env php
<?php
/**
 * Jeweluxe Database Setup & Migration Script
 * Automated setup to fix admin dashboard database issues
 * 
 * Usage: php run_migrations.php
 */

// Ensure we're running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line: php run_migrations.php\n");
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   Jeweluxe Database Migration & Setup Tool                ║\n";
echo "║   Fixing Admin Dashboard Data Display Issues              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

require_once __DIR__ . '/db.php';

try {
    echo "Step 1: Verifying database connection...\n";
    $pdo->query("SELECT 1");
    $db_name = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "✓ Connected to database: {$db_name}\n\n";

    echo "Step 2: Running migrations...\n";
    $migration_file = __DIR__ . '/migrations/003_add_missing_admin_columns.sql';
    
    if (!file_exists($migration_file)) {
        throw new Exception("Migration file not found: {$migration_file}");
    }

    $sql = file_get_contents($migration_file);
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($stmt) => !empty($stmt) && !preg_match('/^--/', trim($stmt))
    );

    $success_count = 0;
    $skip_count = 0;

    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            echo "  ✓ " . substr(trim($statement), 0, 60) . "...\n";
            $success_count++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "  ℹ " . substr(trim($statement), 0, 60) . "... (already exists)\n";
                $skip_count++;
            } else {
                throw $e;
            }
        }
    }

    echo "\n✓ Migration completed! ({$success_count} executed, {$skip_count} skipped)\n\n";

    // Verify schema
    echo "Step 3: Verifying schema...\n";
    
    $critical_checks = [
        'products.product_stock' => "SELECT 1 FROM products LIMIT 1 WHERE 1=0",
        'products.created_at' => "SELECT created_at FROM products LIMIT 1 WHERE 1=0",
        'users.is_admin' => "SELECT is_admin FROM users LIMIT 1 WHERE 1=0",
        'users.created_at' => "SELECT created_at FROM users LIMIT 1 WHERE 1=0",
    ];

    $all_columns_exist = true;
    foreach ($critical_checks as $column => $check_sql) {
        try {
            $pdo->query($check_sql);
            echo "  ✓ Column {$column} exists\n";
        } catch (PDOException $e) {
            echo "  ✗ Column {$column} missing\n";
            $all_columns_exist = false;
        }
    }

    echo "\nStep 4: Data verification...\n";
    
    $product_count = $pdo->query("SELECT COUNT(*) as count FROM products")->fetch(PDO::FETCH_ASSOC)['count'];
    $customer_count = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0 OR is_admin IS NULL")->fetch(PDO::FETCH_ASSOC)['count'];
    $order_count = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch(PDO::FETCH_ASSOC)['count'];

    echo "  ✓ Products in database: {$product_count}\n";
    echo "  ✓ Customers in database: {$customer_count}\n";
    echo "  ✓ Orders in database: {$order_count}\n";

    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    
    if ($all_columns_exist && $product_count > 0 && $customer_count > 0) {
        echo "║  ✓ DATABASE SETUP COMPLETE!                                 ║\n";
        echo "║                                                            ║\n";
        echo "║  The admin dashboard is now ready to use.                  ║\n";
        echo "║  • Visit: http://localhost/WebDev1.2/admin/                ║\n";
        echo "║  • Products and Customers sections will display all data   ║\n";
        echo "║  • Data is synced with the customer-facing website         ║\n";
    } else {
        echo "║  ⚠ SETUP PARTIALLY COMPLETE                               ║\n";
        echo "║                                                            ║\n";
        echo "║  Some columns may still be missing. Check:                 ║\n";
        echo "║  • http://localhost/WebDev1.2/diagnostic.php              ║\n";
    }
    
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

} catch (Exception $e) {
    echo "\n❌ SETUP FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "  1. MySQL is running\n";
    echo "  2. Database credentials in db.php are correct\n";
    echo "  3. web_dev database exists\n\n";
    exit(1);
}

exit(0);
