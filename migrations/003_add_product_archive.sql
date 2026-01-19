-- Add is_archived column to products table
ALTER TABLE products 
ADD COLUMN is_archived TINYINT(1) DEFAULT 0 AFTER product_stock,
ADD INDEX idx_is_archived (is_archived);

-- Add archived_at timestamp
ALTER TABLE products 
ADD COLUMN archived_at DATETIME NULL AFTER is_archived;
