-- SQL script to create woo_categories table
CREATE TABLE IF NOT EXISTS woo_categories (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    woocommerce_id INT DEFAULT NULL,
    owner_id INT NOT NULL,
    store_url VARCHAR(255) DEFAULT NULL,
    original_data JSON DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    slug VARCHAR(255) DEFAULT NULL,
    count INT DEFAULT NULL,
    PRIMARY KEY(id),
    INDEX idx_owner_store (owner_id, store_url)
);