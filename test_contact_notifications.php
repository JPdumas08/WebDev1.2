<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact & Notification System Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4"><i class="fas fa-flask text-primary"></i> Contact & Notification System Test</h1>
        
        <?php
        require_once 'db.php';
        
        $results = [];
        
        // Test 1: Check database tables exist
        $results['tables'] = [];
        $tables = ['contact_messages', 'message_replies', 'notifications', 'admin_notifications'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->rowCount() > 0;
                $results['tables'][$table] = $exists;
            } catch (Exception $e) {
                $results['tables'][$table] = false;
            }
        }
        
        // Test 2: Check files exist
        $results['files'] = [];
        $files = [
            'Customer' => [
                'contactus.php',
                'process_contact.php',
                'user_notifications.php',
                'api_mark_notification_read.php'
            ],
            'Admin' => [
                'admin/messages.php',
                'admin/message_detail.php',
                'admin/admin_notifications.php',
                'admin/api/reply_message.php',
                'admin/api/update_message_status.php',
                'admin/api/mark_admin_notifications_read.php'
            ]
        ];
        
        foreach ($files as $category => $fileList) {
            $results['files'][$category] = [];
            foreach ($fileList as $file) {
                $results['files'][$category][$file] = file_exists(__DIR__ . '/' . $file);
            }
        }
        
        // Test 3: Count records
        $results['counts'] = [];
        try {
            $results['counts']['messages'] = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
            $results['counts']['replies'] = (int)$pdo->query("SELECT COUNT(*) FROM message_replies")->fetchColumn();
            $results['counts']['user_notifications'] = (int)$pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
            $results['counts']['admin_notifications'] = (int)$pdo->query("SELECT COUNT(*) FROM admin_notifications")->fetchColumn();
        } catch (Exception $e) {
            $results['counts']['error'] = $e->getMessage();
        }
        ?>
        
        <!-- Database Tables -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-database"></i> Database Tables</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($results['tables'] as $table => $exists): ?>
                        <div class="col-md-3 mb-2">
                            <div class="alert alert-<?php echo $exists ? 'success' : 'danger'; ?> mb-0">
                                <i class="fas fa-<?php echo $exists ? 'check-circle' : 'times-circle'; ?>"></i>
                                <?php echo $table; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Customer Files -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-user"></i> Customer Files</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($results['files']['Customer'] as $file => $exists): ?>
                        <div class="col-md-6 mb-2">
                            <div class="alert alert-<?php echo $exists ? 'success' : 'danger'; ?> mb-0">
                                <i class="fas fa-<?php echo $exists ? 'check-circle' : 'times-circle'; ?>"></i>
                                <?php echo $file; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Admin Files -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-user-shield"></i> Admin Files</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($results['files']['Admin'] as $file => $exists): ?>
                        <div class="col-md-6 mb-2">
                            <div class="alert alert-<?php echo $exists ? 'success' : 'danger'; ?> mb-0">
                                <i class="fas fa-<?php echo $exists ? 'check-circle' : 'times-circle'; ?>"></i>
                                <?php echo $file; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Database Counts -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Database Records</h5>
            </div>
            <div class="card-body">
                <?php if (isset($results['counts']['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> Error: <?php echo htmlspecialchars($results['counts']['error']); ?>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h2 class="text-primary"><?php echo $results['counts']['messages']; ?></h2>
                                    <p class="mb-0">Contact Messages</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h2 class="text-success"><?php echo $results['counts']['replies']; ?></h2>
                                    <p class="mb-0">Admin Replies</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h2 class="text-info"><?php echo $results['counts']['user_notifications']; ?></h2>
                                    <p class="mb-0">User Notifications</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h2 class="text-warning"><?php echo $results['counts']['admin_notifications']; ?></h2>
                                    <p class="mb-0">Admin Notifications</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-link"></i> Quick Links</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Customer Pages</h6>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <a href="contactus.php" target="_blank">
                                    <i class="fas fa-envelope"></i> Contact Us Page
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="user_notifications.php" target="_blank">
                                    <i class="fas fa-bell"></i> User Notifications
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Admin Pages</h6>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <a href="admin/messages.php" target="_blank">
                                    <i class="fas fa-envelope-open"></i> Messages Dashboard
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="admin/admin_notifications.php" target="_blank">
                                    <i class="fas fa-bell"></i> Admin Notifications
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="admin/index.php" target="_blank">
                                    <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Overall Status -->
        <?php
        $all_tables_ok = !in_array(false, $results['tables'], true);
        $all_customer_files_ok = !in_array(false, $results['files']['Customer'], true);
        $all_admin_files_ok = !in_array(false, $results['files']['Admin'], true);
        $all_ok = $all_tables_ok && $all_customer_files_ok && $all_admin_files_ok;
        ?>
        
        <div class="alert alert-<?php echo $all_ok ? 'success' : 'warning'; ?> mt-4">
            <h4 class="alert-heading">
                <i class="fas fa-<?php echo $all_ok ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                System Status: <?php echo $all_ok ? 'All Systems Operational' : 'Some Issues Detected'; ?>
            </h4>
            <hr>
            <p class="mb-0">
                <?php if ($all_ok): ?>
                    ✅ All database tables created<br>
                    ✅ All customer files present<br>
                    ✅ All admin files present<br>
                    🎉 The Contact & Notification System is ready for use!
                <?php else: ?>
                    Please review the checks above and fix any issues.
                <?php endif; ?>
            </p>
        </div>
        
        <div class="text-center mt-4">
            <a href="CONTACT_NOTIFICATION_SYSTEM.md" class="btn btn-primary btn-lg">
                <i class="fas fa-book"></i> View Documentation
            </a>
            <a href="home.php" class="btn btn-secondary btn-lg ms-2">
                <i class="fas fa-home"></i> Go to Home
            </a>
        </div>
    </div>
</body>
</html>
