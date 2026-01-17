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
<body class="order-history-page">

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

    <div class="orders-wrapper py-5">
        <div class="container-xl">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-9">
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

                                <div class="row g-3">
                                    <?php foreach ($wishlist_items as $item): ?>
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm">
                                                <div class="row g-0">
                                                    <div class="col-4">
                                                        <img src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                                                             class="img-fluid rounded-start" 
                                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                             style="height: 150px; object-fit: cover;">
                                                    </div>
                                                    <div class="col-8">
                                                        <div class="card-body">
                                                            <h6 class="card-title mb-2"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                            <p class="card-text mb-2">
                                                                <strong class="text-primary">₱<?php echo number_format($item['product_price'], 2); ?></strong>
                                                            </p>
                                                            <p class="small text-success mb-2">Available</p>
                                                            <p class="small text-muted mb-3">Added: <?php echo date('M j, Y', strtotime($item['added_at'])); ?></p>
                                                            <div class="d-flex gap-2">
                                                                <button class="btn btn-sm btn-primary add-to-cart-btn" 
                                                                        data-product-id="<?php echo $item['product_id']; ?>">
                                                                    Add to Cart
                                                                </button>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                                    <button type="submit" name="remove_from_wishlist" class="btn btn-sm btn-outline-danger">
                                                                        Remove
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-3">
                    <div class="card shadow-sm border-0 rounded-4 mb-3">
                        <div class="card-body">
                            <h6 class="mb-3">💡 Wishlist Tips</h6>
                            <ul class="small text-muted mb-0">
                                <li class="mb-2">Items in your wishlist are saved forever</li>
                                <li class="mb-2">We'll notify you of price drops</li>
                                <li class="mb-2">Share your wishlist with friends</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body text-center">
                            <h6 class="mb-3">Keep Shopping</h6>
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
