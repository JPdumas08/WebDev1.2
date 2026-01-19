<?php
/**
 * API: Mark Admin Notifications as Read
 */
require_once __DIR__ . '/../init_session.php';
require_once __DIR__ . '/../db.php';
init_session();

header('Content-Type: application/json');

// Check admin auth
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    // Mark all as read
    $sql = "UPDATE admin_notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $affected = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => 'All notifications marked as read',
        'count' => $affected
    ]);
    
} catch (Exception $e) {
    error_log('Mark admin notifications read error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>
