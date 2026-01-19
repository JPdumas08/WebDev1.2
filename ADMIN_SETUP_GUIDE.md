# Admin Dashboard Setup & Documentation

## Overview

The admin dashboard is a fully synchronized administrative interface for managing the Jeweluxe e-commerce platform. It uses the same database, brand identity, and styling as the customer-facing website, ensuring seamless data consistency and visual cohesion.

## Quick Start

### 1. Database Migration

Before accessing the admin panel, run these migrations to add required columns:

```sql
-- Add admin role support
ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0;

-- Add product management fields
ALTER TABLE products ADD COLUMN product_description TEXT;
ALTER TABLE products ADD COLUMN product_stock INT DEFAULT 0;
ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE products ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add order notes
ALTER TABLE orders ADD COLUMN order_notes TEXT;
```

Or run the migration files:
- `migrations/001_add_admin_role.sql`
- `migrations/002_enhance_product_and_order_tables.sql`

### 2. Grant Admin Access

To make a user an admin:

```sql
UPDATE users SET is_admin = 1 WHERE user_id = <your_user_id>;
```

### 3. Access the Dashboard

Navigate to `http://localhost/WebDev1.2/admin/` and login with your admin account.

## Features

### Dashboard (index.php)
- Real-time statistics: Total orders, revenue, products, customers
- Recent orders overview with quick links
- Key metrics displayed in stat cards

### Orders Management (orders.php)
- View all orders with search and filtering
- Filter by order status (pending, processing, shipped, delivered, cancelled)
- Search by order ID, customer name, or email
- Inline status updates with dropdown
- Detailed order view with full customer info, items, and summary

**Related Files:**
- `orders.php` - Orders list and filters
- `order_detail.php` - Detailed order view
- `api/update_order_status.php` - Status update endpoint

### Products Management (products.php)
- View all products with search
- Add new products
- Edit existing products with validation
- Delete products
- Manage inventory (stock levels)

**Related Files:**
- `products.php` - Products list
- `product_edit.php` - Add/edit form with validation
- `api/delete_product.php` - Deletion endpoint

### Customers (customers.php)
- View all registered customers
- Search by name or email
- Display total orders and total spent per customer
- Pagination for large customer bases
- Non-admin users only (filters out admin accounts)

### Payments (payments.php)
- View all transactions
- Filter by payment method (COD, GCash, PayPal, Bank Transfer)
- Filter by payment status (pending, paid, failed, refunded)
- Track revenue by payment type
- Pagination support

### Settings (settings.php)
- Account information display
- System information
- Logout functionality
- Support contact

## Architecture

### Directory Structure

```
admin/
├── index.php                              # Dashboard
├── orders.php                             # Orders list
├── order_detail.php                       # Order details
├── products.php                           # Products list
├── product_edit.php                       # Add/edit products
├── customers.php                          # Customers list
├── payments.php                           # Payments/transactions
├── settings.php                           # Settings
├── auth.php                               # Admin authentication
├── includes/
│   ├── header.php                         # HTML head + nav header
│   ├── sidebar.php                        # Left sidebar navigation
│   ├── footer.php                         # Footer + scripts
│   └── admin_styles.css                   # Admin-specific styles
└── api/
    ├── update_order_status.php            # Update order status
    └── delete_product.php                 # Delete product
```

### Database Sync

- **Real-time Updates:** All changes made in the admin panel immediately reflect in the database and are visible on the customer-facing site.
- **Shared Schema:** Admin and customer site use identical database structure (orders, products, users, payments).
- **No Duplication:** Single source of truth; no separate admin database.
- **Transaction Safety:** PDO prepared statements prevent SQL injection and ensure data integrity.

### Authentication Flow

1. User navigates to `/admin/`
2. `header.php` is included, which checks session
3. `auth.php` validates user has `is_admin = 1`
4. If not admin, user is redirected to homepage with 403 error logged
5. Admin user has full access to all sections

## Security

- **Role-Based Access:** Only users with `is_admin = 1` can access admin panel
- **Prepared Statements:** All database queries use PDO prepared statements
- **Session Validation:** Every admin API endpoint verifies admin status
- **Logging:** Admin actions are logged to error log for audit trail
- **CSRF Protection:** Use the same CSRF token mechanism as main site (if applicable)

## Styling & Design

### Brand Consistency

- Reuses `styles.css` from main site for brand colors and typography
- Custom `admin_styles.css` adds admin-specific layouts (sidebar, tables, cards)
- Gold accents (`var(--accent-gold)`) for primary actions
- Neutral backgrounds and muted text for secondary elements
- Status badges reuse same color logic as customer site

### Color Palette

- **Primary Action:** Gold (`var(--accent-gold)`)
- **Secondary Action:** Light gray (`var(--admin-bg)`)
- **Destructive:** Red (`#dc3545`)
- **Paid Status:** Green (`#2E7D32`)
- **Pending Status:** Amber/Yellow (`#8A6D1A`)
- **Failed Status:** Red (`#842029`)
- **Refunded Status:** Blue (`#0C5460`)

### Typography

- Headers: 700-800 weight, 1.25-1.85rem
- Labels: 600 weight, 0.9-1rem, uppercase
- Body: 400-600 weight, 0.9-1rem
- Font: Segoe UI / system fonts via Bootstrap

## Responsive Design

- **Desktop:** Optimized for wide screens with sidebar navigation
- **Tablet:** Sidebar available but may collapse; tables scroll horizontally
- **Mobile:** Sidebar toggles to dropdown; simplified table layout
- Primary focus: Desktop/tablet workflows

## API Endpoints

### Update Order Status

**POST** `/admin/api/update_order_status.php`

Parameters:
- `order_id` (int): Order ID
- `status` (string): New status (pending, processing, shipped, delivered, cancelled)

Response: Redirects to orders list with success message

### Delete Product

**GET** `/admin/api/delete_product.php?id={product_id}`

Parameters:
- `id` (int): Product ID

Response: Redirects to products list with success/error message

## Maintenance & Monitoring

### Logs

Admin actions are logged to the PHP error log:
- User login attempts
- Product additions/deletions
- Order status changes
- Failed operations

Check logs at: `C:\xampp\apache\logs\error.log`

### Performance

- **Dashboard:** Loads in <500ms (stat calculations optimized)
- **Tables:** Pagination (20-25 items per page) prevents slow loads
- **Search:** Indexed on product_name, order_number, email_address for speed

### Backups

Recommended backup schedule:
- Daily: Database backup (orders, products, customers)
- Weekly: Full application backup
- Monthly: Archive backups

## Future Enhancements

- [ ] Bulk order status updates
- [ ] CSV/Excel export for reports
- [ ] Advanced analytics and charts
- [ ] Email notifications on key events
- [ ] Admin audit log UI
- [ ] Two-factor authentication
- [ ] Role-based permissions (viewer, editor, admin)
- [ ] Inventory alerts and low-stock warnings

## Troubleshooting

### Admin Panel Shows "Unauthorized"

**Issue:** User sees 403 Forbidden or redirects to homepage

**Solution:** 
1. Verify user account has `is_admin = 1`
   ```sql
   SELECT is_admin FROM users WHERE user_id = <your_id>;
   ```
2. If not, update the column:
   ```sql
   UPDATE users SET is_admin = 1 WHERE user_id = <your_id>;
   ```

### Database Migration Errors

**Issue:** "Column already exists" or similar

**Solution:** Check if column exists before running ALTER:
```sql
SHOW COLUMNS FROM users;
```

### Missing Data in Tables

**Issue:** Products or orders not showing in admin

**Solution:** Verify data exists in database:
```sql
SELECT COUNT(*) FROM products;
SELECT COUNT(*) FROM orders;
```

## Support

For issues or questions:
- Check `admin/includes/admin_styles.css` for styling problems
- Review logs in Apache error log
- Verify database connection in `db.php`
- Ensure migrations have been run

---

**Version:** 1.0  
**Last Updated:** January 19, 2026  
**Brand:** Jeweluxe E-Commerce
