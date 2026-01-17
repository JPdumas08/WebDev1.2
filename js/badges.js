// Badge management: cart and wishlist counts.
(function(window) {
  // Update cart badge count
  function updateCartBadge() {
    const $body = document.querySelector('body');
    const isLoggedIn = $body.getAttribute('data-logged-in') === '1';
    const cartBadge = document.querySelector('.cart-count');
    
    if (!cartBadge) return;
    
    if (isLoggedIn) {
      // For logged-in users, fetch from server (database is source of truth)
      fetch('get_cart.php', { signal: AbortSignal.timeout(5000) })
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok');
          return response.json();
        })
        .then(data => {
          if (data && data.success && data.cart && data.cart.items) {
            let totalItems = data.cart.items.reduce((sum, item) => sum + (item.quantity || 1), 0);
            cartBadge.textContent = totalItems;
            cartBadge.style.display = totalItems > 0 ? 'inline-block' : 'none';
          }
        })
        .catch(error => {
          console.error('Error updating cart badge:', error);
          // On error, show 0
          cartBadge.textContent = '0';
          cartBadge.style.display = 'none';
        });
    } else {
      // For guests, use localStorage
      let cart = JSON.parse(localStorage.getItem('jeweluxe_cart')) || [];
      let totalItems = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
      cartBadge.textContent = totalItems;
      cartBadge.style.display = totalItems > 0 ? 'inline-block' : 'none';
    }
  }

  // Update wishlist badge count (fast path with immediate visual update)
  function updateWishlistBadge() {
    const wishlistBadge = document.querySelector('.wishlist-count');
    if (!wishlistBadge) return;
    
    // Immediately show 0 to provide instant visual feedback
    wishlistBadge.textContent = '0';
    wishlistBadge.style.display = 'none';
    
    // Then fetch the actual count asynchronously
    fetch('get_wishlist.php', { signal: AbortSignal.timeout(5000) })
      .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
      })
      .then(data => {
        if (data.success && Array.isArray(data.wishlist)) {
          wishlistBadge.textContent = data.wishlist.length;
          wishlistBadge.style.display = data.wishlist.length > 0 ? 'inline-block' : 'none';
        }
      })
      .catch(error => {
        console.error('Error updating wishlist badge:', error);
        // On error, keep showing 0 (already set above)
      });
  }

  // Check if we need to refresh badges (after login/logout)
  function checkAndRefreshBadges() {
    const params = new URLSearchParams(window.location.search);
    const shouldRefresh = params.has('refresh_badges');
    
    if (shouldRefresh) {
      // Clear cart if user logged out or logged in as different user
      updateCartBadge();
      updateWishlistBadge();
      
      // Remove the refresh parameter from URL to avoid repeated refreshes
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  }

  // Initialize badges on page load
  document.addEventListener('DOMContentLoaded', function() {
    const $body = document.querySelector('body');
    const isNowLoggedIn = $body.getAttribute('data-logged-in') === '1';
    const currentUserId = $body.getAttribute('data-user-id') || '';
    const lastUserId = localStorage.getItem('jeweluxe_last_user_id') || '';
    
    // If user changed or logged out, clear cart immediately
    if (lastUserId && (currentUserId !== lastUserId || !isNowLoggedIn)) {
      localStorage.removeItem('jeweluxe_cart');
    }
    
    // Update user ID for next check
    localStorage.setItem('jeweluxe_last_user_id', currentUserId);
    
    // Update badges (cart immediately, wishlist with async fetch)
    updateCartBadge();
    updateWishlistBadge();
    
    // Check if we need to refresh badges after login/logout
    checkAndRefreshBadges();
  });

  // Export functions for external use
  window.updateCartBadge = updateCartBadge;
  window.updateWishlistBadge = updateWishlistBadge;
  window.checkAndRefreshBadges = checkAndRefreshBadges;
})(window);
