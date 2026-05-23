USE lorinims_db;

-- Update invoices table to support full workflow
-- Note: Some columns may already exist, so we'll use ALTER TABLE with IF NOT EXISTS equivalent
SET @dbname = DATABASE();

-- Check and add columns if they don't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'subtotal') > 0,
    'SELECT 1',
    'ALTER TABLE invoices ADD COLUMN subtotal DECIMAL(12,2) DEFAULT 0.00'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'discount_amount') > 0,
    'SELECT 1',
    'ALTER TABLE invoices ADD COLUMN discount_amount DECIMAL(12,2) DEFAULT 0.00'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'vat_rate') > 0,
    'SELECT 1',
    'ALTER TABLE invoices ADD COLUMN vat_rate DECIMAL(5,2) DEFAULT 0.00'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'vat_amount') > 0,
    'SELECT 1',
    'ALTER TABLE invoices ADD COLUMN vat_amount DECIMAL(12,2) DEFAULT 0.00'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'payment_terms') > 0,
    'SELECT 1',
    'ALTER TABLE invoices ADD COLUMN payment_terms VARCHAR(50) DEFAULT ''Cash'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'approved_by') > 0,
    'SELECT 1',
    'ALTER TABLE invoices ADD COLUMN approved_by INT NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'approved_at') > 0,
    'SELECT 1',
    'ALTER TABLE invoices ADD COLUMN approved_at TIMESTAMP NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'approval_status') > 0,
    'SELECT 1',
    'ALTER TABLE invoices ADD COLUMN approval_status ENUM(''Pending'', ''Approved'', ''Rejected'') DEFAULT ''Pending'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'notes') > 0,
    'SELECT 1',
    'ALTER TABLE invoices ADD COLUMN notes TEXT NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'delivery_receipt_number') > 0,
    'SELECT 1',
    'ALTER TABLE invoices ADD COLUMN delivery_receipt_number VARCHAR(50) NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update status enum to include Partially Paid
ALTER TABLE invoices MODIFY COLUMN status ENUM('Pending', 'Partially Paid', 'Paid', 'Overdue') DEFAULT 'Pending';

-- Create invoice_items table for line items
CREATE TABLE IF NOT EXISTS invoice_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    INDEX idx_invoice_items_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create payments table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    payment_number VARCHAR(50) UNIQUE NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Check', 'Credit Card', 'Other') DEFAULT 'Cash',
    amount DECIMAL(12,2) NOT NULL,
    reference_number VARCHAR(100) NULL,
    notes TEXT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_payments_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create delivery_receipts table
CREATE TABLE IF NOT EXISTS delivery_receipts (
    dr_id INT AUTO_INCREMENT PRIMARY KEY,
    dr_number VARCHAR(50) UNIQUE NOT NULL,
    order_id INT NOT NULL,
    invoice_id INT NULL,
    delivery_date DATE NOT NULL,
    driver_id INT NULL,
    vehicle_info VARCHAR(100) NULL,
    received_by VARCHAR(100) NULL,
    notes TEXT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES sales_orders(order_id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE SET NULL,
    FOREIGN KEY (driver_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_dr_order (order_id),
    INDEX idx_dr_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update sales_orders to track invoice generation
ALTER TABLE sales_orders 
ADD COLUMN IF NOT EXISTS invoice_id INT NULL,
ADD COLUMN IF NOT EXISTS invoice_generated TINYINT(1) DEFAULT 0,
ADD FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE SET NULL;

-- Add index for faster lookups
CREATE INDEX idx_sales_invoice ON sales_orders(invoice_id);
CREATE INDEX idx_sales_invoice_generated ON sales_orders(invoice_generated, status);
