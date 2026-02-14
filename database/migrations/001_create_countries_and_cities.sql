-- ==================================
--
-- ==================================

CREATE TABLE IF NOT EXISTS countries (
                                         id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                                         code CHAR(2) NOT NULL UNIQUE COMMENT 'ISO код: UA, US, FR',
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_code (code),
    INDEX idx_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Countries with ISO codes';

CREATE TABLE IF NOT EXISTS cities (
                                      id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                                      country_id SMALLINT UNSIGNED NOT NULL,
                                      name VARCHAR(100) NOT NULL,
    population INT UNSIGNED DEFAULT 0 COMMENT 'population',
    is_popular BOOLEAN DEFAULT FALSE COMMENT 'popular city',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
    INDEX idx_country (country_id),
    INDEX idx_name (name),
    INDEX idx_popular (is_popular),
    INDEX idx_country_popular (country_id, is_popular)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;