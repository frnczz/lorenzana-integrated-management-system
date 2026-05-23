-- ============================================================================
-- Expiry Date System - Database Migration Script
-- ============================================================================
-- This script adds the necessary columns for automatic expiry date calculation
-- 
-- IMPORTANT: Run this script before using the expiry date system
-- Backup your database first!
-- ============================================================================

-- Step 1: Add shelf_life_days to products table
-- ============================================================================
ALTER TABLE products 
ADD COLUMN shelf_life_days INT NOT NULL DEFAULT 365 COMMENT 'Days product remains shelf-stable after production' 
AFTER product_name;

-- Step 2: Add expiry_date to production_batches table
-- ============================================================================
ALTER TABLE production_batches 
ADD COLUMN expiry_date DATE COMMENT 'Computed as: production_date + product.shelf_life_days' 
AFTER batch_date;

-- Step 3: Create index on expiry_date for better query performance
-- ============================================================================
CREATE INDEX idx_production_batches_expiry_date ON production_batches(expiry_date);

-- Step 4: Set default shelf life for existing products
-- ============================================================================
-- These are example values based on typical shelf life for condiments
-- Update these values based on actual product specifications

UPDATE products SET shelf_life_days = 365 WHERE product_name LIKE '%Patis%' OR product_name LIKE '%Fish Sauce%';
UPDATE products SET shelf_life_days = 180 WHERE product_name LIKE '%Bagoong%';
UPDATE products SET shelf_life_days = 90 WHERE product_name LIKE '%Crab%';
UPDATE products SET shelf_life_days = 365 WHERE product_name LIKE '%Soy%';
UPDATE products SET shelf_life_days = 730 WHERE product_name LIKE '%Vinegar%';
UPDATE products SET shelf_life_days = 365 WHERE product_name LIKE '%Coconut%';
UPDATE products SET shelf_life_days = 365 WHERE product_name LIKE '%Sauce%';

-- Step 5: Optional - Add shelf_life_days to raw_materials table
-- ============================================================================
ALTER TABLE raw_materials 
ADD COLUMN shelf_life_days INT DEFAULT 365 COMMENT 'Days raw material remains usable' 
AFTER unit;

-- Step 6: Verify changes
-- ============================================================================
-- Run these queries to verify the migration was successful:

/*
SELECT * FROM products 
WHERE shelf_life_days > 0 
LIMIT 5;

SELECT batch_number, batch_date, expiry_date, product_id 
FROM production_batches 
LIMIT 5;

SHOW COLUMNS FROM products WHERE Field = 'shelf_life_days';
SHOW COLUMNS FROM production_batches WHERE Field = 'expiry_date';
*/

-- ============================================================================
-- Migration Complete
-- ============================================================================
-- The expiry date system is now ready to use
-- 
-- Next steps:
-- 1. Include includes/expiry_service.php in your PHP files
-- 2. Update production_record.php with the new form
-- 3. Test with calculate_expiry_date.php endpoint
-- 4. Update save_production_batch.php to compute expiry_date on insert
-- ============================================================================
