<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => true, 'wishlist' => []]);
    exit();
}

$user_id = (int) $_SESSION['user_id'];

try {
    $sql = "SELECT product_id FROM wishlist WHERE user_id = :uid";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $user_id]);
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode(['success' => true, 'wishlist' => $wishlist_items]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching wishlist.']);
}
