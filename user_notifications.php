<?php
/**
 * User Notifications Page
 * View all notifications
 */
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
init_session();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;


$pages = 1;
try {
    // Count total notifications
    $count_sql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = :uid";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute([':uid' => $user_id]);
    $total = (int)$count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $pages = max(1, ceil($total / $per_page));
    $notif_sql = "SELECT * FROM notifications 
                  WHERE user_id = :uid 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";
    $notif_stmt = $pdo->prepare($notif_sql);
    $notif_stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
    $notif_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $notif_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $notif_stmt->execute();
    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
    // Get unread count
    $unread_sql = "SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0";
    $unread_stmt = $pdo->prepare($unread_sql);
    $unread_stmt->execute([':uid' => $user_id]);
    $unread_count = (int)$unread_stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Notifications query error: " . $e->getMessage());
    $notifications = [];
    $total = 0;
    $pages = 1;
    $unread_count = 0;
}

$pageTitle = 'Jeweluxe - Notifications';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-bell me-2"></i>Notifications</h1>
                <?php if ($unread_count > 0): ?>
                    <button type="button" class="btn btn-primary" id="markAllBtn">
                        <i class="fas fa-check-double me-2"></i>Mark All as Read
                    </button>
                <?php endif; ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var btn = document.getElementById('markAllBtn');
                if (btn) {
                    btn.addEventListener('click', showMarkAllModal);
                }
            });

            function showMarkAllModal() {
                var modal = new bootstrap.Modal(document.getElementById('markAllModal'));
                modal.show();
            }
            </script>
            <!-- Mark All as Read Modal -->
            <div class="modal fade" id="markAllModal" tabindex="-1" aria-labelledby="markAllModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="markAllModalLabel"><i class="fas fa-check-double me-2"></i>Mark All as Read</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to mark all notifications as read?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="markAllAsRead(); document.getElementById('markAllModal').querySelector('.btn-close').click();">Yes, Mark All</button>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            
            <?php if ($unread_count > 0): ?>
                <div class="alert alert-info">
                    You have <strong><?php echo $unread_count; ?></strong> unread notification<?php echo $unread_count !== 1 ? 's' : ''; ?>.
                </div>
            <?php endif; ?>
            
            <?php if (!empty($notifications)): ?>
                <div class="list-group">
                    <?php foreach ($notifications as $notif): 
                        $icon_class = [
                            'order_status' => 'fa-shopping-bag',
                            'order_update' => 'fa-edit',
                            'message_reply' => 'fa-reply',
                            'system' => 'fa-info-circle',
                            'promotion' => 'fa-tag'
                        ][$notif['type']] ?? 'fa-bell';
                        
                        $badge_class = [
                            'order_status' => 'bg-primary',
                            'order_update' => 'bg-warning',
                            'message_reply' => 'bg-success',
                            'system' => 'bg-info',
                            'promotion' => 'bg-danger'
                        ][$notif['type']] ?? 'bg-secondary';
                    ?>
                        <div class="list-group-item <?php echo $notif['is_read'] ? '' : 'list-group-item-action bg-light'; ?>" 
                             style="<?php echo $notif['is_read'] ? '' : 'border-left: 4px solid #0d6efd;'; ?>">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas <?php echo $icon_class; ?> me-2 text-primary" style="font-size: 1.2rem;"></i>
                                        <h5 class="mb-0"><?php echo htmlspecialchars($notif['title']); ?></h5>
                                        <?php if (!$notif['is_read']): ?>
                                            <span class="badge bg-primary ms-2">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-2"><?php echo htmlspecialchars($notif['message']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('F j, Y g:i A', strtotime($notif['created_at'])); ?>
                                    </small>
                                    
                                    <?php $relatedId = $notif['related_id'] ?? null; ?>
                                    <?php if ($relatedId && in_array($notif['type'], ['order_status', 'order_update'])): ?>
                                        <div class="mt-2">
                                            <a href="order_history.php?view=<?php echo $relatedId; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt me-1"></i>View Order
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!$notif['is_read']): ?>
                                    <button onclick="markAsRead(<?php echo $notif['notification_id']; ?>)" 
                                            class="btn btn-sm btn-outline-secondary ms-3">
                                        <i class="fas fa-check"></i> Mark Read
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $pages; $i++): ?>
                                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash" style="font-size: 4rem; color: #ccc;"></i>
                    <h3 class="mt-3">No notifications yet</h3>
                    <p class="text-muted">We'll notify you when there's something new!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
async function markAsRead(notifId) {
    try {
        const formData = new FormData();
        formData.append('notification_id', notifId);
        
        const response = await fetch('api_mark_notification_read.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to mark as read'));
        }
    } catch (error) {
        console.error('Mark read error:', error);
        alert('An error occurred');
    }
}

async function markAllAsRead() {
    try {
        const formData = new FormData();
        formData.append('mark_all', '1');
        const response = await fetch('api_mark_notification_read.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to mark all as read'));
        }
    } catch (error) {
        console.error('Mark all read error:', error);
        alert('An error occurred');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
