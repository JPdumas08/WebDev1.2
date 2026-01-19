<?php
/**
 * Database Setup & Migration Script
 * Ensures database schema is properly initialized with all required columns and tables
 * This script should be run once after initial setup
 */

require_once __DIR__ . '/db.php';

echo "======================================\n";
echo "Jeweluxe Database Setup & Migration\n";
echo "======================================\n\n";

try {
    // Define migration files in order
    $migrations = [
        '003_add_missing_admin_columns.sql',
    ];
    
    foreach ($migrations as $migration_file) {
        $migration_path = __DIR__ . '/migrations/' . $migration_file;
        
        if (!file_exists($migration_path)) {
            echo "⚠️  Migration file not found: {$migration_file}\n";
            continue;
        }
        
        echo "Running migration: {$migration_file}...\n";
        
        // Read the SQL file
        $sql = file_get_contents($migration_path);
        
        // Split by semicolons and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt)
        );
        
        foreach ($statements as $statement) {
            try {
                $pdo->exec($statement);
                echo "  ✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // If error is about table/column already existing, that's OK
                if (strpos($e->getMessage(), 'already exists') !== false || 
                    strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "  ℹ️  Column/table already exists (skipped): " . substr($statement, 0, 50) . "...\n";
                } else {
                    echo "  ✗ Error: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "\n";
    }
    
    // Verify schema
    echo "Verifying database schema...\n\n";
    
    // Check products table columns
    echo "Products table columns:\n";
    $products_cols = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products_cols as $col) {
        echo "  ✓ " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\nUsers table columns:\n";
    $users_cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users_cols as $col) {
        echo "  ✓ " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    // Count data
    echo "\n======================================\n";
    echo "Data Verification\n";
    echo "======================================\n\n";
    
    $product_count = $pdo->query("SELECT COUNT(*) as count FROM products")->fetch(PDO::FETCH_ASSOC)['count'];
    $user_count = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch(PDO::FETCH_ASSOC)['count'];
    $order_count = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Total Products: {$product_count}\n";
    echo "Total Users/Customers: {$user_count}\n";
    echo "Total Orders: {$order_count}\n";
    
    echo "\n✅ Database setup and verification complete!\n\n";
    echo "You can now safely access the admin dashboard to view all products and customers.\n";
    
} catch (Exception $e) {
    echo "\n❌ Setup failed: " . $e->getMessage() . "\n";
    die(1);
}
?>
