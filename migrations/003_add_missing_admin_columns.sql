-- Migration 003: Add missing columns for admin dashboard functionality
-- This migration ensures the database has all columns required by both the admin and customer-facing sites

-- ===== PRODUCTS TABLE ENHANCEMENTS =====
-- Add missing columns to products table if they don't exist
ALTER TABLE products ADD COLUMN IF NOT EXISTS product_description TEXT AFTER product_name;
ALTER TABLE products ADD COLUMN IF NOT EXISTS product_stock INT DEFAULT 0 AFTER product_price;
ALTER TABLE products ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE products ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- ===== USERS TABLE ENHANCEMENTS =====
-- Add created_at timestamp to users table for customer registration date tracking
ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- ===== ADMIN ROLE SUPPORT =====
-- Add is_admin flag to users table if it doesn't exist (for admin authentication)
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) DEFAULT 0 AFTER password;

-- ===== INDEX IMPROVEMENTS =====
-- Add indexes for better query performance
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_category (category);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_created_at (created_at);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_created_at (created_at);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_email (email_address);
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_user_id (user_id);
ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_created_at (created_at);
