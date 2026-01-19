<?php
/**
 * Admin Header & Top Navigation
 * Premium dashboard header with navigation and user menu
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Admin Dashboard'); ?> - Jeweluxe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="includes/admin_styles.css?v=<?php echo time(); ?>">
</head>
<body class="admin-body">
    <div class="admin-container">
        <!-- Admin Header -->
        <header class="admin-header">
            <div class="header-left">
                <a href="index.php" class="admin-logo">
                    <i class="fas fa-gem"></i>
                    <span>Jeweluxe Admin</span>
                </a>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <?php
                    // Get unread admin notification count
                    $admin_notif_sql = "SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0";
                    $admin_notif_count = (int)$pdo->query($admin_notif_sql)->fetchColumn();
                    ?>
                    <div class="dropdown">
                        <button class="header-icon-btn position-relative" title="Notifications" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php if ($admin_notif_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                    <?php echo $admin_notif_count > 99 ? '99+' : $admin_notif_count; ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow" style="width: 350px; max-height: 400px; overflow-y: auto;">
                            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Notifications</h6>
                                <?php if ($admin_notif_count > 0): ?>
                                    <a href="#" onclick="markAllAdminNotifsRead(); return false;" class="btn btn-sm btn-link">Mark all read</a>
                                <?php endif; ?>
                            </div>
                            <?php
                            // Fetch recent notifications
                            $recent_notifs_sql = "SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 10";
                            $recent_notifs = $pdo->query($recent_notifs_sql)->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (!empty($recent_notifs)):
                                foreach ($recent_notifs as $notif):
                                    $icon = [
                                        'new_message' => 'fa-envelope',
                                        'new_order' => 'fa-shopping-cart',
                                        'low_stock' => 'fa-exclamation-triangle',
                                        'system' => 'fa-cog'
                                    ][$notif['type']] ?? 'fa-bell';
                            ?>
                                <a href="#" class="dropdown-item <?php echo $notif['is_read'] ? '' : 'bg-light'; ?>" style="white-space: normal;">
                                    <div class="d-flex align-items-start">
                                        <i class="fas <?php echo $icon; ?> mt-1 me-2"></i>
                                        <div class="flex-grow-1">
                                            <strong class="d-block"><?php echo htmlspecialchars($notif['title']); ?></strong>
                                            <small class="text-muted"><?php echo htmlspecialchars($notif['message']); ?></small>
                                            <br><small class="text-muted"><?php echo date('M j, g:i A', strtotime($notif['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </a>
                            <?php
                                endforeach;
                            else:
                            ?>
                                <div class="px-3 py-4 text-center text-muted">
                                    <i class="fas fa-bell-slash mb-2" style="font-size: 2rem;"></i>
                                    <p class="mb-0">No notifications</p>
                                </div>
                            <?php endif; ?>
                            <div class="px-3 py-2 border-top text-center">
                                <a href="admin_notifications.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                        </div>
                    </div>
                    
                    <button class="header-icon-btn" id="sidebarToggle" title="Toggle Sidebar" onclick="toggleSidebar()" style="display: none;">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <div class="admin-user">
                    <div class="user-avatar">
                        <?php 
                            $admin = $_SESSION['admin_user'] ?? [];
                            $initials = strtoupper(substr($admin['username'] ?? 'A', 0, 1));
                            echo $initials;
                        ?>
                    </div>
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($admin['username'] ?? 'Administrator'); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <div class="user-menu">
                        <div class="user-menu-header">
                            <div class="user-menu-name"><?php echo htmlspecialchars($admin['username'] ?? 'Admin'); ?></div>
                            <div class="user-menu-email"><?php echo htmlspecialchars($admin['email_address'] ?? ''); ?></div>
                        </div>
                        <ul class="user-menu-items">
                            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <div class="admin-wrapper">
            <?php include 'includes/sidebar.php'; ?>
            <main class="admin-main">
