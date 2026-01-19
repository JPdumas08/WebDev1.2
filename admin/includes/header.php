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
                    <button class="header-icon-btn" title="Notifications" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                    </button>
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
