-- Create product recipes (BOM) table
-- Run this in phpMyAdmin / MySQL (or include in your project DB migration workflow)

CREATE TABLE IF NOT EXISTS `product_recipes` (
    `recipe_id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `material_id` INT NOT NULL,
    `quantity_required` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX (`product_id`),
    INDEX (`material_id`),

    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
    FOREIGN KEY (`material_id`) REFERENCES `raw_materials`(`material_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Recipe (BOM) entries for all products
-- Quantities are per finished unit in the product table.

INSERT INTO `product_recipes` (`product_id`, `material_id`, `quantity_required`) VALUES

-- ==== Patis (Fish Sauce) products ====
(1, 9, 4.00),   -- Lorins Patis Flavor 150 mL pouch: Fermented fish
(1, 14, 0.60),  -- Iodized salt
(1, 20, 0.15),  -- Water
(1, 29, 1.00),  -- Plastic sachets
(1, 30, 1.00),  -- Bottle caps
(1, 31, 1.00),  -- Labels

(2, 9, 5.00),   -- Lorins Patis Flavor 350 mL PET bottle
(2, 14, 0.80),
(2, 20, 0.20),
(2, 28, 1.00),  -- PET bottles
(2, 30, 1.00),
(2, 31, 1.00),

(3, 9, 5.00),   -- Lorins Patis Flavor with Chili 350 mL PET bottle
(3, 14, 0.80),
(3, 20, 0.20),
(3, 17, 0.10),  -- Chili
(3, 28, 1.00),
(3, 30, 1.00),
(3, 31, 1.00),

(4, 9, 13.00),  -- Lorins Patis Flavor 1 L
(4, 14, 2.00),
(4, 20, 0.50),
(4, 27, 1.00),  -- Glass bottles
(4, 30, 1.00),
(4, 31, 1.00),

(5, 9, 25.00),  -- Lorins Patis Flavor 1893 mL (Half Gallon)
(5, 14, 3.50),
(5, 20, 1.00),
(5, 27, 1.00),
(5, 30, 1.00),
(5, 31, 1.00),

(6, 9, 50.00),  -- Lorins Patis Flavor 3785 mL (Gallon)
(6, 14, 7.00),
(6, 20, 2.00),
(6, 27, 1.00),
(6, 30, 1.00),
(6, 31, 1.00),

(28, 9, 4.00),  -- Lorins Patis Puro 150 mL
(28, 14, 0.60),
(28, 20, 0.15),
(28, 29, 1.00),
(28, 30, 1.00),
(28, 32, 1.00), -- Uncoated paper label

(29, 9, 5.00),  -- Lorins Patis Puro 310 mL
(29, 14, 0.80),
(29, 20, 0.20),
(29, 28, 1.00),
(29, 30, 1.00),
(29, 32, 1.00),

(30, 9, 4.00),  -- Lorins Patis Puro Chili Mansi 150 mL
(30, 14, 0.60),
(30, 20, 0.15),
(30, 17, 0.10),
(30, 18, 0.05), -- Calamansi flavor
(30, 29, 1.00),
(30, 30, 1.00),
(30, 32, 1.00),

(31, 9, 5.00),  -- Lorins Patis Puro Chili Mansi 310 mL
(31, 14, 0.80),
(31, 20, 0.20),
(31, 17, 0.10),
(31, 18, 0.05),
(31, 28, 1.00),
(31, 30, 1.00),
(31, 32, 1.00),

(32, 9, 4.00),  -- Lorins Patis Puro Mansi 150 mL
(32, 14, 0.60),
(32, 20, 0.15),
(32, 18, 0.08),
(32, 29, 1.00),
(32, 30, 1.00),
(32, 32, 1.00),

(33, 9, 4.00),  -- Lorins Patis Flavor 7+1 Tipid Pouch
(33, 14, 0.60),
(33, 20, 0.15),
(33, 29, 1.00),
(33, 30, 1.00),
(33, 32, 1.00),

(34, 9, 13.00), -- Lorins Patis Twin Pack 1L x 2
(34, 14, 2.00),
(34, 20, 0.50),
(34, 27, 2.00),
(34, 30, 2.00),
(34, 31, 2.00),

(35, 9, 5.00),  -- Lorins Patis Pouch 350 mL
(35, 14, 0.80),
(35, 20, 0.20),
(35, 29, 1.00),
(35, 30, 1.00),
(35, 32, 1.00),

-- ==== Soy Sauce products ====
(7, 16, 4.00),  -- Lorins Soy Sauce 350 mL PET bottle
(7, 15, 0.70),  -- Salt (sea salt)
(7, 20, 2.00),
(7, 19, 0.05),  -- Caramel coloring
(7, 23, 0.02),  -- Potassium sorbate
(7, 28, 1.00),
(7, 30, 1.00),
(7, 31, 1.00),

(8, 16, 10.00), -- Lorins Soy Sauce 1 L
(8, 15, 2.00),
(8, 20, 5.00),
(8, 19, 0.10),
(8, 23, 0.05),
(8, 28, 1.00),
(8, 30, 1.00),
(8, 31, 1.00),

(9, 16, 40.00), -- Lorins Soy Sauce 3785 mL (Gallon)
(9, 15, 8.00),
(9, 20, 20.00),
(9, 19, 0.40),
(9, 23, 0.12),
(9, 28, 1.00),
(9, 30, 1.00),
(9, 31, 1.00),

-- ==== Vinegar / Coco Suka products ====
(10, 20, 0.10), -- Lorins Coco Suka 150 mL
(10, 3, 0.05),
(10, 14, 0.05),
(10, 23, 0.01),
(10, 28, 1.00),
(10, 30, 1.00),
(10, 31, 1.00),

(11, 20, 0.20), -- Lorins Coco Suka 310 mL
(11, 3, 0.10),
(11, 14, 0.08),
(11, 23, 0.02),
(11, 28, 1.00),
(11, 30, 1.00),
(11, 31, 1.00),

(12, 20, 0.50), -- Lorins Coco Suka 800 mL
(12, 3, 0.20),
(12, 14, 0.20),
(12, 23, 0.04),
(12, 28, 1.00),
(12, 30, 1.00),
(12, 31, 1.00),

(36, 20, 0.10), -- Lorins Vinegar 350 mL
(36, 3, 0.05),
(36, 14, 0.05),
(36, 23, 0.01),
(36, 28, 1.00),
(36, 30, 1.00),
(36, 31, 1.00),

(37, 20, 0.20), -- Lorins Vinegar 1 L
(37, 3, 0.10),
(37, 14, 0.08),
(37, 23, 0.02),
(37, 28, 1.00),
(37, 30, 1.00),
(37, 31, 1.00),

(38, 20, 0.50), -- Lorins Vinegar 3785 mL (Gallon)
(38, 3, 0.20),
(38, 14, 0.20),
(38, 23, 0.04),
(38, 28, 1.00),
(38, 30, 1.00),
(38, 31, 1.00),

-- ==== Value packs ====
(13, 9, 2.00),  -- Lorins Budget / Value Pack
(13, 16, 2.00),
(13, 20, 3.00),
(13, 14, 1.00),
(13, 31, 1.00),

(39, 9, 2.00),  -- Lorins Value Pack (Soy Sauce + Vinegar + Free Patis Pouch)
(39, 16, 2.00),
(39, 20, 3.00),
(39, 14, 1.00),
(39, 31, 1.00),

-- ==== Alamang Guisado ====
(14, 12, 2.00), -- Original
(14, 21, 0.10),
(14, 22, 0.10),
(14, 14, 0.20),
(14, 20, 0.50),
(14, 27, 1.00),
(14, 30, 1.00),
(14, 31, 1.00),

(15, 12, 2.00), -- Sweet
(15, 21, 0.10),
(15, 22, 0.10),
(15, 3, 0.20),  -- Sugar
(15, 14, 0.20),
(15, 20, 0.50),
(15, 27, 1.00),
(15, 30, 1.00),
(15, 31, 1.00),

(16, 12, 2.00), -- Spicy
(16, 21, 0.10),
(16, 22, 0.10),
(16, 17, 0.20),  -- Chili
(16, 14, 0.20),
(16, 20, 0.50),
(16, 27, 1.00),
(16, 30, 1.00),
(16, 31, 1.00),

-- ==== Bagoong Isda ====
(17, 9, 3.00),
(17, 14, 0.30),
(17, 20, 0.50),
(17, 27, 1.00),
(17, 30, 1.00),
(17, 31, 1.00),

-- ==== Crab Paste ====
(18, 10, 2.00),
(18, 14, 0.20),
(18, 20, 0.30),
(18, 17, 0.05),
(18, 27, 1.00),
(18, 30, 1.00),
(18, 31, 1.00),

-- ==== Coconut Milk ====
(19, 20, 0.40),
(19, 3, 0.05),
(19, 27, 1.00),
(19, 30, 1.00),
(19, 31, 1.00),

-- ==== Premium Anchovy Extract ====
(20, 10, 3.00),
(20, 14, 0.20),
(20, 20, 0.10),
(20, 27, 1.00),
(20, 30, 1.00),
(20, 31, 1.00),

-- ==== Nata de Coco ====
(24, 20, 0.20),
(24, 3, 0.20),
(24, 14, 0.02),
(24, 23, 0.01),
(24, 27, 1.00),
(24, 30, 1.00),
(24, 31, 1.00),

(41, 20, 0.40),
(41, 3, 0.40),
(41, 14, 0.04),
(41, 23, 0.02),
(41, 27, 1.00),
(41, 30, 1.00),
(41, 31, 1.00),

-- ==== Kaong ====
(26, 20, 0.20),
(26, 3, 0.20),
(26, 14, 0.02),
(26, 23, 0.01),
(26, 27, 1.00),
(26, 30, 1.00),
(26, 31, 1.00),

(42, 20, 0.40),
(42, 3, 0.40),
(42, 14, 0.04),
(42, 23, 0.02),
(42, 27, 1.00),
(42, 30, 1.00),
(42, 31, 1.00);
