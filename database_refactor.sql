
USE lorinims_db;

-- ========== 1. ORDER_ITEMS (multiple products per order) ==========
CREATE TABLE IF NOT EXISTS order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    reserved TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES sales_orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_product (product_id)
) ENGINE=InnoDB;

-- Add total_amount to sales_orders (ignore error if column already exists)
ALTER TABLE sales_orders ADD COLUMN total_amount DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE sales_orders MODIFY COLUMN product_id INT NULL;
ALTER TABLE sales_orders MODIFY COLUMN quantity DECIMAL(10,2) NULL;

-- Backfill order_items from existing sales_orders that have product_id
INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal, reserved)
SELECT so.order_id, so.product_id, so.quantity, 0, 0, 1
FROM sales_orders so
WHERE so.product_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = so.order_id);

-- Update total_amount from order_items where we have backfilled
UPDATE sales_orders so
SET so.total_amount = (SELECT COALESCE(SUM(oi.subtotal), 0) FROM order_items oi WHERE oi.order_id = so.order_id)
WHERE so.order_id IN (SELECT order_id FROM order_items);

-- ========== 2. STOCK RESERVATION (finished_goods) ==========
ALTER TABLE finished_goods ADD COLUMN reserved_quantity DECIMAL(10,2) NOT NULL DEFAULT 0;

-- ========== 3. PRODUCTS: fermentation eligibility ==========
ALTER TABLE products ADD COLUMN fermentation_eligible TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE products ADD COLUMN unit_price DECIMAL(10,2) NOT NULL DEFAULT 0;

-- ========== 4. ACTIVITY LOG (user-specific activity) ==========
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_activity_user (user_id),
    INDEX idx_activity_created (created_at)
) ENGINE=InnoDB;

-- ========== 5. PROCUREMENT: supplier deliveries ==========
CREATE TABLE IF NOT EXISTS supplier_deliveries (
    delivery_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    delivery_date DATE NOT NULL,
    reference VARCHAR(100) NULL,
    notes TEXT NULL,
    received_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (received_by) REFERENCES users(id),
    INDEX idx_supplier_delivery_date (supplier_id, delivery_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS supplier_delivery_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT NOT NULL,
    raw_material_id INT NULL,
    product_id INT NULL,
    item_name VARCHAR(200) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) DEFAULT 'kg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_id) REFERENCES supplier_deliveries(delivery_id) ON DELETE CASCADE,
    FOREIGN KEY (raw_material_id) REFERENCES raw_materials(material_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    INDEX idx_delivery_items (delivery_id)
) ENGINE=InnoDB;

-- ========== 6. RAW MATERIALS: expiry alert threshold (days) ==========
-- min_stock_level already exists; for near-expiry we use expiry_date in queries (e.g. WHERE expiry_date <= CURDATE() + INTERVAL 30 DAY)
