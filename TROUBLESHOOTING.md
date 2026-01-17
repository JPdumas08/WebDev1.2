# Common Issues & Quick Fixes

## Quick Reference Guide for Common Problems

### 1. Login Issues

#### Problem: "Invalid security token" error
**Cause:** CSRF token mismatch
**Fix:**
```php
// Clear session and try again
session_destroy();
// Or clear browser cookies
```

#### Problem: "Too many login attempts"
**Cause:** Rate limiting triggered
**Fix:**
```php
// In PHP or wait 15 minutes
unset($_SESSION['rate_limit_login']);
```

#### Problem: Login succeeds but redirects to login again
**Cause:** Session not persisting
**Fix:**
1. Check `session.save_path` is writable
2. Verify cookies are enabled in browser
3. Check for mixed HTTP/HTTPS
4. Clear browser cache completely

### 2. Cart Issues

#### Problem: Cart items not showing
**Cause:** User not logged in or cart empty
**Fix:**
```sql
-- Check if cart exists for user
SELECT * FROM cart WHERE user_id = YOUR_USER_ID;

-- Check cart items
SELECT * FROM cart_items WHERE cart_id = YOUR_CART_ID;
```

#### Problem: Cart items disappear after adding
**Cause:** Database connection or session issue
**Fix:**
1. Check `cart_debug.log` for errors
2. Verify database tables exist
3. Check JavaScript console for errors

### 3. CSS/Design Issues

#### Problem: Website looks broken (no CSS)
**Cause:** CSS file not loading
**Fix:**
1. Clear browser cache (Ctrl+F5)
2. Check file exists: `styles.css`
3. Verify path in `includes/header.php`:
   ```html
   <link href="styles.css" rel="stylesheet">
   ```
4. Check browser console for 404 errors

#### Problem: Images not showing
**Cause:** Incorrect image paths
**Fix:**
```php
// Verify image path
$img = !empty($item['image']) ? htmlspecialchars($item['image']) : 'image/placeholder.png';
```

### 4. Database Issues

#### Problem: Connection failed error
**Fix:**
```php
// db.php - verify settings
$host = '127.0.0.1';
$db   = 'web_dev';
$user = 'root';
$pass = '';

// Test connection
mysqli_connect('127.0.0.1', 'root', '', 'web_dev') or die('Cannot connect');
```

#### Problem: Table doesn't exist
**Fix:**
```sql
-- Import schema
SOURCE WEBDEV-MAIN.sql;
SOURCE checkout_tables.sql;

-- Or manually create missing table
-- Check error message for table name
```

#### Problem: Column not found
**Cause:** Database schema mismatch
**Fix:**
```sql
-- Check table structure
DESCRIBE users;
DESCRIBE products;
DESCRIBE cart;

-- Add missing column if needed
ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE;
```

### 5. Session Issues

#### Problem: Session expired message
**Cause:** 2-hour inactivity timeout
**Fix:**
```php
// Adjust timeout in init_session.php
$timeout = 7200; // 2 hours - increase if needed
```

#### Problem: "Session invalid" error
**Cause:** User agent changed (possible hijacking)
**Fix:**
```php
// Legitimate user agent change - clear and re-login
session_destroy();
// Then login again
```

### 6. Form Submission Issues

#### Problem: Form submits but nothing happens
**Cause:** JavaScript error or validation issue
**Fix:**
1. Open browser console (F12)
2. Look for JavaScript errors
3. Check network tab for failed requests
4. Verify form action URL is correct

#### Problem: Validation errors not showing
**Fix:**
```javascript
// Check validation.js is loaded
console.log(typeof validate);

// Verify jQuery is loaded
console.log(typeof jQuery);
```

### 7. Checkout Issues

#### Problem: "Please add a shipping address" error
**Cause:** No addresses saved for user
**Fix:**
```sql
-- Check addresses
SELECT * FROM addresses WHERE user_id = YOUR_USER_ID;

-- Add test address
INSERT INTO addresses (user_id, full_name, phone, address_line1, city, state, postal_code, is_default)
VALUES (1, 'Test User', '09123456789', '123 Test St', 'Manila', 'Metro Manila', '1000', 1);
```

#### Problem: Order not being created
**Cause:** Transaction rollback or validation error
**Fix:**
1. Check `logs/error.log`
2. Verify cart has items
3. Check address is selected
4. Verify payment method is chosen

### 8. Registration Issues

#### Problem: "An account with that email or username already exists"
**Fix:**
```sql
-- Check if user exists
SELECT * FROM users WHERE email_address = 'test@example.com';

-- Delete test account if needed (CAREFUL!)
DELETE FROM users WHERE email_address = 'test@example.com';
```

#### Problem: Password requirements not clear
**Fix:** Update `register.php` or add client-side validation to show:
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number

### 9. Permission Issues (Production)

#### Problem: Can't write to logs
**Fix:**
```bash
# Linux/Mac
chmod 755 logs
chmod 644 logs/*.log

# Windows - Right-click folder → Properties → Security → Edit
# Give write permission to IIS_IUSRS or IUSR
```

#### Problem: Can't read/write session files
**Fix:**
```php
// Check session path
echo session_save_path();

// Make sure it's writable
// Linux: chmod 733 /var/lib/php/sessions
// Windows: Set permissions on temp folder
```

### 10. HTTPS/SSL Issues

#### Problem: Mixed content warnings
**Cause:** Loading HTTP resources on HTTPS page
**Fix:**
```html
<!-- Use protocol-relative URLs -->
<script src="//code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Or use HTTPS explicitly -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
```

## Emergency Fixes

### Completely Reset User Session
```php
<?php
session_start();
$_SESSION = array();
session_destroy();
setcookie(session_name(), '', time()-3600, '/');
echo "Session cleared. Please <a href='login.php'>login</a> again.";
?>
```

### Reset User Password (Admin)
```php
<?php
require_once 'db.php';
$user_id = 1; // Change this
$new_password = 'NewPassword123';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('UPDATE users SET password = :pwd WHERE user_id = :id');
$stmt->execute([':pwd' => $hash, ':id' => $user_id]);
echo "Password reset for user $user_id";
?>
```

### Clear All Carts (Testing)
```sql
-- WARNING: This deletes all cart data!
TRUNCATE TABLE cart_items;
TRUNCATE TABLE cart;
```

### Check PHP Error Log
```php
<?php
// View last 50 lines of error log
$log_file = 'logs/error.log';
if (file_exists($log_file)) {
    $lines = file($log_file);
    echo '<pre>';
    echo htmlspecialchars(implode('', array_slice($lines, -50)));
    echo '</pre>';
} else {
    echo "No error log found.";
}
?>
```

## Useful SQL Queries

### Find User by Email
```sql
SELECT * FROM users WHERE email_address = 'user@example.com';
```

### Get User's Cart
```sql
SELECT ci.*, p.product_name, p.product_price
FROM cart_items ci
JOIN cart c ON ci.cart_id = c.cart_id
JOIN products p ON ci.product_id = p.product_id
WHERE c.user_id = 1;
```

### Recent Orders
```sql
SELECT * FROM orders WHERE user_id = 1 ORDER BY order_date DESC LIMIT 10;
```

### Delete Test Data
```sql
-- Delete test user and all related data
SET FOREIGN_KEY_CHECKS=0;
DELETE FROM cart_items WHERE cart_id IN (SELECT cart_id FROM cart WHERE user_id = 1);
DELETE FROM cart WHERE user_id = 1;
DELETE FROM addresses WHERE user_id = 1;
DELETE FROM orders WHERE user_id = 1;
DELETE FROM users WHERE user_id = 1;
SET FOREIGN_KEY_CHECKS=1;
```

## Performance Issues

### Slow Page Load
1. Enable OPcache in `php.ini`:
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   ```

2. Add database indexes:
   ```sql
   CREATE INDEX idx_user_email ON users(email_address);
   CREATE INDEX idx_cart_user ON cart(user_id);
   ```

3. Enable compression in `.htaccess` (already included)

### Slow Queries
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;

-- Check slow queries
SHOW FULL PROCESSLIST;
```

## Debugging Tips

### Enable Full Error Display (Development Only!)
```php
// Add to top of problematic file
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
```

### Check if File is Being Included
```php
// Add at top of file
error_log('File loaded: ' . __FILE__);
```

### Dump Session Data
```php
<?php
session_start();
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
?>
```

### Test Database Connection
```php
<?php
require_once 'db.php';
try {
    $stmt = $pdo->query('SELECT 1');
    echo "Database connected successfully!";
    print_r($pdo->getAttribute(PDO::ATTR_SERVER_VERSION));
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
```

## Getting Help

1. **Check error logs first:**
   - `logs/error.log`
   - `cart_debug.log`
   - Browser console (F12)

2. **Enable debugging:**
   - Set `APP_ENV` to `development` in `config.php`
   - Check browser Network tab

3. **Search error messages:**
   - Copy exact error message
   - Search in this file
   - Check PHP documentation

4. **Common search queries:**
   - "PDO error" + your error message
   - "PHP session" + your issue
   - "Bootstrap 5" + your problem

---

**Remember:** Always backup before making database changes!
