// Cart modal behaviors extracted from footer/header.
(function(window, $) {
  function isProductsPage() {
    return window.location.pathname.includes('products.php') || window.location.pathname.endsWith('products');
  }

  function updateCartTotals($modal) {
    // This function is no longer needed since totals are shown only on the cart page
    // The modal now only shows items with checkboxes for selection
    console.log('updateCartTotals called (deprecated - totals shown on cart page only)');
  }

  function refreshCartModal($modal) {
    $modal = $modal || $('#cartModal');
    const $empty = $modal.find('#emptyCartMessage');
    const $items = $modal.find('#modalCartItems');
    const $list = $items.find('.cart-item-list');
    const $footer = $modal.find('#cartFooter');

    $empty.hide();
    $items.hide();
    $footer.hide();
    $list.html('<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

    $.getJSON('get_cart.php').done(function(resp) {
      if (!resp || !resp.success) {
        $list.empty();
        $empty.show();
        return;
      }

      const items = resp.cart.items || [];
      if (items.length === 0) {
        $list.empty();
        $empty.show();
        return;
      }

      $list.empty();

      items.forEach(function(it) {
        const $row = $(
          '<div class="d-flex align-items-start mb-3 cart-row" data-item-id="' + it.item_id + '">' +
            '<div class="me-3 mt-2">' +
              '<input type="checkbox" class="form-check-input item-checkbox" data-item-id="' + it.item_id + '">' +
            '</div>' +
            '<div class="me-3">' +
              '<img src="' + (it.image || 'image/placeholder.png') + '" alt="' + it.name + '" style="width: 70px; height: 70px; object-fit: cover; border-radius: 6px;">' +
            '</div>' +
            '<div class="flex-grow-1">' +
              '<strong></strong>' +
              '<div class="small text-muted price-line"></div>' +
              '<div class="mt-2 d-flex align-items-center qty-controls">' +
                '<div class="btn-group" role="group" aria-label="Quantity controls">' +
                  '<button type="button" class="btn btn-sm btn-outline-secondary btn-decrease">−</button>' +
                  '<input type="text" class="form-control form-control-sm qty-input text-center" value="' + it.quantity + '" style="width:70px; display:inline-block;">' +
                  '<button type="button" class="btn btn-sm btn-outline-secondary btn-increase">+</button>' +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-link text-danger ms-3 btn-remove">Remove</button>' +
              '</div>' +
            '</div>' +
            '<div class="text-end ms-3 line-total">₱' + Number(it.line_total).toFixed(2) + '</div>' +
          '</div>'
        );

        $row.find('strong').text(it.name);
        $row.find('.price-line').text('₱' + Number(it.price).toFixed(2) + ' each');

        // Checkbox change event
        $row.find('.item-checkbox').on('change', function() {
          saveCheckedItemsModal($modal);
        });

        $row.find('.btn-increase').on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          const current = parseInt($row.find('.qty-input').val() || '0', 10);
          const next = current + 1;
          updateCartItem(it.item_id, next, $modal);
        });

        $row.find('.btn-decrease').on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          const current = parseInt($row.find('.qty-input').val() || '0', 10);
          const next = current - 1;
          updateCartItem(it.item_id, next, $modal);
        });

        $row.find('.qty-input').on('change', function(e) {
          e.preventDefault();
          e.stopPropagation();
          let val = parseInt($(this).val() || '0', 10);
          if (isNaN(val) || val < 0) val = 0;
          updateCartItem(it.item_id, val, $modal);
        });

        $row.find('.btn-remove').on('click', function() {
          removeCartItem(it.item_id, $modal);
        });

        $list.append($row);
      });

      // Select All functionality
      const $selectAll = $modal.find('#selectAllCart');
      $selectAll.off('change').on('change', function() {
        const isChecked = $(this).is(':checked');
        $list.find('.item-checkbox').prop('checked', isChecked);
        saveCheckedItemsModal($modal);
      });

      // Load previously checked items from localStorage
      loadCheckedItemsModal($modal, items);
      $items.show();
      $footer.show();
    }).fail(function() {
      $list.empty();
      $empty.show();
    });
  }

  function updateCartItem(item_id, quantity, $modal) {
    $modal = $modal || $('#cartModal');
    
    // Store current scroll position of modal body
    const $modalBody = $modal.find('.modal-body');
    const scrollPos = $modalBody.scrollTop();
    
    // Get the cart row element
    const $row = $modal.find('[data-item-id="' + item_id + '"]');
    
    $.post('update_cart.php', { item_id: item_id, quantity: quantity }).done(function(resp) {
      if (!resp || !resp.success) {
        if (window.ToastNotification) ToastNotification.error('Could not update cart item.');
        return;
      }

      if (resp.removed) {
        $row.fadeOut(300, function() { $row.remove(); });
        return;
      }

      // Update the quantity input and line total without full refresh
      if ($row.length) {
        $row.find('.qty-input').val(quantity);
        $row.find('.line-total').text('₱' + Number(resp.line_total || 0).toFixed(2));
      }
      
      // Update the cart total
      $.getJSON('get_cart.php').done(function(cartResp) {
        if (cartResp && cartResp.cart) {
          $modal.find('#cartTotal').text('₱' + Number(cartResp.cart.total).toFixed(2));
        }
        // Restore scroll position
        $modalBody.scrollTop(scrollPos);
      });
      
      updateCartBadge();
    }).fail(function() {
      if (window.ToastNotification) ToastNotification.error('Network error while updating cart.');
      // Restore scroll position even on error
      $modalBody.scrollTop(scrollPos);
    });
  }

  function removeCartItem(item_id, $modal) {
    $modal = $modal || $('#cartModal');
    $.post('remove_from_cart.php', { item_id: item_id }).done(function(resp) {
      if (!resp || !resp.success) {
        if (window.ToastNotification) ToastNotification.error('Could not remove item from cart.');
        return;
      }
      refreshCartModal($modal);
    }).fail(function() {
      if (window.ToastNotification) ToastNotification.error('Network error while removing item.');
    });
  }

  $(function() {
    const $cartLink = $('#cartLink');
    const $cartModal = $('#cartModal');

    if ($cartLink.length) {
      $cartLink.on('click', function(e) {
        e.preventDefault();

        if (isProductsPage() && $cartModal.length) {
          $cartModal.modal('show');
        } else {
          window.location.href = 'cart.php';
        }
      });

      if (isProductsPage() && $cartModal.length) {
        $cartLink.attr('data-bs-toggle', 'modal').attr('data-bs-target', '#cartModal');
      } else {
        $cartLink.attr('href', 'cart.php');
      }
    }
  });

  $(document).on('show.bs.modal', '#cartModal', function() {
    refreshCartModal($(this));
  });

  $(document).on('click', '#cartProceedBtn', function() {
    const loggedInAttr = $(this).attr('data-logged-in');
    const loggedIn = loggedInAttr === '1' || loggedInAttr === 1 || $(this).data('logged-in') === 1;

    // Get selected items from cart
    const $cartModal = $('#cartModal');
    const $list = $cartModal.find('.cart-item-list');
    const selectedItems = [];
    
    $list.find('.item-checkbox:checked').each(function() {
      selectedItems.push($(this).data('item-id'));
    });

    if (selectedItems.length === 0) {
      if (window.ToastNotification) {
        ToastNotification.warning('Please select at least one item to checkout.');
      } else {
        alert('Please select at least one item to checkout.');
      }
      return;
    }

    if (loggedIn) {
      // Store selected items in session storage and navigate to checkout
      sessionStorage.setItem('selectedCartItems', JSON.stringify(selectedItems));
      window.location.href = 'checkout.php';
      return;
    }

    const cartEl = document.getElementById('cartModal');
    const accountEl = document.getElementById('accountModal');

    if (cartEl && typeof bootstrap !== 'undefined') {
      bootstrap.Modal.getOrCreateInstance(cartEl).hide();
    }

    if (accountEl && typeof bootstrap !== 'undefined') {
      bootstrap.Modal.getOrCreateInstance(accountEl).show();
    } else {
      window.location.href = 'login.php?redirect=checkout';
    }
  });

  // Save checked items from modal to localStorage
  function saveCheckedItemsModal($modal) {
    const checkedItems = [];
    $modal.find('.cart-item-list .item-checkbox:checked').each(function() {
      checkedItems.push($(this).data('item-id'));
    });
    localStorage.setItem('jeweluxe_cart_checked', JSON.stringify(checkedItems));
    console.log('Saved checked items from modal:', checkedItems);
  }

  // Load checked items into modal from localStorage
  function loadCheckedItemsModal($modal, items) {
    const checkedItems = JSON.parse(localStorage.getItem('jeweluxe_cart_checked') || '[]');
    console.log('Loading checked items into modal:', checkedItems);
    
    $modal.find('.cart-item-list .item-checkbox').each(function() {
      const itemId = $(this).data('item-id');
      const shouldBeChecked = checkedItems.includes(itemId);
      console.log(`Modal item ${itemId}: shouldBeChecked=${shouldBeChecked}`);
      $(this).prop('checked', shouldBeChecked);
    });
    
    // Update Select All checkbox state
    const allCheckboxes = $modal.find('.cart-item-list .item-checkbox').length;
    const checkedCheckboxes = $modal.find('.cart-item-list .item-checkbox:checked').length;
    $modal.find('#selectAllCart').prop('checked', allCheckboxes > 0 && allCheckboxes === checkedCheckboxes);
    console.log(`Modal Select All checkbox updated: all=${allCheckboxes}, checked=${checkedCheckboxes}`);
  }

  // Listen for localStorage changes from other tabs/pages (like cart.php)
  window.addEventListener('storage', function(e) {
    if (e.key === 'jeweluxe_cart_checked') {
      console.log('Cart checked items updated from another page, refreshing modal...');
      const $modal = $('#cartModal');
      if ($modal.length && $modal.hasClass('show')) {
        // Modal is open, update its checkboxes
        const checkedItems = JSON.parse(e.newValue || '[]');
        console.log('Syncing modal with new checked items:', checkedItems);
        
        $modal.find('.cart-item-list .item-checkbox').each(function() {
          const itemId = $(this).data('item-id');
          $(this).prop('checked', checkedItems.includes(itemId));
        });
        
        // Update Select All checkbox
        const allCheckboxes = $modal.find('.cart-item-list .item-checkbox').length;
        const checkedCheckboxes = $modal.find('.cart-item-list .item-checkbox:checked').length;
        $modal.find('#selectAllCart').prop('checked', allCheckboxes > 0 && allCheckboxes === checkedCheckboxes);
      }
    }
  });

  window.CartModal = {
    refresh: refreshCartModal,
    updateItem: updateCartItem,
    removeItem: removeCartItem
  };
})(window, jQuery);
