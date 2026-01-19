# Contact Us & Notification System Documentation

## Overview
Complete two-way communication system between customers and admins with real-time notifications for order updates.

## System Components

### 1. Database Tables

#### contact_messages
Stores customer inquiries submitted through the Contact Us page.

```sql
CREATE TABLE contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new','read','replied','closed') DEFAULT 'new',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);
```

**Status Workflow:**
- `new` → Initial state when message submitted
- `read` → Admin has viewed the message
- `replied` → Admin has sent a reply
- `closed` → Conversation is closed

#### message_replies
Stores admin responses to customer messages.

```sql
CREATE TABLE message_replies (
    reply_id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    admin_id INT NOT NULL,
    reply_text TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (message_id) REFERENCES contact_messages(message_id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_message_id (message_id),
    INDEX idx_admin_id (admin_id)
);
```

#### notifications
User notifications for order updates and message replies.

```sql
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('order_status','order_update','message_reply','system','promotion') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id INT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created (created_at)
);
```

**Notification Types:**
- `order_status` → Order status changed (pending, processing, shipped, delivered, cancelled)
- `order_update` → Order items modified by admin
- `message_reply` → Admin replied to customer's message
- `system` → System announcements
- `promotion` → Promotional notifications

#### admin_notifications
Admin notifications for new messages, orders, and system alerts.

```sql
CREATE TABLE admin_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('new_message','new_order','low_stock','system') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id INT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created (created_at)
);
```

**Admin Notification Types:**
- `new_message` → New contact message received
- `new_order` → New order placed
- `low_stock` → Product stock below threshold
- `system` → System alerts

---

## 2. Customer-Facing Components

### Contact Us Page
**File:** `contactus.php`

**Features:**
- Elegant hero section with jewelry theme
- Contact form with subject categorization
- Pre-filled fields for logged-in users
- Contact information sidebar (email, business hours)
- Form validation (minimum 10 characters for message)
- Success/error message display

**Form Fields:**
- Full Name (required, min 2 characters)
- Email (required, valid email format)
- Subject (dropdown: Order Inquiry, Product Question, Payment Issue, etc.)
- Message (required, min 10 characters)

**Subject Options:**
1. Order Inquiry
2. Product Question
3. Payment Issue
4. Shipping/Delivery
5. Return/Refund
6. Technical Support
7. Feedback
8. Other

### Form Processing
**File:** `process_contact.php`

**Process Flow:**
1. Validate all form inputs
2. Insert message into `contact_messages` table
3. Create admin notification in `admin_notifications` table
4. Redirect with success message

**Validation Rules:**
- Name: minimum 2 characters
- Email: valid email format
- Subject: must be selected
- Message: minimum 10 characters

### User Notifications Page
**File:** `user_notifications.php`

**Features:**
- List all user notifications with pagination
- Visual indicators for unread notifications
- Icon and color coding by notification type
- Mark individual notification as read
- Mark all notifications as read
- Direct links to related orders
- Responsive design

**Notification Display:**
- **Unread:** Light blue background, bold text, "New" badge
- **Read:** Normal background, regular text
- **Icons:** Different icon per type (order, message, system)
- **Timestamps:** Formatted date/time display

### Notification Bell (Navbar)
**File:** `includes/navbar.php`

**Features:**
- Bell icon with unread count badge
- Shows count up to 99+ for large numbers
- Only visible for logged-in users
- Direct link to user_notifications.php

---

## 3. Admin Panel Components

### Messages Management
**File:** `admin/messages.php`

**Features:**
- Status filter cards (New, Read, Replied, Closed)
- Messages table with preview
- Sender information (name, email, username if registered)
- Message preview (first 100 characters)
- Pagination (20 messages per page)
- Quick access to message details

**Status Cards:**
- New Messages (blue)
- Read (cyan)
- Replied (green)
- Closed (gray)

### Message Detail & Reply
**File:** `admin/message_detail.php`

**Features:**
- Full message display with sender details
- Reply history chronologically ordered
- Reply form with character validation
- Status management (close/reopen)
- Auto-mark as read on first view

**Reply System:**
- Reply form with minimum 10 characters
- JavaScript submission with loading state
- Creates notification for user
- Updates message status to "replied"
- Displays admin username in reply

**Status Actions:**
- Close Message → Locks conversation
- Reopen Message → Allows new replies

### Admin Notification Center
**File:** `admin/admin_notifications.php`

**Features:**
- List all admin notifications
- Filter by type (new message, new order, low stock, system)
- Unread count with mark all as read button
- Direct links to related items (messages, orders)
- Pagination (30 notifications per page)

**Notification Types Display:**
- New Message → Blue envelope icon
- New Order → Green cart icon
- Low Stock → Yellow warning icon
- System → Cyan gear icon

### Admin Notification Bell (Header)
**File:** `admin/includes/header.php`

**Features:**
- Dropdown notification panel
- Unread count badge
- Recent 10 notifications preview
- Mark all as read option
- Link to full notification center
- Bootstrap dropdown integration

---

## 4. API Endpoints

### Reply to Message
**Endpoint:** `admin/api/reply_message.php`
**Method:** POST
**Auth:** Admin only

**Parameters:**
- `message_id` (int, required)
- `reply_text` (string, required, min 10 chars)

**Process:**
1. Validate message exists and not closed
2. Insert reply into `message_replies`
3. Update message status to 'replied'
4. Create user notification (if user has account)
5. Return JSON success/error

**Response:**
```json
{
  "success": true,
  "message": "Reply sent successfully",
  "message_id": 123
}
```

### Update Message Status
**Endpoint:** `admin/api/update_message_status.php`
**Method:** POST
**Auth:** Admin only

**Parameters:**
- `message_id` (int, required)
- `status` (enum, required: new|read|replied|closed)

**Response:**
```json
{
  "success": true,
  "message": "Status updated successfully",
  "message_id": 123,
  "new_status": "closed"
}
```

### Update Order Status (Enhanced)
**Endpoint:** `admin/api/update_order_status.php`
**Method:** POST
**Auth:** Admin only

**Enhanced Features:**
- Fetches user_id from order
- Creates customer notification on status change
- Customized messages per status

**Status Messages:**
- `pending` → "Your order #X is now pending."
- `processing` → "Your order #X is being processed."
- `shipped` → "Great news! Your order #X has been shipped!"
- `delivered` → "Your order #X has been delivered."
- `cancelled` → "Your order #X has been cancelled."

### Mark User Notification Read
**Endpoint:** `api_mark_notification_read.php`
**Method:** POST
**Auth:** User only

**Parameters:**
- `notification_id` (int) → Mark single notification
- `mark_all` (string, "1") → Mark all user notifications

**Response:**
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

### Mark Admin Notifications Read
**Endpoint:** `admin/api/mark_admin_notifications_read.php`
**Method:** POST
**Auth:** Admin only

**Process:**
Marks all unread admin notifications as read.

**Response:**
```json
{
  "success": true,
  "message": "All notifications marked as read",
  "count": 15
}
```

---

## 5. Notification Triggers

### Order Status Change
**Trigger Location:** `admin/api/update_order_status.php`

**When Triggered:**
- Admin changes order status via dropdown
- Status actually changes (not same status)
- Order has valid user_id

**Notification Created:**
```sql
INSERT INTO notifications 
(user_id, type, title, message, related_id, is_read, created_at) 
VALUES 
(:user_id, 'order_status', 'Order Status Updated', :custom_message, :order_id, 0, NOW())
```

### Contact Message Submitted
**Trigger Location:** `process_contact.php`

**When Triggered:**
- Customer submits contact form
- Form validation passes

**Admin Notification Created:**
```sql
INSERT INTO admin_notifications 
(type, title, message, related_id, is_read, created_at) 
VALUES 
('new_message', 'New Contact Message', 'New message from [Name] - Subject: [Subject]', :message_id, 0, NOW())
```

### Admin Reply Sent
**Trigger Location:** `admin/api/reply_message.php`

**When Triggered:**
- Admin submits reply to message
- Message has associated user_id
- Message not closed

**User Notification Created:**
```sql
INSERT INTO notifications 
(user_id, type, title, message, related_id, is_read, created_at) 
VALUES 
(:user_id, 'message_reply', 'Reply to Your Message', 'An admin has replied to your message: "[Subject]"', :message_id, 0, NOW())
```

---

## 6. User Interface Integration

### Customer Navbar
- Notification bell appears next to cart for logged-in users
- Badge shows unread count (1-99+)
- Clicking bell navigates to `user_notifications.php`
- Real-time count updated on page load

### Admin Header
- Notification bell in header with dropdown
- Shows recent 10 notifications
- Badge displays unread count
- Quick access to mark all as read
- Link to full notification center

### Admin Sidebar
- "Messages" menu item added between Products and Customers
- Active state highlighting on messages.php and message_detail.php
- Envelope icon for easy identification

---

## 7. Testing Checklist

### Contact Form Testing
- [ ] Submit form as guest user
- [ ] Submit form as logged-in user
- [ ] Verify all validation rules
- [ ] Check admin notification created
- [ ] Verify message appears in admin panel
- [ ] Test subject dropdown options
- [ ] Test error message display

### Reply System Testing
- [ ] View message as admin
- [ ] Verify auto-mark as read
- [ ] Submit reply with <10 characters (should fail)
- [ ] Submit valid reply
- [ ] Verify user notification created (if user has account)
- [ ] Check message status updated to 'replied'
- [ ] Test close/reopen message

### Notification System Testing
- [ ] Create order and change status
- [ ] Verify customer receives notification
- [ ] Check notification bell badge count
- [ ] Test mark as read (single)
- [ ] Test mark all as read
- [ ] Verify read notifications styled differently
- [ ] Test pagination on notifications page

### Admin Notification Testing
- [ ] Submit contact form
- [ ] Verify admin notification created
- [ ] Check notification dropdown in header
- [ ] Test mark all as read
- [ ] Verify notification links to message detail
- [ ] Check full notification center page

---

## 8. Security Features

### Authentication
- Contact form accessible to all (guests and users)
- User notifications require login
- Admin endpoints check `is_admin` flag
- Message replies require admin authentication

### Authorization
- Users can only view their own notifications
- Users can only mark their own notifications as read
- Admins cannot access user notification endpoints
- Message ownership validated before actions

### Input Validation
- All form inputs sanitized
- Email validation using PHP filter
- Minimum length requirements enforced
- HTML special characters escaped in output
- SQL injection prevented with prepared statements

### XSS Protection
- `htmlspecialchars()` used for all user input display
- JavaScript variables properly escaped
- No direct HTML output from user data

---

## 9. Future Enhancements

### Email Notifications (Optional)
- Send email when admin replies to message
- Email template with reply content
- Link to view full conversation
- Configurable email settings

### Real-Time Updates
- WebSocket integration for live notifications
- Auto-refresh notification counts
- Push notifications for mobile

### Advanced Features
- Message attachments (images, files)
- Internal notes on messages (admin only)
- Message categories/tagging
- Auto-responses for common questions
- Priority levels for messages
- Notification preferences per user
- Bulk actions on messages

### Analytics
- Response time tracking
- Message volume statistics
- Common inquiry topics
- Admin performance metrics

---

## 10. File Summary

### Customer Files
- `contactus.php` - Contact form page
- `process_contact.php` - Form submission handler
- `user_notifications.php` - User notification center
- `api_mark_notification_read.php` - Mark notification read API
- `includes/navbar.php` - Added notification bell

### Admin Files
- `admin/messages.php` - Message management dashboard
- `admin/message_detail.php` - Message detail and reply interface
- `admin/admin_notifications.php` - Admin notification center
- `admin/api/reply_message.php` - Reply submission API
- `admin/api/update_message_status.php` - Message status API
- `admin/api/update_order_status.php` - Enhanced with notifications
- `admin/api/mark_admin_notifications_read.php` - Mark admin notifs read
- `admin/includes/header.php` - Added notification dropdown
- `admin/includes/sidebar.php` - Added Messages menu item

### Database Files
- `migrations/004_contact_notification_system.sql` - Database schema

---

## Support
For issues or questions about this system, refer to the code comments in each file or contact the development team.

**Last Updated:** <?php echo date('F j, Y'); ?>
