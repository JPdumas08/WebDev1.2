-- Additional Product Columns for Admin Management
-- Adds missing fields to products table

ALTER TABLE products ADD COLUMN product_description TEXT AFTER product_name;
ALTER TABLE products ADD COLUMN product_stock INT DEFAULT 0 AFTER product_price;
ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE products ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add missing order_notes column
ALTER TABLE orders ADD COLUMN order_notes TEXT AFTER order_status;

-- Add missing username column if not exists (for customer reference)
-- This is already in the schema, no action needed
