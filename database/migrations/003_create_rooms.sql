-- ==================================
--
-- ==================================

CREATE TABLE IF NOT EXISTS room_types (
                                          id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                                          name VARCHAR(50) NOT NULL UNIQUE COMMENT 'Standard, Deluxe, Suite, Penthouse',
    slug VARCHAR(50) NOT NULL UNIQUE,
    max_guests TINYINT UNSIGNED NOT NULL DEFAULT 2,
    description TEXT,
    amenities JSON COMMENT 'WiFi, TV, minibar, etc',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rooms (
                                     id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                                     hotel_id INT UNSIGNED NOT NULL,
                                     room_type_id SMALLINT UNSIGNED NOT NULL,
                                     room_number VARCHAR(10) NOT NULL,
    floor TINYINT UNSIGNED,
    base_price DECIMAL(10,2) NOT NULL,
    area SMALLINT UNSIGNED COMMENT,
    beds_count TINYINT UNSIGNED DEFAULT 1,
    has_window BOOLEAN DEFAULT TRUE,
    has_balcony BOOLEAN DEFAULT FALSE,
    view_type ENUM('city', 'sea', 'mountain', 'garden', 'courtyard') DEFAULT 'city',
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id),

    UNIQUE KEY unique_room (hotel_id, room_number),
    INDEX idx_hotel (hotel_id),
    INDEX idx_hotel_type (hotel_id, room_type_id),
    INDEX idx_type (room_type_id),
    INDEX idx_available (is_available),
    INDEX idx_price (base_price),
    INDEX idx_hotel_available_price (hotel_id, is_available, base_price)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS price_modifiers (
                                               id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                                               room_id INT UNSIGNED NOT NULL,
                                               start_date DATE NOT NULL,
                                               end_date DATE NOT NULL,
                                               price_per_night DECIMAL(10,2) NOT NULL,
    modifier_type ENUM('seasonal', 'holiday', 'discount', 'premium') DEFAULT 'seasonal',
    reason VARCHAR(100) COMMENT 'Summer season, New Year, Black Friday, etc',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_room (room_id),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_room_dates (room_id, start_date, end_date),

    CONSTRAINT chk_price_dates CHECK (end_date >= start_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='';