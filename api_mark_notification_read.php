<?php
/**
 * API: Mark Notification as Read
 */
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';
init_session();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    // Check if marking all as read
    if (isset($_POST['mark_all']) && $_POST['mark_all'] === '1') {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
        exit;
    }
    
    // Mark single notification
    $notification_id = (int)($_POST['notification_id'] ?? 0);
    
    if (!$notification_id) {
        echo json_encode(['success' => false, 'error' => 'Notification ID is required']);
        exit;
    }
    
    // Verify notification belongs to user
    $check_sql = "SELECT notification_id FROM notifications WHERE notification_id = :nid AND user_id = :uid";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':nid' => $notification_id, ':uid' => $user_id]);
    
    if (!$check_stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Notification not found']);
        exit;
    }
    
    // Mark as read
    $update_sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = :nid";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([':nid' => $notification_id]);
    
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    
} catch (Exception $e) {
    error_log('Mark notification read error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>
