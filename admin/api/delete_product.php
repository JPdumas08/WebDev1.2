<?php
/**
 * API: Delete Product
 */
require_once __DIR__ . '/../init_session.php';
require_once __DIR__ . '/../db.php';

// Check admin auth
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$user_id = (int)$_SESSION['user_id'];
$user_sql = "SELECT is_admin FROM users WHERE user_id = :uid";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute([':uid' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['is_admin']) {
    http_response_code(403);
    exit('Unauthorized');
}

$product_id = (int)($_GET['id'] ?? 0);

if (!$product_id) {
    header('Location: ../products.php');
    exit();
}

try {
    $delete_sql = "DELETE FROM products WHERE product_id = :pid";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([':pid' => $product_id]);

    error_log("Product {$product_id} deleted by admin {$user_id}");
    $_SESSION['message'] = 'Product deleted successfully';
} catch (Exception $e) {
    error_log("Product delete error: " . $e->getMessage());
    $_SESSION['error'] = 'Error deleting product';
}

header('Location: ../products.php');
exit();
?>
