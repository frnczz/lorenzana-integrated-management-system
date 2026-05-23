-- Product category to raw materials mapping (Lorins product formulations)
-- Run this after raw_materials and product_categories exist.

-- Table: which raw materials are used for which product category
CREATE TABLE IF NOT EXISTS product_category_materials (
    category_id INT NOT NULL,
    material_id INT NOT NULL,
    PRIMARY KEY (category_id, material_id),
    FOREIGN KEY (category_id) REFERENCES product_categories(category_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE
);

-- Ensure common raw materials exist (by name); use existing IDs where present
-- category_id: 1=Patis, 2=Soy Sauce, 3=Vinegar, 4=Alamang, 5=Bagoong, 6=Specialty, 9=Nata/Kaong

-- Map existing materials (Soybeans=1, Salt=2, Sugar=3) to categories
INSERT IGNORE INTO product_category_materials (category_id, material_id) VALUES
(1, 2),   -- Patis: Salt
(2, 1), (2, 2), (2, 3),   -- Soy Sauce: Soybeans, Salt, Sugar
(3, 2),   -- Vinegar: Salt (placeholder; add Water, Cane vinegar etc. in raw_materials then link here)
(4, 2), (4, 3),   -- Alamang: Salt, Sugar
(5, 2),   -- Bagoong: Salt
(6, 2),   -- Specialty/Crab: Salt
(9, 3);   -- Nata/Kaong: Sugar

-- Add request_id to production_batches (link batch to production request)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_batches' AND COLUMN_NAME = 'request_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE production_batches ADD COLUMN request_id INT NULL AFTER created_by, ADD KEY (request_id)', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add request_group_id to production_requests (group lines from same customer order)
SET @col2_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_requests' AND COLUMN_NAME = 'request_group_id');
SET @sql2 = IF(@col2_exists = 0, 
    'ALTER TABLE production_requests ADD COLUMN request_group_id VARCHAR(50) NULL AFTER due_date, ADD KEY (request_group_id)', 
    'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
