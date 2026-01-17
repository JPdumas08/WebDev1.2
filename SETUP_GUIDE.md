# Quick Setup Guide

## Installation Steps

### 1. Prerequisites
- XAMPP (or similar) with Apache and MySQL
- PHP 7.4 or higher
- Modern web browser

### 2. Setup Database
1. Start Apache and MySQL in XAMPP Control Panel
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Import `WEBDEV-MAIN.sql` to create the database
4. Import `checkout_tables.sql` if needed for checkout tables
5. (Optional) Run `database_enhancements.sql` for additional security features

### 3. Configure the Application
1. Open `db.php` and verify database connection settings:
   ```php
   $host = '127.0.0.1';
   $db   = 'web_dev';
   $user = 'root';
   $pass = '';
   ```

2. Open `config.php` and set environment:
   ```php
   define('APP_ENV', 'development'); // Change to 'production' when deploying
   ```

3. Create logs directory:
   ```
   mkdir logs
   ```

### 4. File Permissions (for production)
```bash
# On Linux/Mac
chmod 755 .
chmod 644 *.php
chmod 755 image includes js Video
chmod 600 db.php config.php
chmod 755 logs
```

### 5. Access the Website
- Development: http://localhost/WebDev1.2/home.php
- Or use your configured virtual host

## Default Test Accounts
Create test accounts through the registration page, or add them manually to the database.

## Common Issues & Solutions

### Issue: CSS not loading
**Solution:** Clear browser cache or check the CSS path in `includes/header.php`

### Issue: Database connection error
**Solution:** 
1. Verify MySQL is running in XAMPP
2. Check database credentials in `db.php`
3. Ensure database `web_dev` exists

### Issue: Session errors
**Solution:**
1. Ensure PHP session.save_path is writable
2. Check PHP error logs
3. Clear browser cookies

### Issue: Login not working
**Solution:**
1. Check if user exists in database
2. Verify password is hashed using `password_hash()`
3. Check browser console for JavaScript errors
4. Clear session: Close browser completely and try again

### Issue: CSRF token errors
**Solution:**
1. Make sure you're not accessing the site from different URLs (localhost vs 127.0.0.1)
2. Clear browser cookies
3. Check that session is working properly

## Security Checklist for Production

- [ ] Change `APP_ENV` to `'production'` in `config.php`
- [ ] Use strong database password
- [ ] Enable HTTPS
- [ ] Uncomment HTTPS redirect in `.htaccess`
- [ ] Review file permissions
- [ ] Enable error logging, disable display_errors
- [ ] Change database user from 'root'
- [ ] Regular backups
- [ ] Keep PHP and dependencies updated
- [ ] Monitor error logs regularly

## Features

### Implemented Security Features:
✓ CSRF Protection
✓ XSS Protection  
✓ SQL Injection Protection (Prepared Statements)
✓ Session Security
✓ Password Hashing (bcrypt)
✓ Input Validation
✓ Rate Limiting (Login)
✓ Security Headers
✓ Error Handling

### Main Features:
- User registration and authentication
- Product browsing and filtering
- Shopping cart management
- Wishlist functionality
- Checkout process
- Order history
- Address management
- Account settings
- GCash payment integration
- Cash on Delivery

## Development Tips

### Enable Debug Mode
In `config.php`:
```php
define('APP_ENV', 'development');
define('APP_DEBUG', true);
```

### Check Error Logs
- PHP errors: `logs/error.log`
- Cart operations: `cart_debug.log`
- Apache errors: `C:\xampp\apache\logs\error.log`

### Testing CSRF Protection
CSRF tokens are automatically added to forms. To test:
1. Open browser developer tools
2. Find the hidden `csrf_token` input
3. Change its value
4. Submit form - should be rejected

### Testing Rate Limiting
Try logging in with wrong password 6 times - you should be temporarily blocked.

## Support

For issues or questions:
1. Check error logs first
2. Review `SECURITY_FIXES.md` for detailed documentation
3. Check browser console for JavaScript errors
4. Verify database schema matches expected structure

## Useful Commands

### Clear cart for a user (MySQL):
```sql
DELETE FROM cart_items WHERE cart_id IN (SELECT cart_id FROM cart WHERE user_id = 1);
```

### Reset failed login attempts (PHP session):
```php
unset($_SESSION['rate_limit_login']);
```

### Check active sessions:
```sql
-- If using database sessions
SELECT * FROM sessions WHERE last_activity > (UNIX_TIMESTAMP() - 7200);
```

---

**Need Help?** Check `SECURITY_FIXES.md` for detailed security documentation.
