<?php
require_once __DIR__ . '/admin_session.php';
require_once __DIR__ . '/../db.php';

// If already logged in, go to dashboard
if (admin_logged_in()) {
    header('Location: index.php');
    exit();
}

// Simple error messaging
$error = $_GET['error'] ?? '';
$redirect = $_GET['redirect'] ?? 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Jeweluxe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="includes/admin_styles.css?v=<?php echo time(); ?>">
    <style>
        body { background: #0f172a; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { background: #111827; border: 1px solid #1f2937; border-radius: 16px; padding: 32px; width: 100%; max-width: 420px; box-shadow: 0 20px 80px rgba(0,0,0,0.35); }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .brand .icon { width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #22d3ee, #818cf8); display: grid; place-items: center; color: #0f172a; font-size: 20px; font-weight: 700; }
        .form-control { background: #0b1220; border: 1px solid #1f2937; color: #e2e8f0; }
        .form-control:focus { border-color: #22d3ee; box-shadow: 0 0 0 3px rgba(34,211,238,0.25); }
        .btn-primary { background: linear-gradient(135deg, #22d3ee, #6366f1); border: none; }
        .btn-primary:hover { filter: brightness(1.05); }
        .alert { border-radius: 12px; }
        .helper { font-size: 0.9rem; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <div class="icon"><i class="fas fa-gem"></i></div>
            <div>
                <div style="font-size: 1.2rem; font-weight: 700;">Jeweluxe Admin</div>
                <div class="helper">Secure admin access</div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php
                    switch ($error) {
                        case 'invalid':
                            echo 'Invalid credentials. Please try again.';
                            break;
                        case 'session_expired':
                            echo 'Your session expired. Please sign in again.';
                            break;
                        case 'session_invalid':
                            echo 'Session validation failed. Please sign in again.';
                            break;
                        case 'unauthorized':
                            echo 'Access denied. Please sign in as an admin user.';
                            break;
                        default:
                            echo 'Please sign in to continue.';
                    }
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login_handler.php" novalidate>
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
            <div class="mb-3">
                <label class="form-label">Username or Email</label>
                <input type="text" name="username" class="form-control" placeholder="admin or admin@example.com" required autofocus>
                <div class="invalid-feedback">Username or email is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                <div class="invalid-feedback">Password is required.</div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Sign in</button>
            </div>
        </form>

        <p class="helper mt-3">Admin access is isolated from customer login. Keep this tab open alongside the store to monitor changes in real time.</p>
    </div>
    
    <script>
    // Custom validation for admin login
    document.querySelector('form').addEventListener('submit', function(e) {
        let isValid = true;
        const form = this;
        
        // Clear previous validation
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        
        // Validate username
        const username = form.querySelector('[name="username"]');
        if (!username.value.trim()) {
            username.classList.add('is-invalid');
            isValid = false;
        }
        
        // Validate password
        const password = form.querySelector('[name="password"]');
        if (!password.value.trim()) {
            password.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            const firstError = form.querySelector('.is-invalid');
            if (firstError) firstError.focus();
        }
    });
    </script>
</body>
</html>
