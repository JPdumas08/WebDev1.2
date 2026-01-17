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
        <h5 class="modal-title" id="accountModalLabel">
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
          <div class="text-center py-3">
            <div class="user-avatar-large mb-3">
              <i class="fas fa-user-circle fa-3x text-primary"></i>
            </div>
            <h4 class="welcome-title">Welcome Back!</h4>
            <p class="user-email"><?php echo htmlspecialchars($display, ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="account-quick-actions d-grid gap-2 mt-3">
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
          
          <form id="loginForm" method="post" action="login_handler.php" novalidate>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/', ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="mb-3 form-group">
              <label for="loginEmail" class="form-label">
                  <i class="fas fa-envelope me-1 text-muted"></i>Email or Username
              </label>
              <input name="email" type="text" class="form-control" id="loginEmail" placeholder="Enter your email or username" required data-validate="login-email">
              <small class="invalid-feedback">Please enter a valid email or username (letters, numbers, underscores only)</small>
            </div>
            
            <div class="mb-3 form-group">
              <label for="loginPassword" class="form-label">
                  <i class="fas fa-lock me-1 text-muted"></i>Password
              </label>
              <input name="password" type="password" class="form-control" id="loginPassword" placeholder="Enter your password" required data-validate="password-login">
              <small class="invalid-feedback">Password is required</small>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="showLoginPassword" onclick="togglePassword('loginPassword')">
                  <label class="form-check-label" for="showLoginPassword">
                  Show Password
                </label>
              </div>
            </div>
            
            <div class="alert alert-danger py-2 px-3 d-none" role="alert" id="loginError" aria-live="polite">
              Incorrect username or password.
            </div>

            <div class="d-grid gap-2 mb-3">
              <button type="submit" class="btn btn-primary" id="loginSubmitBtn">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
              </button>
            </div>
          </form>
          
          <div class="signup-section text-center">
            <p class="text-muted mb-2" style="font-size: 0.8875rem;">Don't have an account yet?</p>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">
              <i class="fas fa-user-plus me-1"></i>Create New Account
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
        <h5 class="modal-title" id="registerModalLabel">
          <i class="fas fa-user-plus me-2"></i>Create Your Jeweluxe Account
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="registerForm" method="post" action="register.php" novalidate>
          <?php echo csrf_field(); ?>
          <div id="registerFeedback" class="mb-3" aria-live="polite"></div>
          
          <div class="row g-2">
            <div class="col-md-6 form-group">
              <label for="firstName" class="form-label">
                <i class="fas fa-user me-1 text-muted"></i>First Name
              </label>
              <input type="text" class="form-control" id="firstName" name="first_name" placeholder="First name" required data-validate="name">
              <small class="invalid-feedback">First name required (2-50 letters, spaces, or hyphens only)</small>
              <small class="form-text validation-hint">2-50 letters, spaces, or hyphens</small>
            </div>
            <div class="col-md-6 form-group">
              <label for="lastName" class="form-label">
                <i class="fas fa-user me-1 text-muted"></i>Last Name
              </label>
              <input type="text" class="form-control" id="lastName" name="last_name" placeholder="Last name" required data-validate="name">
              <small class="invalid-feedback">Last name required (2-50 letters, spaces, or hyphens only)</small>
              <small class="form-text validation-hint">2-50 letters, spaces, or hyphens</small>
            </div>
          </div>
          
          <div class="mb-3 form-group">
            <label for="registerEmail" class="form-label">
              <i class="fas fa-envelope me-1 text-muted"></i>Email Address
            </label>
            <input type="email" class="form-control" id="registerEmail" name="email" placeholder="your@email.com" required data-validate="email">
            <small class="invalid-feedback">Please enter a valid, unique email address</small>
            <small class="form-text validation-hint">Will be used to verify your account</small>
          </div>

          <div class="mb-3 form-group">
            <label for="registerUsername" class="form-label">
              <i class="fas fa-at me-1 text-muted"></i>Username
            </label>
            <input type="text" class="form-control" id="registerUsername" name="username" placeholder="Choose a username (4-20 characters)" required data-validate="username">
            <small class="invalid-feedback">Username must be 4-20 characters (letters, numbers, underscores only)</small>
            <small class="form-text validation-hint">Used to log in to your account</small>
          </div>
          
          <div class="row g-2">
            <div class="col-md-6 form-group">
              <label for="registerPassword" class="form-label">
                <i class="fas fa-lock me-1 text-muted"></i>Password
              </label>
              <input type="password" class="form-control" id="registerPassword" name="password" placeholder="Create a strong password" required data-validate="password">
              <small class="invalid-feedback">Password does not meet requirements</small>
              
              <div class="validation-requirements mt-2">
                <div class="validation-requirement unmet" id="req-length">
                  <i class="fas fa-check-circle"></i>
                  <span>At least 8 characters</span>
                </div>
                <div class="validation-requirement unmet" id="req-upper">
                  <i class="fas fa-check-circle"></i>
                  <span>One uppercase letter (A-Z)</span>
                </div>
                <div class="validation-requirement unmet" id="req-lower">
                  <i class="fas fa-check-circle"></i>
                  <span>One lowercase letter (a-z)</span>
                </div>
                <div class="validation-requirement unmet" id="req-number">
                  <i class="fas fa-check-circle"></i>
                  <span>One number (0-9)</span>
                </div>
                <div class="validation-requirement unmet" id="req-special">
                  <i class="fas fa-check-circle"></i>
                  <span>One special character (!@#$%^&*?)</span>
                </div>
              </div>
              
              <div class="strength-meter mt-2 mb-2">
                <div class="strength-bar" id="strengthBar"></div>
              </div>
              <small class="password-strength-text" id="strengthText">Password strength: None</small>
              
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="showRegisterPassword" onclick="togglePassword('registerPassword')">
                <label class="form-check-label" for="showRegisterPassword">
                  Show Password
                </label>
              </div>
            </div>
            <div class="col-md-6 form-group">
              <label for="confirmPassword" class="form-label">
                <i class="fas fa-lock me-1 text-muted"></i>Confirm Password
              </label>
              <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Confirm your password" required data-validate="confirm-password">
              <small class="invalid-feedback">Passwords do not match</small>
              <small class="form-text validation-hint">Must match the password above</small>
              
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="showConfirmPassword" onclick="togglePassword('confirmPassword')">
                <label class="form-check-label" for="showConfirmPassword">
                  Show Password
                </label>
              </div>
            </div>
          </div>
          
          <div class="mb-3 form-group">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="agreeTerms" name="agree_terms" required data-validate="terms">
              <label class="form-check-label" for="agreeTerms">
                I agree to the <a href="#" class="text-primary" target="_blank">Terms & Conditions</a> and <a href="#" class="text-primary" target="_blank">Privacy Policy</a>
              </label>
              <small class="invalid-feedback d-block">You must agree to the Terms & Conditions</small>
            </div>
          </div>
          
          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-success" id="registerSubmitBtn">
              <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
          </div>
        </form>
        
        <div class="divider-section">
          <div class="divider-line"></div>
          <span class="divider-text">ALREADY HAVE AN ACCOUNT?</span>
          <div class="divider-line"></div>
        </div>
        
        <div class="text-center">
          <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#accountModal" data-bs-dismiss="modal">
            <i class="fas fa-sign-in-alt me-1"></i>Sign In Instead
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Shopping Cart Modal -->
<?php include __DIR__ . '/cart-modal.php'; ?>

<script>
// ===== COMPREHENSIVE FORM VALIDATION =====

// Toggle password visibility
function togglePassword(fieldId) {
  const field = document.getElementById(fieldId);
  if (field) {
    field.type = field.type === 'password' ? 'text' : 'password';
  }
}

// Validation patterns and rules
const ValidationRules = {
  name: {
    pattern: /^[A-Za-z\s'-]{2,50}$/,
    message: 'Must be 2-50 characters (letters, spaces, or hyphens only)'
  },
  email: {
    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    message: 'Invalid email format'
  },
  username: {
    pattern: /^[A-Za-z0-9_]{4,20}$/,
    message: 'Must be 4-20 characters (letters, numbers, underscores only)'
  },
  password: {
    minLength: 8,
    requirements: {
      uppercase: /[A-Z]/,
      lowercase: /[a-z]/,
      number: /[0-9]/,
      special: /[!@#$%^&*?]/
    }
  },
  loginEmail: {
    pattern: /^[^\s@].+@[^\s]+\.[^\s]+$|^[^\s]+$/, // Email OR any non-empty/non-space string
    message: 'Email or username is required'
  }
};

// Availability checking state helpers
function setAvailability(field, status, message = '') {
  if (!field) return;
  field.dataset.availability = status; // 'available', 'taken', 'checking', ''
  if (message) {
    field.dataset.availMessage = message;
  } else {
    delete field.dataset.availMessage;
  }
}

function scheduleAvailabilityCheck(field, type, timerRef) {
  if (!field) return timerRef;
  clearTimeout(timerRef);
  return setTimeout(() => runAvailabilityCheck(field, type), 450);
}

function runAvailabilityCheck(field, type) {
  const value = field.value.trim();
  if (!value) {
    setAvailability(field, '');
    validateField(field);
    return;
  }

  // Only check if format is valid
  if (type === 'email' && !ValidationRules.email.pattern.test(value)) {
    setAvailability(field, '');
    return;
  }
  if (type === 'username' && !ValidationRules.username.pattern.test(value)) {
    setAvailability(field, '');
    return;
  }

  setAvailability(field, 'checking');
  fetch(`check_account.php?${type}=` + encodeURIComponent(value), {
    method: 'GET',
    headers: { 'Accept': 'application/json' }
  })
    .then(res => res.ok ? res.json() : Promise.reject())
    .then(data => {
      if (!data || data.success === false) return;
      if (type === 'email' && data.emailExists) {
        setAvailability(field, 'taken', 'Email address is already in use.');
      } else if (type === 'username' && data.usernameExists) {
        setAvailability(field, 'taken', 'Username is already taken.');
      } else {
        setAvailability(field, 'available');
      }
      validateField(field);
    })
    .catch(() => {
      setAvailability(field, '');
    });
}

// Real-time password strength indicator
function updatePasswordStrength() {
  const password = document.getElementById('registerPassword').value;
  const strengthBar = document.getElementById('strengthBar');
  const strengthText = document.getElementById('strengthText');
  
  if (!password) {
    strengthBar.className = 'strength-bar';
    strengthText.textContent = 'Password strength: None';
    return;
  }
  
  let strength = 0;
  if (password.length >= 8) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[a-z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[!@#$%^&*?]/.test(password)) strength++;
  
  const levels = ['', 'weak', 'fair', 'good', 'strong'];
  const texts = ['None', 'Weak', 'Fair', 'Good', 'Strong'];
  
  strengthBar.className = `strength-bar ${levels[strength]}`;
  strengthText.textContent = `Password strength: ${texts[strength]}`;
  strengthText.className = `password-strength-text ${levels[strength]}`;
}

// Update password requirements display
function updatePasswordRequirements() {
  const password = document.getElementById('registerPassword').value;
  const rules = ValidationRules.password.requirements;
  
  const updateReq = (id, met) => {
    const el = document.getElementById(id);
    if (el) {
      el.classList.toggle('met', met);
      el.classList.toggle('unmet', !met);
    }
  };
  
  updateReq('req-length', password.length >= 8);
  updateReq('req-upper', rules.uppercase.test(password));
  updateReq('req-lower', rules.lowercase.test(password));
  updateReq('req-number', rules.number.test(password));
  updateReq('req-special', rules.special.test(password));
}

// Validate individual field and update error messages
function validateField(field) {
  const validation = field.dataset.validate;
  const value = field.value.trim();
  
  if (!validation) return true;
  
  let isValid = true;
  let errorMessage = '';
  
  // Determine validity and error message
  switch(validation) {
    case 'name':
      isValid = ValidationRules.name.pattern.test(value) && value.length >= 2 && value.length <= 50;
      if (!isValid && value.length > 0) {
        errorMessage = 'Must be 2-50 characters (letters, spaces, hyphens, or apostrophes only)';
      }
      break;
    case 'email':
      isValid = ValidationRules.email.pattern.test(value);
      if (!isValid && value.length > 0) {
        errorMessage = 'Please enter a valid email address';
      } else if (field.dataset.availability === 'taken') {
        isValid = false;
        errorMessage = field.dataset.availMessage || 'Email address is already in use.';
      }
      break;
    case 'username':
      isValid = ValidationRules.username.pattern.test(value);
      if (!isValid && value.length > 0) {
        errorMessage = 'Must be 4-20 characters (letters, numbers, underscores only)';
      } else if (field.dataset.availability === 'taken') {
        isValid = false;
        errorMessage = field.dataset.availMessage || 'Username is already taken.';
      }
      break;
    case 'password':
      const rules = ValidationRules.password.requirements;
      isValid = value.length >= 8 && 
                rules.uppercase.test(value) &&
                rules.lowercase.test(value) &&
                rules.number.test(value) &&
                rules.special.test(value);
      if (!isValid && value.length > 0) {
        errorMessage = 'Password does not meet all requirements';
      }
      break;
    case 'confirm-password':
      const passwordField = document.getElementById('registerPassword');
      isValid = value === passwordField.value && passwordField.value.length > 0;
      if (!isValid && value.length > 0) {
        errorMessage = 'Passwords do not match';
      }
      break;
    case 'terms':
      isValid = field.checked;
      if (!isValid) {
        errorMessage = 'You must agree to the Terms & Conditions';
      }
      break;
    case 'login-email':
      isValid = value.length > 0 && !/^\s+$/.test(value);
      if (!isValid && value.length > 0) {
        errorMessage = 'Please enter a valid email or username';
      }
      break;
    case 'password-login':
      isValid = value.length > 0 && !/^\s+$/.test(value);
      break;
  }
  
  // Update field styling and error message visibility
  const hasInput = value.length > 0 || (validation === 'terms' && field.checked) || field.classList.contains('was-validated');
  
  if (hasInput) {
    field.classList.toggle('is-invalid', !isValid);
    field.classList.toggle('is-valid', isValid);
    
    // Update error message visibility
    const feedbackElement = field.parentElement.querySelector('.invalid-feedback');
    if (feedbackElement) {
      if (isValid) {
        feedbackElement.style.display = 'none';
      } else {
        feedbackElement.textContent = errorMessage || 'Invalid input';
        feedbackElement.style.display = 'block';
      }
    }
  } else {
    // Clear styling if empty
    field.classList.remove('is-invalid', 'is-valid');
    const feedbackElement = field.parentElement.querySelector('.invalid-feedback');
    if (feedbackElement) {
      feedbackElement.style.display = 'none';
    }
  }
  
  return isValid;
}

// Handle sign-up form submission
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    // Real-time validation for password field
    const passwordField = document.getElementById('registerPassword');
    if (passwordField) {
      passwordField.addEventListener('input', function() {
        updatePasswordStrength();
        updatePasswordRequirements();
        validateField(this);
        
        // Also validate confirm password if it has input
        const confirmPasswordField = document.getElementById('confirmPassword');
        if (confirmPasswordField && confirmPasswordField.value.length > 0) {
          validateField(confirmPasswordField);
        }
      });
    }
    
    // Real-time validation for confirm password field
    const confirmPasswordField = document.getElementById('confirmPassword');
    if (confirmPasswordField) {
      confirmPasswordField.addEventListener('input', function() {
        validateField(this);
      });
    }

    // Availability checks for email and username
    const registerEmailField = document.getElementById('registerEmail');
    const registerUsernameField = document.getElementById('registerUsername');
    let emailCheckTimer = null;
    let usernameCheckTimer = null;

    if (registerEmailField) {
      registerEmailField.addEventListener('input', function() {
        setAvailability(this, '');
        validateField(this);
        emailCheckTimer = scheduleAvailabilityCheck(this, 'email', emailCheckTimer);
      });
      registerEmailField.addEventListener('blur', function() {
        emailCheckTimer = scheduleAvailabilityCheck(this, 'email', emailCheckTimer);
      });
    }

    if (registerUsernameField) {
      registerUsernameField.addEventListener('input', function() {
        setAvailability(this, '');
        validateField(this);
        usernameCheckTimer = scheduleAvailabilityCheck(this, 'username', usernameCheckTimer);
      });
      registerUsernameField.addEventListener('blur', function() {
        usernameCheckTimer = scheduleAvailabilityCheck(this, 'username', usernameCheckTimer);
      });
    }
    
    // Real-time validation for all fields
    registerForm.querySelectorAll('[data-validate]').forEach(field => {
      // Validate on input (as user types)
      field.addEventListener('input', function() {
        // Don't validate on every keystroke if field hasn't been touched yet
        if (this.classList.contains('was-validated') || this.value.trim().length > 0) {
          validateField(this);
        }
      });
      
      // Validate on blur (when field loses focus)
      field.addEventListener('blur', function() {
        validateField(this);
      });
      
      // Validate on change (for checkboxes)
      field.addEventListener('change', function() {
        validateField(this);
      });
    });
  
  // Form submission
  registerForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    this.classList.add('was-validated');
    
    // Validate all fields
    let allValid = true;
    this.querySelectorAll('[data-validate]').forEach(field => {
      if (!validateField(field)) {
        allValid = false;
      }
    });
    
    if (!allValid) {
      return false;
    }
    
    // Disable submit button and show loading state
    const submitBtn = document.getElementById('registerSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating Account...';
    
    // Submit form via AJAX
    const formData = new FormData(this);
    fetch('register.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (!response.ok) {
        console.error('HTTP Error:', response.status, response.statusText);
      }
      return response.text();
    })
    .then(text => {
      try {
        const data = JSON.parse(text);
        if (data.success) {
          if (window.ToastNotification) {
            ToastNotification.success('Account created! Redirecting to sign in...');
          }
          setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
            if (modal) modal.hide();
            const loginModal = new bootstrap.Modal(document.getElementById('accountModal'));
            loginModal.show();
            registerForm.reset();
            registerForm.classList.remove('was-validated');
          }, 1500);
        } else {
          if (window.ToastNotification) {
            ToastNotification.error(data.message || 'Registration failed. Please try again.');
          }
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Account';
        }
      } catch (e) {
        console.error('JSON Parse Error:', e, 'Response:', text);
        if (window.ToastNotification) {
          ToastNotification.error('Server error. Please try again.');
        }
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Account';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      if (window.ToastNotification) {
        ToastNotification.error('An error occurred. Please try again.');
      }
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Account';
    });
    
    return false;
  });
}

// Handle sign-in form validation
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    const loginError = document.getElementById('loginError');
    const hideLoginError = () => {
      if (loginError) {
        loginError.classList.add('d-none');
      }
    };

    // Real-time validation for all fields
    loginForm.querySelectorAll('[data-validate]').forEach(field => {
      // Validate on input (as user types)
      field.addEventListener('input', function() {
        hideLoginError();
        // Don't validate on every keystroke if field hasn't been touched yet
        if (this.classList.contains('was-validated') || this.value.trim().length > 0) {
          validateField(this);
        }
      });
      
      // Validate on blur (when field loses focus)
      field.addEventListener('blur', function() {
        validateField(this);
      });
    });
  
  loginForm.addEventListener('submit', function(e) {
    e.preventDefault();
    hideLoginError();
    
    this.classList.add('was-validated');
    
    // Validate fields
    let allValid = true;
    this.querySelectorAll('[data-validate]').forEach(field => {
      if (!validateField(field)) {
        allValid = false;
      }
    });
    
    if (!allValid) {
      return false;
    }
    
    // Disable submit button and show loading state
    const submitBtn = document.getElementById('loginSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing In...';
    
    // Submit login via AJAX
    const formData = new FormData(this);
    fetch('login_handler.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (!response.ok) {
        console.error('HTTP Error:', response.status, response.statusText);
      }
      return response.text();
    })
    .then(text => {
      try {
        const data = JSON.parse(text);
        if (data.success) {
          setTimeout(() => {
            window.location.href = data.redirect_url || 'home.php';
          }, 1000);
        } else {
          if (loginError) {
            loginError.textContent = 'Incorrect username or password.';
            loginError.classList.remove('d-none');
          }
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Sign In';
        }
      } catch (e) {
        console.error('JSON Parse Error:', e, 'Response:', text);
        if (loginError) {
          loginError.textContent = 'Incorrect username or password.';
          loginError.classList.remove('d-none');
        }
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Sign In';
      }
    })
    
    return false;
  });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  // Initialize password strength meter
  const passwordField = document.getElementById('registerPassword');
  if (passwordField) {
    updatePasswordStrength();
    updatePasswordRequirements();
  }
  
  // Handle page load messages and modals
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
