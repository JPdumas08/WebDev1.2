-- Fix missing core columns after migration
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS product_stock INT DEFAULT 0 AFTER product_price,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Helpful indexes
ALTER TABLE products
    ADD INDEX IF NOT EXISTS idx_products_created_at (created_at),
    ADD INDEX IF NOT EXISTS idx_products_updated_at (updated_at);

ALTER TABLE users
    ADD INDEX IF NOT EXISTS idx_users_created_at (created_at),
    ADD INDEX IF NOT EXISTS idx_users_updated_at (updated_at),
    ADD INDEX IF NOT EXISTS idx_users_is_admin (is_admin);

-- Ensure order_notes exists on orders
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS order_notes TEXT;

-- Ensure product_reviews table exists (idempotent)
CREATE TABLE IF NOT EXISTS product_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NULL,
    rating TINYINT NOT NULL,
    review_content TEXT NOT NULL,
    status ENUM('pending','approved','hidden','removed') DEFAULT 'pending',
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_product2 FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    CONSTRAINT fk_review_user2 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_review_order2 FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL,
    UNIQUE KEY uniq_user_product2 (user_id, product_id),
    KEY idx_product_status2 (product_id, status),
    KEY idx_status2 (status),
    KEY idx_rating2 (rating)
);
