<?php
/**
 * Admin Settings
 * Site configuration and preferences
 */
require_once __DIR__ . '/auth.php';

$page_title = 'Settings';

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Settings</h1>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
    <!-- Account Settings -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Account</h2>
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="font-weight: 600;">Admin User</label>
            <p style="margin: .5rem 0 0 0; color: var(--admin-text-muted);">
                <?php echo htmlspecialchars($_SESSION['admin_user']['username']); ?>
            </p>
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="font-weight: 600;">Email</label>
            <p style="margin: .5rem 0 0 0; color: var(--admin-text-muted);">
                <?php echo htmlspecialchars($_SESSION['admin_user']['email_address']); ?>
            </p>
        </div>

        <a href="../logout.php" class="btn btn-danger">Logout</a>
    </div>

    <!-- System Info -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">System Information</h2>
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="font-weight: 600;">PHP Version</label>
            <p style="margin: .5rem 0 0 0; color: var(--admin-text-muted);">
                <?php echo phpversion(); ?>
            </p>
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="font-weight: 600;">Database</label>
            <p style="margin: .5rem 0 0 0; color: var(--admin-text-muted);">
                MySQL / PDO
            </p>
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="font-weight: 600;">Platform</label>
            <p style="margin: .5rem 0 0 0; color: var(--admin-text-muted);">
                Jeweluxe E-Commerce
            </p>
        </div>
    </div>

    <!-- Help & Support -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Support</h2>
        </div>

        <p style="color: var(--admin-text-muted); margin-bottom: 1rem;">
            For technical support or issues with the admin panel, please contact the development team.
        </p>

        <a href="mailto:support@jeweluxe.com" class="btn btn-secondary">Email Support</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
