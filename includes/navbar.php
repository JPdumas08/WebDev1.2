<?php
// Navbar include — outputs the site navigation bar
// Assumes includes/auth.php is available and session can be initialized
require_once __DIR__ . '/auth.php';
init_session();

$user = current_user();
?>
<!-- MODERN ECOMMERCE NAVIGATION -->
<nav class="navbar navbar-expand-lg modern-navbar">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand fw-bold" href="home.php">
      <i class="fas fa-gem brand-icon me-2"></i>
      <span class="brand-text">Jeweluxe</span>
    </a>

    <!-- Mobile Toggle -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Main Navigation -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item">
          <a class="nav-link" href="home.php">
            <i class="fas fa-home me-1"></i>Home
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link" href="products.php">
            <i class="fas fa-shopping-bag me-1"></i>Products
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link" href="about.php">
            <i class="fas fa-info-circle me-1"></i>About
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link" href="contactus.php">
            <i class="fas fa-envelope me-1"></i>Contact
          </a>
        </li>
      </ul>

      <!-- Action Icons -->
      <ul class="navbar-nav ms-auto">
        <!-- Wishlist -->
        <li class="nav-item">
          <a class="nav-link" href="wishlist.php">
            <i class="far fa-heart"></i>
            <span class="wishlist-count badge bg-danger">0</span>
          </a>
        </li>
        
        <!-- Cart -->
        <li class="nav-item">
          <a class="nav-link cart-toggle" href="#" id="cartLink">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart-count badge bg-danger">0</span>
          </a>
        </li>
        
        <!-- User Account -->
        <?php if (!empty($user)): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="accountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="user-avatar me-2">
                <i class="fas fa-user-circle"></i>
              </div>
              <span class="user-name d-none d-md-inline"><?= htmlspecialchars($user['first_name'] ?? $user['username'] ?? 'Account') ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountDropdown">
              <li class="dropdown-header">My Account</li>
              <li><a class="dropdown-item" href="account_settings.php">
                <i class="fas fa-cog me-2"></i>Account Settings
              </a></li>
              <li><a class="dropdown-item" href="wishlist.php">
                <i class="fas fa-heart me-2"></i>My Wishlist
              </a></li>
              <li><a class="dropdown-item" href="order_history.php">
                <i class="fas fa-history me-2"></i>Order History
              </a></li>
              <li><a class="dropdown-item" href="notifications.php">
                <i class="fas fa-bell me-2"></i>Notifications
              </a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
              </a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link login-toggle" href="#" data-bs-toggle="modal" data-bs-target="#accountModal">
              <i class="fas fa-user-circle me-1"></i>
              <span class="d-none d-md-inline">Sign In</span>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav></div>
