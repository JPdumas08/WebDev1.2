<?php
/**
 * Admin Notifications Page
 * View all admin notifications
 */
require_once __DIR__ . '/auth.php';

$page_title = 'Notifications';

$page = (int)($_GET['page'] ?? 1);
$per_page = 30;
$offset = ($page - 1) * $per_page;

try {
    // Count total notifications
    $count_sql = "SELECT COUNT(*) as total FROM admin_notifications";
    $total = (int)$pdo->query($count_sql)->fetchColumn();
    $pages = ceil($total / $per_page);
    
    // Fetch notifications
    $notifs_sql = "SELECT * FROM admin_notifications 
                   ORDER BY created_at DESC 
                   LIMIT :limit OFFSET :offset";
    $notifs_stmt = $pdo->prepare($notifs_sql);
    $notifs_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $notifs_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $notifs_stmt->execute();
    $notifications = $notifs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unread count
    $unread_count = (int)$pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0")->fetchColumn();
    
} catch (Exception $e) {
    error_log("Admin notifications query error: " . $e->getMessage());
    $notifications = [];
    $total = 0;
    $pages = 1;
    $unread_count = 0;
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Admin Notifications</h1>
        <p class="page-subtitle">Stay updated with system alerts and new activity</p>
    </div>
    <?php if ($unread_count > 0): ?>
        <button onclick="markAllAdminNotifsRead()" class="btn btn-primary">
            <i class="fas fa-check-double me-2"></i>Mark All as Read (<?php echo $unread_count; ?>)
        </button>
    <?php endif; ?>
</div>

<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title mb-0">Recent Notifications</h2>
    </div>

    <?php if (!empty($notifications)): ?>
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $notif): 
                $icon_class = [
                    'new_message' => 'fa-envelope text-primary',
                    'new_order' => 'fa-shopping-cart text-success',
                    'low_stock' => 'fa-exclamation-triangle text-warning',
                    'system' => 'fa-cog text-info'
                ][$notif['type']] ?? 'fa-bell text-secondary';
            ?>
                <div class="list-group-item <?php echo $notif['is_read'] ? '' : 'bg-light'; ?>" 
                     style="<?php echo $notif['is_read'] ? '' : 'border-left: 4px solid var(--admin-primary);'; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas <?php echo $icon_class; ?> fa-lg me-3"></i>
                                <div>
                                    <h6 class="mb-0">
                                        <?php echo htmlspecialchars($notif['title']); ?>
                                        <?php if (!$notif['is_read']): ?>
                                            <span class="badge badge-primary ms-2">New</span>
                                        <?php endif; ?>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('F j, Y g:i A', strtotime($notif['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            <p class="mb-0 ms-5"><?php echo htmlspecialchars($notif['message']); ?></p>
                            
                            <?php if ($notif['related_id']): ?>
                                <div class="mt-2 ms-5">
                                    <?php if ($notif['type'] === 'new_message'): ?>
                                        <a href="message_detail.php?id=<?php echo $notif['related_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i>View Message
                                        </a>
                                    <?php elseif ($notif['type'] === 'new_order'): ?>
                                        <a href="order_detail.php?id=<?php echo $notif['related_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i>View Order
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
            <div style="padding: 2rem 1.5rem; text-align: center; border-top: 1px solid var(--admin-border); display: flex; justify-content: center; gap: .5rem;">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="btn <?php echo $page === $i ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="padding: 3rem; text-align: center; color: var(--admin-text-muted);">
            <i class="fas fa-bell-slash" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <p>No notifications yet.</p>
        </div>
    <?php endif; ?>
</div>

<script>
async function markAllAdminNotifsRead() {
    if (!confirm('Mark all notifications as read?')) {
        return;
    }
    
    try {
        const response = await fetch('api/mark_admin_notifications_read.php', {
            method: 'POST'
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
</script>

<?php include 'includes/footer.php'; ?>
