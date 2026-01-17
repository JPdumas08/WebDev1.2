<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
init_session();

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = null;

if ($productId > 0) {
    try {
        $stmt = $pdo->prepare('SELECT product_id, product_name, product_price, product_image, category, product_description FROM products WHERE product_id = :pid LIMIT 1');
        $stmt->execute([':pid' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $product = null;
    }
}

$pageTitle = $product ? 'Jeweluxe - ' . ($product['product_name'] ?? 'Product') : 'Product Not Found';
require_once __DIR__ . '/includes/header.php';

if (!$product) {
?>
  <main class="py-5">
    <div class="container text-center">
      <div class="alert alert-warning d-inline-flex align-items-center" role="alert">
        <i class="fas fa-box-open me-2"></i>
        We could not find that product.
      </div>
      <div class="mt-3">
        <a href="products.php" class="btn btn-primary">
          <i class="fas fa-arrow-left me-2"></i>Back to Products
        </a>
      </div>
    </div>
  </main>
<?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$img = !empty($product['product_image']) ? $product['product_image'] : 'image/placeholder.png';
$name = htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8');
$price = number_format((float) $product['product_price'], 2);
$category = htmlspecialchars($product['category'] ?? 'Jewelry', ENT_QUOTES, 'UTF-8');
$description = !empty($product['product_description']) ? htmlspecialchars($product['product_description'], ENT_QUOTES, 'UTF-8') : 'Designed for every occasion, this piece pairs timeless elegance with modern craftsmanship. Perfect as a gift or a personal staple.';
?>

<main class="product-detail-page">
  <section class="product-detail-hero">
    <div class="container">
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="home.php">Home</a></li>
          <li class="breadcrumb-item"><a href="products.php">Products</a></li>
          <li class="breadcrumb-item active" aria-current="page"><?php echo $name; ?></li>
        </ol>
      </nav>
      <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0">
          <div class="product-gallery card shadow-sm">
            <div class="p-3 text-center">
              <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo $name; ?>" class="img-fluid rounded" style="max-height: 520px; object-fit: cover; width: 100%;">
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="product-detail-card card shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <span class="badge bg-light text-dark border me-2"><i class="fas fa-tag me-1"></i><?php echo $category; ?></span>
                <span class="badge bg-success-soft text-success"><i class="fas fa-check-circle me-1"></i>In Stock</span>
              </div>
              <h1 class="h3 fw-bold mb-2"><?php echo $name; ?></h1>
              <div class="d-flex align-items-center mb-3">
                <div class="text-warning me-2">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star-half-alt"></i>
                </div>
                <small class="text-muted">4.5 (128 reviews)</small>
              </div>
              <div class="d-flex align-items-end mb-4">
                <div>
                  <div class="text-muted small">Price</div>
                  <div class="product-detail-price">₱<?php echo $price; ?></div>
                </div>
                <div class="ms-3 text-muted"><del>₱<?php echo number_format((float) $product['product_price'] * 1.2, 2); ?></del></div>
              </div>
              <p class="text-muted mb-4"><?php echo $description; ?></p>
              <ul class="list-unstyled mb-4">
                <li class="mb-2"><i class="fas fa-gem text-primary me-2"></i>Premium materials with careful finishing</li>
                <li class="mb-2"><i class="fas fa-shield-alt text-primary me-2"></i>Secure SSL checkout and buyer protection</li>
                <li class="mb-2"><i class="fas fa-truck text-primary me-2"></i>Fast shipping nationwide</li>
              </ul>
              <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
                <div class="pd-quantity d-flex align-items-center">
                  <label class="me-2 mb-0 text-muted" for="pdQuantity">Qty</label>
                  <input type="number" id="pdQuantity" class="form-control" value="1" min="1" max="99" style="width: 90px;">
                </div>
                <div class="flex-grow-1 d-flex gap-2">
                  <button id="pdAddToCart" class="btn btn-primary flex-fill">
                    <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                  </button>
                  <button id="pdBuyNow" class="btn btn-dark flex-fill">
                    <i class="fas fa-bolt me-2"></i>Buy Now
                  </button>
                </div>
              </div>
              <div class="row g-3 pd-meta">
                <div class="col-sm-6">
                  <div class="info-box">
                    <i class="fas fa-shield-alt text-success me-2"></i>
                    <div>
                      <div class="fw-semibold">Authenticity Guaranteed</div>
                      <small class="text-muted">Verified quality and materials</small>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="info-box">
                    <i class="fas fa-undo text-primary me-2"></i>
                    <div>
                      <div class="fw-semibold">7-Day Easy Return</div>
                      <small class="text-muted">Hassle-free returns</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card shadow-sm mb-4">
            <div class="card-body">
              <h4 class="fw-bold mb-3">Product Details</h4>
              <p class="text-muted"><?php echo $description; ?></p>
              <div class="row g-3">
                <div class="col-sm-6">
                  <div class="info-box neutral">
                    <i class="fas fa-palette text-primary me-2"></i>
                    <div>
                      <div class="fw-semibold">Category</div>
                      <small class="text-muted"><?php echo $category; ?></small>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="info-box neutral">
                    <i class="fas fa-weight-hanging text-primary me-2"></i>
                    <div>
                      <div class="fw-semibold">Care</div>
                      <small class="text-muted">Avoid harsh chemicals; wipe gently</small>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="info-box neutral">
                    <i class="fas fa-gift text-primary me-2"></i>
                    <div>
                      <div class="fw-semibold">Packaging</div>
                      <small class="text-muted">Comes with a premium gift pouch</small>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="info-box neutral">
                    <i class="fas fa-medal text-primary me-2"></i>
                    <div>
                      <div class="fw-semibold">Warranty</div>
                      <small class="text-muted">6-month craftsmanship guarantee</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="fw-bold mb-3">Why shoppers love this</h5>
              <ul class="list-unstyled mb-0">
                <li class="mb-3 d-flex"><i class="fas fa-check text-success me-2 mt-1"></i><span>Comfortable to wear all day with a secure fit.</span></li>
                <li class="mb-3 d-flex"><i class="fas fa-check text-success me-2 mt-1"></i><span>Pairs effortlessly with both casual and formal looks.</span></li>
                <li class="mb-3 d-flex"><i class="fas fa-check text-success me-2 mt-1"></i><span>Great as a gift—arrives ready to present.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-5 bg-light">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="mb-0 fw-bold">You may also like</h4>
        <a href="products.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-th me-1"></i>View all</a>
      </div>
      <div class="row g-3">
        <?php
        try {
            $suggest = $pdo->query('SELECT product_id, product_name, product_price, product_image FROM products ORDER BY RAND() LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $suggest = [];
        }
        if ($suggest):
          foreach ($suggest as $s):
            $sImg = !empty($s['product_image']) ? $s['product_image'] : 'image/placeholder.png';
            $sName = htmlspecialchars($s['product_name'], ENT_QUOTES, 'UTF-8');
            $sPrice = number_format((float) $s['product_price'], 2);
        ?>
        <div class="col-md-4">
          <div class="card h-100 shadow-sm product-suggestion" data-detail-url="product_detail.php?id=<?php echo (int) $s['product_id']; ?>">
            <div class="p-3 text-center">
              <img src="<?php echo htmlspecialchars($sImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo $sName; ?>" class="img-fluid rounded" style="height: 180px; object-fit: cover; width: 100%;">
            </div>
            <div class="card-body">
              <h6 class="fw-bold"><a href="product_detail.php?id=<?php echo (int) $s['product_id']; ?>" class="text-decoration-none text-dark"><?php echo $sName; ?></a></h6>
              <div class="text-primary fw-semibold mb-2">₱<?php echo $sPrice; ?></div>
              <a href="product_detail.php?id=<?php echo (int) $s['product_id']; ?>" class="btn btn-outline-primary btn-sm w-100">View details</a>
            </div>
          </div>
        </div>
        <?php endforeach; else: ?>
        <div class="col-12">
          <p class="text-muted">No recommendations available right now.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
  updateCartBadge();
  updateWishlistBadge();

  const qtyInput = document.getElementById('pdQuantity');
  const addBtn = document.getElementById('pdAddToCart');
  const buyBtn = document.getElementById('pdBuyNow');

  function sanitizeQty() {
    let val = parseInt(qtyInput.value, 10);
    if (isNaN(val) || val < 1) val = 1;
    if (val > 99) val = 99;
    qtyInput.value = val;
    return val;
  }

  async function addToCart(quantity, redirectAfter, productIdForBuyNow = null) {
    addBtn.disabled = true;
    buyBtn.disabled = true;
    const payload = new URLSearchParams({ product_id: '<?php echo (int) $productId; ?>', quantity: quantity });

    try {
      const resp = await fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload.toString()
      });
      const data = await resp.json();

      if (data && data.error === 'Please log in to use the cart.') {
        showToast('Please log in to continue', 'warning');
        const modalEl = document.getElementById('accountModal');
        if (modalEl && window.bootstrap) {
          const modal = new bootstrap.Modal(modalEl);
          modal.show();
        } else {
          window.location.href = 'login.php';
        }
        return;
      }

      if (!data || data.success !== true) {
        showToast('Could not add to cart right now', 'error');
        return;
      }

      updateCartBadge();

      // Only mark buy-now after a successful add
      if (redirectAfter && productIdForBuyNow) {
        sessionStorage.setItem('buyNowProductId', productIdForBuyNow);
      } else {
        sessionStorage.removeItem('buyNowProductId');
      }

      if (redirectAfter) {
        window.location.href = 'checkout.php';
      } else {
        showToast('Added to cart', 'success');
      }
    } catch (err) {
      showToast('Network error. Please try again.', 'error');
    } finally {
      addBtn.disabled = false;
      buyBtn.disabled = false;
    }
  }

  addBtn.addEventListener('click', function(e) {
    e.preventDefault();
    const qty = sanitizeQty();
    // Regular add: clear any previous buy-now flag
    sessionStorage.removeItem('buyNowProductId');
    addToCart(qty, false, null);
  });

  buyBtn.addEventListener('click', function(e) {
    e.preventDefault();
    const qty = sanitizeQty();
    const productId = '<?php echo (int) $productId; ?>';
    // Buy Now: set flag only after add succeeds
    addToCart(qty, true, productId);
  });

  document.querySelectorAll('.product-suggestion').forEach(function(card) {
    card.addEventListener('click', function(e) {
      if (e.target.closest('a')) return;
      const url = card.getAttribute('data-detail-url');
      if (url) window.location.href = url;
    });
  });
});

function showToast(message, type = 'info') {
  const types = {
    success: 'alert-success',
    error: 'alert-danger',
    warning: 'alert-warning',
    info: 'alert-info'
  };
  const toast = document.createElement('div');
  toast.className = 'alert ' + (types[type] || types.info) + ' alert-dismissible fade show position-fixed';
  toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 280px; border-radius: 6px;';
  toast.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 2000);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
