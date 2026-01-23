<?php
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
init_session();
$pageTitle = 'Jeweluxe - Products';
require_once __DIR__ . '/includes/header.php';
?>

  <style>
/* List View Styling */
.product-list-card {
  transition: all 0.3s ease;
  border: 1px solid #e0e0e0 !important;
}

.product-list-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
  border-color: #8b6f47 !important;
}

.product-list-card .card-body {
  padding: 1.5rem;
}

.product-list-card img {
  transition: transform 0.3s ease;
}

.product-list-card:hover img {
  transform: scale(1.05);
}

.product-list-card .card-title a:hover {
  color: #8b6f47 !important;
}

/* Responsive adjustments for list view */
@media (max-width: 768px) {
  .product-list-card .card-body {
    padding: 1rem;
  }
  
  .product-list-card .row > div {
    margin-bottom: 1rem;
  }
  
  .product-list-card .d-flex {
    flex-direction: column;
  }
  
  .product-list-card .d-flex button {
    width: 100% !important;
    margin-bottom: 0.5rem;
  }
}

/* View toggle button active state */
.view-toggle-btn.active {
  background-color: #8b6f47 !important;
  border-color: #8b6f47 !important;
  color: white !important;
}
</style>

<!-- ELEGANT JEWELRY HERO SECTION -->
<header class="jewelry-hero" style="background: linear-gradient(135deg, rgba(139, 111, 71, 0.75) 0%, rgba(168, 153, 104, 0.75) 100%), url('Video/wallpaper.jpg') center/cover no-repeat; min-height: 50vh; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
  <!-- Decorative overlay -->
  <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(ellipse at center top, rgba(255,255,255,0.1) 0%, rgba(0,0,0,0.3) 100%); pointer-events: none;"></div>
  
  <!-- Centered Content -->
  <div class="container text-center text-white position-relative" style="z-index: 2; max-width: 900px;">
    <!-- Decorative element -->
    <div style="margin-bottom: 30px; opacity: 0.9;">
      <i class="fas fa-gem" style="font-size: 3rem; color: #ffd700; text-shadow: 0 2px 10px rgba(0,0,0,0.3);"></i>
    </div>
    
    <!-- Main heading -->
    <h1 style="font-size: clamp(2.5rem, 8vw, 4rem); font-weight: 800; margin-bottom: 25px; letter-spacing: -0.5px; text-shadow: 0 4px 12px rgba(0,0,0,0.3); line-height: 1.2;">
      Explore Our Collection
    </h1>
    
    <!-- Subheading -->
    <p style="font-size: clamp(1.1rem, 3vw, 1.5rem); margin-bottom: 50px; color: #f5f5f5; font-weight: 500; letter-spacing: 0.5px; text-shadow: 0 2px 8px rgba(0,0,0,0.2); line-height: 1.6;">
      Discover exquisite pieces, crafted with precision and passion
    </p>
    
    <!-- Feature badges -->
    <div class="d-flex justify-content-center flex-wrap gap-3 mb-5">
      <span style="background: rgba(255,255,255,0.15); color: white; padding: 8px 16px; border-radius: 50px; font-size: 0.9rem; font-weight: 600; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
        <i class="fas fa-filter me-2"></i>500+ Products
      </span>
      <span style="background: rgba(255,255,255,0.15); color: white; padding: 8px 16px; border-radius: 50px; font-size: 0.9rem; font-weight: 600; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
        <i class="fas fa-truck me-2"></i>Free Shipping
      </span>
      <span style="background: rgba(255,255,255,0.15); color: white; padding: 8px 16px; border-radius: 50px; font-size: 0.9rem; font-weight: 600; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
        <i class="fas fa-shield-alt me-2"></i>Authentic Guaranteed
      </span>
    </div>
    
    <!-- CTA Button -->
    <div class="d-flex justify-content-center gap-4 flex-wrap">
      <a href="#products-section" class="btn btn-lg px-5 py-3" style="background-color: #8b6f47; border: none; color: white; font-weight: 700; letter-spacing: 1.5px; font-size: 1rem; box-shadow: 0 6px 25px rgba(0,0,0,0.25); transition: all 0.3s ease; border-radius: 8px;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 35px rgba(0,0,0,0.35)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 25px rgba(0,0,0,0.25)';">
        <i class="fas fa-shopping-bag me-2"></i>Shop Now
      </a>
    </div>
  </div>
</header>

<section class="container py-5">
  <?php
  // Load products once for all tabs
  require_once 'db.php';
  try {
    $pstmt = $pdo->query('SELECT product_id, product_name, product_price, product_image, category, product_stock FROM products WHERE is_archived = 0 ORDER BY product_id DESC');
    $prods = $pstmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    $prods = [];
  }
  ?>
<!-- MODERN CATEGORY FILTERS -->
  <div class="container mb-5">
    <div class="category-filters-wrapper">
      <div class="filter-header d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 fw-bold">Shop by Category</h2>
        <div class="view-options">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary active view-toggle-btn" data-view="grid" title="Grid View">
              <i class="fas fa-th"></i>
            </button>
            <button type="button" class="btn btn-outline-primary view-toggle-btn" data-view="list" title="List View">
              <i class="fas fa-list"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Sort Section -->
      <div class="row mb-4 align-items-end">
        <div class="col-md-12">
          <label for="sortSelect" class="form-label mb-2">Sort By</label>
          <select class="form-select" id="sortSelect">
            <option value="featured">Featured</option>
            <option value="price-low">Price: Low to High</option>
            <option value="price-high">Price: High to Low</option>
            <option value="newest">Newest First</option>
            <option value="name-asc">Name: A-Z</option>
          </select>
        </div>
      </div>
      
      <ul class="nav nav-pills modern-category-tabs justify-content-center mb-4" id="productTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active rounded-pill px-4 py-2" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true" data-category="all">
            <i class="fas fa-gem me-2"></i>All Products
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link rounded-pill px-4 py-2" id="bracelets-tab" data-bs-toggle="tab" data-bs-target="#bracelets" type="button" role="tab" aria-controls="bracelets" aria-selected="false" data-category="Bracelet">
            <i class="fas fa-circle-notch me-2"></i>Bracelets
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link rounded-pill px-4 py-2" id="earrings-tab" data-bs-toggle="tab" data-bs-target="#earrings" type="button" role="tab" aria-controls="earrings" aria-selected="false" data-category="Earrings">
            <i class="fas fa-dot-circle me-2"></i>Earrings
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link rounded-pill px-4 py-2" id="necklaces-tab" data-bs-toggle="tab" data-bs-target="#necklaces" type="button" role="tab" aria-controls="necklaces" aria-selected="false" data-category="Necklace">
            <i class="fas fa-link me-2"></i>Necklaces
          </button>
        </li>
      </ul>
      
      <div class="products-toolbar d-flex justify-content-between align-items-center">
        <div class="results-count">
          <span class="text-muted">Showing <span id="productCount">0</span> products</span>
        </div>
      </div>
    </div>
  </div>
  <!-- Tabs content -->
  <div class="tab-content products-container" id="productTabsContent">
    <!-- Products will be dynamically generated by JavaScript -->
  </div>
</section>

<!-- Comprehensive Product Page JavaScript -->
<script>
// Global product data
let allProducts = <?php echo json_encode($prods); ?>;
let currentView = 'grid';
let currentSort = 'featured';
let currentCategory = 'all';

document.addEventListener('DOMContentLoaded', function() {
  // Initialize functionality
  updateCartBadge();
  updateWishlistBadge();
  initializeProductDisplay();
  initializeSort();
  initializeViewToggle();
  initializeCategoryFilter();
  initializeAddToCart();
  initializeQuickView();
  initializeWishlist();
  initializeQuantitySelectors();
  initializeCheckout();
  initializeProductCardNavigation();
});

// Initialize product display with filtering
function initializeProductDisplay() {
  let filteredProducts = filterAndSortProducts(allProducts);
  updateProductDisplay(filteredProducts);
  updateProductCount(filteredProducts.length);
}

// Filter and sort products
function filterAndSortProducts(products) {
  let filtered = products;

  // Filter by category
  if (currentCategory && currentCategory !== 'all') {
    filtered = filtered.filter(p => 
      p.category && p.category.toLowerCase() === currentCategory.toLowerCase()
    );
  }

  // Sort products
  switch(currentSort) {
    case 'price-low':
      filtered.sort((a, b) => parseFloat(a.product_price) - parseFloat(b.product_price));
      break;
    case 'price-high':
      filtered.sort((a, b) => parseFloat(b.product_price) - parseFloat(a.product_price));
      break;
    case 'name-asc':
      filtered.sort((a, b) => a.product_name.localeCompare(b.product_name));
      break;
    case 'newest':
      filtered.reverse();
      break;
    default: // featured
      break;
  }

  return filtered;
}

// Update product display
function updateProductDisplay(products) {
  const container = document.querySelector('.products-container');
  if (!container) return;

  let html = '<div class="tab-pane fade show active" role="tabpanel">';
  html += currentView === 'grid' 
    ? '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">'
    : '<div class="row">';

  if (products.length === 0) {
    html += '<div class="col"><p class="text-muted text-center">No products found.</p></div>';
  } else {
    products.forEach(p => {
      const img = p.product_image || 'image/placeholder.png';
      const name = p.product_name;
      const price = '₱' + parseFloat(p.product_price).toFixed(2);
      const originalPrice = '₱' + (parseFloat(p.product_price) * 1.2).toFixed(2);
      const stock = parseInt(p.product_stock) || 0;
      const stockStatus = stock > 10 ? 'In Stock' : stock > 0 ? 'Low Stock' : 'Out of Stock';
      const stockClass = stock > 10 ? 'text-success' : stock > 0 ? 'text-warning' : 'text-danger';
      const stockIcon = stock > 10 ? 'fa-check-circle' : stock > 0 ? 'fa-exclamation-circle' : 'fa-times-circle';

      if (currentView === 'grid') {
        html += `
          <div class="col">
            <div class="modern-product-card h-100" data-detail-url="product_detail.php?id=${p.product_id}">
              <div class="product-image-container position-relative overflow-hidden">
                <img src="${img}" class="product-main-image w-100" alt="${name}" style="height: 300px; object-fit: cover; transition: transform 0.3s ease;">
                
                <div class="product-badges position-absolute top-0 start-0 p-3">
                  <span class="badge bg-danger me-1">New</span>
                  <span class="badge bg-success">-20%</span>
                </div>
                
                <button class="btn btn-light btn-sm position-absolute top-0 end-0 m-2 rounded-circle wishlist-btn shadow-sm" 
                        data-product-id="${p.product_id}" 
                        style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; border: none; background: rgba(255,255,255,0.95); z-index: 10; cursor: pointer;">
                  <i class="far fa-heart wishlist-icon text-danger"></i>
                </button>
                
                <div class="product-quick-actions position-absolute bottom-0 start-0 w-100 p-3">
                  <div class="d-flex gap-2">
                    <button class="btn btn-light btn-sm flex-fill quick-view-btn" data-product-id="${p.product_id}" data-product-name="${name}" data-product-price="${p.product_price}" data-product-image="${img}">
                      <i class="fas fa-eye me-1"></i>Quick View
                    </button>
                    <button class="btn btn-primary btn-sm flex-fill add-to-cart" 
                            data-id="${p.product_id}" 
                            data-name="${name}" 
                            data-price="${p.product_price}" 
                            data-image="${img}">
                      <i class="fas fa-shopping-cart me-1"></i>Add to Cart
                    </button>
                  </div>
                </div>
              </div>
              
              <div class="product-info p-3">
                <div class="product-category text-muted small mb-2">
                  <i class="fas fa-tag me-1"></i>${(p.category || 'Jewelry').charAt(0).toUpperCase() + (p.category || 'Jewelry').slice(1)}
                </div>
                
                <h5 class="product-title fw-bold mb-2"><a href="product_detail.php?id=${p.product_id}" class="text-decoration-none text-dark">${name}</a></h5>
                
                <div class="product-rating mb-2">
                  <div class="stars text-warning">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                  </div>
                  <small class="text-muted ms-1">(4.5)</small>
                </div>
                
                <div class="product-stock mb-2">
                  <small class="${stockClass}">
                    <i class="fas ${stockIcon} me-1"></i>${stockStatus} ${stock > 0 ? '(' + stock + ' available)' : ''}
                  </small>
                </div>
                
                <div class="product-price d-flex align-items-center justify-content-between mb-3">
                  <div>
                    <span class="current-price h5 text-primary fw-bold mb-0">${price}</span>
                    <span class="original-price text-muted small ms-2"><del>${originalPrice}</del></span>
                  </div>
                  <div class="discount-badge">
                    <span class="badge bg-success">Save 20%</span>
                  </div>
                </div>
                <button class="btn btn-primary w-100 rounded-pill add-to-cart" 
                        data-id="${p.product_id}" 
                        data-name="${name}" 
                        data-price="${p.product_price}" 
                        data-image="${img}"
                        ${stock <= 0 ? 'disabled' : ''}>
                  <i class="fas fa-shopping-cart me-2"></i>${stock <= 0 ? 'Out of Stock' : 'Add to Cart'}
                </button>
              </div>
            </div>
          </div>
        `;
      } else {
        // List view
        html += `
          <div class="col-12 mb-3">
            <div class="card product-list-card shadow-sm" data-detail-url="product_detail.php?id=${p.product_id}" style="border: 1px solid #e0e0e0; border-radius: 8px; transition: all 0.3s ease;">
              <div class="card-body p-4">
                <div class="row align-items-center">
                  <div class="col-md-2 col-12 mb-3 mb-md-0">
                    <img src="${img}" alt="${name}" class="img-fluid rounded" style="height: 150px; width: 100%; object-fit: cover; cursor: pointer;" onclick="window.location.href='product_detail.php?id=${p.product_id}'">
                  </div>
                  <div class="col-md-5 col-12">
                    <h5 class="card-title mb-2">
                      <a href="product_detail.php?id=${p.product_id}" class="text-decoration-none text-dark fw-bold">${name}</a>
                    </h5>
                    <p class="text-muted mb-2 small">
                      <i class="fas fa-tag me-1"></i>${(p.category || 'Jewelry').charAt(0).toUpperCase() + (p.category || 'Jewelry').slice(1)}
                    </p>
                    <div class="stars text-warning mb-2">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star-half-alt"></i>
                      <small class="text-muted ms-2">(4.5)</small>
                    </div>
                    <div class="mb-2">
                      <small class="${stockClass}">
                        <i class="fas ${stockIcon} me-1"></i><strong>${stockStatus}</strong> ${stock > 0 ? '(' + stock + ' available)' : ''}
                      </small>
                    </div>
                  </div>
                  <div class="col-md-2 col-12 text-center mb-3 mb-md-0">
                    <div class="mb-2">
                      <h5 class="text-primary fw-bold mb-0">${price}</h5>
                      <small class="text-muted"><del>${originalPrice}</del></small>
                    </div>
                    <span class="badge bg-success">Save 20%</span>
                  </div>
                  <div class="col-md-3 col-12">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end">
                      <button class="btn btn-sm btn-outline-danger wishlist-btn" 
                              data-product-id="${p.product_id}"
                              title="Add to Wishlist"
                              style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                        <i class="far fa-heart wishlist-icon"></i>
                      </button>
                      <button class="btn btn-sm btn-info quick-view-btn" 
                              data-product-id="${p.product_id}" 
                              data-product-name="${name}" 
                              data-product-price="${p.product_price}" 
                              data-product-image="${img}"
                              title="Quick View">
                        <i class="fas fa-eye me-1"></i> View
                      </button>
                      <button class="btn btn-sm btn-primary add-to-cart flex-fill flex-md-grow-0" 
                              data-id="${p.product_id}" 
                              data-name="${name}" 
                              data-price="${p.product_price}" 
                              data-image="${img}"
                              ${stock <= 0 ? 'disabled' : ''}
                              style="min-width: 120px;">
                        <i class="fas fa-shopping-cart me-1"></i> ${stock <= 0 ? 'Out of Stock' : 'Add to Cart'}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
      }
    });
  }

  html += '</div></div>';
  container.innerHTML = html;

  // Re-attach event listeners to new elements (not needed for wishlist/add-to-cart with event delegation)
  initializeQuickView();
  initializeProductCardNavigation();
}

function updateProductCount(count) {
  const countElement = document.getElementById('productCount');
  if (countElement) countElement.textContent = count;
}

// Initialize sort functionality
function initializeSort() {
  const sortSelect = document.getElementById('sortSelect');
  if (sortSelect) {
    sortSelect.addEventListener('change', function(e) {
      currentSort = e.target.value;
      initializeProductDisplay();
    });
  }
}

// Initialize category filter
function initializeCategoryFilter() {
  const categoryButtons = document.querySelectorAll('[data-category]');
  categoryButtons.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      currentCategory = this.dataset.category || 'all';
      initializeProductDisplay();
    });
  });
}

// Initialize grid/list view toggle
function initializeViewToggle() {
  document.querySelectorAll('.view-toggle-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      currentView = this.dataset.view;
      document.querySelectorAll('.view-toggle-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      initializeProductDisplay();
    });
  });
}

// Navigate to detail page when clicking a product card (excluding interactive controls)
function initializeProductCardNavigation() {
  document.querySelectorAll('.modern-product-card, .product-list-card').forEach(card => {
    if (card.dataset.navBound === '1') return;
    card.dataset.navBound = '1';
    card.addEventListener('click', function(e) {
      if (e.target.closest('button, a, input, select, textarea, .quick-view-btn, .wishlist-btn, .add-to-cart')) return;
      const url = card.getAttribute('data-detail-url');
      if (url) window.location.href = url;
    });
  });
}

// Initialize add to cart - using event delegation to avoid duplicate listeners
let addToCartInitialized = false;

function initializeAddToCart() {
  // Only attach the main event listener once
  if (addToCartInitialized) return;
  addToCartInitialized = true;

  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.add-to-cart');
    if (!btn) return;

    e.preventDefault();

    const productId = btn.dataset.id;
    const name = btn.dataset.name;
    const price = parseFloat(btn.dataset.price);
    const image = btn.dataset.image;

    btn.textContent = 'Adding...';
    btn.disabled = true;
    btn.classList.remove('btn-primary');
    btn.classList.add('btn-secondary');

    // Start fetching cart data immediately for faster badge update
    const cartFetch = fetch('get_cart.php').then(r => r.json());

    fetch('add_to_cart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'product_id=' + productId + '&quantity=1'
    })
    .then(response => response.json())
    .then(resp => {
      if (resp && resp.error === 'Please log in to use the cart.') {
        showToast('Please log in to add items to your cart', 'warning');
        setTimeout(() => {
          const accountModal = new bootstrap.Modal(document.getElementById('accountModal'));
          accountModal.show();
        }, 500);
        btn.textContent = 'Add to Cart';
        btn.disabled = false;
        btn.classList.add('btn-primary');
        btn.classList.remove('btn-secondary');
        return;
      }
      
      if (resp && resp.success) {
        // Update badge with cart data (use cached fetch from above)
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

        btn.textContent = 'Added!';
        setTimeout(() => {
          btn.textContent = 'Add to Cart';
          btn.disabled = false;
          btn.classList.add('btn-primary');
          btn.classList.remove('btn-secondary');
        }, 1500);
        showToast(name + ' added to cart!', 'success');
      } else {
        showToast('No stock available.', 'error');
        btn.textContent = 'Add to Cart';
        btn.disabled = false;
        btn.classList.add('btn-primary');
        btn.classList.remove('btn-secondary');
      }
    })
    .catch(error => {
      showToast('Server error', 'error');
      btn.textContent = 'Add to Cart';
      btn.disabled = false;
      btn.classList.add('btn-primary');
      btn.classList.remove('btn-secondary');
    });
  });
}

// Add to cart helper
function addToCart(name, price, image) {
  // For logged-in users, the database is the source of truth
  // Just trigger a badge update from server data
  updateCartBadge();
}

// Update cart badge count
function updateCartBadge() {
  const $body = document.querySelector('body');
  const isLoggedIn = $body.getAttribute('data-logged-in') === '1';
  
  if (isLoggedIn) {
    // For logged-in users, fetch from server (database is source of truth)
    fetch('get_cart.php')
      .then(response => response.json())
      .then(data => {
        if (data && data.success && data.cart && data.cart.items) {
          let totalItems = data.cart.items.reduce((sum, item) => sum + (item.quantity || 1), 0);
          const cartBadge = document.querySelector('.cart-count');
          if (cartBadge) {
            cartBadge.textContent = totalItems;
            cartBadge.style.display = totalItems > 0 ? 'inline-block' : 'none';
          }
        }
      })
      .catch(error => console.error('Error updating cart badge:', error));
  } else {
    // For guests, use localStorage
    let cart = JSON.parse(localStorage.getItem('jeweluxe_cart')) || [];
    let totalItems = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
    const cartBadge = document.querySelector('.cart-count');
    if (cartBadge) {
      cartBadge.textContent = totalItems;
      cartBadge.style.display = totalItems > 0 ? 'inline-block' : 'none';
    }
  }
}

// Update wishlist badge count
function updateWishlistBadge() {
  const wishlistBadge = document.querySelector('.wishlist-count');
  if (wishlistBadge) {
    fetch('get_wishlist.php')
      .then(response => response.json())
      .then(data => {
        if (data.success && data.wishlist) {
          wishlistBadge.textContent = data.wishlist.length;
        }
      })
      .catch(error => console.error('Error updating wishlist badge:', error));
  }
}

// Initialize quick view
function initializeQuickView() {
  // Get or create modal instance once
  const modalElement = document.getElementById('quickViewModal');
  let quickViewModalInstance = bootstrap.Modal.getInstance(modalElement);
  if (!quickViewModalInstance) {
    quickViewModalInstance = new bootstrap.Modal(modalElement, { backdrop: 'static', keyboard: true });
  }

  // Handle close button and backdrop properly
  modalElement.addEventListener('hidden.bs.modal', function () {
    // Ensure backdrop is removed
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
      backdrop.remove();
    });
    document.body.classList.remove('modal-open');
  });

  document.querySelectorAll('.quick-view-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const productId = this.dataset.productId;
      const productName = this.dataset.productName;
      const productPrice = parseFloat(this.dataset.productPrice);
      const productImage = this.dataset.productImage;

      document.getElementById('quickViewImage').src = productImage;
      document.getElementById('quickViewName').textContent = productName;
      document.getElementById('quickViewPrice').textContent = '₱' + productPrice.toFixed(2);
      document.getElementById('quickViewOriginal').innerHTML = '<del>₱' + (productPrice * 1.2).toFixed(2) + '</del>';
      document.getElementById('quickViewQuantity').value = 1;

      // Store product info for add to cart
      document.getElementById('quickViewAddToCart').dataset.productId = productId;
      document.getElementById('quickViewAddToCart').dataset.productName = productName;
      document.getElementById('quickViewAddToCart').dataset.productPrice = productPrice;
      document.getElementById('quickViewAddToCart').dataset.productImage = productImage;

      // Show the modal using the same instance
      quickViewModalInstance.show();
    });
  });
}

// Initialize quantity selectors
function initializeQuantitySelectors() {
  const quantityInput = document.getElementById('quickViewQuantity');
  if (quantityInput) {
    const decBtn = document.getElementById('decreaseQty');
    const incBtn = document.getElementById('increaseQty');

    const clamp = (val) => {
      const num = isNaN(parseInt(val, 10)) ? 1 : parseInt(val, 10);
      if (num < 1) return 1;
      if (num > 99) return 99;
      return num;
    };

    const setQty = (val) => {
      const clamped = clamp(val);
      quantityInput.value = clamped;
      return clamped;
    };

    if (decBtn) {
      decBtn.addEventListener('click', function(e) {
        e.preventDefault();
        setQty(clamp(quantityInput.value) - 1);
      });
    }

    if (incBtn) {
      incBtn.addEventListener('click', function(e) {
        e.preventDefault();
        setQty(clamp(quantityInput.value) + 1);
      });
    }

    quantityInput.addEventListener('change', function() {
      setQty(quantityInput.value);
    });

    quantityInput.addEventListener('blur', function() {
      setQty(quantityInput.value);
    });

    // Quick view add to cart
    document.getElementById('quickViewAddToCart').addEventListener('click', function() {
      const quantity = parseInt(document.getElementById('quickViewQuantity').value);
      const productId = this.dataset.productId;
      const name = this.dataset.productName;
      const price = parseFloat(this.dataset.productPrice);
      const image = this.dataset.productImage;

      this.textContent = 'Adding...';
      this.disabled = true;

      // Start fetching cart data immediately for faster badge update
      const cartFetch = fetch('get_cart.php').then(r => r.json());

      fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&quantity=' + quantity
      })
      .then(response => response.json())
      .then(resp => {
        if (resp && resp.success) {
          // Update badge with cart data (use cached fetch from above)
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

          showToast('Added ' + quantity + ' item(s) to cart!', 'success');
          const modal = bootstrap.Modal.getInstance(document.getElementById('quickViewModal'));
          if (modal) {
            modal.hide();
          }
          this.textContent = 'Add to Cart';
          this.disabled = false;
        } else {
          showToast('Could not add to cart', 'error');
          this.textContent = 'Add to Cart';
          this.disabled = false;
        }
      });
    });
  }
}

// Initialize wishlist - using event delegation to avoid duplicate listeners
let wishlistInitialized = false;

function initializeWishlist() {
  // Only attach the main event listener once
  if (wishlistInitialized) return;
  wishlistInitialized = true;

  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.wishlist-btn');
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();
    
    const productId = btn.dataset.productId;
    const icon = btn.querySelector('.wishlist-icon');
    
    if (!<?php echo !empty($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
      showToast('Please log in to use the wishlist', 'warning');
      setTimeout(() => {
        const accountModal = new bootstrap.Modal(document.getElementById('accountModal'));
        accountModal.show();
      }, 500);
      return;
    }
    
    fetch('add_to_wishlist.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        if (data.action === 'added') {
          // Just highlight the heart with red
          if (icon) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            icon.style.color = '#dc3545';
          }
          showToast('Added to wishlist', 'success');
          updateWishlistBadge();
        } else if (data.action === 'removed') {
          // Remove the red highlight
          if (icon) {
            icon.classList.remove('fas');
            icon.classList.add('far');
            icon.style.color = '';
          }
          showToast('Removed from wishlist', 'info');
          updateWishlistBadge();
        }
      } else {
        showToast('Error updating wishlist', 'error');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('An error occurred', 'error');
    });
  });
}

// Initialize checkout
function initializeCheckout() {
  const checkoutBtn = document.querySelector('[data-checkout]');
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', function() {
      const cart = JSON.parse(localStorage.getItem('jeweluxe_cart')) || [];
      if (cart.length === 0) {
        showToast('Your cart is empty', 'warning');
        return;
      }
      window.location.href = 'checkout.php';
    });
  }
}

// Toast notification helper
function showToast(message, type = 'info') {
  const types = {
    'success': 'alert-success',
    'error': 'alert-danger',
    'warning': 'alert-warning',
    'info': 'alert-info'
  };

  const toast = document.createElement('div');
  toast.className = 'alert ' + (types[type] || types['info']) + ' alert-dismissible fade show position-fixed';
  toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 6px;';
  toast.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.remove();
  }, 2000);
}

// Load wishlist on page load
<?php if (!empty($_SESSION['user_id'])): ?>
fetch('get_wishlist.php')
  .then(response => response.json())
  .then(data => {
    if (data.success && data.wishlist) {
      const wishlistIds = data.wishlist;
      document.querySelectorAll('.wishlist-btn').forEach(btn => {
        const productId = parseInt(btn.dataset.productId);
        if (wishlistIds.includes(productId)) {
          const icon = btn.querySelector('.wishlist-icon');
          if (icon) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            icon.style.color = '#dc3545';
          }
        }
      });
    }
  })
  .catch(error => console.error('Error loading wishlist:', error));
<?php endif; ?>
</script>

<!-- QUICK VIEW MODAL -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="quickViewModalLabel">Product Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-5">
            <img id="quickViewImage" src="" class="img-fluid rounded" alt="Product">
          </div>
          <div class="col-md-7">
            <h3 id="quickViewName"></h3>
            <div id="quickViewRating" class="mb-3">
              <div class="stars text-warning mb-2">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
              <small class="text-muted">(4.5 out of 5 stars)</small>
            </div>
            <div class="mb-3">
              <span id="quickViewPrice" class="h4 text-primary fw-bold"></span>
              <span id="quickViewOriginal" class="text-muted ms-2"><del></del></span>
            </div>
            <p id="quickViewCategory" class="text-muted mb-3"></p>
            <p id="quickViewDescription" class="mb-3">Premium jewelry crafted with attention to detail. Perfect for any occasion.</p>
            
            <!-- Quantity Selector -->
            <div class="mb-3">
              <label class="form-label">Quantity:</label>
              <div class="quantity-selector" style="width: fit-content;">
                <button type="button" id="decreaseQty">-</button>
                <input type="number" id="quickViewQuantity" value="1" min="1" max="99" aria-label="Quantity">
                <button type="button" id="increaseQty">+</button>
              </div>
            </div>

            <div class="d-grid gap-2">
              <button class="btn btn-primary btn-lg" id="quickViewAddToCart">
                <i class="fas fa-shopping-cart me-2"></i>Add to Cart
              </button>
              <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
              </button>
            </div>

            <div class="mt-4 pt-3 border-top">
              <div class="row text-center">
                <div class="col-4">
                  <i class="fas fa-truck text-primary mb-2" style="font-size: 1.5rem;"></i>
                  <small class="d-block">Free Shipping</small>
                </div>
                <div class="col-4">
                  <i class="fas fa-shield-alt text-success mb-2" style="font-size: 1.5rem;"></i>
                  <small class="d-block">Secure Payment</small>
                </div>
                <div class="col-4">
                  <i class="fas fa-undo text-info mb-2" style="font-size: 1.5rem;"></i>
                  <small class="d-block">Easy Returns</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php';
?>