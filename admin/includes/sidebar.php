<?php
/**
 * Admin Sidebar Navigation
 * Premium sidebar with enhanced icons and active states
 */
$current_page = basename($_SERVER['PHP_SELF']);

// Load notification counts
require_once __DIR__ . '/notification_counts.php';
?>
<aside class="admin-sidebar" id="adminSidebar">
    <nav class="sidebar-nav">
        <!-- Main Section -->
        <div class="nav-section">
            <div class="nav-section-title">
                <i class="fas fa-home"></i>
                Main
                <div class="nav-section-divider"></div>
            </div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Orders</span>
                        <?php if ($notification_counts['orders'] > 0): ?>
                            <span class="nav-badge"><?php echo $notification_counts['orders']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products.php" class="nav-link <?php echo $current_page === 'products.php' && (!isset($_GET['status']) || $_GET['status'] !== 'archived') ? 'active' : ''; ?>">
                        <i class="fas fa-gem"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products.php?status=archived" class="nav-link <?php echo $current_page === 'products.php' && isset($_GET['status']) && $_GET['status'] === 'archived' ? 'active' : ''; ?>">
                        <i class="fas fa-archive"></i>
                        <span>Archived Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="messages.php" class="nav-link <?php echo $current_page === 'messages.php' || $current_page === 'message_detail.php' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i>
                        <span>Messages</span>
                        <?php if ($notification_counts['messages'] > 0): ?>
                            <span class="nav-badge"><?php echo $notification_counts['messages']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reviews.php" class="nav-link <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i>
                        <span>Reviews</span>
                        <?php if ($notification_counts['reviews'] > 0): ?>
                            <span class="nav-badge"><?php echo $notification_counts['reviews']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="customers.php" class="nav-link <?php echo $current_page === 'customers.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Finance Section -->
        <div class="nav-section">
            <div class="nav-section-title">
                <i class="fas fa-coins"></i>
                Finance
                <div class="nav-section-divider"></div>
            </div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="payments.php" class="nav-link <?php echo $current_page === 'payments.php' ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card"></i>
                        <span>Payments</span>
                        <?php if ($notification_counts['payments'] > 0): ?>
                            <span class="nav-badge"><?php echo $notification_counts['payments']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Configuration Section -->
        <div class="nav-section">
            <div class="nav-section-title">
                <i class="fas fa-sliders-h"></i>
                Configuration
                <div class="nav-section-divider"></div>
            </div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

// Close sidebar when clicking on a link on mobile
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function() {
        const sidebar = document.getElementById('adminSidebar');
        if (sidebar && window.innerWidth <= 768) {
            sidebar.classList.remove('open');
        }
    });
});

// Show/hide sidebar toggle button based on screen size
function updateSidebarToggle() {
    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) {
        toggleBtn.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
    }
}

window.addEventListener('resize', updateSidebarToggle);
updateSidebarToggle();

// Real-time notification badge updates via AJAX polling
function updateNotificationBadges() {
    fetch('api/get_notification_counts.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.counts) {
                // Update each badge
                updateBadge('orders.php', data.counts.orders);
                updateBadge('messages.php', data.counts.messages);
                updateBadge('reviews.php', data.counts.reviews);
                updateBadge('payments.php', data.counts.payments);
            }
        })
        .catch(error => console.error('Notification update failed:', error));
}

function updateBadge(page, count) {
    const link = document.querySelector(`a[href="${page}"], a[href^="${page}?"]`);
    if (!link) return;
    
    let badge = link.querySelector('.nav-badge');
    
    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'nav-badge';
            link.appendChild(badge);
        }
        badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
}

// Poll every 30 seconds for updates
setInterval(updateNotificationBadges, 30000);

// Initial update after 5 seconds
setTimeout(updateNotificationBadges, 5000);

</script>
