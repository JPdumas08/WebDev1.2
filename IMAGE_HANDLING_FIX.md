# Product Image Handling - Admin Dashboard Fix

## Overview

Fixed product image display in the admin dashboard by implementing proper image path resolution for the subdirectory structure. Images now display correctly in both the customer-facing website and admin dashboard.

## Problem Identified

**Issue**: Product images displayed correctly on the customer-facing website (`/`) but were broken/missing in the admin dashboard (`/admin/`).

**Root Cause**: 
- Database stores relative paths: `image/lotusbrace.jpg`
- Customer site at root level resolves to: `/image/lotusbrace.jpg` ✅ Works
- Admin site at `/admin/` subdirectory resolves to: `/admin/image/lotusbrace.jpg` ❌ Broken (file not there)

**Impact**: Admin users saw broken image placeholders or missing images when managing products.

## Solution Implemented

### 1. Image Path Utilities Module
**File**: `includes/image_utils.php`

Created a comprehensive image utility module with helper functions:

```php
// Get proper image URL based on current context
get_image_url($db_path, $context = 'auto')

// Get image path specifically for admin display
get_admin_image_path($db_path)

// Get image path specifically for web/customer display
get_web_image_path($db_path)

// Verify image file exists on filesystem
image_exists($db_path)

// Get formatted file size
get_image_size_formatted($db_path)

// Sanitize filename for safe storage
sanitize_filename($filename)

// Validate image upload
validate_image_upload($file, $max_size = 5242880)
```

### 2. Updated Admin Pages

#### `admin/products.php` (Line 167)
**Before**:
```php
<img src="<?php echo htmlspecialchars($product['product_image']); ?>" alt="Product" 
     style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
```

**After**:
```php
<img src="<?php echo htmlspecialchars(get_admin_image_path($product['product_image'])); ?>" 
     alt="Product" 
     style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--admin-border); background: var(--admin-bg-secondary);" 
     onerror="this.src='../image/placeholder.png'">
```

**Improvements**:
- Uses `get_admin_image_path()` to convert relative paths to correct relative URLs for admin subdirectory
- Added `onerror` handler for graceful fallback to placeholder.png
- Enhanced styling with border and background color
- Automatic path correction: `image/file.jpg` → `../image/file.jpg`

#### `admin/order_detail.php` (Line 248)
Applied same fix to product images displayed in order detail view.

#### `admin/product_edit.php`
Added image utilities import for future image upload functionality.

### 3. Placeholder Image
**File**: `image/placeholder.png`

Created a SVG placeholder image for when product images are missing. Displays:
- Generic image icon
- "No Image Available" text
- Gray background matching admin theme

## How It Works

### Path Conversion Logic
The `get_admin_image_path()` function handles these conversions:

| Database Value | Converted To | Location | Result |
|---|---|---|---|
| `image/lotusbrace.jpg` | `../image/lotusbrace.jpg` | `/admin/products.php` | `/image/lotusbrace.jpg` ✅ |
| `./image/product.jpg` | `../image/product.jpg` | `/admin/products.php` | `/image/product.jpg` ✅ |
| `/image/file.jpg` | `../image/file.jpg` | `/admin/products.php` | `/image/file.jpg` ✅ |
| Empty/null | `../image/placeholder.png` | `/admin/products.php` | `/image/placeholder.png` ✅ |

### Fallback Mechanism
```html
onerror="this.src='../image/placeholder.png'"
```
If the image fails to load for any reason, JavaScript automatically displays the placeholder.

## Files Modified

1. **includes/image_utils.php** (NEW)
   - Comprehensive image handling utilities
   - Helper functions for path conversion
   - Validation and sanitization functions

2. **admin/products.php**
   - Added image utilities import
   - Updated image display code with path correction
   - Added fallback placeholder handling

3. **admin/order_detail.php**
   - Added image utilities import
   - Updated order item image display
   - Added fallback handling

4. **admin/product_edit.php**
   - Added image utilities import for future use

5. **image/placeholder.png** (NEW)
   - Default placeholder image for missing product images

## Testing the Fix

### 1. Admin Products Page
1. Go to `/admin/products.php`
2. Look at products table
3. Verify product images display as 50x50px thumbnails with proper styling
4. Confirm images match those on customer website

### 2. Order Detail Page
1. Go to `/admin/orders.php`
2. Click on any order
3. Look at order items section
4. Verify product images display correctly

### 3. Verify Image Sync
1. Check `/image/` directory for image files
2. Verify database stores paths like `image/lotusbrace.jpg`
3. Compare with customer site at `/products.php`
4. Both should display same images

### 4. Test Placeholder Fallback
1. Edit a product in admin
2. Change image path to non-existent file: `image/nonexistent.jpg`
3. Save and view products list
4. Image should show placeholder.png

## Security Considerations

### Path Validation
The utilities prevent path traversal attacks:
```php
// Only allows relative paths in format:
// - image/filename.jpg (relative)
// - /image/filename.jpg (root-relative)
// - ../image/filename.jpg (already prefixed)
```

### File Upload Validation
When implementing image uploads, use:
```php
$validation = validate_image_upload($_FILES['image']);
if (!$validation['valid']) {
    echo "Error: " . $validation['error'];
}
```

Checks:
- File size (5MB default max)
- MIME type validation
- Extension whitelist: jpg, jpeg, png, gif, webp
- Uses finfo for reliable MIME detection

### Filename Sanitization
```php
$safe_filename = sanitize_filename($_FILES['image']['name']);
// Removes special characters, normalizes to lowercase
// Example: "Product Photo!@#.JPG" → "product-photo.jpg"
```

## Future Enhancements

### 1. Image Upload Functionality
When implementing product image uploads:
```php
require_once __DIR__ . '/../includes/image_utils.php';

if ($_FILES['image']['name']) {
    $validation = validate_image_upload($_FILES['image']);
    if ($validation['valid']) {
        $filename = sanitize_filename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], 
                          __DIR__ . '/../image/' . $filename);
        $db_path = 'image/' . $filename;
    }
}
```

### 2. Multiple Images Per Product
Currently stores single image. Future:
- Create `product_images` table
- Store multiple image references
- Display image gallery in admin

### 3. Image Optimization
- Implement image compression on upload
- Generate thumbnails
- Support WebP format for faster loading

### 4. Image Management Dashboard
- Browse and manage image files
- Upload, replace, delete images
- View image usage across products
- Bulk operations

## Troubleshooting

### Images Still Not Displaying
1. Check browser console for broken image errors
2. Verify image files exist in `/image/` directory:
   ```bash
   ls -la /xampp/htdocs/WebDev1.2/image/
   ```
3. Check database image paths:
   ```sql
   SELECT product_name, product_image FROM products;
   ```
4. Verify file permissions: images should be readable (644)

### Placeholder Shows Instead of Real Image
1. Check if actual image file exists
2. Verify database path matches filename
3. Check browser cache: clear and refresh
4. Check file permissions on image

### Path Conversion Not Working
1. Verify `includes/image_utils.php` is included
2. Check function is called: `get_admin_image_path($path)`
3. Verify database path format: should be `image/filename.jpg`
4. Check for typos in path (case-sensitive on Linux)

## Database Verification

Current database schema has product_image column:
```sql
SELECT product_id, product_name, product_image FROM products LIMIT 3;
```

Should return paths like:
```
1 | Lotus Bracelet | image/lotusbrace.jpg
2 | Pearl Earrings | image/earpearl.jpg
3 | Tied Necklace  | image/necktied.jpg
```

## Summary

✅ Created comprehensive image utilities module
✅ Fixed image paths in admin products display
✅ Fixed image paths in order detail display
✅ Added fallback placeholder image
✅ Implemented graceful error handling
✅ Added security measures for image handling
✅ Documented for future image upload implementation

Product images now display correctly in admin dashboard, matching the customer-facing website.
