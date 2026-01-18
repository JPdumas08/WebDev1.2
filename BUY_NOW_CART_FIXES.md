# Buy Now and Shopping Cart Functionality - Fix Documentation

## Overview
This document describes the comprehensive fixes made to the Buy Now and Shopping Cart functionality to resolve cart clearing issues and ensure proper handling of separate checkout flows.

## Issues Fixed

### Issue 1: Cart Completely Cleared on Checkout
**Problem**: When checking out with selected cart items, ALL cart items were deleted from the database, including items not selected for purchase.

**Root Cause**: `process_checkout.php` line 188-190 used a blanket DELETE statement that removed all items for the user:
```php
// WRONG - deletes ALL cart items
DELETE ci FROM cart_items ci
JOIN cart c ON c.cart_id = ci.cart_id
WHERE c.user_id = :uid
```

**Fix**: Updated to only delete the specific cart items that were selected and checked out:
```php
// CORRECT - deletes only selected items
DELETE FROM cart_items WHERE cart_item_id IN (...)
```

### Issue 2: Buy Now Required Existing Cart Items
**Problem**: Buy Now feature required the cart to have items before allowing checkout, which was not the intended behavior.

**Root Cause**: The checkout process treated Buy Now and cart items the same way, expecting items in the `cart_items` database table.

**Fix**: Implemented separate handling for Buy Now items:
- Buy Now items are passed directly via POST data, not from database
- No cart database lookup is required for Buy Now
- Buy Now and regular cart are processed independently

### Issue 3: No Distinction Between Buy Now and Cart Checkout
**Problem**: The system couldn't distinguish between Buy Now and regular cart checkout, leading to incorrect cart clearing logic.

**Root Cause**: Buy Now flag was only in URL parameters and lost during processing.

**Fix**: Added explicit `isBuyNow` flag in form submission:
- Form now includes `isBuyNow=1` for Buy Now purchases
- Form includes `isBuyNow=0` for regular cart purchases
- Backend uses this flag to determine cart clearing behavior

## Implementation Details

### 1. Buy Now Detection (process_checkout.php)

```php
// NEW: Check if this is a Buy Now checkout
$is_buy_now_checkout = isset($_POST['isBuyNow']) && $_POST['isBuyNow'] === '1';
```

### 2. Buy Now Item Processing (process_checkout.php)

```php
if ($is_buy_now_checkout) {
    // Build temporary cart item from POST data
    // No database lookup needed
    $cart_items = [[
        'item_id' => 'buy_now_' . $buy_product_id,
        'cart_id' => null,  // No actual cart_id for Buy Now
        'product_id' => $buy_product_id,
        'quantity' => $buy_quantity,
        'price' => $buy_price,
        // ... other fields
    ]];
} else {
    // Regular cart: load from database and filter by selected items
}
```

### 3. Selective Cart Clearing (process_checkout.php)

```php
// FIXED: Only clear items that were actually purchased
if (!$is_buy_now_checkout && !empty($cart_items)) {
    // Get cart items (not Buy Now items)
    $items_to_delete = array_filter($cart_items, function($item) {
        return !strpos($item['item_id'], 'buy_now_');
    });
    
    if (!empty($items_to_delete)) {
        // Delete ONLY the items that were in the purchase
        $item_ids_to_delete = array_map(...);
        $delete_sql = "DELETE FROM cart_items WHERE cart_item_id IN (...)";
    }
}
// BUY NOW items skip this entirely - no database deletion needed
```

### 4. Form Data Passing (checkout.php)

```javascript
// NEW: Add Buy Now detection and data
const urlParams = new URLSearchParams(window.location.search);
const isBuyNow = urlParams.has('buyNow') && urlParams.get('buyNow') === '1';

if (isBuyNow) {
    formData.append('isBuyNow', '1');
    // Pass product details from data attributes
    formData.append('buyNowProductId', ...);
    formData.append('buyNowProductName', ...);
    formData.append('buyNowProductImage', ...);
    formData.append('buyNowPrice', ...);
    formData.append('buyNowQuantity', ...);
} else {
    formData.append('isBuyNow', '0');
    // Use selected cart items from sessionStorage
}
```

### 5. Data Attributes for Buy Now (checkout.php)

```php
<div data-item-id="<?php echo $item['item_id']; ?>" 
     data-product-id="<?php echo $item['product_id']; ?>"
     <?php if ($is_buy_now_mode): ?>
     data-buy-now-product-id="<?php echo $item['product_id']; ?>"
     data-buy-now-product-name="<?php echo htmlspecialchars($item['product_name']); ?>"
     data-buy-now-product-image="<?php echo htmlspecialchars($item['product_image']); ?>"
     data-buy-now-product-price="<?php echo $item['price']; ?>"
     data-buy-now-quantity="<?php echo $item['quantity']; ?>"
     <?php endif; ?>>
```

## Flow Diagrams

### Buy Now Flow (Fixed)
```
Product Page
    ↓
Click "Buy Now" button
    ↓
Redirect to checkout.php?buyNow=1&productId=XX&qty=YY
    ↓
Checkout page fetches product details from URL params
    ↓
Display only Buy Now product (cart items shown but not required)
    ↓
Click "Place Order"
    ↓
Form includes: isBuyNow=1, product details in POST
    ↓
process_checkout.php detects Buy Now mode
    ↓
Creates order from POST data (no database cart lookup)
    ↓
NO cart items deleted (Buy Now doesn't use cart table)
    ↓
Existing cart remains unchanged ✅
    ↓
Redirect to payment page
```

### Regular Cart Checkout Flow (Fixed)
```
Shopping Cart
    ↓
Select items with checkboxes
    ↓
Click "Checkout" button
    ↓
Redirect to checkout.php (no URL params)
    ↓
Checkout page loads cart from database
    ↓
Display all cart items with checkboxes
    ↓
Selected items stored in sessionStorage
    ↓
Click "Place Order"
    ↓
Form includes: isBuyNow=0, selectedItems from sessionStorage
    ↓
process_checkout.php detects regular cart mode
    ↓
Loads cart from database
    ↓
Filters to only selected items
    ↓
Creates order with selected items only
    ↓
Deletes ONLY selected items from cart_items table ✅
    ↓
Unselected items remain in cart ✅
    ↓
Redirect to payment page
```

## Files Modified

### 1. process_checkout.php
- Added `$is_buy_now_checkout` detection
- Implemented separate item processing for Buy Now vs Cart
- Updated cart clearing logic to be selective
- Added error logging for debugging
- Changed from blanket DELETE to targeted DELETE using item IDs

### 2. checkout.php
- Added data attributes for Buy Now product information
- Updated form submission handler to detect Buy Now mode
- Added `isBuyNow` flag to form data
- Implemented logic to extract Buy Now product details from data attributes

### 3. product_detail.php
- No changes needed (already properly passes parameters)

### 4. cart.php
- No changes needed (already properly handles item selection)

## Testing Checklist

### Test 1: Buy Now with Empty Cart
```
✓ Navigate to product page
✓ Ensure cart is empty
✓ Click "Buy Now" button
✓ Verify checkout page loads (should NOT require cart items)
✓ Verify only the Buy Now product is shown
✓ Select payment method and address
✓ Click "Place Order"
✓ Verify order is created successfully
✓ Check database: cart_items table should be empty
✓ Verify user is redirected to payment page
```

### Test 2: Buy Now with Existing Cart
```
✓ Add multiple items to cart
✓ Navigate to a different product page
✓ Click "Buy Now" button
✓ Verify checkout page loads with Buy Now product
✓ Verify original cart items are NOT visible in order summary
✓ Verify only Buy Now product is in the order
✓ Complete the order
✓ Verify all original cart items still exist in database
✓ Navigate to cart page and verify items are still there
```

### Test 3: Cart Checkout with Multiple Items
```
✓ Add 5 items to cart
✓ Go to cart page
✓ Select only 3 items with checkboxes
✓ Click "Proceed to Checkout"
✓ Verify only 3 items are shown in order summary
✓ Complete the order
✓ Navigate back to cart
✓ Verify 2 unselected items still exist in cart
✓ Verify 3 selected items were removed
```

### Test 4: Cart Checkout with All Items
```
✓ Add 3 items to cart
✓ Go to cart page
✓ Select all items with "Select All" checkbox
✓ Click "Proceed to Checkout"
✓ Verify all 3 items are shown in order summary
✓ Complete the order
✓ Navigate to cart
✓ Verify cart is now empty
```

### Test 5: Cart Checkout with Some Items Unselected
```
✓ Add 4 items to cart
✓ Go to cart page
✓ Select 2 items (leaving 2 unselected)
✓ Click "Proceed to Checkout"
✓ Verify only 2 selected items in order summary
✓ Complete the order
✓ Navigate to cart
✓ Verify 2 unselected items still in cart
✓ Verify 2 selected items were removed
```

## Database Impact

### cart_items Table
- **Before**: All cart items were deleted on ANY checkout
- **After**: Only selected items are deleted; unselected items remain

### orders Table
- No changes to structure or behavior
- Orders created normally for both Buy Now and Cart

### payments Table
- No changes to structure or behavior
- Payment records created normally for both purchase types

## Code Quality Improvements

1. **Clear Intent**: Code now explicitly shows Buy Now vs Cart processing
2. **Better Logging**: Added error log for debugging checkout issues
3. **Proper Separation**: Buy Now and Cart have distinct code paths
4. **Selective Deletion**: Cart clearing now respects user selection
5. **Data Validation**: Buy Now product data is validated before processing

## Edge Cases Handled

✅ Empty cart + Buy Now → Works (doesn't require cart)
✅ Existing cart + Buy Now → Works (buy now item independent)
✅ Select some cart items → Works (others remain)
✅ Select all cart items → Works (cart becomes empty)
✅ Select no items → Shows validation error (can't checkout with nothing)
✅ Buy Now with invalid product → Shows product not found
✅ Browser back button after order → No issues (session cleared properly)

## Performance Considerations

- **Buy Now**: No database cart query needed (faster)
- **Cart Checkout**: Selective deletion is targeted (faster than full clear)
- **No N+1 queries**: All item fetching is batched
- **Transaction safety**: All changes wrapped in transaction

## Security

- ✅ CSRF token validation (existing)
- ✅ User authentication required (existing)
- ✅ Order ownership verification (existing)
- ✅ Input validation for item IDs (improved)
- ✅ SQL injection prevention with prepared statements (existing)

## Backward Compatibility

✅ Existing functionality preserved
✅ Session handling unchanged
✅ Database schema unchanged
✅ URL parameters unchanged
✅ API responses unchanged

## Rollback Plan

If issues arise, changes can be reverted:
1. Revert process_checkout.php to previous version
2. Revert checkout.php to previous version
3. No database migrations needed

---

**Version**: 1.0
**Date**: January 18, 2026
**Status**: Complete and Tested
