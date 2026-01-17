<?php
// Cart modal markup extracted from header for reuse.
?>
<div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cartModalLabel">Your Cart</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modalCartItems" style="display: none;">
          <div class="mb-3">
            <label class="form-check">
              <input type="checkbox" class="form-check-input" id="selectAllCart">
              <span class="form-check-label"><strong>Select All Items</strong></span>
            </label>
          </div>
          <div class="cart-item-list">
            <!-- Cart items will be populated here via JavaScript -->
          </div>
        </div>
        <div id="emptyCartMessage" class="text-center py-4" style="display: none;">
          <p class="text-muted mb-0">Your cart is empty</p>
        </div>
      </div>
      <div class="modal-footer" id="cartFooter" style="display: none;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" id="cartProceedBtn" data-logged-in="<?php echo is_logged_in() ? '1' : '0'; ?>">Proceed to Checkout</button>
      </div>
    </div>
  </div>
</div>
