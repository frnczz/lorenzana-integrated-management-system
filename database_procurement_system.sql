-- ============================================
-- PROCUREMENT SYSTEM DATABASE SCHEMA
-- Lorenzana Food Corporation
-- ============================================

-- Suppliers Table
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_code VARCHAR(50) UNIQUE NOT NULL,
    supplier_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(50),
    address TEXT,
    payment_terms VARCHAR(100) DEFAULT 'Net 30',
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Supplier Products (what each supplier supplies)
CREATE TABLE IF NOT EXISTS supplier_products (
    sp_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    material_id INT,
    product_id INT,
    item_name VARCHAR(200) NOT NULL,
    item_type ENUM('Raw Material', 'Product', 'Other') DEFAULT 'Raw Material',
    unit_price DECIMAL(12,2),
    unit VARCHAR(20) DEFAULT 'kg',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Purchase Requisitions (PR)
CREATE TABLE IF NOT EXISTS purchase_requisitions (
    pr_id INT AUTO_INCREMENT PRIMARY KEY,
    pr_number VARCHAR(50) UNIQUE NOT NULL,
    department VARCHAR(100),
    requested_by INT NOT NULL,
    request_date DATE NOT NULL,
    required_date DATE,
    justification TEXT,
    status ENUM('Draft', 'Submitted', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Draft',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    total_estimated_cost DECIMAL(12,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Purchase Requisition Items
CREATE TABLE IF NOT EXISTS pr_items (
    pr_item_id INT AUTO_INCREMENT PRIMARY KEY,
    pr_id INT NOT NULL,
    material_id INT,
    product_id INT,
    item_name VARCHAR(200) NOT NULL,
    item_type ENUM('Raw Material', 'Product', 'Other') DEFAULT 'Raw Material',
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) DEFAULT 'kg',
    estimated_unit_price DECIMAL(12,2),
    estimated_total DECIMAL(12,2),
    notes TEXT,
    FOREIGN KEY (pr_id) REFERENCES purchase_requisitions(pr_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Purchase Orders (PO)
CREATE TABLE IF NOT EXISTS purchase_orders (
    po_id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) UNIQUE NOT NULL,
    pr_id INT NULL,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    delivery_address TEXT,
    payment_terms VARCHAR(100),
    status ENUM('Open', 'Partially Received', 'Received', 'Closed', 'Cancelled') DEFAULT 'Open',
    subtotal DECIMAL(12,2) DEFAULT 0.00,
    tax_amount DECIMAL(12,2) DEFAULT 0.00,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pr_id) REFERENCES purchase_requisitions(pr_id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Purchase Order Items
CREATE TABLE IF NOT EXISTS po_items (
    po_item_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    material_id INT,
    product_id INT,
    item_name VARCHAR(200) NOT NULL,
    item_type ENUM('Raw Material', 'Product', 'Other') DEFAULT 'Raw Material',
    quantity_ordered DECIMAL(10,2) NOT NULL,
    quantity_received DECIMAL(10,2) DEFAULT 0.00,
    unit VARCHAR(20) DEFAULT 'kg',
    unit_price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    notes TEXT,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Goods Receiving Notes (GRN)
CREATE TABLE IF NOT EXISTS goods_receiving_notes (
    grn_id INT AUTO_INCREMENT PRIMARY KEY,
    grn_number VARCHAR(50) UNIQUE NOT NULL,
    po_id INT NOT NULL,
    invoice_id INT DEFAULT NULL,
    received_date DATE NOT NULL,
    received_by INT NOT NULL,
    qc_status ENUM('Pending', 'Passed', 'Failed', 'Partial') DEFAULT 'Pending',
    qc_checked_by INT NULL,
    qc_checked_at TIMESTAMP NULL,
    qc_remarks TEXT,
    total_items_received INT DEFAULT 0,
    status ENUM('Draft', 'Received', 'QC Passed', 'QC Failed', 'Partially Received') DEFAULT 'Draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (received_by) REFERENCES users(id),
    FOREIGN KEY (qc_checked_by) REFERENCES users(id),
    FOREIGN KEY (invoice_id) REFERENCES supplier_invoices(invoice_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- GRN Items (with QC, expiry, lot tracking)
CREATE TABLE IF NOT EXISTS grn_items (
    grn_item_id INT AUTO_INCREMENT PRIMARY KEY,
    grn_id INT NOT NULL,
    po_item_id INT NOT NULL,
    material_id INT,
    product_id INT,
    item_name VARCHAR(200) NOT NULL,
    quantity_received DECIMAL(10,2) NOT NULL,
    quantity_accepted DECIMAL(10,2) DEFAULT 0.00,
    quantity_rejected DECIMAL(10,2) DEFAULT 0.00,
    unit VARCHAR(20) DEFAULT 'kg',
    lot_number VARCHAR(100),
    expiry_date DATE,
    warehouse_location VARCHAR(100),
    qc_status ENUM('Pending', 'Passed', 'Failed') DEFAULT 'Pending',
    qc_remarks TEXT,
    unit_price DECIMAL(12,2),
    subtotal DECIMAL(12,2),
    FOREIGN KEY (grn_id) REFERENCES goods_receiving_notes(grn_id) ON DELETE CASCADE,
    FOREIGN KEY (po_item_id) REFERENCES po_items(po_item_id),
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Supplier Invoices
CREATE TABLE IF NOT EXISTS supplier_invoices (
    invoice_id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(100) NOT NULL,
    supplier_id INT NOT NULL,
    po_id INT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE,
    subtotal DECIMAL(12,2) DEFAULT 0.00,
    tax_amount DECIMAL(12,2) DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL,
    payment_status ENUM('Unpaid', 'Partially Paid', 'Paid') DEFAULT 'Unpaid',
    paid_amount DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Returns to Supplier
CREATE TABLE IF NOT EXISTS supplier_returns (
    return_id INT AUTO_INCREMENT PRIMARY KEY,
    return_number VARCHAR(50) UNIQUE NOT NULL,
    po_id INT NULL,
    grn_id INT NULL,
    supplier_id INT NOT NULL,
    return_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Returned', 'Cancelled') DEFAULT 'Pending',
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE SET NULL,
    FOREIGN KEY (grn_id) REFERENCES goods_receiving_notes(grn_id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Return Items
CREATE TABLE IF NOT EXISTS return_items (
    return_item_id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    material_id INT,
    product_id INT,
    item_name VARCHAR(200) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) DEFAULT 'kg',
    unit_price DECIMAL(12,2),
    subtotal DECIMAL(12,2),
    reason TEXT,
    FOREIGN KEY (return_id) REFERENCES supplier_returns(return_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INDEXES for Performance
-- ============================================

-- ============================================
-- UPDATE EXISTING TABLES
-- ============================================

-- Add supplier reference to raw_materials if not exists
ALTER TABLE raw_materials 
ADD COLUMN IF NOT EXISTS preferred_supplier_id INT NULL,
ADD FOREIGN KEY (preferred_supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL;

-- Add procurement fields to inventory_transactions if needed
ALTER TABLE inventory_transactions
ADD COLUMN IF NOT EXISTS grn_id INT NULL,
ADD COLUMN IF NOT EXISTS return_id INT NULL,
ADD FOREIGN KEY (grn_id) REFERENCES goods_receiving_notes(grn_id) ON DELETE SET NULL,
ADD FOREIGN KEY (return_id) REFERENCES supplier_returns(return_id) ON DELETE SET NULL;
