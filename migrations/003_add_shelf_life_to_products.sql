-- ============================================================================
-- Migration: Add Shelf Life Configuration to Products
-- ============================================================================
-- Purpose: Add shelf_life_days column to products table for expiry date calculation
-- Status: Ready to execute
-- Created: 2026-03-02
-- ============================================================================

-- Add shelf_life_days column to products table
ALTER TABLE products ADD COLUMN shelf_life_days INT DEFAULT 365 NOT NULL 
COMMENT 'Number of days product remains shelf-stable after production. Used for automatic expiry date calculation.';

-- Set specific shelf life for known Lorenzana products
-- Fish Sauce / Patis (24 months = ~730 days)
UPDATE products SET shelf_life_days = 730 
WHERE product_name LIKE '%Patis%' OR product_name LIKE '%Fish Sauce%';

-- Bagoong / Shrimp Paste (24 months = ~730 days)
UPDATE products SET shelf_life_days = 730 
WHERE product_name LIKE '%Bagoong%' OR product_name LIKE '%Shrimp Paste%';

-- Soy Sauce (36 months = ~1095 days)
UPDATE products SET shelf_life_days = 1095 
WHERE product_name LIKE '%Soy Sauce%';

-- Vinegar (36 months = ~1095 days)
UPDATE products SET shelf_life_days = 1095 
WHERE product_name LIKE '%Vinegar%';

-- Crab Paste (24 months = ~730 days)
UPDATE products SET shelf_life_days = 730 
WHERE product_name LIKE '%Crab Paste%' OR product_name LIKE '%Aligue%';

-- Value Packs (24 months = ~730 days)
UPDATE products SET shelf_life_days = 730 
WHERE product_name LIKE '%Value Pack%' OR product_name LIKE '%Combo%';

-- All others default to 365 days (1 year)
-- (Already set by DEFAULT 365 in ALTER TABLE)

-- ============================================================================
-- Verification - Run these queries to verify the migration
-- ============================================================================
-- SELECT product_id, product_name, shelf_life_days FROM products ORDER BY product_name;
-- SELECT COUNT(*) as total_products, COUNT(CASE WHEN shelf_life_days > 0 THEN 1 END) as configured FROM products;
-- ============================================================================
