-- ==================================
--
-- ==================================

CREATE TABLE IF NOT EXISTS users (
                                     id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                                     email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other') DEFAULT NULL,
    country_code CHAR(2),
    city VARCHAR(100),
    address TEXT,
    passport_number VARCHAR(50),
    loyalty_points INT UNSIGNED DEFAULT 0,
    total_bookings INT UNSIGNED DEFAULT 0,
    total_spent DECIMAL(12,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_active (is_active),
    INDEX idx_created (created_at),
    INDEX idx_loyalty (loyalty_points DESC),
    INDEX idx_total_spent (total_spent DESC),
    FULLTEXT INDEX idx_fulltext_name (first_name, last_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;