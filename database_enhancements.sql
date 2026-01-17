-- Optional database enhancements for improved security
-- Run these if you want to add extra security features

-- Add indexes for better performance
CREATE INDEX idx_users_email ON users(email_address);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_cart_user ON cart(user_id);
CREATE INDEX idx_cart_items_cart ON cart_items(cart_id);
CREATE INDEX idx_addresses_user ON addresses(user_id);

-- Add created_at and updated_at columns if they don't exist
-- (Check your schema first - these might already exist)
-- ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
-- ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add last_login column for security monitoring
-- ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL;

-- Add account status column (active, suspended, deleted)
-- ALTER TABLE users ADD COLUMN status ENUM('active', 'suspended', 'deleted') DEFAULT 'active';

-- Add email verification
-- ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE;
-- ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL;

-- Add password reset functionality
-- CREATE TABLE IF NOT EXISTS password_resets (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     user_id INT NOT NULL,
--     token VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     expires_at TIMESTAMP NOT NULL,
--     used BOOLEAN DEFAULT FALSE,
--     FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
--     INDEX idx_token (token),
--     INDEX idx_expires (expires_at)
-- );

-- Add activity log table for security monitoring
-- CREATE TABLE IF NOT EXISTS activity_log (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     user_id INT NULL,
--     action VARCHAR(100) NOT NULL,
--     ip_address VARCHAR(45) NULL,
--     user_agent TEXT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
--     INDEX idx_user (user_id),
--     INDEX idx_action (action),
--     INDEX idx_created (created_at)
-- );

-- Add failed login attempts tracking
-- CREATE TABLE IF NOT EXISTS login_attempts (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     email_or_username VARCHAR(255) NOT NULL,
--     ip_address VARCHAR(45) NOT NULL,
--     attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     success BOOLEAN DEFAULT FALSE,
--     INDEX idx_email (email_or_username),
--     INDEX idx_ip (ip_address),
--     INDEX idx_attempted (attempted_at)
-- );
