-- ==================================
--
-- ==================================

CREATE TABLE IF NOT EXISTS hotels (
                                      id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                                      city_id INT UNSIGNED NOT NULL,
                                      name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    description TEXT,
    stars TINYINT UNSIGNED DEFAULT 3,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_rooms SMALLINT UNSIGNED DEFAULT 0,
    email VARCHAR(255),
    phone VARCHAR(20),
    website VARCHAR(255),
    check_in_time TIME DEFAULT '14:00:00',
    check_out_time TIME DEFAULT '12:00:00',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
    INDEX idx_city (city_id),
    INDEX idx_stars (stars),
    INDEX idx_rating (rating DESC),
    INDEX idx_active (is_active),
    INDEX idx_city_rating (city_id, rating DESC),
    INDEX idx_city_stars (city_id, stars DESC),
    FULLTEXT INDEX idx_fulltext_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;