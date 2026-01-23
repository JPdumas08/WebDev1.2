<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';
init_session();

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php?redirect=my_messages');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Get specific message if viewing detail
$viewing_message_id = isset($_GET['view']) ? (int)$_GET['view'] : null;
$viewing_message = null;
$message_replies = [];

if ($viewing_message_id) {
    // Fetch the specific message with replies
    $msg_sql = "SELECT * FROM contact_messages WHERE message_id = :id AND user_id = :uid";
    $msg_stmt = $pdo->prepare($msg_sql);
    $msg_stmt->execute([':id' => $viewing_message_id, ':uid' => $user_id]);
    $viewing_message = $msg_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($viewing_message) {
        // Fetch replies for this message
        $replies_sql = "SELECT mr.*, au.username as admin_name 
                       FROM message_replies mr 
                       JOIN admin_users au ON mr.admin_id = au.id 
                       WHERE mr.message_id = :id 
                       ORDER BY mr.created_at ASC";
        $replies_stmt = $pdo->prepare($replies_sql);
        $replies_stmt->execute([':id' => $viewing_message_id]);
        $message_replies = $replies_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Fetch all user messages
$messages_sql = "SELECT * FROM contact_messages WHERE user_id = :uid ORDER BY created_at DESC";
$messages_stmt = $pdo->prepare($messages_sql);
$messages_stmt->execute([':uid' => $user_id]);
$all_messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My Messages - Jeweluxe';
include 'includes/header.php';
?>

<style>
    .message-item {
        transition: all 0.2s;
        cursor: pointer;
    }
    .message-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }
    .message-item.active {
        background-color: #e7f3ff;
        border-left: 4px solid #0d6efd;
    }
    .message-replied {
        background-color: #d1e7dd;
        border-left: 4px solid #198754;
    }
    .message-closed {
        background-color: #f8f9fa;
        opacity: 0.7;
    }
    .reply-bubble {
        background: #e7f3ff;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .admin-reply-bubble {
        background: #d1e7dd;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        border-left: 4px solid #198754;
    }
</style>

<body class="order-history-page">
    <section class="orders-hero">
        <div class="container-xl">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light btn-sm" onclick="window.history.back();" type="button" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h1 class="mb-0 text-white">My Messages</h1>
            </div>
        </div>
    </section>

    <div class="orders-wrapper py-5">
        <div class="container-xl">
            <div class="row g-4">
                <?php 
                $active_page = 'messages';
                include 'includes/account_sidebar.php'; 
                ?>
                
                <main class="col-lg-9">
            <?php if (empty($all_messages)): ?>
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body text-center py-5">
                        <div class="mb-3" style="font-size: 3rem;">💬</div>
                        <h5 class="mb-2">No messages yet</h5>
                        <p class="text-muted mb-4">You haven't sent any messages to us.</p>
                        <a href="contactus.php" class="btn btn-primary">
                            <i class="fas fa-envelope me-2"></i>Send a Message
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Messages List -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm border-0 rounded-4">
                            <div class="card-header bg-white border-0 p-4">
                                <h5 class="mb-0">Your Messages</h5>
                                <small class="text-muted"><?php echo count($all_messages); ?> total</small>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($all_messages as $msg): ?>
                                        <a href="?view=<?php echo $msg['message_id']; ?>" 
                                           id="message-<?php echo $msg['message_id']; ?>"
                                           class="list-group-item message-item <?php echo $viewing_message_id == $msg['message_id'] ? 'active' : ''; ?> <?php echo $msg['status'] === 'replied' ? 'message-replied' : ''; ?> <?php echo $msg['status'] === 'closed' ? 'message-closed' : ''; ?> p-3 text-decoration-none">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($msg['subject']); ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y', strtotime($msg['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <?php
                                                $status_badges = [
                                                    'new' => '<span class="badge bg-primary">New</span>',
                                                    'read' => '<span class="badge bg-info">Read</span>',
                                                    'replied' => '<span class="badge bg-success">Replied</span>',
                                                    'closed' => '<span class="badge bg-secondary">Closed</span>'
                                                ];
                                                echo $status_badges[$msg['status']] ?? '';
                                                ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message Detail -->
                    <div class="col-lg-8">
                        <?php if ($viewing_message): ?>
                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-header bg-white border-0 p-4">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($viewing_message['subject']); ?></h5>
                                    <small class="text-muted">
                                        Sent on <?php echo date('F j, Y g:i A', strtotime($viewing_message['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="card-body p-4">
                                    <h6 class="mb-3">Your Message:</h6>
                                    <div class="reply-bubble">
                                        <?php echo nl2br(htmlspecialchars($viewing_message['message'])); ?>
                                    </div>

                                    <?php if (!empty($message_replies)): ?>
                                        <hr class="my-4">
                                        <h6 class="mb-3">Admin Replies:</h6>
                                        <?php foreach ($message_replies as $reply): ?>
                                            <div class="admin-reply-bubble">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong>
                                                        <i class="fas fa-user-shield text-success me-2"></i>
                                                        Admin Reply
                                                    </strong>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y g:i A', strtotime($reply['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <?php echo nl2br(htmlspecialchars($reply['reply_text'])); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php if ($viewing_message['status'] !== 'closed'): ?>
                                        <div class="alert alert-info mt-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <?php if (empty($message_replies)): ?>
                                                We've received your message and will reply soon!
                                            <?php else: ?>
                                                Need more help? Send us another message from the <a href="contactus.php">contact page</a>.
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-secondary mt-4">
                                            <i class="fas fa-lock me-2"></i>
                                            This conversation is closed. Need more help? <a href="contactus.php">Send a new message</a>.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card shadow-sm border-0 rounded-4">
                                <div class="card-body text-center py-5">
                                    <div class="mb-3" style="font-size: 3rem;">📧</div>
                                    <h5 class="mb-2">Select a message</h5>
                                    <p class="text-muted">Click on a message from the list to view details and replies.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
                </main>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
    // Auto-scroll and highlight message if coming from notification
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const viewMessageId = urlParams.get('view');
        
        if (viewMessageId) {
            // Mark the message as active (already done server-side, but ensure it's visible)
            const messageElement = document.getElementById('message-' + viewMessageId);
            if (messageElement) {
                // Scroll to the message in the sidebar
                messageElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Add a highlight animation
                messageElement.style.transition = 'background-color 0.3s';
                messageElement.style.backgroundColor = '#e7f3ff';
                setTimeout(function() {
                    messageElement.style.backgroundColor = '';
                }, 2000);
            }
        }
    });
    </script>
</body>
</html>
