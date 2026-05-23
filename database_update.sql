
-- Add product categories table
CREATE TABLE IF NOT EXISTS product_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add category_id to products if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = "products";
SET @columnname = "category_id";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add foreign key if it doesn't exist
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (constraint_name = 'products_ibfk_1')
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD FOREIGN KEY (category_id) REFERENCES product_categories(category_id) ON DELETE SET NULL")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ensure sales_orders.status enum supports pickup workflow values
SET @status_enum = (SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'status');

SET @needs_update = (NOT (LOCATE('Picked Up', @status_enum) > 0)) OR (NOT (LOCATE('Ready for Pickup', @status_enum) > 0));

SET @alter_sql = IF(
    @needs_update,
    "ALTER TABLE `sales_orders` MODIFY COLUMN `status` ENUM('Pending','Confirmed','Dispatched','Delivered','Cancelled','Ready for Pickup','Picked Up') DEFAULT 'Pending'",
    "SELECT 1"
);

PREPARE alterStatus FROM @alter_sql;
EXECUTE alterStatus;
DEALLOCATE PREPARE alterStatus;

-- Insert Product Categories
INSERT IGNORE INTO product_categories (category_name, description) VALUES
('Patis (Fish Sauce)', 'Fish sauce products under Lorins Patis Flavor brand'),
('Soy Sauce', 'Soy sauce products under Lorins brand'),
('Vinegar', 'Vinegar products including Coco Suka and value packs'),
('Alamang (Shrimp Paste)', 'Sauteed shrimp paste products'),
('Bagoong', 'Fermented fish products'),
('Specialty Products', 'Specialty items like crab paste and coconut milk'),
('Premium Products', 'Premium and extra-virgin products'),
('Variants', 'Special variants and limited editions');

-- Update products with categories
UPDATE products SET category_id = 1 WHERE product_name LIKE '%Patis%';
UPDATE products SET category_id = 2 WHERE product_name LIKE '%Soy Sauce%';
UPDATE products SET category_id = 3 WHERE product_name LIKE '%Coco Suka%' OR product_name LIKE '%Value Pack%';
UPDATE products SET category_id = 4 WHERE product_name LIKE '%Alamang%';
UPDATE products SET category_id = 5 WHERE product_name LIKE '%Bagoong%';
UPDATE products SET category_id = 6 WHERE product_name LIKE '%Crab Paste%' OR product_name LIKE '%Coconut Milk%';
UPDATE products SET category_id = 7 WHERE product_name LIKE '%Premium%' OR product_name LIKE '%Extra-Virgin%';
UPDATE products SET category_id = 8 WHERE product_name LIKE '%Chili%' OR product_name LIKE '%Kalamansi%' OR product_name LIKE '%Spicy-Sweet%';

-- Payroll and Employee Management Tables
CREATE TABLE IF NOT EXISTS employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    employee_number VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    position VARCHAR(100),
    department VARCHAR(50),
    hire_date DATE,
    salary DECIMAL(10,2) DEFAULT 0,
    status ENUM('Active', 'Inactive', 'Terminated') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    time_in TIME,
    time_out TIME,
    hours_worked DECIMAL(4,2) DEFAULT 0,
    status ENUM('Present', 'Absent', 'Late', 'Half Day', 'Leave') DEFAULT 'Present',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (employee_id, attendance_date)
);

CREATE TABLE IF NOT EXISTS payroll (
    payroll_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    payroll_period_start DATE NOT NULL,
    payroll_period_end DATE NOT NULL,
    basic_salary DECIMAL(10,2) DEFAULT 0,
    overtime_pay DECIMAL(10,2) DEFAULT 0,
    allowances DECIMAL(10,2) DEFAULT 0,
    gross_pay DECIMAL(10,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    net_pay DECIMAL(10,2) DEFAULT 0,
    status ENUM('Draft', 'Processed', 'Paid') DEFAULT 'Draft',
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS payroll_deductions (
    deduction_id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_id INT NOT NULL,
    deduction_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    FOREIGN KEY (payroll_id) REFERENCES payroll(payroll_id) ON DELETE CASCADE
);
