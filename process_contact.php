<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';
init_session();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contactus.php');
    exit;
}

// Get form data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

// Validation
$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Name must be at least 2 characters';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

if (empty($subject)) {
    $errors[] = 'Subject is required';
}

if (empty($message) || strlen($message) < 10) {
    $errors[] = 'Message must be at least 10 characters';
}

if (!empty($errors)) {
    header('Location: contactus.php?error=' . urlencode(implode(', ', $errors)));
    exit;
}

try {
    // Insert message into database
    $sql = "INSERT INTO contact_messages (user_id, name, email, subject, message, status, created_at) 
            VALUES (:user_id, :name, :email, :subject, :message, 'new', NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':name' => $name,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message
    ]);
    
    $message_id = $pdo->lastInsertId();
    
    // Create admin notification
    $admin_notif_sql = "INSERT INTO admin_notifications (type, title, message, related_id, is_read, created_at) 
                        VALUES ('new_message', :title, :message, :related_id, 0, NOW())";
    
    $admin_notif_stmt = $pdo->prepare($admin_notif_sql);
    $admin_notif_stmt->execute([
        ':title' => 'New Contact Message',
        ':message' => 'New message from ' . $name . ' - Subject: ' . $subject,
        ':related_id' => $message_id
    ]);
    
    // Redirect with success
    header('Location: contactus.php?success=sent');
    exit;
    
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    header('Location: contactus.php?error=' . urlencode('An error occurred. Please try again.'));
    exit;
}
?>
