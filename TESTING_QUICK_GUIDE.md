# Quick Testing Guide - Cart Selection Persistence Fix

## The Fix in 30 Seconds

**Problem:** Validation errors during checkout caused selected items to be lost on retry.
**Solution:** 
1. Don't clear sessionStorage until order succeeds (client-side)
2. Backup selections in PHP SESSION (server-side)
3. Use SESSION as fallback if client storage is cleared

---

## Quick Test (5 Minutes)

### Setup
1. Log into your account
2. Add 5 different products to cart
3. Go to cart page

### Test Steps
```
STEP 1: SELECT ITEMS
- Check exactly 2 items (leave 3 unchecked)
- Click "Proceed to Checkout"

STEP 2: TRIGGER ERROR
- Leave "Shipping Address" empty
- Click "Place Order"
- See error: "Please select a shipping address"

STEP 3: VERIFY PERSISTENCE
- Open DevTools (F12)
- Go to Application tab → SessionStorage
- Check: selectedCartItems = "[1, 3]" (or your selected IDs)
- ✅ Should still have values!

STEP 4: RETRY CHECKOUT
- Select an address
- Click "Place Order" again

STEP 5: VERIFY ORDER
- Should create order with ONLY 2 items
- Cart should have remaining 3 items
```

**Result:** ✅ If only 2 items were ordered, fix is working!

---

## Expected Behavior

### Before Fix ❌
1. Select items [2, 4]
2. Click checkout → error
3. Retry checkout → ALL items selected
4. Order includes all items in cart

### After Fix ✅
1. Select items [2, 4]
2. Click checkout → error  
3. Retry checkout → ONLY [2, 4] selected
4. Order includes only [2, 4]
5. Unselected items remain in cart

---

## Advanced Verification

### Check DevTools Console
```javascript
// BEFORE placing order:
sessionStorage.getItem('selectedCartItems')
// Output: "[1,3,5]" ✅

// AFTER validation error (BEFORE fix):
// Output: null ❌

// AFTER validation error (AFTER fix):
// Output: "[1,3,5]" ✅

// AFTER successful order:
// Output: null ✅ (cleared only on success)
```

### Check Server Logs
```bash
# XAMPP path: C:\xampp\apache\logs\error.log
# Look for: "Cart checkout using post:" or "Cart checkout using session:"

# If you see "using session:" then fallback was triggered ✅
# This means client sessionStorage was cleared but server backup was used
```

### Check Database
```sql
-- Before checkout: cart_items has 5 items for user
SELECT COUNT(*) FROM cart_items 
WHERE cart_id IN (SELECT cart_id FROM cart WHERE user_id = YOUR_USER_ID);
-- Result: 5

-- After checkout with 2 selected items:
-- Result: 3 (only unselected items remain)
```

---

## Test Scenarios

### ✅ Scenario 1: Single Validation Error
1. Select [2, 4, 6]
2. Leave address empty → error
3. Retry with address → ✅ Works

### ✅ Scenario 2: Multiple Errors
1. Select [1, 3]
2. Missing address → error
3. Missing payment method → error  
4. All fields filled → ✅ Works

### ✅ Scenario 3: Network Error
1. Select [2, 5]
2. Disable internet → error
3. Re-enable internet → retry → ✅ Works

### ✅ Scenario 4: Buy Now (shouldn't affect)
1. Select items in cart [1, 2, 3]
2. Go to product page, click "Buy Now" 
3. Buy Now should work independently
4. Original cart selection [1, 2, 3] preserved ✅

---

## Common Issues & Solutions

### Issue 1: All items ordered despite selecting 2
**Cause:** Fix not applied or JavaScript error
**Solution:** 
- Clear browser cache (Ctrl+Shift+Del)
- Check console for errors (F12 → Console)
- Verify checkout.php is updated

### Issue 2: Order never creates (keeps giving errors)
**Cause:** Could be unrelated issue
**Solution:**
- Check server logs: `logs/php_error.log`
- Verify database connection
- Run test with complete form data

### Issue 3: sessionStorage shows null even with items selected
**Cause:** JavaScript issue in cart.php
**Solution:**
- Verify "Proceed to Checkout" button exists
- Check browser console for JS errors
- Verify cart.js loaded correctly

---

## Success Indicators

- ✅ sessionStorage persists across validation errors
- ✅ Only selected items appear in order summary on retry
- ✅ Only selected items charged in final order
- ✅ Unselected items remain in cart after checkout
- ✅ No JavaScript console errors
- ✅ No PHP errors in logs

---

## Rollback Instructions (If Needed)

If something goes wrong, simply revert the two files:

### Revert checkout.php
```php
// Change line 807 back to BEFORE the fetch:
if (selectedItems) {
    formData.append('selectedItems', selectedItems);
    sessionStorage.removeItem('selectedCartItems');  // Put this back here
}
```

### Revert process_checkout.php  
```php
// Simplify the selectedItems handling back to original:
$cart_items = $all_cart_items;
if (isset($_POST['selectedItems']) && !empty($_POST['selectedItems'])) {
    $selected_items = json_decode($_POST['selectedItems'], true);
    if (is_array($selected_items)) {
        $selected_item_ids = array_map('intval', $selected_items);
        $cart_items = array_filter($all_cart_items, function($item) use ($selected_item_ids) {
            return in_array((int)$item['item_id'], $selected_item_ids);
        });
    }
}
```

---

## Questions?

### What gets cleared?
- sessionStorage['selectedCartItems'] ← Client storage
- $_SESSION['checkout_selected_items'] ← Server storage  
- Both cleared ONLY after successful order

### What if browser sessionStorage is disabled?
- Server-side $_SESSION backup kicks in automatically
- Order still processes correctly

### Does this affect Buy Now?
- No, Buy Now uses separate isBuyNow flag
- Buy Now items not affected by selection persistence

### Does this break anything else?
- No, checked for backward compatibility
- Existing functionality preserved
- No database schema changes

---

## Contact/Support

For issues:
1. Check logs: `C:\xampp\apache\logs\error.log`
2. Check DevTools: F12 → Console and Application
3. Run verification steps above
4. Review CART_SELECTION_PERSISTENCE.md for technical details

---

**Test Status:** READY
**Estimated Test Time:** 5-10 minutes
**Difficulty:** Easy - Just select items and try checkout with error
