-- Admin users isolated from customer accounts
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'admin',
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed a default superadmin (change password after first login)
INSERT INTO admin_users (username, email, password_hash, role, status)
VALUES ('admin', 'admin@example.com', '$2y$12$sFld6ADCQGGCfDVtqyHP3epmJWyyuquIXCcSGBd7qmh0sxvqI3vgu', 'superadmin', 'active')
ON DUPLICATE KEY UPDATE username = username;
