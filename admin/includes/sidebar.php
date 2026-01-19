<?php
/**
 * Admin Sidebar Navigation
 * Premium sidebar with enhanced icons and active states
 */
$current_page = basename($_SERVER['PHP_SELF']);
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
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products.php" class="nav-link <?php echo $current_page === 'products.php' ? 'active' : ''; ?>">
                        <i class="fas fa-gem"></i>
                        <span>Products</span>
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
</script>
