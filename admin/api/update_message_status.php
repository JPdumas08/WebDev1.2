<?php
/**
 * API: Update Message Status
 */
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

// Check admin auth
if (!isset($_SESSION['admin_user_id']) || !$_SESSION['admin_user_id']) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$message_id = (int)($_POST['message_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

// Validation
if (!$message_id) {
    echo json_encode(['success' => false, 'error' => 'Message ID is required']);
    exit;
}

$valid_statuses = ['new', 'read', 'replied', 'closed'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    // Check if message exists
    $check_sql = "SELECT message_id, status FROM contact_messages WHERE message_id = :id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':id' => $message_id]);
    $message = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        echo json_encode(['success' => false, 'error' => 'Message not found']);
        exit;
    }
    
    // Update status
    $update_sql = "UPDATE contact_messages SET status = :status, updated_at = NOW() WHERE message_id = :id";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([
        ':status' => $status,
        ':id' => $message_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'message_id' => $message_id,
        'new_status' => $status
    ]);
    
} catch (Exception $e) {
    error_log('Update message status error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>
