-- Verification Script for Expiry System v2
-- Run these queries to verify the migration was successful
-- Comment out as needed or run all at once

-- ============================================================================
-- PART 1: Verify Table Structure
-- ============================================================================

-- Check if production_settings table exists and has correct structure
SELECT 'production_settings structure:' AS verification;
DESC production_settings;

-- Verify column types and defaults
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'production_settings' AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;

-- Check if production_batches.expiry_date is NOT NULL
SELECT 'production_batches.expiry_date constraint:' AS verification;
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'production_batches' 
  AND COLUMN_NAME = 'expiry_date' 
  AND TABLE_SCHEMA = DATABASE();

-- ============================================================================
-- PART 2: Verify Product Shelf Life Settings
-- ============================================================================

-- View all products with their shelf life settings
SELECT 'All products with shelf life configuration:' AS verification;
SELECT 
    p.product_id,
    p.product_name,
    ps.expiry_value,
    ps.expiry_unit,
    ps.description,
    CASE 
        WHEN ps.product_id IS NOT NULL THEN 'production_settings'
        ELSE 'no_setting'
    END AS source
FROM products p
LEFT JOIN production_settings ps ON p.product_id = ps.product_id
ORDER BY p.product_name;

-- Count products with shelf life configured
SELECT 'Count of configured products:' AS verification;
SELECT 
    COUNT(*) AS total_products,
    COUNT(DISTINCT ps.product_id) AS products_with_settings
FROM products p
LEFT JOIN production_settings ps ON p.product_id = ps.product_id;

-- ============================================================================
-- PART 3: Verify Lorenzana Product Defaults
-- ============================================================================

-- Check Fish Sauce products (should have 24 months)
SELECT 'Fish Sauce products (should be 24 months):' AS verification;
SELECT 
    p.product_id,
    p.product_name,
    ps.expiry_value,
    ps.expiry_unit
FROM products p
LEFT JOIN production_settings ps ON p.product_id = ps.product_id
WHERE p.product_name LIKE '%Patis%' OR p.product_name LIKE '%Fish Sauce%'
ORDER BY p.product_name;

-- Check Soy Sauce (should have 36 months)
SELECT 'Soy Sauce products (should be 36 months):' AS verification;
SELECT 
    p.product_id,
    p.product_name,
    ps.expiry_value,
    ps.expiry_unit
FROM products p
LEFT JOIN production_settings ps ON p.product_id = ps.product_id
WHERE p.product_name LIKE '%Soy Sauce%'
ORDER BY p.product_name;

-- Check Vinegar (should have 36 months)
SELECT 'Vinegar products (should be 36 months):' AS verification;
SELECT 
    p.product_id,
    p.product_name,
    ps.expiry_value,
    ps.expiry_unit
FROM products p
LEFT JOIN production_settings ps ON p.product_id = ps.product_id
WHERE p.product_name LIKE '%Vinegar%'
ORDER BY p.product_name;

-- Check Bagoong (should have 24 months)
SELECT 'Bagoong products (should be 24 months):' AS verification;
SELECT 
    p.product_id,
    p.product_name,
    ps.expiry_value,
    ps.expiry_unit
FROM products p
LEFT JOIN production_settings ps ON p.product_id = ps.product_id
WHERE p.product_name LIKE '%Bagoong%' OR p.product_name LIKE '%Fermented%'
ORDER BY p.product_name;

-- Check Value Packs (should have 24 months)
SELECT 'Value Pack products (should be 24 months):' AS verification;
SELECT 
    p.product_id,
    p.product_name,
    ps.expiry_value,
    ps.expiry_unit
FROM products p
LEFT JOIN production_settings ps ON p.product_id = ps.product_id
WHERE p.product_name LIKE '%Value Pack%' OR p.product_name LIKE '%Combo%'
ORDER BY p.product_name;

-- ============================================================================
-- PART 4: Verify Data Integrity
-- ============================================================================

-- Check for any NULL or invalid expiry_date values in production_batches
SELECT 'Any NULL or zero dates in production_batches:' AS verification;
SELECT 
    COUNT(*) AS invalid_count,
    GROUP_CONCAT(batch_id) AS batch_ids
FROM production_batches
WHERE expiry_date IS NULL 
   OR expiry_date = '0000-00-00'
   OR expiry_date = '';

-- Check for invalid expiry_date (before production_date)
SELECT 'Any expiry_date before production_date:' AS verification;
SELECT 
    batch_id,
    batch_number,
    batch_date,
    expiry_date,
    DATEDIFF(expiry_date, batch_date) AS days_until_expiry
FROM production_batches
WHERE expiry_date < batch_date
ORDER BY batch_id;

-- Check expiry dates validity
SELECT 'Sample production batches with calculated expiry:' AS verification;
SELECT 
    pb.batch_id,
    pb.batch_number,
    p.product_name,
    pb.batch_date,
    pb.expiry_date,
    DATEDIFF(pb.expiry_date, pb.batch_date) AS shelf_life_days,
    ps.expiry_value,
    ps.expiry_unit,
    ROUND(DATEDIFF(pb.expiry_date, pb.batch_date) / 30) AS approx_months
FROM production_batches pb
JOIN products p ON pb.product_id = p.product_id
LEFT JOIN production_settings ps ON p.product_id = ps.product_id
ORDER BY pb.batch_id DESC
LIMIT 20;

-- ============================================================================
-- PART 5: Test Manual Calculation
-- ============================================================================

-- Example: Fish Sauce produced on 2026-03-01, expiry should be 2028-03-01 (24 months)
SELECT 'Manual calculation example (Fish Sauce - 24 months):' AS verification;
SELECT 
    '2026-03-01' AS production_date,
    DATE_ADD('2026-03-01', INTERVAL 24 MONTH) AS calculated_expiry,
    '2028-03-01' AS expected_expiry,
    IF(DATE_ADD('2026-03-01', INTERVAL 24 MONTH) = '2028-03-01', 'CORRECT', 'ERROR') AS result;

-- Example: Soy Sauce produced on 2026-06-15, expiry should be 2029-06-15 (36 months)
SELECT 'Manual calculation example (Soy Sauce - 36 months):' AS verification;
SELECT 
    '2026-06-15' AS production_date,
    DATE_ADD('2026-06-15', INTERVAL 36 MONTH) AS calculated_expiry,
    '2029-06-15' AS expected_expiry,
    IF(DATE_ADD('2026-06-15', INTERVAL 36 MONTH) = '2029-06-15', 'CORRECT', 'ERROR') AS result;

-- ============================================================================
-- PART 6: Summary Report
-- ============================================================================

SELECT 'MIGRATION VERIFICATION SUMMARY' AS report;

-- Check existence of each key component
SELECT 
    'production_settings table' AS component,
    IF(COUNT(*) > 0, 'EXISTS', 'MISSING') AS status,
    COUNT(*) AS record_count
FROM production_settings
UNION ALL
SELECT 
    'production_batches.expiry_date NOT NULL' AS component,
    'CHECK CONSTRAINT' AS status,
    COUNT(*) AS record_count
FROM production_batches
WHERE expiry_date IS NOT NULL
UNION ALL
SELECT 
    'Products with shelf life configured' AS component,
    CONCAT(COUNT(*), ' products') AS status,
    COUNT(*) AS record_count
FROM production_settings
UNION ALL
SELECT 
    'Invalid expiry_date values' AS component,
    IF(COUNT(*) = 0, 'NONE (GOOD)', CONCAT(COUNT(*), ' FOUND')) AS status,
    COUNT(*) AS record_count
FROM production_batches
WHERE expiry_date IS NULL OR expiry_date = '0000-00-00';

-- ============================================================================
-- PART 7: Performance Check
-- ============================================================================

-- Check indexes are in place
SELECT 'Indexes on production_settings:' AS verification;
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_NAME = 'production_settings' 
  AND TABLE_SCHEMA = DATABASE()
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- ============================================================================
-- All verification complete
-- ============================================================================
