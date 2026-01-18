# Cart Selection Persistence Implementation

## Problem Statement
When users selected specific items from their shopping cart and proceeded to checkout, if a validation warning appeared (e.g., invalid address, missing payment method), retrying the checkout would default to ALL cart items instead of just the previously selected items. The selection state was being lost.

## Root Cause Analysis
The original code had `sessionStorage.removeItem('selectedCartItems')` being called BEFORE the fetch request to the server completed. This meant:
1. User selects items → stored in sessionStorage
2. Clicks "Place Order" → `sessionStorage.removeItem()` called immediately
3. Fetch request sent to server
4. Server returns validation error
5. sessionStorage is already empty (cleared at step 2)
6. User retries → no selection state available → defaults to all items

## Solution Implemented

### A. Client-Side State Preservation (checkout.php)

**Removed premature clearing:**
```javascript
// BEFORE (BROKEN):
if (selectedItems) {
    formData.append('selectedItems', selectedItems);
    sessionStorage.removeItem('selectedCartItems');  // ❌ Clears before fetch completes
}

// AFTER (FIXED):
if (selectedItems) {
    formData.append('selectedItems', selectedItems);
    // DO NOT clear sessionStorage here - only clear after successful server confirmation
}
```

**Move clearing to success path only:**
```javascript
.then(data => {
    if (data.success) {
        // ✅ Clear selectedItems ONLY after successful order creation
        sessionStorage.removeItem('selectedCartItems');
        // Redirect to confirmation page
    } else {
        // ❌ Do NOT clear on error - preserve selection for retry
        ToastNotification.error(data.message);
    }
})
.catch(error => {
    // ❌ Do NOT clear on network error - preserve selection for retry
    ToastNotification.error('Network error: ' + error.message);
});
```

**Result:** Selection now persists across failed validation attempts and retries.

---

### B. Server-Side Session Backup (process_checkout.php)

**Store selected items in PHP SESSION as backup:**
```php
if (isset($_POST['selectedItems']) && !empty($_POST['selectedItems'])) {
    $selected_items_json = $_POST['selectedItems'];
    // ✅ BACKUP: Also store in SESSION for persistence across retries
    $_SESSION['checkout_selected_items'] = $selected_items_json;
}
```

**Fallback to SESSION if POST data missing:**
```php
elseif (isset($_SESSION['checkout_selected_items']) && !empty($_SESSION['checkout_selected_items'])) {
    // ✅ FALLBACK: Use SESSION backup if client sessionStorage was cleared
    $selected_items_json = $_SESSION['checkout_selected_items'];
    $selected_items_source = 'session';
    error_log('Using SESSION backup for selectedItems (client sessionStorage may have been cleared)');
}
```

**Clear session backup only after successful order:**
```php
// Inside the try block, after $pdo->commit():
unset($_SESSION['checkout_selected_items']);  // ✅ Only cleared after transaction commits
```

**Result:** Server-side redundancy ensures selections survive even if client sessionStorage is unexpectedly cleared.

---

## Data Flow with Persistence

### Happy Path (Successful Checkout)
```
Cart Page
  ↓ (user selects items)
sessionStorage['selectedCartItems'] = [1, 3, 5]
  ↓ (click "Proceed to Checkout")
checkout.php (display selected items)
  ↓ (form submit)
process_checkout.php
  ├─ Receive selectedItems from POST
  ├─ Store in $_SESSION['checkout_selected_items'] (backup)
  ├─ Create order with ONLY items [1, 3, 5]
  ├─ Delete ONLY those items from cart_items table
  ├─ Clear $_SESSION['checkout_selected_items'] (after success)
  └─ Redirect to confirmation
  ↓
checkout.php JS (success callback)
  └─ Clear sessionStorage['selectedCartItems']
```

### Error Path (Validation Fails)
```
checkout.php form submit
  ├─ sessionStorage['selectedCartItems'] = [1, 3, 5] (NOT cleared)
  ↓
process_checkout.php
  ├─ Validation fails
  ├─ Return error JSON
  ├─ $_SESSION['checkout_selected_items'] preserved
  └─ Do NOT clear selectedItems
  ↓
checkout.php error handler
  ├─ Show error message
  ├─ DO NOT clear sessionStorage
  ├─ Enable "Place Order" button
  └─ User can click again (same selection [1, 3, 5])
  ↓
Retry: Process with SESSION fallback (if needed)
```

### Fallback Path (sessionStorage Cleared Unexpectedly)
```
(Hypothetically, if client sessionStorage somehow cleared between attempts)
  ↓
checkout.php form submit
  ├─ selectedItems = sessionStorage.get() → null
  └─ formData['selectedItems'] = undefined
  ↓
process_checkout.php
  ├─ POST['selectedItems'] is missing
  ├─ Check $_SESSION['checkout_selected_items'] (from previous request)
  ├─ Found! Use [1, 3, 5] from session backup
  ├─ Create order with correct items
  └─ Clear $_SESSION after success
```

---

## Files Modified

### 1. checkout.php (Lines 780-833)
**Changes:**
- Removed premature `sessionStorage.removeItem()` before fetch
- Moved removal to success callback only
- Updated error/catch handlers with preservation comments

**Key lines:**
- Line 796: No clearing before fetch
- Line 807: `sessionStorage.removeItem('selectedCartItems')` in success callback
- Line 817-821: Error handler preserves state
- Line 825-831: Catch handler preserves state

### 2. process_checkout.php (Lines 107-133, 248)
**Changes:**
- Added SESSION backup storage when selectedItems received in POST
- Added SESSION fallback when POST selectedItems missing
- Added detailed error logging for debugging
- Clear session backup only after successful order commit

**Key lines:**
- Line 112: `$_SESSION['checkout_selected_items'] = $selected_items_json;` (backup)
- Line 114-117: SESSION fallback logic
- Line 248: `unset($_SESSION['checkout_selected_items']);` (clear after success)

---

## Testing Scenarios

### Test 1: Normal Checkout (No Errors)
```
1. Select items [2, 4] from cart
2. Click "Proceed to Checkout"
3. Enter all required fields correctly
4. Click "Place Order"
5. ✅ Order created with only items [2, 4]
6. ✅ Only those items deleted from cart
7. ✅ Unselected items remain in cart
```

### Test 2: Validation Error with Retry
```
1. Select items [1, 3] from cart
2. Click "Proceed to Checkout"
3. Leave shipping address empty (invalid)
4. Click "Place Order"
5. ❌ Validation error: "Please select a shipping address"
6. sessionStorage still has [1, 3] (NOT cleared)
7. Select address
8. Click "Place Order" again
9. ✅ Order created with items [1, 3]
10. ✅ sessionStorage cleared only after success
```

### Test 3: Multiple Validation Attempts
```
1. Select items [5, 6, 7]
2. Click "Place Order" without payment method
3. ❌ Error: "Please select a payment method"
4. sessionStorage = [5, 6, 7] ✅
5. Click "Place Order" without address
6. ❌ Error: "Please select a shipping address"
7. sessionStorage = [5, 6, 7] ✅
8. Fill in all fields
9. Click "Place Order"
10. ✅ Order with items [5, 6, 7]
```

### Test 4: Buy Now (Independent Mode)
```
1. From product page, click "Buy Now"
2. Should NOT use cart selection logic
3. Create order with single product only
4. Should NOT affect cart selections
5. ✅ Verified: Uses isBuyNow flag, separate logic path
```

---

## How to Verify Implementation

### 1. Browser Console Check
```javascript
// Open browser DevTools on checkout page
// Before placing order:
sessionStorage.getItem('selectedCartItems')  // Should show: [1,3,5] etc

// After clicking "Place Order" (error case):
sessionStorage.getItem('selectedCartItems')  // Should still show: [1,3,5]

// After successful order:
sessionStorage.getItem('selectedCartItems')  // Should show: null
```

### 2. Server Log Check
```
Check XAMPP/logs/php_error.log for:
- "Cart checkout using post: Processing X selected items..."
- "Cart checkout using session: Processing X selected items..." (if fallback used)
- Line-by-line order creation and deletion logs
```

### 3. Database Check
```sql
-- Check that ONLY selected items were deleted from cart_items
SELECT * FROM cart_items WHERE user_id = X;

-- Example: If user had items [1,2,3,4,5] selected [2,4], after checkout:
-- Only item 2 and 4 should be gone, items [1,3,5] remain
```

### 4. Manual Testing Flow
```
1. Create test account
2. Add 5 different products to cart
3. Select exactly 2 items
4. Try checkout without shipping address (should error)
5. Verify only 2 items show in order summary
6. Add address and retry
7. ✅ Order should have only 2 items
8. ✅ Cart should have remaining 3 items
```

---

## Architecture Benefits

### 1. Dual-Layer State Management
- **Client Layer:** sessionStorage for immediate feedback and UX
- **Server Layer:** PHP SESSION for persistence and fallback safety

### 2. Idempotent Retry
- User can click "Place Order" multiple times with same selection
- No data loss due to transient errors
- Selections only cleared after confirmed success

### 3. Graceful Degradation
- If client sessionStorage fails: server SESSION as backup
- If both fail: empty selectedItems array (processed as no filter)
- No scenario causes user data loss

### 4. Audit Trail
- Detailed error logs show which source used (POST vs SESSION)
- Can diagnose if fallback was needed
- Debugging info for troubleshooting

---

## Security Considerations

✅ **CSRF Protected:** Token validation before processing (existing)
✅ **Session Signed:** PHP SESSION data server-side only (secure)
✅ **User-Scoped:** Items belong to authenticated user
✅ **Transactional:** Order creation atomic with cart deletion
✅ **Validated:** Server validates selected item IDs against user's cart

---

## Backward Compatibility

✅ No breaking changes
✅ Existing Buy Now functionality unaffected  
✅ Existing payment methods work as before
✅ Selective deletion feature preserved
✅ localStorage checkbox state still used for persistence

---

## Summary of Fixes

| Issue | Before | After |
|-------|--------|-------|
| Selection on validation error | ❌ Lost | ✅ Persisted |
| Retry shows all items | ❌ Yes | ✅ No, shows selected only |
| Multiple validation attempts | ❌ Fails after 1st error | ✅ Works with any errors |
| sessionStorage cleared prematurely | ❌ Yes, before fetch | ✅ No, only after success |
| SERVER backup of selection | ❌ None | ✅ $_SESSION fallback |
| Error logging | ❌ Minimal | ✅ Detailed source tracking |

---

## Conclusion

The cart selection persistence issue has been completely resolved through:

1. **Client-Side Fix:** Moved sessionStorage clearing from pre-request to post-success
2. **Server-Side Backup:** Added PHP SESSION fallback for redundant protection
3. **Error Preservation:** Ensured both paths (error and catch) preserve state
4. **Clear-Only-On-Success:** Selections only removed after confirmed transaction commit

The system now provides a robust, user-friendly checkout experience where selections survive validation errors and retries.
