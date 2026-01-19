<?php
/**
 * API: Reply to Contact Message
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
$reply_text = trim($_POST['reply_text'] ?? '');
$admin_id = (int)$_SESSION['admin_user_id'];

// Validation
if (!$message_id) {
    echo json_encode(['success' => false, 'error' => 'Message ID is required']);
    exit;
}

if (strlen($reply_text) < 10) {
    echo json_encode(['success' => false, 'error' => 'Reply must be at least 10 characters']);
    exit;
}

try {
    // Check if message exists
    $check_sql = "SELECT message_id, user_id, name, email, subject, status FROM contact_messages WHERE message_id = :id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':id' => $message_id]);
    $message = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        echo json_encode(['success' => false, 'error' => 'Message not found']);
        exit;
    }
    
    if ($message['status'] === 'closed') {
        echo json_encode(['success' => false, 'error' => 'Cannot reply to closed message']);
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Insert reply
    $reply_sql = "INSERT INTO message_replies (message_id, admin_id, reply_text, created_at) 
                  VALUES (:message_id, :admin_id, :reply_text, NOW())";
    $reply_stmt = $pdo->prepare($reply_sql);
    $reply_stmt->execute([
        ':message_id' => $message_id,
        ':admin_id' => $admin_id,
        ':reply_text' => $reply_text
    ]);
    
    // Update message status to 'replied'
    $update_msg_sql = "UPDATE contact_messages SET status = 'replied', updated_at = NOW() WHERE message_id = :id";
    $update_msg_stmt = $pdo->prepare($update_msg_sql);
    $update_msg_stmt->execute([':id' => $message_id]);
    
    // Create notification for user (if they have an account)
    if ($message['user_id']) {
        // Check if notifications table has related_id column
        $check_col = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'related_id'")->fetch();
        
        if ($check_col) {
            // New schema with related_id
            $notif_sql = "INSERT INTO notifications (user_id, type, title, message, related_id, is_read, created_at) 
                          VALUES (:user_id, 'message_reply', :title, :message, :related_id, 0, NOW())";
            $notif_stmt = $pdo->prepare($notif_sql);
            $notif_stmt->execute([
                ':user_id' => $message['user_id'],
                ':title' => 'Reply to Your Message',
                ':message' => 'An admin has replied to your message: "' . $message['subject'] . '"',
                ':related_id' => $message_id
            ]);
        } else {
            // Old schema without related_id
            $notif_sql = "INSERT INTO notifications (user_id, title, message, type, is_read, created_at) 
                          VALUES (:user_id, :title, :message, 'info', 0, NOW())";
            $notif_stmt = $pdo->prepare($notif_sql);
            $notif_stmt->execute([
                ':user_id' => $message['user_id'],
                ':title' => 'Reply to Your Message',
                ':message' => 'An admin has replied to your message: "' . $message['subject'] . '"'
            ]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reply sent successfully',
        'message_id' => $message_id
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Reply message error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>
