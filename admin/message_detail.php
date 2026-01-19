<?php
/**
 * Admin Message Detail View
 * View individual message and reply
 */
require_once __DIR__ . '/auth.php';

$message_id = (int)($_GET['id'] ?? 0);

if (!$message_id) {
    header('Location: messages.php');
    exit;
}

try {
    // Fetch message
    $msg_sql = "SELECT cm.*, u.username
                FROM contact_messages cm
                LEFT JOIN users u ON cm.user_id = u.user_id
                WHERE cm.message_id = :id";
    $msg_stmt = $pdo->prepare($msg_sql);
    $msg_stmt->execute([':id' => $message_id]);
    $message = $msg_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        header('Location: messages.php');
        exit;
    }
    
    // Mark as read if new
    if ($message['status'] === 'new') {
        $update_sql = "UPDATE contact_messages SET status = 'read' WHERE message_id = :id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([':id' => $message_id]);
        $message['status'] = 'read';
    }
    
    // Fetch replies
    $replies_sql = "SELECT mr.*, au.username as admin_name
                    FROM message_replies mr
                    JOIN admin_users au ON mr.admin_id = au.id
                    WHERE mr.message_id = :id
                    ORDER BY mr.created_at ASC";
    $replies_stmt = $pdo->prepare($replies_sql);
    $replies_stmt->execute([':id' => $message_id]);
    $replies = $replies_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Message detail error: " . $e->getMessage());
    header('Location: messages.php');
    exit;
}

$page_title = 'Message Details';
include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <a href="messages.php" class="btn btn-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left"></i> Back to Messages
        </a>
        <h1 class="page-title">Message Details</h1>
    </div>
</div>

<!-- Original Message -->
<div class="admin-card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="card-title mb-0">Original Message</h2>
            <div>
                <?php
                $status_classes = [
                    'new' => 'badge-primary',
                    'read' => 'badge-info',
                    'replied' => 'badge-success',
                    'closed' => 'badge-secondary'
                ];
                $status_class = $status_classes[$message['status']] ?? 'badge-secondary';
                ?>
                <span class="badge-status <?php echo $status_class; ?>">
                    <?php echo ucfirst($message['status']); ?>
                </span>
            </div>
        </div>
    </div>
    <div style="padding: 1.5rem;">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>From:</strong> <?php echo htmlspecialchars($message['name']); ?>
                <?php if ($message['username']): ?>
                    <span class="badge bg-light text-dark ms-2">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($message['username']); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <strong>Email:</strong> 
                <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>">
                    <?php echo htmlspecialchars($message['email']); ?>
                </a>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Subject:</strong> <?php echo htmlspecialchars($message['subject']); ?>
            </div>
            <div class="col-md-6">
                <strong>Received:</strong> <?php echo date('F j, Y g:i A', strtotime($message['created_at'])); ?>
            </div>
        </div>
        <div class="mb-3">
            <strong>Message:</strong>
            <div style="margin-top: 0.5rem; padding: 1rem; background: var(--admin-bg-secondary); border-radius: 8px; white-space: pre-wrap;">
                <?php echo htmlspecialchars($message['message']); ?>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="d-flex gap-2">
            <?php if ($message['status'] !== 'closed'): ?>
                <button type="button" class="btn btn-secondary" onclick="updateStatus('closed')">
                    <i class="fas fa-times-circle"></i> Close Message
                </button>
            <?php endif; ?>
            <?php if ($message['status'] === 'closed'): ?>
                <button type="button" class="btn btn-info" onclick="updateStatus('read')">
                    <i class="fas fa-redo"></i> Reopen Message
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Replies -->
<?php if (!empty($replies)): ?>
    <div class="admin-card mb-4">
        <div class="card-header">
            <h2 class="card-title mb-0">Replies (<?php echo count($replies); ?>)</h2>
        </div>
        <div style="padding: 1.5rem;">
            <?php foreach ($replies as $reply): ?>
                <div style="padding: 1rem; background: var(--admin-bg-secondary); border-radius: 8px; margin-bottom: 1rem;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>
                            <i class="fas fa-user-shield text-primary"></i> <?php echo htmlspecialchars($reply['admin_name']); ?>
                        </strong>
                        <small class="text-muted">
                            <?php echo date('F j, Y g:i A', strtotime($reply['created_at'])); ?>
                        </small>
                    </div>
                    <div style="white-space: pre-wrap;">
                        <?php echo htmlspecialchars($reply['reply_text']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Reply Form -->
<?php if ($message['status'] !== 'closed'): ?>
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title mb-0">Send Reply</h2>
        </div>
        <div style="padding: 1.5rem;">
            <form id="replyForm" onsubmit="submitReply(event)" novalidate>
                <div class="mb-3">
                    <label class="form-label">Your Reply</label>
                    <textarea class="form-control" id="reply_text" rows="6" required 
                              placeholder="Type your reply here..."></textarea>
                    <div class="invalid-feedback">Please enter your reply (at least 10 characters).</div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    The customer will be notified of your reply through their notification center.
                </div>
                <button type="submit" class="btn btn-primary" id="replyBtn">
                    <i class="fas fa-paper-plane"></i> Send Reply
                </button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-secondary">
        <i class="fas fa-lock"></i> This message is closed. Reopen it to send replies.
    </div>
<?php endif; ?>

<script>
async function submitReply(e) {
    e.preventDefault();
    
    const replyBtn = document.getElementById('replyBtn');
    const replyTextarea = document.getElementById('reply_text');
    const replyText = replyTextarea.value.trim();
    
    // Clear previous validation
    replyTextarea.classList.remove('is-invalid');
    
    if (replyText.length < 10) {
        replyTextarea.classList.add('is-invalid');
        replyTextarea.focus();
        return;
    }
    
    replyBtn.disabled = true;
    replyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    try {
        const formData = new FormData();
        formData.append('message_id', <?php echo $message_id; ?>);
        formData.append('reply_text', replyText);
        
        const response = await fetch('api/reply_message.php', {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        console.log('Response text:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response was:', text);
            AdminModal.error('Server returned invalid response. Check console for details.');
            replyBtn.disabled = false;
            replyBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reply';
            return;
        }
        
        if (data.success) {
            AdminModal.success('Reply sent successfully!');
            setTimeout(() => location.reload(), 1500);
        } else {
            AdminModal.error(data.error || 'Failed to send reply');
            replyBtn.disabled = false;
            replyBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reply';
        }
    } catch (error) {
        console.error('Reply error:', error);
        AdminModal.error('An error occurred. Please try again.');
        replyBtn.disabled = false;
        replyBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reply';
    }
}

async function updateStatus(newStatus) {
    const title = newStatus === 'closed' ? '⚠️ Close Message' : '🔄 Reopen Message';
    const message = `Are you sure you want to ${newStatus === 'closed' ? 'close' : 'reopen'} this message?`;
    
    AdminModal.show(title, message, async function() {
        try {
            const formData = new FormData();
            formData.append('message_id', <?php echo $message_id; ?>);
            formData.append('status', newStatus);
            
            const response = await fetch('api/update_message_status.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                AdminModal.error(data.error || 'Failed to update status');
            }
        } catch (error) {
            console.error('Status update error:', error);
            AdminModal.error('An error occurred. Please try again.');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
