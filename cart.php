<?php
$pageTitle = 'Jeweluxe - Cart';
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
init_session();
require_once __DIR__ . '/includes/header.php';

// Get cart from database instead of session
$user_id = $_SESSION['user_id'] ?? null;
$cart = [];
$subtotal = 0;
$shipping = 0;  // Initialize to 0 since nothing is selected yet
$total = 0;     // Initialize to 0 since nothing is selected yet

if ($user_id) {
    try {
        // Get user's cart from database
        $stmt = $pdo->prepare('SELECT cart_id FROM cart WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $user_id]);
        $cartRow = $stmt->fetch();
        
        if ($cartRow) {
            $cart_id = (int)$cartRow['cart_id'];
            
            // Get cart items with product details
            $q = "SELECT ci.cart_item_id AS item_id, ci.product_id, ci.quantity, p.product_name AS name, p.product_image AS image, ci.price
                FROM cart_items ci
                JOIN products p ON p.product_id = ci.product_id
                WHERE ci.cart_id = :cart_id
                ORDER BY ci.cart_item_id DESC";
            $cstmt = $pdo->prepare($q);
            $cstmt->execute([':cart_id' => $cart_id]);
            $cart = $cstmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate subtotal
            foreach ($cart as $item) {
                $itemTotal = (float)$item['price'] * (int)$item['quantity'];
                $subtotal += $itemTotal;
            }
        }
    } catch (Exception $e) {
        error_log('Cart retrieval error: ' . $e->getMessage());
    }
}

$total += $subtotal;

// Determine if cart is empty
$isEmpty = empty($cart);
?>

  <main class="flex-grow-1">
<!-- MODERN ECOMMERCE CART HERO -->
    <header class="cart-hero text-white py-5" style="background: linear-gradient(135deg, rgba(124, 92, 43, 0.9), rgba(139, 69, 19, 0.9)), url(Video/wallpaper.jpg) center/cover no-repeat; min-height: 50vh; display: flex; align-items: center;">
      <div class="container">
        <div class="hero-content text-center">
          <div class="hero-breadcrumb mb-3">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb justify-content-center bg-transparent">
                <li class="breadcrumb-item"><a href="home.php" class="text-white text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="products.php" class="text-white text-decoration-none">Products</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Shopping Cart</li>
              </ol>
            </nav>
          </div>
          <h1 class="display-3 fw-bold mb-4">Shopping Cart</h1>
          <p class="lead mb-4">Review your selected jewelry items and proceed to secure checkout</p>
          <div class="cart-progress mb-4">
            <div class="progress-steps d-flex justify-content-center align-items-center">
              <div class="step completed">
                <div class="step-icon"><i class="fas fa-shopping-cart"></i></div>
                <span class="step-label">Cart</span>
              </div>
              <div class="step-connector"></div>
              <div class="step">
                <div class="step-icon"><i class="fas fa-credit-card"></i></div>
                <span class="step-label">Checkout</span>
              </div>
              <div class="step-connector"></div>
              <div class="step">
                <div class="step-icon"><i class="fas fa-check"></i></div>
                <span class="step-label">Complete</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

<!-- MODERN CART CONTENT -->
    <section class="cart-content py-5">
      <div class="container">
        <!-- Empty Cart Content -->
        <div id="emptyCart" class="empty-cart-content text-center py-5" style="display: <?php echo $isEmpty ? 'block' : 'none'; ?>;">
          <div class="empty-cart-illustration mb-4">
            <div class="empty-cart-icon">
              <i class="fas fa-shopping-cart"></i>
            </div>
          </div>
          <h3 class="empty-cart-title mb-3">Your cart is empty</h3>
          <p class="empty-cart-description text-muted mb-4">Looks like you haven't found your perfect piece yet. Let's change that!</p>
          <div class="empty-cart-actions">
            <a href="products.php" class="btn btn-primary btn-lg rounded-pill me-3">
              <i class="fas fa-shopping-bag me-2"></i>Start Shopping
            </a>
            <button class="btn btn-outline-primary btn-lg rounded-pill" onclick="window.location.href='home.php'">
              <i class="fas fa-home me-2"></i>Back to Home
            </button>
          </div>
          
          <!-- Popular Products Suggestion -->
          <div class="popular-suggestions mt-5">
            <h4 class="mb-4">You might also like</h4>
            <div class="row g-3">
              <?php
              // Fetch random products to suggest
              try {
                $suggest_sql = "SELECT product_id, product_name, product_price, product_image FROM products ORDER BY RAND() LIMIT 4";
                $suggest_stmt = $pdo->prepare($suggest_sql);
                $suggest_stmt->execute();
                $suggested_products = $suggest_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($suggested_products as $product):
                  $img = !empty($product['product_image']) ? $product['product_image'] : 'image/placeholder.png';
                  $name = htmlspecialchars($product['product_name']);
                  $price = number_format($product['product_price'], 2);
              ?>
              <div class="col-md-3">
                <div class="suggestion-card" style="cursor: pointer;" onclick="window.location.href='products.php'">
                  <img src="<?= $img ?>" alt="<?= $name ?>" class="w-100 rounded mb-2" style="height: 200px; object-fit: cover;">
                  <h6><?= $name ?></h6>
                  <p class="text-primary fw-bold">₱<?= $price ?></p>
                </div>
              </div>
              <?php 
                endforeach;
              } catch (Exception $e) {
                echo '<p class="text-muted">Unable to load suggestions</p>';
              }
              ?>
            </div>
          </div>
        </div>
        
<!-- Modern Cart Items Content -->
        <div id="cartItems" class="cart-items-container" style="display: <?php echo !$isEmpty ? 'block' : 'none'; ?>;">
          <div class="row">
            <div class="col-lg-8">
              <div class="cart-items-wrapper">
                <div class="cart-header d-flex justify-content-between align-items-center mb-4">
                  <h3 class="cart-title mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>Cart Items 
                    <span class="badge bg-primary ms-2"><?php echo count($cart); ?></span>
                  </h3>
                  <button class="btn btn-outline-danger btn-sm" id="clearCartBtn" data-bs-toggle="modal" data-bs-target="#confirmClearModal">
                    <i class="fas fa-trash-alt me-1"></i>Clear Cart
                  </button>
                </div>
                
                <!-- Select All Checkbox -->
                <div class="mb-3">
                  <label class="form-check">
                    <input type="checkbox" class="form-check-input" id="selectAllCart">
                    <span class="form-check-label"><strong>Select All Items</strong></span>
                  </label>
                </div>
                
                <!-- Dynamic cart items will be inserted here by footer.php JavaScript -->
                <div class="cart-item-list">
                  <!-- Cart items dynamically loaded -->
                </div>
              </div>
            </div>
            
            <div class="col-lg-4">
              <div class="order-summary-modern">
                <div class="summary-header">
                  <h4 class="summary-title">
                    <i class="fas fa-receipt me-2"></i>Order Summary
                  </h4>
                </div>
                
                <div class="summary-body">
                  <div class="summary-item">
                    <div class="summary-label">Subtotal (<span id="cartPageSelectedCount">0</span> selected)</div>
                    <div class="summary-value" id="cartPageSubtotal">₱0.00</div>
                  </div>
                  
                  <div class="summary-item">
                    <div class="summary-label">Shipping</div>
                    <div class="summary-value" id="cartPageShipping">₱0.00</div>
                  </div>
                  
                  <div class="summary-item discount-item">
                    <div class="summary-label">
                      <i class="fas fa-tag me-1"></i>Discount
                    </div>
                    <div class="summary-value text-success">-₱0.00</div>
                  </div>
                  
                  <div class="summary-divider"></div>
                  
                  <div class="summary-item total-item">
                    <div class="summary-label fw-bold">Total</div>
                    <div class="summary-value h4 fw-bold text-primary" id="cartPageTotal">₱0.00</div>
                  </div>
                  
                  <div class="promo-code-section mb-4">
                    <div class="input-group">
                      <input type="text" class="form-control" placeholder="Promo code">
                      <button class="btn btn-outline-primary">Apply</button>
                    </div>
                  </div>
                  
                  <div class="checkout-actions">
                    <button type="button" class="btn btn-primary btn-lg w-100 mb-3 rounded-pill" onclick="proceedToCheckout()">
                      <i class="fas fa-lock me-2"></i>Secure Checkout
                    </button>
                    
                    <div class="payment-methods mb-3">
                      <div class="payment-icons text-center">
                        <i class="fab fa-cc-visa me-2"></i>
                        <i class="fab fa-cc-mastercard me-2"></i>
                        <i class="fab fa-paypal me-2"></i>
                        <i class="fas fa-money-bill-wave"></i>
                      </div>
                    </div>
                    
                    <a href="products.php" class="btn btn-outline-primary w-100 rounded-pill">
                      <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                    </a>
                  </div>
                  
                  <div class="security-badges mt-3 text-center">
                    <div class="badge-text">
                      <i class="fas fa-shield-alt text-success me-1"></i>
                      <span class="small">Secure SSL Checkout</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Cart Page JavaScript -->
  <script>
  $(document).ready(function() {
    console.log('Cart page functionality loading...');
    console.log('jQuery version:', $.fn.jquery);
    console.log('Document ready fired');
    
    // Set up Select All checkbox listener IMMEDIATELY
    console.log('Setting up Select All listener immediately...');
    $(document).on('change', '#selectAllCart', function() {
      console.log('!!! SELECT ALL CHECKBOX CHANGED on cart page, checked:', this.checked);
      const isChecked = this.checked;
      
      // Update all checkboxes in cart page only
      const cartPageContainer = document.getElementById('cartItems');
      if (cartPageContainer) {
        cartPageContainer.querySelectorAll('.item-checkbox').forEach(function(checkbox) {
          checkbox.checked = isChecked;
        });
      }
      
      // Save and update
      saveCheckedItems();
      updateSelectedTotals();
    });
    console.log('Select All listener attached');
    
    // Set up individual item checkbox listener
    console.log('Setting up individual item checkbox listeners...');
    $(document).on('change', '#cartItems .item-checkbox', function() {
      console.log('!!! Individual item checkbox changed:', $(this).attr('data-item-index'), 'Checked:', this.checked);
      try {
        if (typeof updateSelectedTotals === 'function') {
          console.log('Calling updateSelectedTotals');
          updateSelectedTotals();
        } else {
          console.error('updateSelectedTotals is not a function');
        }
      } catch (e) {
        console.error('Error in updateSelectedTotals:', e);
      }
      
      try {
        if (typeof saveCheckedItems === 'function') {
          console.log('Calling saveCheckedItems');
          saveCheckedItems();
        } else {
          console.error('saveCheckedItems is not a function');
        }
      } catch (e) {
        console.error('Error in saveCheckedItems:', e);
      }
      
      try {
        if (typeof syncSelectAllCheckbox === 'function') {
          console.log('Calling syncSelectAllCheckbox');
          syncSelectAllCheckbox();
        } else {
          console.error('syncSelectAllCheckbox is not a function');
        }
      } catch (e) {
        console.error('Error in syncSelectAllCheckbox:', e);
      }
    });
    console.log('Individual item checkbox listeners attached');
    
    // Listen for localStorage changes from cart modal (other tabs/windows)
    window.addEventListener('storage', function(e) {
      if (e.key === 'jeweluxe_cart_checked') {
        console.log('Cart checked items updated from modal, syncing cart page...');
        const checkedItems = JSON.parse(e.newValue || '[]');
        console.log('Syncing cart page with new checked items:', checkedItems);
        
        // Update all checkboxes in cart page only
        const cartPageContainer = document.getElementById('cartItems');
        if (cartPageContainer) {
          cartPageContainer.querySelectorAll('.item-checkbox').forEach(function(checkbox) {
            const itemIndex = parseInt(checkbox.getAttribute('data-item-index'));
            if (cart[itemIndex]) {
              checkbox.checked = checkedItems.includes(cart[itemIndex].item_id);
            }
          });
          
          // Update Select All checkbox state
          const allCheckboxes = cartPageContainer.querySelectorAll('.item-checkbox').length;
          const checkedCheckboxes = cartPageContainer.querySelectorAll('.item-checkbox:checked').length;
          document.getElementById('selectAllCart').checked = allCheckboxes > 0 && allCheckboxes === checkedCheckboxes;
        }
        
        // Update totals
        if (typeof updateSelectedTotals === 'function') {
          updateSelectedTotals();
        }
      }
    });
    
    let cart = [];
    
    function displayCart() {
      console.log('Displaying cart, items:', cart.length);
      const emptyCartEl = document.getElementById('emptyCart');
      const cartItemsEl = document.getElementById('cartItems');
      
      if (cart.length === 0) {
        console.log('Cart is empty, showing empty message');
        if (emptyCartEl) {
          emptyCartEl.style.display = 'block';
          emptyCartEl.style.visibility = 'visible';
        }
        if (cartItemsEl) {
          cartItemsEl.style.display = 'none';
          cartItemsEl.style.visibility = 'hidden';
        }
        // Scroll to top to show empty cart message
        window.scrollTo(0, 0);
      } else {
        console.log('Cart has items, showing cart items');
        if (emptyCartEl) {
          emptyCartEl.style.display = 'none';
          emptyCartEl.style.visibility = 'hidden';
        }
        if (cartItemsEl) {
          cartItemsEl.style.display = 'block';
          cartItemsEl.style.visibility = 'visible';
        }
        updateCartDisplay();
      }
    }
    
    function updateCartDisplay() {
      let cartHtml = '';
      let subtotal = 0;
      const maxStock = 99;
      
      // Add table header
      cartHtml += '<div class="row mb-3 pb-2 border-bottom bg-light">' +
        '<div class="col-md-1"><strong>Select</strong></div>' +
        '<div class="col-md-3"><strong>Product</strong></div>' +
        '<div class="col-md-2 text-center"><strong>Unit Price</strong></div>' +
        '<div class="col-md-2 text-center"><strong>Quantity</strong></div>' +
        '<div class="col-md-2 text-center"><strong>Total Price</strong></div>' +
        '<div class="col-md-2 text-center"><strong>Actions</strong></div>' +
      '</div>';
      
      cart.forEach(function(item, index) {
        const qty = item.quantity || 1;
        const itemTotal = (item.price || 0) * qty;
        subtotal += itemTotal;
        const itemId = item.item_id || index;
        const isMinQty = qty <= 1;
        const isMaxQty = qty >= maxStock;
        
        cartHtml += '<div class="card mb-3">' +
          '<div class="card-body">' +
            '<div class="row align-items-center">' +
              '<div class="col-md-1">' +
                '<input type="checkbox" class="form-check-input item-checkbox" data-item-index="' + index + '">' +
              '</div>' +
              '<div class="col-md-3">' +
                '<div class="d-flex align-items-center">' +
                  '<img src="' + (item.image || 'image/placeholder.png') + '" alt="' + item.name + '" class="me-3" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">' +
                  '<div>' +
                    '<h6 class="mb-0">' + item.name + '</h6>' +
                  '</div>' +
                '</div>' +
              '</div>' +
              '<div class="col-md-2 text-center">' +
                '<span class="fw-bold">₱' + item.price.toFixed(2) + '</span>' +
              '</div>' +
              '<div class="col-md-2 text-center">' +
                '<div class="quantity-selector d-inline-flex">' +
                  '<button type="button" id="decrement-' + itemId + '" onclick="changeQuantity(' + index + ', -1)" ' + (isMinQty ? 'disabled' : '') + '>-</button>' +
                  '<input type="number" id="quantity-' + itemId + '" value="' + qty + '" min="1" max="' + maxStock + '" onchange="handleQuantityInput(' + index + ', this.value)" style="width: 50px; height: 32px; text-align: center; border: 1px solid #ddd; border-left: 1px solid #ddd; border-right: 1px solid #ddd; padding: 0 0 0 7px; margin: 0; font-size: 16px;">' +
                  '<button type="button" id="increment-' + itemId + '" onclick="changeQuantity(' + index + ', 1)" ' + (isMaxQty ? 'disabled' : '') + '>+</button>' +
                '</div>' +
              '</div>' +
              '<div class="col-md-2 text-center">' +
                '<span class="fw-bold text-danger">₱' + itemTotal.toFixed(2) + '</span>' +
              '</div>' +
              '<div class="col-md-2 text-center">' +
                '<button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(' + index + ')">Delete</button>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>';
      });
      
      $('.cart-item-list').html(cartHtml);
      
      // Re-attach checkbox event listeners after rendering
      attachCheckboxListeners();
      
      // Load previously saved checked items from localStorage
      loadCheckedItems();
      
      // Update totals based on selected items (with small delay to ensure DOM is updated)
      setTimeout(function() {
        updateSelectedTotals();
      }, 50);
    }
    
    function useLocalCart() {
      console.log('Using localStorage cart');
      cart = JSON.parse(localStorage.getItem('jeweluxe_cart')) || [];
      displayCart();
    }
    
    // Fetch cart from server
    $.getJSON('get_cart.php').done(function(resp) {
      console.log('Cart server response:', resp);
      
      if (!resp || !resp.success) {
        console.log('Server failed, using localStorage');
        useLocalCart();
        return;
      }

      const items = resp.cart.items || [];
      if (items.length === 0) {
        console.log('No server items, using localStorage');
        useLocalCart();
        return;
      }

      // Map server items to local format
      cart = items.map(function(it) {
        return {
          item_id: it.item_id,
          product_id: it.product_id,
          name: it.name,
          price: parseFloat(it.price),
          image: it.image || 'image/placeholder.png',
          quantity: parseInt(it.quantity),
          sku: it.sku || it.name.replace(/\s+/g, '').toUpperCase()
        };
      });
      
      console.log('Cart array after mapping:', cart);
      cart.forEach((item, idx) => {
        console.log(`Item ${idx}: Name=${item.name}, Price=${item.price}, Qty=${item.quantity}, Total=${item.price * item.quantity}`);
      });
      displayCart();
      
      // Initialize totals after cart is displayed
      setTimeout(function() {
        console.log('Calling updateSelectedTotals after displayCart');
        updateSelectedTotals();
      }, 150);
      
    }).fail(function(xhr, status, err) {
      console.log('AJAX failed, using localStorage:', status, err);
      useLocalCart();
      
      // Initialize totals after cart is displayed
      setTimeout(function() {
        updateSelectedTotals();
      }, 100);
    });
    
    // Handle quantity input changes (when user types)
    window.handleQuantityInput = function(index, value) {
      const maxStock = 99;
      const newQuantity = parseInt(value) || 1;
      
      // Validate quantity limits
      if (newQuantity < 1) {
        cart[index].quantity = 1;
        displayCart();
        return;
      }
      
      if (newQuantity > maxStock) {
        cart[index].quantity = maxStock;
        displayCart();
        return;
      }
      
      // Update quantity
      cart[index].quantity = newQuantity;
      
      // Update in database if item has item_id
      const item = cart[index];
      if (item && item.item_id) {
        $.post('update_cart.php', { item_id: item.item_id, quantity: newQuantity })
          .done(function(resp) {
            if (resp && resp.success) {
              item.quantity = resp.quantity || newQuantity;
              localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
              displayCart();
              updateCartBadge();
            } else {
              localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
              displayCart();
            }
          })
          .fail(function() {
            localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
            displayCart();
          });
      } else {
        localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
        displayCart();
      }
    };
    
    // Cart management functions
    window.changeQuantity = function(index, change) {
      const maxStock = 99;
      const item = cart[index];
      if (!item) return;
      
      const newQuantity = (item.quantity || 1) + change;
      
      // Validate quantity limits
      if (newQuantity < 1 || newQuantity > maxStock) {
        return;
      }
      
      // Update quantity
      cart[index].quantity = newQuantity;
      
      // Update in database if item has item_id
      if (item && item.item_id) {
        $.post('update_cart.php', { item_id: item.item_id, quantity: newQuantity })
          .done(function(resp) {
            if (resp && resp.success) {
              item.quantity = resp.quantity || newQuantity;
              localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
              displayCart();
              updateCartBadge();
            } else {
              localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
              displayCart();
            }
          })
          .fail(function() {
            localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
            displayCart();
          });
      } else {
        localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
        displayCart();
      }
    };
    
    window.removeFromCart = function(index) {
      const item = cart[index];
      if (item && item.item_id) {
        $.post('remove_from_cart.php', { item_id: item.item_id })
          .done(function(resp) {
            if (resp && resp.success) {
              cart.splice(index, 1);
              localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
              displayCart();
              updateCartBadge();
            } else {
              cart.splice(index, 1);
              localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
              displayCart();
              updateCartBadge();
            }
          })
          .fail(function() {
            cart.splice(index, 1);
            localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
            displayCart();
            updateCartBadge();
          });
      } else {
        cart.splice(index, 1);
        localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
        displayCart();
        updateCartBadge();
      }
    };

    window.clearCart = function() {
      console.log('Clear cart clicked');
      // Clear UI immediately (optimistic update - don't wait for server)
      cart = [];
      localStorage.setItem('jeweluxe_cart', JSON.stringify(cart));
      console.log('Cart array cleared, calling displayCart()');
      displayCart();
      updateCartBadge();
      
      const modal = bootstrap.Modal.getInstance(document.getElementById('confirmClearModal'));
      if (modal) modal.hide();
      
      // Then sync with server and reload page to ensure it's truly cleared
      $.post('clear_cart.php', function() {
        console.log('Cart cleared on server, reloading page');
        // Reload after a short delay to show the clearing happened
        setTimeout(function() {
          window.location.reload();
        }, 500);
      }).fail(function() {
        console.error('Failed to clear cart on server');
      });
    };

    window.proceedToCheckout = function(event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }

      // Get selected items from checkboxes
      const selectedItems = [];
      const cartPageContainer = document.getElementById('cartItems');
      if (cartPageContainer) {
        cartPageContainer.querySelectorAll('.item-checkbox:checked').forEach((checkbox) => {
          const itemIndex = parseInt(checkbox.getAttribute('data-item-index'));
          if (cart[itemIndex]) {
            selectedItems.push(cart[itemIndex].item_id);
          }
        });
      }
      
      if (selectedItems.length === 0) {
        if (window.ToastNotification) {
          ToastNotification.warning('Please select at least one item to checkout.');
        } else {
          alert('Please select at least one item to checkout.');
        }
        return false;
      }
      
      // Store selected items in session storage
      sessionStorage.setItem('selectedCartItems', JSON.stringify(selectedItems));
      window.location.href = 'checkout.php';
      return false;
    };

    // Global handler for checkbox changes - MUST BE DEFINED BEFORE attachCheckboxListeners
    window.handleCheckboxChange = function(event) {
      console.log('!!! Checkbox changed on cart page:', event.target.getAttribute('data-item-index'), 'Checked:', event.target.checked);
      
      // Save to localStorage immediately
      saveCheckedItems();
      
      // Update totals
      updateSelectedTotals();
      
      // Update Select All checkbox state
      syncSelectAllCheckbox();
    };

    // Attach checkbox event listeners
    window.attachCheckboxListeners = function() {
      console.log('=== attachCheckboxListeners called ===');
      // Don't call loadCheckedItems here - let the user's manual checkbox state be the source of truth
      // loadCheckedItems will be called when the page first loads to restore previous selections
    };

    // Update totals based on selected items
    window.updateSelectedTotals = function() {
      let selectedSubtotal = 0;
      let selectedCount = 0;

      // Get all checkboxes ONLY from the cart page (not the modal)
      // Use a more specific selector that targets the cart page container
      const cartPageContainer = document.getElementById('cartItems');
      if (!cartPageContainer) {
        console.warn('Cart page container not found');
        return;
      }
      
      const checkboxes = cartPageContainer.querySelectorAll('.item-checkbox');
      console.log('Found ' + checkboxes.length + ' checkboxes in cart page');
      
      checkboxes.forEach((checkbox, idx) => {
        // Use the data-item-index attribute to get the correct cart index
        const itemIndex = parseInt(checkbox.getAttribute('data-item-index'));
        console.log(`Checkbox ${idx}: itemIndex=${itemIndex}, checked=${checkbox.checked}`);
        
        if (checkbox.checked && cart[itemIndex]) {
          const qty = parseInt(cart[itemIndex].quantity) || 1;
          const price = parseFloat(cart[itemIndex].price) || 0;
          console.log(`  Item ${itemIndex}: name="${cart[itemIndex].name}", price=${price}, qty=${qty}, total=${price * qty}`);
          selectedSubtotal += (price * qty);
          selectedCount++;
        }
      });

      // Update the display elements
      const shipping = selectedSubtotal > 0 ? 150 : 0;
      const total = selectedSubtotal + shipping;
      
      console.log(`Totals: count=${selectedCount}, subtotal=${selectedSubtotal}, shipping=${shipping}, total=${total}`);
      
      // Find and update elements by ID
      const countEl = document.getElementById('cartPageSelectedCount');
      const subtotalEl = document.getElementById('cartPageSubtotal');
      const shippingEl = document.getElementById('cartPageShipping');
      const totalEl = document.getElementById('cartPageTotal');
      
      console.log(`Elements found: count=${!!countEl}, subtotal=${!!subtotalEl}, shipping=${!!shippingEl}, total=${!!totalEl}`);
      
      if (countEl) {
        countEl.textContent = selectedCount;
        console.log(`Updated cartPageSelectedCount to: ${selectedCount}`);
      }
      if (subtotalEl) {
        subtotalEl.textContent = '₱' + selectedSubtotal.toFixed(2);
        console.log(`Updated cartPageSubtotal to: ₱${selectedSubtotal.toFixed(2)}`);
      }
      if (shippingEl) {
        shippingEl.textContent = '₱' + shipping.toFixed(2);
        console.log(`Updated cartPageShipping to: ₱${shipping.toFixed(2)}`);
      }
      if (totalEl) {
        totalEl.textContent = '₱' + total.toFixed(2);
        console.log(`Updated cartPageTotal to: ₱${total.toFixed(2)}`);
      }
    };

    // Update Select All checkbox based on individual checkboxes
    window.syncSelectAllCheckbox = function() {
      const cartPageContainer = document.getElementById('cartItems');
      if (!cartPageContainer) return;
      
      const allCheckboxes = cartPageContainer.querySelectorAll('.item-checkbox').length;
      const checkedCheckboxes = cartPageContainer.querySelectorAll('.item-checkbox:checked').length;
      document.getElementById('selectAllCart').checked = allCheckboxes > 0 && allCheckboxes === checkedCheckboxes;
    };

    // Save checked items to localStorage
    window.saveCheckedItems = function() {
      const cartPageContainer = document.getElementById('cartItems');
      if (!cartPageContainer) return;
      
      const checkedItems = [];
      cartPageContainer.querySelectorAll('.item-checkbox:checked').forEach(function(checkbox) {
        const itemIndex = parseInt(checkbox.getAttribute('data-item-index'));
        if (cart[itemIndex]) {
          checkedItems.push(cart[itemIndex].item_id);
        }
      });
      localStorage.setItem('jeweluxe_cart_checked', JSON.stringify(checkedItems));
      console.log('Saved checked items:', checkedItems);
    };

    // Load checked items from localStorage
    window.loadCheckedItems = function() {
      const cartPageContainer = document.getElementById('cartItems');
      if (!cartPageContainer) return;
      
      const checkedItems = JSON.parse(localStorage.getItem('jeweluxe_cart_checked') || '[]');
      console.log('Loading checked items from localStorage:', checkedItems);
      
      cartPageContainer.querySelectorAll('.item-checkbox').forEach(function(checkbox) {
        const itemIndex = parseInt(checkbox.getAttribute('data-item-index'));
        if (cart[itemIndex]) {
          const itemId = cart[itemIndex].item_id;
          const shouldBeChecked = checkedItems.includes(itemId);
          console.log(`Item ${itemIndex} (id=${itemId}): shouldBeChecked=${shouldBeChecked}`);
          checkbox.checked = shouldBeChecked;
        }
      });
      
      console.log('Finished loading checked items');
      syncSelectAllCheckbox();
      
      // Update totals after a small delay to ensure checkboxes are updated in DOM
      setTimeout(function() {
        updateSelectedTotals();
      }, 50);
    };
  });
  </script>

<!-- Confirmation Modal for Clear Cart -->
<div class="modal fade" id="confirmClearModal" tabindex="-1" aria-labelledby="confirmClearLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 bg-danger text-white">
        <h5 class="modal-title" id="confirmClearLabel">
          <i class="fas fa-exclamation-triangle me-2"></i>Clear Cart
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to clear your entire cart? This action cannot be undone.</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i>Cancel
        </button>
        <button type="button" class="btn btn-danger" onclick="clearCart();" data-bs-dismiss="modal">
          <i class="fas fa-trash-alt me-1"></i>Yes, Clear Cart
        </button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>