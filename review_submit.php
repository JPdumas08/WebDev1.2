<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';
init_session();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to write a review.']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$product_id = (int) ($_POST['product_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$review_content = trim($_POST['review_content'] ?? '');

if ($product_id <= 0 || $rating < 1 || $rating > 5 || $review_content === '') {
    echo json_encode(['success' => false, 'message' => 'Please provide a rating (1-5) and your review.']);
    exit();
}

// Ensure product exists and is active
$product_sql = "SELECT product_id FROM products WHERE product_id = :pid AND (is_archived IS NULL OR is_archived = 0) LIMIT 1";
$product_stmt = $pdo->prepare($product_sql);
$product_stmt->execute([':pid' => $product_id]);
$product = $product_stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit();
}

// Verify the user purchased this product on a completed order
$verified_sql = "
    SELECT oi.order_id
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = :uid
      AND oi.product_id = :pid
      AND o.payment_status = 'paid'
      AND o.order_status IN ('processing','shipped','delivered')
    ORDER BY o.created_at DESC
    LIMIT 1
";
$verified_stmt = $pdo->prepare($verified_sql);
$verified_stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
$order = $verified_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Reviews are limited to verified purchases of this product.']);
    exit();
}

$pdo->beginTransaction();
try {
    // Check if user already reviewed this product
    $existing_sql = "SELECT review_id, status FROM product_reviews WHERE user_id = :uid AND product_id = :pid LIMIT 1";
    $existing_stmt = $pdo->prepare($existing_sql);
    $existing_stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
    $existing = $existing_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $update_sql = "
            UPDATE product_reviews
               SET rating = :rating,
                   review_content = :content,
                   status = 'pending',
                   is_verified = 1,
                   order_id = :oid,
                   updated_at = NOW()
             WHERE review_id = :rid
             LIMIT 1";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([
            ':rating' => $rating,
            ':content' => $review_content,
            ':oid' => $order['order_id'],
            ':rid' => $existing['review_id']
        ]);
        $message = 'Review updated and sent for approval.';
    } else {
        $insert_sql = "
            INSERT INTO product_reviews
                (product_id, user_id, order_id, rating, review_content, status, is_verified, created_at, updated_at)
            VALUES
                (:pid, :uid, :oid, :rating, :content, 'pending', 1, NOW(), NOW())";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([
            ':pid' => $product_id,
            ':uid' => $user_id,
            ':oid' => $order['order_id'],
            ':rating' => $rating,
            ':content' => $review_content
        ]);
        $message = 'Review submitted for approval.';
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message, 'status' => 'pending']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Review submit error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to save review right now.']);
}
