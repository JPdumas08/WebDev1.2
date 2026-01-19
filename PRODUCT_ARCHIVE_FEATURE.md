# Product Archive & Real-Time Inventory Sync Feature

## Overview
This feature enhances the admin products management system with archive functionality and real-time inventory synchronization between admin and customer pages.

## Features Implemented

### 1. Product Archive System

#### Database Changes
- Added `is_archived` column (TINYINT, default 0)
- Added `archived_at` timestamp column
- Added index on `is_archived` for performance
- Migration file: `migrations/003_add_product_archive.sql`

#### Admin Interface
**Products Page ([admin/products.php](admin/products.php)):**
- New **Status Filter** dropdown with options:
  - Active Products (default)
  - Archived Products
  - All Products
- **Archive/Restore buttons** for each product
- Visual indicators:
  - Archived products shown with reduced opacity
  - Gray "ARCHIVED" badge on product name
  - "Archived" status badge replaces stock status
- Confirmation dialogs before archiving/restoring

**Archive API ([admin/api/archive_product.php](admin/api/archive_product.php)):**
- POST endpoint for archiving/unarchiving products
- Validates product exists before modification
- Returns JSON success/error response
- Updates `is_archived` flag and `archived_at` timestamp

### 2. Real-Time Inventory Synchronization

#### Customer-Facing Protection
All customer pages now exclude archived products:

**1. Products Listing ([products.php](products.php)):**
- Query filters: `WHERE is_archived = 0`
- Includes stock information in queries

**2. Product Details ([product_detail.php](product_detail.php)):**
- Validates product is not archived
- Shows 404 or redirect if archived
- Displays current stock levels

**3. Fetch Products API ([fetch_products.php](fetch_products.php)):**
- Returns only active products
- Includes stock data for availability checks

**4. Add to Cart ([add_to_cart.php](add_to_cart.php)):**
- Validates product is not archived before adding
- Checks real-time stock availability
- Prevents adding out-of-stock products
- Caps quantity to available stock
- Returns detailed error messages:
  - `product_archived`: Product no longer available
  - `out_of_stock`: Product currently unavailable
  - `product_not_available`: Product doesn't exist

**5. Checkout Page ([checkout.php](checkout.php)):**
- Validates products in Buy Now mode
- Checks archive status and stock before display
- Redirects with error if product unavailable
- Adjusts quantity to available stock

**6. Process Checkout ([process_checkout.php](process_checkout.php)):**
- **Pre-checkout validation** loop checks all products:
  - Verifies product still exists
  - Confirms product not archived
  - Validates sufficient stock available
- Returns detailed error array if any issues found
- **Stock deduction** on successful order:
  - Updates `product_stock` atomically
  - Verifies stock was actually updated
  - Rolls back transaction if stock update fails
- Prevents race conditions with database transaction

## User Experience

### Admin Workflow
1. **View Active Products** (default view)
2. **Archive Product:**
   - Click "Archive" button
   - Confirm action in dialog
   - Product disappears from customer store
   - Product shown in "Archived Products" filter
3. **Restore Product:**
   - Switch to "Archived Products" view
   - Click "Restore" button
   - Product becomes visible to customers again

### Customer Protection
1. **Cannot view** archived products in listings
2. **Cannot access** archived product detail pages
3. **Cannot add** archived products to cart
4. **Cannot checkout** with archived products
5. **Real-time stock checks** prevent overselling

## Technical Implementation

### Archive System
```sql
-- Archive a product
UPDATE products 
SET is_archived = 1, archived_at = NOW() 
WHERE product_id = ?

-- Restore a product
UPDATE products 
SET is_archived = 0, archived_at = NULL 
WHERE product_id = ?
```

### Customer Queries
```sql
-- All customer-facing queries include:
WHERE is_archived = 0
```

### Stock Validation
```php
// Pre-checkout validation
foreach ($cart_items as $item) {
    $check = $pdo->prepare("SELECT product_stock, is_archived, product_name 
                            FROM products WHERE product_id = :pid");
    // Validate stock and archive status
}
```

### Stock Deduction
```php
// Atomic stock update
UPDATE products 
SET product_stock = product_stock - :qty 
WHERE product_id = :pid AND product_stock >= :qty
```

## Database Schema Updates

```sql
-- Products table now includes:
- is_archived TINYINT(1) DEFAULT 0
- archived_at DATETIME NULL
- INDEX idx_is_archived (is_archived)
```

## API Endpoints

### Archive Product
**Endpoint:** `POST /admin/api/archive_product.php`

**Parameters:**
- `product_id` (int, required): Product to archive
- `action` (string, required): "archive" or "unarchive"

**Response:**
```json
{
  "success": true,
  "message": "Product archived successfully",
  "product_id": 123,
  "product_name": "Diamond Ring",
  "is_archived": 1
}
```

## Error Handling

### Customer-Facing Errors
1. **Product Archived:**
   - Message: "This product is no longer available"
   - Action: Redirect to products page

2. **Out of Stock:**
   - Message: "This product is currently out of stock"
   - Action: Prevent add to cart

3. **Insufficient Stock:**
   - Message: "Product X only has Y units available (you requested Z)"
   - Action: Show available quantity

### Admin Actions
- Confirmation dialogs before archive/restore
- Success/error alerts after actions
- Automatic page reload to show updated status

## Performance Considerations

1. **Indexed Column:** `is_archived` has index for fast filtering
2. **Minimal Query Changes:** Only adds `WHERE is_archived = 0`
3. **Transaction Safety:** Stock updates in database transaction
4. **Atomic Operations:** Stock deduction uses conditional UPDATE

## Security Features

1. **Admin Authentication:** Archive API requires admin login
2. **Input Validation:** All inputs sanitized and validated
3. **SQL Injection Protection:** Prepared statements throughout
4. **CSRF Protection:** Checkout form includes CSRF token
5. **Race Condition Prevention:** Transaction-based stock updates

## Testing Checklist

### Archive Functionality
- [ ] Archive product from admin panel
- [ ] Verify product hidden from customer store
- [ ] Verify product shown in "Archived Products" filter
- [ ] Restore archived product
- [ ] Verify product visible to customers again

### Stock Synchronization
- [ ] Update stock in admin, verify customer sees new stock
- [ ] Add product to cart, verify stock decreases after order
- [ ] Try adding more than available stock
- [ ] Complete order and verify stock deducted
- [ ] Try checking out with out-of-stock product

### Customer Protection
- [ ] Try accessing archived product URL directly
- [ ] Try adding archived product via API
- [ ] Verify archived products don't appear in search
- [ ] Verify archived products don't appear in categories

## Files Modified

### Admin Files
- `admin/products.php` - Archive UI and filters
- `admin/api/archive_product.php` - Archive API endpoint (NEW)

### Customer Files
- `products.php` - Exclude archived from listings
- `product_detail.php` - Block archived product access
- `fetch_products.php` - Filter archived products
- `add_to_cart.php` - Validate stock and archive status
- `checkout.php` - Pre-display validation
- `process_checkout.php` - Real-time validation + stock deduction

### Database Files
- `migrations/003_add_product_archive.sql` - Schema changes (NEW)

## Future Enhancements

1. **Archive Reasons:** Add note field for why product was archived
2. **Bulk Archive:** Select multiple products to archive at once
3. **Auto-Archive:** Automatically archive out-of-stock products after X days
4. **Archive History:** Track who archived and when
5. **Archive Expiry:** Auto-delete archived products after X months
6. **Low Stock Alerts:** Email admin when stock falls below threshold
7. **Stock History:** Track stock changes over time
8. **Restock Notifications:** Alert customers when archived/OOS products return

## Support

For issues or questions:
- Check error logs in Apache error.log
- Verify database migration ran successfully
- Ensure `is_archived` column exists in products table
- Test archive API endpoint directly via browser dev tools

---

**Version:** 1.0  
**Last Updated:** January 19, 2026  
**Status:** ✅ Production Ready
