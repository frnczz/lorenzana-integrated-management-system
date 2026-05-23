-- Batch Level Inventory Tracking Implementation
-- This adds batch tracking to raw_materials and finished_goods tables
-- Enables proper FEFO (First Expired First Out) inventory management

USE lorinims_db;

-- Add batch tracking to raw_materials table
ALTER TABLE raw_materials ADD COLUMN batch_id INT NULL AFTER material_id;
ALTER TABLE raw_materials ADD COLUMN production_date DATE NULL AFTER batch_id;
ALTER TABLE raw_materials ADD COLUMN received_date DATE NULL AFTER production_date;
ALTER TABLE raw_materials ADD INDEX idx_raw_batch (batch_id);

-- Add batch tracking to finished_goods table
ALTER TABLE finished_goods ADD COLUMN batch_id INT NULL AFTER fg_id;
ALTER TABLE finished_goods ADD COLUMN production_date DATE NULL AFTER batch_id;
ALTER TABLE finished_goods ADD INDEX idx_fg_batch (batch_id);

-- Create raw material batches table for tracking batch metadata
CREATE TABLE IF NOT EXISTS raw_material_batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(50) UNIQUE NOT NULL,
    material_id INT NOT NULL,
    supplier_id INT NULL,
    quantity_received DECIMAL(10,2) NOT NULL,
    quantity_remaining DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) DEFAULT 'kg',
    production_date DATE NULL,
    expiry_date DATE NULL,
    received_date DATE NOT NULL,
    warehouse_location VARCHAR(100),
    qc_approved TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_rmb_material (material_id),
    INDEX idx_rmb_expiry (expiry_date)
) ENGINE=InnoDB;

-- Create finished goods batches table for tracking batch metadata
CREATE TABLE IF NOT EXISTS finished_goods_batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(50) UNIQUE NOT NULL,
    product_id INT NOT NULL,
    production_batch_id INT NULL, -- Links to production_batches table
    quantity_produced DECIMAL(10,2) NOT NULL,
    quantity_remaining DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) DEFAULT 'pcs',
    production_date DATE NOT NULL,
    expiry_date DATE NULL,
    warehouse_location VARCHAR(100),
    qc_approved TINYINT(1) NOT NULL DEFAULT 0,
    qc_record_id INT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (production_batch_id) REFERENCES production_batches(batch_id),
    FOREIGN KEY (qc_record_id) REFERENCES qc_records(qc_id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_fgb_product (product_id),
    INDEX idx_fgb_expiry (expiry_date)
) ENGINE=InnoDB;

-- Update existing raw_materials to have batch tracking
-- For existing data, create default batches
INSERT INTO raw_material_batches (batch_number, material_id, quantity_received, quantity_remaining, unit, expiry_date, received_date, warehouse_location, qc_approved, created_by)
SELECT
    CONCAT('RM-', material_id, '-', DATE_FORMAT(CURDATE(), '%Y%m%d')) as batch_number,
    material_id,
    quantity,
    quantity,
    unit,
    expiry_date,
    CURDATE(),
    warehouse_location,
    1, -- Assume existing materials are approved
    1 -- Default admin user
FROM raw_materials
WHERE quantity > 0;

-- Update raw_materials table to link to batches
UPDATE raw_materials rm
INNER JOIN raw_material_batches rmb ON rmb.material_id = rm.material_id
SET rm.batch_id = rmb.batch_id
WHERE rm.quantity > 0;

-- Update existing finished_goods to have batch tracking
-- For existing data, create default batches
INSERT INTO finished_goods_batches (batch_number, product_id, quantity_produced, quantity_remaining, production_date, expiry_date, warehouse_location, qc_approved, created_by)
SELECT
    CONCAT('FG-', fg_id, '-', DATE_FORMAT(CURDATE(), '%Y%m%d')) as batch_number,
    product_id,
    quantity,
    quantity,
    CURDATE(),
    expiry_date,
    warehouse_location,
    qc_approved,
    1 -- Default admin user
FROM finished_goods
WHERE quantity > 0;

-- Update finished_goods table to link to batches
UPDATE finished_goods fg
INNER JOIN finished_goods_batches fgb ON fgb.product_id = fg.product_id AND fgb.quantity_produced = fg.quantity
SET fg.batch_id = fgb.batch_id,
    fg.production_date = fgb.production_date
WHERE fg.quantity > 0;

-- Add settings for batch tracking
INSERT IGNORE INTO warehouse_settings (setting_key, setting_value) VALUES
('enable_batch_tracking', '1'),
('default_batch_number_prefix', 'BATCH-'),
('auto_generate_batch_numbers', '1');</content>
<parameter name="filePath">c:\xampp\htdocs\lorinims\database_batch_tracking.sql