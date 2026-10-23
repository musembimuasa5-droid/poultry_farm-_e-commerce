USE golden_eggs;

SET @schema_name = DATABASE();

SET @statement = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'products' AND COLUMN_NAME = 'batch_tracking') = 0,
    'ALTER TABLE products ADD COLUMN batch_tracking TINYINT(1) NOT NULL DEFAULT 0 AFTER featured',
    'SELECT 1'
);
PREPARE storefront_migration FROM @statement;
EXECUTE storefront_migration;
DEALLOCATE PREPARE storefront_migration;

SET @statement = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'products' AND COLUMN_NAME = 'vaccinated') = 0,
    'ALTER TABLE products ADD COLUMN vaccinated TINYINT(1) NOT NULL DEFAULT 0 AFTER batch_tracking',
    'SELECT 1'
);
PREPARE storefront_migration FROM @statement;
EXECUTE storefront_migration;
DEALLOCATE PREPARE storefront_migration;

SET @statement = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'products' AND COLUMN_NAME = 'farm_fresh_daily') = 0,
    'ALTER TABLE products ADD COLUMN farm_fresh_daily TINYINT(1) NOT NULL DEFAULT 0 AFTER vaccinated',
    'SELECT 1'
);
PREPARE storefront_migration FROM @statement;
EXECUTE storefront_migration;
DEALLOCATE PREPARE storefront_migration;

SET @statement = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'products' AND COLUMN_NAME = 'quality_assured') = 0,
    'ALTER TABLE products ADD COLUMN quality_assured TINYINT(1) NOT NULL DEFAULT 0 AFTER farm_fresh_daily',
    'SELECT 1'
);
PREPARE storefront_migration FROM @statement;
EXECUTE storefront_migration;
DEALLOCATE PREPARE storefront_migration;

SET @statement = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'delivery_area') = 0,
    'ALTER TABLE orders ADD COLUMN delivery_area VARCHAR(80) NULL AFTER address',
    'SELECT 1'
);
PREPARE storefront_migration FROM @statement;
EXECUTE storefront_migration;
DEALLOCATE PREPARE storefront_migration;

CREATE TABLE IF NOT EXISTS product_batches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    batch_code VARCHAR(80) NOT NULL,
    hatch_date DATE NOT NULL,
    available_units INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY product_batch_code (product_id, batch_code),
    KEY product_batch_release (product_id, status, hatch_date),
    CONSTRAINT product_batches_product_fk
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
