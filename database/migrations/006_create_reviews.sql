-- ==================================
--
-- ==================================

CREATE TABLE IF NOT EXISTS reviews (
                                       id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                                       hotel_id INT UNSIGNED NOT NULL,
                                       user_id INT UNSIGNED NOT NULL,
                                       booking_id BIGINT UNSIGNED NOT NULL,

                                       overall_rating TINYINT UNSIGNED NOT NULL COMMENT '1-5',
                                       cleanliness_rating TINYINT UNSIGNED COMMENT '1-5',
                                       staff_rating TINYINT UNSIGNED COMMENT '1-5',
                                       location_rating TINYINT UNSIGNED COMMENT '1-5',
                                       value_rating TINYINT UNSIGNED COMMENT '1-5',
                                       comfort_rating TINYINT UNSIGNED COMMENT '1-5',

                                       title VARCHAR(255),
    comment TEXT,
    pros TEXT,
    cons TEXT,

    is_verified BOOLEAN DEFAULT FALSE,
    is_visible BOOLEAN DEFAULT TRUE,
    helpful_count INT UNSIGNED DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id),

    UNIQUE KEY unique_booking_review (booking_id),

    INDEX idx_hotel (hotel_id),
    INDEX idx_user (user_id),
    INDEX idx_hotel_rating (hotel_id, overall_rating DESC),
    INDEX idx_created (created_at DESC),
    INDEX idx_visible (is_visible),
    INDEX idx_verified (is_verified),
    FULLTEXT INDEX idx_fulltext_comment (title, comment),

    CONSTRAINT chk_overall_rating CHECK (overall_rating BETWEEN 1 AND 5),
    CONSTRAINT chk_cleanliness_rating CHECK (cleanliness_rating IS NULL OR cleanliness_rating BETWEEN 1 AND 5),
    CONSTRAINT chk_staff_rating CHECK (staff_rating IS NULL OR staff_rating BETWEEN 1 AND 5),
    CONSTRAINT chk_location_rating CHECK (location_rating IS NULL OR location_rating BETWEEN 1 AND 5),
    CONSTRAINT chk_value_rating CHECK (value_rating IS NULL OR value_rating BETWEEN 1 AND 5),
    CONSTRAINT chk_comfort_rating CHECK (comfort_rating IS NULL OR comfort_rating BETWEEN 1 AND 5)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS review_responses (
                                                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                                                review_id BIGINT UNSIGNED NOT NULL,
                                                hotel_id INT UNSIGNED NOT NULL,
                                                response_text TEXT NOT NULL,
                                                responder_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,

    INDEX idx_review (review_id),
    INDEX idx_hotel (hotel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;