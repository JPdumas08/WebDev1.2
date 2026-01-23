<?php
/**
 * Account Sidebar Component
 * Reusable sidebar menu for all account pages
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    return; // Do not show sidebar if not logged in
}

$user_id = (int) $_SESSION['user_id'];

/* ==========================
   FETCH USER DATA
========================== */
$user_name  = 'Valued Customer';
$user_email = '';

try {   
    $user_sql = "
        SELECT first_name, last_name, email_address
        FROM users
        WHERE user_id = :uid
        LIMIT 1
    ";

    $stmt = $pdo->prepare($user_sql);
    $stmt->execute(['uid' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $first = trim($user['first_name'] ?? '');
        $last  = trim($user['last_name'] ?? '');

        if ($first !== '' || $last !== '') {
            $user_name = trim($first . ' ' . $last);
        }

        $user_email = $user['email_address'] ?? '';
    }
} catch (PDOException $e) {
    error_log('Sidebar user fetch error: ' . $e->getMessage());
}

/* ==========================
   ACTIVE PAGE DETECTION
========================== */
if (!isset($active_page)) {
    $active_page = basename($_SERVER['PHP_SELF'], '.php');

    if ($active_page === 'my_messages') {
        $active_page = 'messages';
    }
}

/* ==========================
   MENU CONFIG
========================== */
$menu_items = [
    'account_settings' => [
        'url'   => 'account_settings.php',
        'label' => 'Personal Information',
        'icon'  => 'fas fa-user'
    ],
    'address' => [
        'url'   => 'address.php',
        'label' => 'Address',
        'icon'  => 'fas fa-map-marker-alt'
    ],
    'wishlist' => [
        'url'   => 'wishlist.php',
        'label' => 'Wishlist',
        'icon'  => 'fas fa-heart'
    ],
    'order_history' => [
        'url'   => 'order_history.php',
        'label' => 'My Orders',
        'icon'  => 'fas fa-shopping-bag'
    ],
    'messages' => [
        'url'   => 'my_messages.php',
        'label' => 'Messages',
        'icon'  => 'fas fa-envelope'
    ],
    'logout' => [
        'url'   => 'logout.php',
        'label' => 'Logout',
        'icon'  => 'fas fa-sign-out-alt'
    ]
];
?>

<aside class="col-lg-3">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-3">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="avatar-circle">
                <?= strtoupper(substr($user_name, 0, 1)); ?>
            </div>
            <div>
                <div class="text-muted small">Hello,</div>
                <div class="fw-semibold"><?= htmlspecialchars($user_name); ?></div>
                <?php if ($user_email): ?>
                    <div class="small text-muted"><?= htmlspecialchars($user_email); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="list-group list-group-flush orders-nav">
            <?php foreach ($menu_items as $key => $item): ?>
                <a
                    href="<?= htmlspecialchars($item['url']); ?>"
                    class="list-group-item <?= $active_page === $key ? 'active' : '' ?> <?= $key === 'logout' ? 'text-danger' : '' ?>"
                >
                    <i class="<?= htmlspecialchars($item['icon']); ?> me-2"></i>
                    <?= htmlspecialchars($item['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body text-center">
            <div class="fs-5 fw-semibold mb-2">Need Help?</div>
            <p class="text-muted small mb-3">
                Have questions or concerns regarding your account? Contact our support team.
            </p>
            <a href="contactus.php" class="btn btn-outline-primary w-100">
                Contact Support
            </a>
        </div>
    </div>
</aside>
