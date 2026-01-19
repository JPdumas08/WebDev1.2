<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php?redirect=notifications');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notification_id = (int) $_POST['notification_id'];
    $update_sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = :nid AND user_id = :uid";
    $stmt = $pdo->prepare($update_sql);
    $stmt->execute([':nid' => $notification_id, ':uid' => $user_id]);
}

// Handle mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $update_all_sql = "UPDATE notifications SET is_read = 1 WHERE user_id = :uid";
    $stmt = $pdo->prepare($update_all_sql);
    $stmt->execute([':uid' => $user_id]);
}

// Handle delete notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification'])) {
    $notification_id = (int) $_POST['notification_id'];
    $delete_sql = "DELETE FROM notifications WHERE notification_id = :nid AND user_id = :uid";
    $stmt = $pdo->prepare($delete_sql);
    $stmt->execute([':nid' => $notification_id, ':uid' => $user_id]);
}

// Fetch notifications
$notifications_sql = "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC";
$stmt = $pdo->prepare($notifications_sql);
$stmt->execute([':uid' => $user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread
$unread_count = count(array_filter($notifications, fn($n) => !$n['is_read']));

// Sample notifications if none exist (for demo)
if (empty($notifications)) {
    // First check if related_id column exists
    $columns = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'related_id'")->fetch();
    
    if ($columns) {
        // New schema with related_id
        $sample_notifications = [
            ['title' => 'Welcome to Jeweluxe!', 'message' => 'Thank you for joining us. Start shopping for exquisite jewelry pieces.', 'type' => 'system'],
            ['title' => 'Order Updates', 'message' => 'We will notify you about your order status here.', 'type' => 'system'],
        ];
        
        foreach ($sample_notifications as $notif) {
            $insert_sql = "INSERT INTO notifications (user_id, type, title, message, related_id, is_read, created_at) 
                           VALUES (:uid, :type, :title, :message, NULL, 0, NOW())";
            $stmt = $pdo->prepare($insert_sql);
            $stmt->execute([
                ':uid' => $user_id,
                ':type' => $notif['type'],
                ':title' => $notif['title'],
                ':message' => $notif['message']
            ]);
        }
    } else {
        // Old schema without related_id
        $sample_notifications = [
            ['title' => 'Welcome to Jeweluxe!', 'message' => 'Thank you for joining us. Start shopping for exquisite jewelry pieces.', 'type' => 'system'],
            ['title' => 'Order Updates', 'message' => 'We will notify you about your order status here.', 'type' => 'system'],
        ];
        
        foreach ($sample_notifications as $notif) {
            $insert_sql = "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                           VALUES (:uid, :type, :title, :message, 0, NOW())";
            $stmt = $pdo->prepare($insert_sql);
            $stmt->execute([
                ':uid' => $user_id,
                ':type' => $notif['type'],
                ':title' => $notif['title'],
                ':message' => $notif['message']
            ]);
        }
    }
    
    // Refresh notifications
    $stmt = $pdo->prepare($notifications_sql);
    $stmt->execute([':uid' => $user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $unread_count = count($notifications);
}
?>

<?php
$pageTitle = 'Notifications - Jeweluxe';
include 'includes/header.php';
?>
<link rel="stylesheet" href="styles.css">
<style>
    .notification-item {
            transition: background-color 0.2s;
            position: relative;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-item.clickable {
            cursor: pointer;
        }
        .notification-item.clickable:hover {
            background-color: #e8f4ff;
        }
        .notification-unread {
            background-color: #e7f3ff;
            border-left: 4px solid #0d6efd;
        }
        .notification-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
<body class="order-history-page">

    <section class="orders-hero">
        <div class="container-xl">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light btn-sm" onclick="window.history.back();" type="button" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h1 class="mb-0 text-white">Notifications</h1>
            </div>
        </div>
    </section>

    <div class="orders-wrapper py-5">
        <div class="container-xl">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center p-4">
                            <div>
                                <h5 class="mb-0">All Notifications</h5>
                                <?php if ($unread_count > 0): ?>
                                    <small class="text-muted"><?php echo $unread_count; ?> unread</small>
                                <?php endif; ?>
                            </div>
                            <?php if ($unread_count > 0): ?>
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                                        Mark All as Read
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($notifications)): ?>
                                <div class="text-center py-5">
                                    <div class="mb-3" style="font-size: 3rem;">🔔</div>
                                    <h5 class="mb-2">No notifications yet</h5>
                                    <p class="text-muted">We'll notify you when something arrives.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($notifications as $notification): 
                                        // Determine notification link
                                        $notification_link = '';
                                        $is_clickable = false;
                                        
                                        // Check if related_id exists in notification
                                        $related_id = isset($notification['related_id']) ? $notification['related_id'] : null;
                                        
                                        // Get notification type
                                        $notif_type = isset($notification['type']) ? $notification['type'] : '';
                                        
                                        switch ($notif_type) {
                                            case 'order_status':
                                            case 'order_update':
                                                if ($related_id) {
                                                    $notification_link = 'order_history.php?view=' . $related_id;
                                                    $is_clickable = true;
                                                } else {
                                                    // If no related_id, go to general order history
                                                    $notification_link = 'order_history.php';
                                                    $is_clickable = true;
                                                }
                                                break;
                                            case 'message_reply':
                                            case 'info': // Handle old schema where message_reply was stored as 'info'
                                                // Check if this is a message reply by title
                                                if (stripos($notification['title'], 'reply') !== false || stripos($notification['title'], 'message') !== false) {
                                                    $notification_link = 'my_messages.php';
                                                    $is_clickable = true;
                                                }
                                                break;
                                        }
                                    ?>
                                        <div class="list-group-item notification-item <?php echo !$notification['is_read'] ? 'notification-unread' : ''; ?> <?php echo $is_clickable ? 'clickable' : ''; ?> p-4" 
                                             <?php if ($is_clickable): ?>
                                             onclick="handleNotificationClick(event, '<?php echo htmlspecialchars($notification_link, ENT_QUOTES); ?>', <?php echo $notification['notification_id']; ?>)"
                                             <?php endif; ?>
                                             data-notification-id="<?php echo $notification['notification_id']; ?>">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                        <?php if (!$notification['is_read']): ?>
                                                            <span class="badge bg-primary notification-badge">New</span>
                                                        <?php endif; ?>
                                                        <?php
                                                        // Map notification types to badge colors
                                                        $badge_class = 'secondary';
                                                        $type_display = ucfirst(str_replace('_', ' ', $notification['type']));
                                                        
                                                        // Check if it's a message reply by title (for old 'info' type)
                                                        $is_message_reply = ($notification['type'] === 'message_reply') || 
                                                                          ($notification['type'] === 'info' && 
                                                                           (stripos($notification['title'], 'reply') !== false || 
                                                                            stripos($notification['title'], 'message') !== false));
                                                        
                                                        if ($is_message_reply) {
                                                            $badge_class = 'success';
                                                            $type_display = 'Reply';
                                                        } else {
                                                            switch ($notification['type']) {
                                                                case 'order_status': 
                                                                    $badge_class = 'primary'; 
                                                                    $type_display = 'Order Status';
                                                                    break;
                                                                case 'order_update': 
                                                                    $badge_class = 'warning'; 
                                                                    $type_display = 'Order Update';
                                                                    break;
                                                                case 'system': 
                                                                    $badge_class = 'info'; 
                                                                    $type_display = 'System';
                                                                    break;
                                                                case 'promotion': 
                                                                    $badge_class = 'danger'; 
                                                                    $type_display = 'Promotion';
                                                                    break;
                                                                case 'info':
                                                                    $badge_class = 'info';
                                                                    $type_display = 'Info';
                                                                    break;
                                                            }
                                                        }
                                                        ?>
                                                        <span class="badge bg-<?php echo $badge_class; ?> notification-badge">
                                                            <?php echo $type_display; ?>
                                                        </span>
                                                    </div>
                                                    <p class="mb-2 text-muted"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                                    </small>
                                                    <?php if (isset($notification['related_id']) && $notification['related_id'] && isset($notification['type']) && in_array($notification['type'], ['order_status', 'order_update'])): ?>
                                                        <div class="mt-2">
                                                            <a href="order_history.php?view=<?php echo $notification['related_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-external-link-alt me-1"></i>View Order
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex gap-2 ms-3">
                                                    <?php if (!$notification['is_read']): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                                            <button type="submit" name="mark_read" class="btn btn-sm btn-outline-primary" title="Mark as read">
                                                                ✓
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" class="d-inline delete-notification-form">
                                                        <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-notification-btn" title="Delete">
                                                            ✕
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
        // Handle notification click
        function handleNotificationClick(event, link, notificationId) {
            // Don't trigger if clicking on buttons
            if (event.target.closest('button') || event.target.closest('form') || event.target.closest('a')) {
                return;
            }
            
            // Mark as read via AJAX
            const formData = new FormData();
            formData.append('notification_id', notificationId);
            formData.append('mark_read', '1');
            
            fetch('', {
                method: 'POST',
                body: formData
            }).then(() => {
                // Navigate to the link
                window.location.href = link;
            }).catch(error => {
                console.error('Error marking notification as read:', error);
                // Still navigate even if marking as read fails
                window.location.href = link;
            });
        }
        
        // Handle notification delete with custom confirmation
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-notification-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const form = this.closest('.delete-notification-form');
                    const notificationId = form.querySelector('input[name="notification_id"]').value;
                    
                    ConfirmModal.show(
                        '⚠️ Delete Notification',
                        'Are you sure you want to delete this notification?',
                        function() {
                            const formData = new FormData();
                            formData.append('notification_id', notificationId);
                            formData.append('delete_notification', '1');
                            
                            fetch('', {
                                method: 'POST',
                                body: formData
                            }).then(() => {
                                ToastNotification.success('Notification deleted successfully.');
                                setTimeout(() => location.reload(), 1500);
                            }).catch(error => {
                                ToastNotification.error('Error deleting notification.');
                                console.error('Error:', error);
                            });
                        }
                    );
                });
            });
        });
    </script>