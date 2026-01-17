<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/db.php';

init_session();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email = trim($_GET['email'] ?? '');
$username = trim($_GET['username'] ?? '');

$response = [
    'success' => true,
    'emailExists' => false,
    'usernameExists' => false,
];

try {
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE LOWER(email_address) = LOWER(?) LIMIT 1');
        $stmt->execute([$email]);
        $response['emailExists'] = $stmt->fetchColumn() ? true : false;
    }

    if ($username !== '' && preg_match('/^[A-Za-z0-9_]{4,20}$/', $username)) {
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE LOWER(user_name) = LOWER(?) LIMIT 1');
        $stmt->execute([$username]);
        $response['usernameExists'] = $stmt->fetchColumn() ? true : false;
    }

    echo json_encode($response);
    exit;
} catch (Exception $e) {
    error_log('Availability check error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
