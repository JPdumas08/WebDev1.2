<?php
header('Content-Type: application/json');
require_once __DIR__ . '/init_session.php';
require_once 'db.php'; 
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$errors = [];
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Verify CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    $errors[] = 'Invalid security token. Please try again.';
}

// Sanitize and trim inputs
$first = trim($_POST['first_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$agree_terms = isset($_POST['agree_terms']) ? (bool)$_POST['agree_terms'] : false;

// ===== COMPREHENSIVE VALIDATION =====

// Validate First Name
if ($first === '') {
    $errors[] = 'First name is required.';
} elseif (!preg_match("/^[A-Za-z\s'-]{2,50}$/", $first)) {
    $errors[] = 'First name must be 2-50 characters (letters, spaces, or hyphens only).';
}

// Validate Last Name
if ($last === '') {
    $errors[] = 'Last name is required.';
} elseif (!preg_match("/^[A-Za-z\s'-]{2,50}$/", $last)) {
    $errors[] = 'Last name must be 2-50 characters (letters, spaces, or hyphens only).';
}

// Validate Email
if ($email === '') {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
} elseif (strpos($email, ' ') !== false) {
    $errors[] = 'Email cannot contain spaces.';
} else {
    // Check if email exists
    $emailCheck = $pdo->prepare("SELECT user_id FROM users WHERE LOWER(email_address) = LOWER(?)");
    try {
        $emailCheck->execute([$email]);
        if ($emailCheck->rowCount() > 0) {
            $errors[] = 'This email address is already in use.';
        }
    } catch (Exception $e) {
        $errors[] = 'Error validating email. Please try again.';
    }
}

// Validate Username
if ($username === '') {
    $errors[] = 'Username is required.';
} elseif (!preg_match('/^[A-Za-z0-9_]{4,20}$/', $username)) {
    $errors[] = 'Username must be 4-20 characters (letters, numbers, underscores only).';
} else {
    // Check if username exists in user_name column
    $usernameCheck = $pdo->prepare("SELECT user_id FROM users WHERE LOWER(user_name) = LOWER(?)");
    try {
        $usernameCheck->execute([$username]);
        if ($usernameCheck->rowCount() > 0) {
            $errors[] = 'This username is already in use.';
        }
    } catch (Exception $e) {
        $errors[] = 'Error validating username. Please try again.';
    }
}

// Validate Password
if ($password === '') {
    $errors[] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
} elseif (!preg_match('/[A-Z]/', $password)) {
    $errors[] = 'Password must contain at least one uppercase letter.';
} elseif (!preg_match('/[a-z]/', $password)) {
    $errors[] = 'Password must contain at least one lowercase letter.';
} elseif (!preg_match('/[0-9]/', $password)) {
    $errors[] = 'Password must contain at least one number.';
} elseif (!preg_match('/[!@#$%^&*?]/', $password)) {
    $errors[] = 'Password must contain at least one special character (!@#$%^&*?).';
} elseif (strpos($password, ' ') !== false) {
    $errors[] = 'Password cannot contain spaces.';
}

// Validate Confirm Password
if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match.';
}

// Validate Terms Agreement
if (!$agree_terms) {
    $errors[] = 'You must agree to the Terms and Conditions.';
}

if (empty($errors)) {
  try {
    // Hash password securely
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert user into database
    $sql = "INSERT INTO users (first_name, last_name, email_address, user_name, password) 
            VALUES (:first, :last, :email, :user_name, :password)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':first' => $first,
      ':last' => $last,
      ':email' => $email,
      ':user_name' => $username,
      ':password' => $hash
    ]);
    
    // Return success response
    echo json_encode([
      'success' => true,
      'message' => 'Account created successfully! Please sign in.'
    ]);
    exit;
    
  } catch (Exception $e) {
    $errors[] = 'Failed to create account. Please try again.';
    error_log('Registration error: ' . $e->getMessage());
  }
}

// Return error response
http_response_code(400);
echo json_encode([
  'success' => false,
  'message' => $errors[0] ?? 'Registration failed. Please try again.',
  'errors' => $errors
]);
exit;
