-- ==================================
--
-- ==================================

CREATE TABLE IF NOT EXISTS bookings (
                                        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                                        booking_number VARCHAR(20) UNIQUE NOT NULL COMMENT 'fe: BK-2026-00001',
    user_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    nights INT UNSIGNED GENERATED ALWAYS AS (DATEDIFF(check_out, check_in)) STORED,
    guests_count TINYINT UNSIGNED NOT NULL DEFAULT 1,
    adults_count TINYINT UNSIGNED NOT NULL DEFAULT 1,
    children_count TINYINT UNSIGNED DEFAULT 0,

    base_price DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    taxes DECIMAL(10,2) DEFAULT 0.00,
    total_price DECIMAL(10,2) NOT NULL,

    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'partial', 'paid', 'refunded') DEFAULT 'unpaid',

    special_requests TEXT,
    cancellation_reason TEXT,
    cancelled_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),

    INDEX idx_booking_number (booking_number),
    INDEX idx_user (user_id),
    INDEX idx_room (room_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_check_in (check_in),
    INDEX idx_check_out (check_out),
    INDEX idx_dates (check_in, check_out),
    INDEX idx_created (created_at DESC),

    INDEX idx_room_dates_status (room_id, check_in, check_out, status),
    INDEX idx_user_status (user_id, status, created_at DESC),
    INDEX idx_status_dates (status, check_in, check_out),

    CONSTRAINT chk_booking_dates CHECK (check_out > check_in),
    CONSTRAINT chk_guests CHECK (guests_count = adults_count + children_count),
    CONSTRAINT chk_prices CHECK (total_price >= 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Bookings for hotel rooms'
    PARTITION BY RANGE (YEAR(check_in)) (
                                            PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p2027 VALUES LESS THAN (2028),
    PARTITION p_future VALUES LESS THAN MAXVALUE
    );

CREATE TABLE IF NOT EXISTS booking_guests (
                                              id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                                              booking_id BIGINT UNSIGNED NOT NULL,
                                              first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    passport_number VARCHAR(50),
    is_primary BOOLEAN DEFAULT FALSE COMMENT 'Chief guest for the booking',

    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
                                        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                                        booking_id BIGINT UNSIGNED NOT NULL,
                                        amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) DEFAULT 'USD',
    payment_method ENUM('card', 'cash', 'bank_transfer', 'paypal', 'crypto') NOT NULL,
    transaction_id VARCHAR(255) UNIQUE,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_status (status),
    INDEX idx_transaction (transaction_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;