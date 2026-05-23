USE lorinims_db;

-- Update product prices
-- Note: Product names are matched as they appear in the database

-- 🐟 Lorins Patis Products
UPDATE products SET unit_price = 11.27 WHERE product_name = 'Lorins Patis Flavor 150 mL pouch';
UPDATE products SET unit_price = 33.60 WHERE product_name = 'Lorins Patis Puro 150 mL';
UPDATE products SET unit_price = 36.33 WHERE product_name = 'Lorins Patis Puro Mansi 150 mL';
UPDATE products SET unit_price = 48.83 WHERE product_name = 'Lorins Patis Puro 310 mL';
UPDATE products SET unit_price = 68.91 WHERE product_name = 'Lorins Patis Flavor 1 L';
UPDATE products SET unit_price = 78.79 WHERE product_name = 'Lorins Patis Flavor 7+1 Tipid Pouch';

-- Add Lorins Patis Puro 800 mL if it doesn't exist, then set price
INSERT INTO products (product_name, description, unit, category_id, image_path, unit_price)
SELECT 'Lorins Patis Puro 800 mL', 'Lorins Patis Puro 800 mL', 'pcs', 1, 'patis-PURO-800ML.webp', 92.42
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Lorins Patis Puro 800 mL' LIMIT 1);

-- If product already exists, update the price
UPDATE products SET unit_price = 92.42 WHERE product_name = 'Lorins Patis Puro 800 mL';

-- 🍶 Lorins Soy Sauce Products
UPDATE products SET unit_price = 19.89 WHERE product_name = 'Lorins Soy Sauce 350 mL PET bottle';
UPDATE products SET unit_price = 51.63 WHERE product_name = 'Lorins Soy Sauce 1 L';
UPDATE products SET unit_price = 176.76 WHERE product_name = 'Lorins Soy Sauce 3785 mL (Gallon)';

-- 🧂 Combos / Packs
UPDATE products SET unit_price = 56.20 WHERE product_name = 'Lorins Budget / Value Pack';
UPDATE products SET unit_price = 91.68 WHERE product_name = 'Lorins Value Pack (Soy Sauce + Vinegar + Free Patis Pouch)';

-- 🦀 Specialty Products
UPDATE products SET unit_price = 169.22 WHERE product_name = 'Lorins Crab Paste 8 oz';
UPDATE products SET unit_price = 95.08 WHERE product_name = 'Lorins Alamang Guisado Original 8 oz / 250 g';

-- 🍯 Filtaste Sweet Products (Nata/Kaong)
-- Update Lorins brand products (assuming Filtaste is same as Lorins or sub-brand)
UPDATE products SET unit_price = 124.53 WHERE product_name = 'Lorins Nata de Coco 32 oz';
UPDATE products SET unit_price = 163.77 WHERE product_name = 'Lorins Kaong 32 oz';

-- If Filtaste is a separate brand, add/update those products
INSERT INTO products (product_name, description, unit, category_id, image_path, unit_price)
SELECT 'Filtaste Nata de Coco 32 oz', 'Filtaste Nata de Coco 32 oz', 'pcs', 9, 'NATA-DE-COCO-32OZ.webp', 124.53
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Filtaste Nata de Coco 32 oz' LIMIT 1);

INSERT INTO products (product_name, description, unit, category_id, image_path, unit_price)
SELECT 'Filtaste Kaong 32 oz', 'Filtaste Kaong 32 oz', 'pcs', 9, 'KAONG-32OZ.webp', 163.77
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM products WHERE product_name = 'Filtaste Kaong 32 oz' LIMIT 1);

-- Update Filtaste prices if they exist
UPDATE products SET unit_price = 124.53 WHERE product_name = 'Filtaste Nata de Coco 32 oz';
UPDATE products SET unit_price = 163.77 WHERE product_name = 'Filtaste Kaong 32 oz';

-- Verify updates
SELECT product_id, product_name, unit_price 
FROM products 
WHERE unit_price > 0 
ORDER BY category_id, product_name;
