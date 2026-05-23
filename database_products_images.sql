

USE lorinims_db;

-- Add image_path column to products if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'image_path');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL DEFAULT NULL AFTER description', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing products with image filenames
UPDATE products SET image_path = 'patis-POUCH-150ML.webp' WHERE (product_name LIKE '%Patis Flavor 150%pouch%' OR product_name LIKE '%150 mL pouch%') AND category_id = 1;
UPDATE products SET image_path = 'PATIS-350ML-PETbottle.webp' WHERE product_name LIKE '%Patis Flavor 350%PET%' AND product_name NOT LIKE '%Chili%';
UPDATE products SET image_path = 'CHILI-PATIS-350ML-PETbottle.webp' WHERE product_name LIKE '%Chili%350%' OR product_name LIKE '%with Chili%';
UPDATE products SET image_path = 'PATIS-1LITER.webp' WHERE product_name LIKE '%Patis Flavor 1 L%' OR (product_name LIKE '%1 Liter%' AND product_name LIKE '%Patis%');
UPDATE products SET image_path = 'PATIS-HALF-GALLON.webp' WHERE product_name LIKE '%1893%' OR product_name LIKE '%Half Gallon%';
UPDATE products SET image_path = 'PATIS-1GALLON.webp' WHERE product_name LIKE '%3785%' AND product_name LIKE '%Patis%';
UPDATE products SET image_path = 'SOY-SAUCE-350ML.webp' WHERE product_name LIKE '%Soy Sauce 350%';
UPDATE products SET image_path = 'SOY-SAUCE-1LITER.webp' WHERE product_name LIKE '%Soy Sauce 1 L%';
UPDATE products SET image_path = 'SOY-SAUCE-1GALLON.webp' WHERE product_name LIKE '%Soy Sauce%3785%' OR (product_name LIKE '%Soy Sauce%' AND product_name LIKE '%Gallon%');
UPDATE products SET image_path = 'COCO-SUKA-150ML.webp' WHERE product_name LIKE '%Coco Suka 150%';
UPDATE products SET image_path = 'COCO-SUKA-310ML.webp' WHERE product_name LIKE '%Coco Suka 310%' AND product_name NOT LIKE '%Spicy%';
UPDATE products SET image_path = 'COCO-SUKA-800ML.webp' WHERE product_name LIKE '%Coco Suka 800%';
UPDATE products SET image_path = 'BUDGET-PACK(vinegar, fishsauce-and-soysauce).webp' WHERE product_name LIKE '%Budget%' OR product_name LIKE '%Value Pack%';
UPDATE products SET image_path = 'ALAMANG-GUISADO-ORIGINAL-8oz.webp' WHERE product_name LIKE '%Alamang%Original%' OR (product_name LIKE '%Alamang Guisado%' AND product_name NOT LIKE '%Sweet%' AND product_name NOT LIKE '%Spicy%');
UPDATE products SET image_path = 'ALAMANG-GUISADO-SWEET-8oz.webp' WHERE product_name LIKE '%Alamang%Sweet%';
UPDATE products SET image_path = 'ALAMANG-GUISADO-SPICY-8oz.webp' WHERE product_name LIKE '%Alamang%Spicy%';
UPDATE products SET image_path = 'BAGOONG-ISDA-original-310ML.webp' WHERE product_name LIKE '%Bagoong Isda%' OR product_name LIKE '%Bagoong%310%';
UPDATE products SET image_path = 'CRAB-PASTE-8oz.webp' WHERE product_name LIKE '%Crab Paste%';
UPDATE products SET image_path = 'COCONUT-MILK.webp' WHERE product_name LIKE '%Coconut Milk%';
UPDATE products SET image_path = 'PREMIUM-extra-virgin-anchovy-ectract-200ML.webp' WHERE product_name LIKE '%Premium%' OR product_name LIKE '%Anchovy%' OR product_name LIKE '%Extra-Virgin%';
UPDATE products SET image_path = 'patis-800ML.webp' WHERE product_name LIKE '%800 mL%' AND product_name LIKE '%Fish Sauce%';
UPDATE products SET image_path = 'patis-puro-CHILIMANSI-310ML.webp' WHERE product_name LIKE '%Chili%Kalamansi%' OR product_name LIKE '%Chili & Kalamansi%';
UPDATE products SET image_path = 'COCO-SUKA-310ML.webp' WHERE product_name LIKE '%Spicy-Sweet%310%' OR product_name LIKE '%Coco Suka Spicy-Sweet%';

-- Add new category for Nata de Coco & Kaong
INSERT IGNORE INTO product_categories (category_name, description) VALUES ('Nata de Coco & Kaong', 'Nata de coco and kaong products');

-- Insert missing products (skip if product_name already exists)
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Nata de Coco 12 oz', 'Lorins Nata de Coco 12 oz', 'pcs', (SELECT category_id FROM product_categories WHERE category_name = 'Nata de Coco & Kaong' LIMIT 1), 'NATA-DE-COCO-12OZ.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Nata de Coco 12 oz' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Nata de Coco 32 oz', 'Lorins Nata de Coco 32 oz', 'pcs', (SELECT category_id FROM product_categories WHERE category_name = 'Nata de Coco & Kaong' LIMIT 1), 'NATA-DE-COCO-32OZ.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Nata de Coco 32 oz' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Kaong 12 oz', 'Lorins Kaong 12 oz', 'pcs', (SELECT category_id FROM product_categories WHERE category_name = 'Nata de Coco & Kaong' LIMIT 1), 'KAONG-12OZ.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Kaong 12 oz' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Kaong 32 oz', 'Lorins Kaong 32 oz', 'pcs', (SELECT category_id FROM product_categories WHERE category_name = 'Nata de Coco & Kaong' LIMIT 1), 'KAONG-32OZ.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Kaong 32 oz' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Patis Puro 150 mL', 'Lorins Patis Puro 150 mL', 'pcs', 1, 'patis-PURO-150ML.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Patis Puro 150 mL' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Patis Puro 310 mL', 'Lorins Patis Puro 310 mL', 'pcs', 1, 'patis-PURO-310ML.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Patis Puro 310 mL' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Patis Puro Chili Mansi 150 mL', 'Lorins Patis Puro Chili Mansi 150 mL', 'pcs', 8, 'patis-puro-CHILIMANSI-150ML.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Patis Puro Chili Mansi 150 mL' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Patis Puro Chili Mansi 310 mL', 'Lorins Patis Puro Chili Mansi 310 mL', 'pcs', 8, 'patis-puro-CHILIMANSI-310ML.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Patis Puro Chili Mansi 310 mL' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Patis Puro Mansi 150 mL', 'Lorins Patis Puro Mansi 150 mL', 'pcs', 8, 'patis-PURO-MANSI-150ML.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Patis Puro Mansi 150 mL' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Patis Flavor 7+1 Tipid Pouch', 'Lorins Patis Flavor 7+1 Tipid Pouch', 'pcs', 1, 'Lorins-patis-7+1(patis-flavor-tipidpouch).webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Patis Flavor 7+1 Tipid Pouch' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Patis Twin Pack 1L x 2', 'Lorins Patis Twin Pack 1 Liter x 2', 'pcs', 1, 'patis-TWINPACK(1litterx2).webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Patis Twin Pack 1L x 2' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Patis Pouch 350 mL', 'Lorins Patis Pouch 350 mL', 'pcs', 1, 'patis-POUCH-350ML.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Patis Pouch 350 mL' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Vinegar 350 mL', 'Lorins Vinegar 350 mL', 'pcs', 3, 'VINEGAR-350ML.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Vinegar 350 mL' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Vinegar 1 L', 'Lorins Vinegar 1 Liter', 'pcs', 3, 'VINEGAR-1LITER.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Vinegar 1 L' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Vinegar 3785 mL (Gallon)', 'Lorins Vinegar Gallon', 'pcs', 3, 'VINEGAR-1GALLON.webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Vinegar 3785 mL (Gallon)' LIMIT 1);
INSERT INTO products (product_name, description, unit, category_id, image_path)
SELECT 'Lorins Value Pack (Soy Sauce + Vinegar + Free Patis Pouch)', 'Lorins Value Pack with free patis pouch', 'pcs', 3, 'VALUEPACK(soysauce-and-vinegar-with-freepatispouch).webp'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Value Pack (Soy Sauce + Vinegar + Free Patis Pouch)' LIMIT 1);
