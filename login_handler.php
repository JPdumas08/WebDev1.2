<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
init_session();

// ===== RATE LIMITING FOR BRUTE FORCE PROTECTION =====
function checkRateLimit($identifier, $maxAttempts = 5, $windowSeconds = 900) {
    $cacheKey = 'login_attempts_' . md5($identifier);
    $sessionKey = 'login_attempts_' . md5($identifier);
    
    $attempts = $_SESSION[$sessionKey] ?? 0;
    $firstAttempt = $_SESSION[$sessionKey . '_time'] ?? 0;
    $now = time();
    
    // Reset if outside time window
    if ($now - $firstAttempt > $windowSeconds) {
        $_SESSION[$sessionKey] = 0;
        $_SESSION[$sessionKey . '_time'] = $now;
        return true;
    }
    
    if ($attempts >= $maxAttempts) {
        return false;
    }
    
    return true;
}

function recordFailedAttempt($identifier) {
    $sessionKey = 'login_attempts_' . md5($identifier);
    $_SESSION[$sessionKey] = ($_SESSION[$sessionKey] ?? 0) + 1;
    $_SESSION[$sessionKey . '_time'] = time();
}

function resetAttempts($identifier) {
    $sessionKey = 'login_attempts_' . md5($identifier);
    unset($_SESSION[$sessionKey]);
    unset($_SESSION[$sessionKey . '_time']);
}

function normalize_redirect(string $raw = ''): string {
    $raw = trim($raw);

    if ($raw === '') {
        return 'home.php';
    }

    // Prevent open redirects
    if (preg_match('#^https?://#i', $raw)) {
        return 'home.php';
    }

    if (!preg_match('/\.[a-z0-9]+($|\?)/i', $raw) && strpos($raw, '/') === false) {
        $raw .= '.php';
    }

    if (strpos($raw, '?') === false && strpos($raw, '&') !== false) {
        $raw = preg_replace('/&/', '?', $raw, 1);
    }

    return $raw;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Verify CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Security validation failed. Please try again.']);
    exit;
}

$emailOrUser = trim($_POST['email'] ?? $_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$redirectTo = normalize_redirect($_POST['redirect_to'] ?? '');

// ===== INPUT VALIDATION =====

// Validate Email or Username field
if ($emailOrUser === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email or username is required.']);
    exit;
}

if (strlen($emailOrUser) > 255 || strpos($emailOrUser, ' ') !== false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email or username format.']);
    exit;
}

// Validate Password field
if ($password === '' || (is_string($password) && ctype_space($password))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password is required.']);
    exit;
}

// Check rate limiting
if (!checkRateLimit($emailOrUser)) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Too many login attempts. Please try again in 15 minutes.'
    ]);
    exit;
}

try {
    // Find user by email or username
    $sql = "SELECT user_id, first_name, last_name, email_address, username, password 
            FROM users 
            WHERE email_address = :id OR username = :id 
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $emailOrUser]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generic error message (don't reveal if email/username or password is wrong)
    if (!$user || !password_verify($password, $user['password'] ?? '')) {
        recordFailedAttempt($emailOrUser);
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email/username or password. Please try again.'
        ]);
        exit;
    }

    // Authentication successful - reset rate limit and set session
    resetAttempts($emailOrUser);
    
    $_SESSION['user'] = [
        'id' => $user['user_id'],
        'username' => $user['username'],
        'email' => $user['email_address'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name']
    ];
    $_SESSION['user_id'] = $user['user_id'];
    session_regenerate_id(true);
    
    // Return success with redirect
    echo json_encode([
        'success' => true,
        'message' => 'Login successful!',
        'redirect_url' => $redirectTo
    ]);
    exit;

} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
    exit;
}
