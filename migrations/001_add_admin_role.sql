-- Admin Panel Database Migration
-- Adds is_admin column to users table for role-based access control

ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER password;

-- Set the first user as admin (optional, comment out if not needed)
-- UPDATE users SET is_admin = 1 LIMIT 1;

-- To make a specific user an admin, use:
-- UPDATE users SET is_admin = 1 WHERE user_id = 1;
