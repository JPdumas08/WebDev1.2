<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';

// Check if we should show login modal instead of redirecting
$showLoginModal = isset($_GET['showLogin']);

// Redirect if not logged in (unless we're showing the login modal)
if (empty($_SESSION['user_id']) && !$showLoginModal) {
    header('Location: login.php?redirect=wishlist');
    exit();
}

$user_id = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$success_message = '';

// Handle remove from wishlist (only if logged in)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_wishlist']) && $user_id > 0) {
    $product_id = (int) $_POST['product_id'];
    $pdo->prepare("DELETE FROM wishlist WHERE user_id = :uid AND product_id = :pid")
        ->execute([':uid' => $user_id, ':pid' => $product_id]);
    $success_message = 'Item removed from wishlist.';
}

// Fetch wishlist items with product details (only if logged in)
$wishlist_items = [];
if ($user_id > 0) {
    $wishlist_sql = "SELECT w.*, p.product_name, p.product_price, p.product_image
                     FROM wishlist w
                     JOIN products p ON w.product_id = p.product_id
                     WHERE w.user_id = :uid
                     ORDER BY w.added_at DESC";
    $stmt = $pdo->prepare($wishlist_sql);
    $stmt->execute([':uid' => $user_id]);
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php
$pageTitle = 'My Wishlist - Jeweluxe';
include 'includes/header.php';
?>
<link rel="stylesheet" href="styles.css">
<body class="order-history-page wishlist-page">

    <section class="orders-hero">
        <div class="container-xl">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light btn-sm" onclick="window.history.back();" type="button" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h1 class="mb-0 text-white">My Wishlist</h1>
            </div>
        </div>
    </section>

    <div class="orders-wrapper py-5 wishlist-wrapper">
        <div class="container-xl">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row wishlist-layout g-4">
                <div class="col-lg-8 col-xl-9">
                    <?php if (empty($wishlist_items)): ?>
                        <div class="card shadow-sm border-0 rounded-4">
                            <div class="card-body text-center py-5">
                                <div class="mb-3" style="font-size: 3rem;">💝</div>
                                <h5 class="mb-2">Your wishlist is empty</h5>
                                <p class="text-muted mb-3">Save items you love for later!</p>
                                <a href="products.php" class="btn btn-primary">Browse Products</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card shadow-sm border-0 rounded-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">Saved Items (<?php echo count($wishlist_items); ?>)</h5>
                                </div>

                                <div class="wishlist-items">
                                    <?php foreach ($wishlist_items as $item): ?>
                                        <div class="card wishlist-card border-0 shadow-sm">
                                            <div class="wishlist-thumb">
                                                <img src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                                                     class="img-fluid" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                            </div>
                                            <div class="card-body p-3 d-flex flex-column">
                                                <div>
                                                    <h6 class="card-title wishlist-title mb-1 text-truncate" title="<?php echo htmlspecialchars($item['product_name']); ?>"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                    <div class="wishlist-price price mb-1">₱<?php echo number_format($item['product_price'], 2); ?></div>
                                                    <div class="wishlist-status text-success meta mb-1">Available</div>
                                                    <div class="wishlist-added text-muted meta">Added: <?php echo date('M j, Y', strtotime($item['added_at'])); ?></div>
                                                </div>
                                                <div class="wishlist-actions d-flex gap-2 mt-3">
                                                    <button class="btn btn-sm btn-primary flex-grow-1 add-to-cart-btn" 
                                                            data-product-id="<?php echo $item['product_id']; ?>">
                                                        Add to Cart
                                                    </button>
                                                    <form method="POST" class="d-inline flex-grow-1">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                        <button type="submit" name="remove_from_wishlist" class="btn btn-sm btn-outline-secondary w-100">
                                                            Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-4 col-xl-3 wishlist-sidebar">
                    <div class="card shadow-sm border-0 rounded-4 mb-3">
                        <div class="card-body wishlist-tips">
                            <h6 class="mb-3 d-flex align-items-center gap-2"><span class="tips-icon">💡</span> Wishlist Tips</h6>
                            <ul class="small text-muted mb-0">
                                <li class="mb-1">Items stay saved for you.</li>
                                <li class="mb-1">Price drop alerts.</li>
                                <li class="mb-1">Share with friends.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body text-center wishlist-cta">
                            <h6 class="mb-2">Keep Shopping</h6>
                            <a href="products.php" class="btn btn-outline-primary w-100">Browse Products</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                
                // Start fetching cart data immediately for faster badge update
                const cartFetch = fetch('get_cart.php').then(r => r.json());
                
                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=1`
                })
                .then(response => response.json())
                .then(data => {
                    // Check if user needs to log in
                    if (data.error === 'Please log in to use the cart.') {
                        ToastNotification.warning('Please log in to add items to your cart.');
                        // Open account modal for login
                        setTimeout(() => {
                            const accountModal = new bootstrap.Modal(document.getElementById('accountModal'));
                            accountModal.show();
                        }, 500);
                        return;
                    }
                    
                    if (data.success) {
                        // Update cart badge with data fetched in parallel
                        cartFetch.then(cartData => {
                            if (cartData && cartData.success && cartData.cart && cartData.cart.items) {
                                let totalItems = cartData.cart.items.reduce((sum, item) => sum + (item.quantity || 1), 0);
                                const cartBadge = document.querySelector('.cart-count');
                                if (cartBadge) {
                                    cartBadge.textContent = totalItems;
                                    cartBadge.style.display = totalItems > 0 ? 'inline-block' : 'none';
                                }
                            }
                        }).catch(error => console.error('Error updating cart badge:', error));
                        
                        ToastNotification.success('Added to cart!');
                    } else {
                        ToastNotification.error(data.message || 'Failed to add to cart.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    ToastNotification.error('An error occurred.');
                });
            });
        });
        
        // Show login modal if showLogin parameter is present
        <?php if ($showLoginModal): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const accountModal = document.getElementById('accountModal');
            if (accountModal) {
                const modal = new bootstrap.Modal(accountModal);
                modal.show();
                
                // Remove showLogin parameter from URL after showing modal
                const url = new URL(window.location);
                url.searchParams.delete('showLogin');
                window.history.replaceState({}, '', url);
            }
        });
        <?php endif; ?>
