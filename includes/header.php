<?php
// Header include: prints document head and opens <body>.
// Usage: set $pageTitle before including if you want a custom title.
require_once __DIR__ . '/auth.php';
init_session();
$pageTitle = $pageTitle ?? 'Jeweluxe';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Custom stylesheet -->
  <link href="styles.css?v=<?= time() ?>" rel="stylesheet">
  <!-- jQuery (needed by some inline page scripts) -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body data-logged-in="<?php echo is_logged_in() ? '1' : '0'; ?>" data-user-id="<?php echo is_logged_in() ? (current_user()['id'] ?? '') : ''; ?>">
<?php include __DIR__ . '/navbar.php'; ?>
<!-- Shared Modals moved here so they are available early in the document -->
<!-- MODERN ACCOUNT LOGIN MODAL -->
<div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modern-modal">
      <div class="modal-header border-0 bg-gradient-primary text-white">
        <h5 class="modal-title" id="accountModalLabel" style="font-size: 1.05rem;">
          <i class="fas fa-user-circle me-2"></i>Sign In to Your Account
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php
        // auth.php and init_session() are already included above in this header.
        if (is_logged_in()):
          $u = current_user();
          $display = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: ($u['username'] ?? $u['email'] ?? '');
        ?>
          <div class="text-center py-4">
            <div class="user-avatar-large mb-3">
              <i class="fas fa-user-circle fa-4x text-primary"></i>
            </div>
            <h4 class="welcome-title">Welcome Back!</h4>
            <p class="user-email lead"><?php echo htmlspecialchars($display, ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="account-quick-actions d-grid gap-2 mt-4">
              <a class="btn btn-outline-primary" href="account_settings.php">
                <i class="fas fa-cog me-2"></i>Account Settings
              </a>
              <a class="btn btn-outline-secondary" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Sign Out
              </a>
            </div>
          </div>
        <?php else: ?>
          <div class="divider-section">
            <div class="divider-line"></div>
            <span class="divider-text">Sign In</span>
            <div class="divider-line"></div>
          </div>
          
          <form id="loginForm" method="post" action="login_handler.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/', ENT_QUOTES, 'UTF-8'); ?>">
            <div class="mb-3">
              <label for="loginEmail" class="form-label fw-bold" style="font-size: 0.85rem;">
                  <i class="fas fa-envelope me-1 text-muted"></i>Email or Username
              </label>
              <input name="email" type="text" class="form-control form-control-lg" id="loginEmail" placeholder="Enter your email or username" required>
            </div>
            
            <div class="mb-3">
              <label for="loginPassword" class="form-label fw-bold" style="font-size: 0.85rem;">
                  <i class="fas fa-lock me-1 text-muted"></i>Password
              </label>
              <input name="password" type="password" class="form-control form-control-lg" id="loginPassword" placeholder="Enter your password" data-required="true">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="showLoginPassword" onclick="togglePassword('loginPassword')">
                  <label class="form-check-label" for="showLoginPassword" style="font-size: 0.85rem; color: #6c757d;">
                  Show Password
                </label>
              </div>
            </div>
            
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary btn-lg" style="font-size: 1rem;">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
              </button>
            </div>
          </form>
          
          <div class="signup-section text-center mt-4">
            <p class="text-muted" style="font-size: 0.875rem;">Don't have an account yet?</p>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal" style="font-size: 0.9rem;">
              <i class="fas fa-user-plus me-2"></i>Create New Account
            </button>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- MODERN REGISTRATION MODAL -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content modern-modal">
      <div class="modal-header border-0 bg-gradient-success text-white">
        <h5 class="modal-title" id="registerModalLabel" style="font-size: 1.05rem;">
          <i class="fas fa-user-plus me-2"></i>Create Your Jeweluxe Account
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="registerForm" method="post" action="register.php" novalidate>
          <?php echo csrf_field(); ?>
          <div id="registerFeedback" class="mb-3" aria-live="polite"></div>
          <div class="row g-3">
            <div class="col-md-6">
                <label for="firstName" class="form-label fw-bold" style="font-size: 0.85rem;">
                <i class="fas fa-user me-1 text-muted"></i>First Name
              </label>
              <input type="text" class="form-control form-control-lg" id="firstName" name="first_name" placeholder="Enter your first name" required pattern="^[A-Za-z][A-Za-z\s'-]*$" title="Letters only" inputmode="text" oninput="this.value=this.value.replace(/[^A-Za-z\s'-]/g,'');">
            </div>
            <div class="col-md-6">
                <label for="lastName" class="form-label fw-bold" style="font-size: 0.85rem;">
                <i class="fas fa-user me-1 text-muted"></i>Last Name
              </label>
              <input type="text" class="form-control form-control-lg" id="lastName" name="last_name" placeholder="Enter your last name" required pattern="^[A-Za-z][A-Za-z\s'-]*$" title="Letters only" inputmode="text" oninput="this.value=this.value.replace(/[^A-Za-z\s'-]/g,'');">
            </div>
          </div>
          
          <div class="mb-3">
            <label for="registerEmail" class="form-label fw-bold" style="font-size: 0.85rem;">
              <i class="fas fa-envelope me-1 text-muted"></i>Email Address
            </label>
            <input type="email" class="form-control form-control-lg" id="registerEmail" name="email" placeholder="Enter your email address" required>
          </div>
          
          <div class="mb-3">
            <label for="username" class="form-label fw-bold" style="font-size: 0.85rem;">
              <i class="fas fa-at me-1 text-muted"></i>Username
            </label>
            <input type="text" class="form-control form-control-lg" id="username" name="username" placeholder="Choose a unique username" required>
          </div>
          
          <div class="row g-3">
            <div class="col-md-6">
                <label for="registerPassword" class="form-label fw-bold" style="font-size: 0.85rem;">
                <i class="fas fa-lock me-1 text-muted"></i>Password
              </label>
              <input type="password" class="form-control form-control-lg" id="registerPassword" name="password" placeholder="Create a strong password" data-required="true">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="showRegisterPassword" onclick="togglePassword('registerPassword')">
                  <label class="form-check-label" for="showRegisterPassword" style="font-size: 0.85rem; color: #6c757d;">
                  Show Password
                </label>
              </div>
            </div>
            <div class="col-md-6">
                <label for="confirmPassword" class="form-label fw-bold" style="font-size: 0.85rem;">
                <i class="fas fa-lock me-1 text-muted"></i>Confirm Password
              </label>
              <input type="password" class="form-control form-control-lg" id="confirmPassword" name="confirm_password" placeholder="Confirm your password" data-required="true">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="showConfirmPassword" onclick="togglePassword('confirmPassword')">
                  <label class="form-check-label" for="showConfirmPassword" style="font-size: 0.85rem; color: #6c757d;">
                  Show Password
                </label>
              </div>
            </div>
          </div>
          
          <div class="password-strength mb-3">
            <div class="strength-meter mb-2">
              <div class="strength-bar" id="strengthBar"></div>
            </div>
            <small class="text-muted" id="strengthText">Password strength: None</small>
          </div>
          
          <div class="mb-4">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="agreeTerms" required>
              <label class="form-check-label" for="agreeTerms" style="font-size: 0.875rem;">
                I agree to the <a href="#" class="text-primary">Terms & Conditions</a> and <a href="#" class="text-primary">Privacy Policy</a>
              </label>
            </div>
          </div>
          
          <div class="d-grid">
            <button type="submit" class="btn btn-success btn-lg" style="font-size: 1rem;">
              <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
          </div>
        </form>
        
        <div class="divider-section my-4">
          <div class="divider-line"></div>
          <span class="divider-text" style="font-size: 0.8rem;">ALREADY HAVE AN ACCOUNT?</span>
          <div class="divider-line"></div>
        </div>
        
        <div class="text-center">
          <button type="button" class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#accountModal" data-bs-dismiss="modal" style="font-size: 0.9rem;">
            <i class="fas fa-sign-in-alt me-2"></i>Sign In Instead
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Shopping Cart Modal -->
<?php include __DIR__ . '/cart-modal.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const isLoggedIn = <?php echo is_logged_in() ? 'true' : 'false'; ?>;
  const params = new URLSearchParams(window.location.search);
  const loginStatus = params.get('login');
  const showLogin = params.get('showLogin');
  const registered = params.get('registered');

  const loginMessages = {
    ok: { type: 'success', text: 'Login successful! Welcome back.' },
    missing: { type: 'error', text: 'Please fill in all required fields.' },
    notfound: { type: 'error', text: 'User not found. Please check your credentials.' },
    bad: { type: 'error', text: 'Invalid password. Please try again.' },
    err: { type: 'error', text: 'An error occurred. Please try again.' }
  };

  if (loginStatus && typeof ToastNotification !== 'undefined' && loginMessages[loginStatus]) {
    const msg = loginMessages[loginStatus];
    ToastNotification[msg.type](msg.text);
  }

  if (registered === '1' && typeof ToastNotification !== 'undefined') {
    ToastNotification.success('Your account was created. Please sign in.');
  }

  if (isLoggedIn) {
    return;
  }

  const shouldOpenModal = showLogin === '1' || loginStatus === 'required' || loginStatus === 'missing' || loginStatus === 'notfound' || loginStatus === 'bad' || loginStatus === 'err';
  if (shouldOpenModal) {
    const accountEl = document.getElementById('accountModal');
    if (accountEl && typeof bootstrap !== 'undefined') {
      const modal = bootstrap.Modal.getOrCreateInstance(accountEl);
      modal.show();
    }
  }
});
</script>
