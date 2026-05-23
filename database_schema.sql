
-- Drop existing database if it exists (THIS WILL DELETE ALL DATA!)
DROP DATABASE IF EXISTS lorinims_db;

-- Create fresh database
CREATE DATABASE lorinims_db;
USE lorinims_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'production', 'warehouse', 'qc', 'accounting', 'sales', 'delivery', 'procurement') NOT NULL,
    full_name VARCHAR(100),
    email VARCHAR(100),
    phone_number VARCHAR(20),
    address TEXT,
    birth_date DATE,
    profile_picture VARCHAR(255),
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Product Categories Table (must be created before products)
CREATE TABLE IF NOT EXISTS product_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    description TEXT,
    unit VARCHAR(20) DEFAULT 'pcs',
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(category_id) ON DELETE SET NULL
);

-- Raw Materials Table
CREATE TABLE IF NOT EXISTS raw_materials (
    material_id INT AUTO_INCREMENT PRIMARY KEY,
    material_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    quantity DECIMAL(10,2) DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'kg',
    expiry_date DATE,
    warehouse_location VARCHAR(100),
    min_stock_level DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Finished Goods Table
CREATE TABLE IF NOT EXISTS finished_goods (
    fg_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    quantity DECIMAL(10,2) DEFAULT 0,
    expiry_date DATE,
    warehouse_location VARCHAR(100),
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Production Batches Table
CREATE TABLE IF NOT EXISTS production_batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(50) UNIQUE NOT NULL,
    product_id INT,
    batch_date DATE NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    fermentation_status ENUM('Not Started', 'Ongoing', 'Completed') DEFAULT 'Not Started',
    packaging_status ENUM('Pending', 'In Progress', 'Finished') DEFAULT 'Pending',
    status VARCHAR(50) DEFAULT 'Processing',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Batch Details (Materials Used)
CREATE TABLE IF NOT EXISTS batch_details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT,
    material_id INT,
    quantity_used DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (batch_id) REFERENCES production_batches(batch_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quality Control Records Table
CREATE TABLE IF NOT EXISTS qc_records (
    qc_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(50) NOT NULL,
    inspector_name VARCHAR(100) NOT NULL,
    inspection_date DATE NOT NULL,
    test_result ENUM('Passed', 'Failed', 'Pending') DEFAULT 'Pending',
    non_conformance_details TEXT,
    corrective_action TEXT,
    approval_status ENUM('Approved', 'Rejected', 'For Re-inspection') DEFAULT 'For Re-inspection',
    inspected_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspected_by) REFERENCES users(id)
);

-- Suppliers Table
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    contact_number VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Purchase Requests Table
CREATE TABLE IF NOT EXISTS purchase_requests (
    pr_id INT AUTO_INCREMENT PRIMARY KEY,
    pr_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT,
    item_name VARCHAR(100) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) DEFAULT 'kg',
    expected_delivery_date DATE,
    status ENUM('Pending', 'Approved', 'Ordered', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    requested_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (requested_by) REFERENCES users(id)
);

-- Customers Table
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    contact_number VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sales Orders Table
CREATE TABLE IF NOT EXISTS sales_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    product_id INT,
    quantity DECIMAL(10,2) NOT NULL,
    order_date DATE NOT NULL,
    delivery_address TEXT,
    delivery_date DATE,
    status ENUM('Pending', 'Confirmed', 'Dispatched', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Delivery Assignments Table
CREATE TABLE IF NOT EXISTS delivery_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    driver_id INT,
    vehicle_info VARCHAR(100),
    dispatch_time DATETIME,
    status ENUM('Dispatched', 'On the Way', 'Arrived', 'Delivered', 'Failed') DEFAULT 'Dispatched',
    proof_of_delivery VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES sales_orders(order_id),
    FOREIGN KEY (driver_id) REFERENCES users(id)
);

-- GPS Tracking Table
CREATE TABLE IF NOT EXISTS gps_tracking (
    tracking_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES delivery_assignments(assignment_id)
);

-- Invoices Table
CREATE TABLE IF NOT EXISTS invoices (
    invoice_id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    order_id INT,
    amount DECIMAL(10,2) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('Pending', 'Paid', 'Overdue') DEFAULT 'Pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (order_id) REFERENCES sales_orders(order_id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Expenses Table
CREATE TABLE IF NOT EXISTS expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('Raw Materials', 'Labor', 'Utilities', 'Transportation', 'Other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Inventory Transactions Table (for tracking all inventory movements)
CREATE TABLE IF NOT EXISTS inventory_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    item_type ENUM('Raw Material', 'Finished Product') NOT NULL,
    item_id INT NOT NULL,
    transaction_type ENUM('In', 'Out', 'Adjustment') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    reference_type VARCHAR(50), -- e.g., 'Production', 'Purchase', 'Sale'
    reference_id INT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

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

-- Insert Sample Data
INSERT INTO users (username, password, role, full_name) VALUES
('admin', 'admin123', 'admin', 'System Administrator'),
('production', 'prod123', 'production', 'Production Manager'),
('warehouse', 'ware123', 'warehouse', 'Warehouse Staff'),
('qc', 'qc123', 'qc', 'Quality Control Inspector'),
('accounting', 'acc123', 'accounting', 'Accountant'),
('sales', 'sales123', 'sales', 'Sales Representative'),
('delivery', 'del123', 'delivery', 'Delivery Driver');

-- Insert Lorins Products
-- Insert Product Categories
INSERT INTO product_categories (category_name, description) VALUES
('Patis (Fish Sauce)', 'Fish sauce products under Lorins Patis Flavor brand'),
('Soy Sauce', 'Soy sauce products under Lorins brand'),
('Vinegar', 'Vinegar products including Coco Suka and value packs'),
('Alamang (Shrimp Paste)', 'Sauteed shrimp paste products'),
('Bagoong', 'Fermented fish products'),
('Specialty Products', 'Specialty items like crab paste and coconut milk'),
('Premium Products', 'Premium and extra-virgin products'),
('Variants', 'Special variants and limited editions');

-- Insert Products with Categories
INSERT INTO products (product_name, description, unit, category_id) VALUES
-- Patis (Fish Sauce) Products
('Lorins Patis Flavor 150 mL pouch', 'Lorins Patis Flavor 150 mL pouch', 'pcs', 1),
('Lorins Patis Flavor 350 mL PET bottle', 'Lorins Patis Flavor 350 mL PET bottle', 'pcs', 1),
('Lorins Patis Flavor with Chili 350 mL PET bottle', 'Lorins Patis Flavor with Chili 350 mL PET bottle', 'pcs', 1),
('Lorins Patis Flavor 1 L', 'Lorins Patis Flavor 1 Liter', 'pcs', 1),
('Lorins Patis Flavor 1893 mL (Half Gallon)', 'Lorins Patis Flavor 1893 mL (Half Gallon)', 'pcs', 1),
('Lorins Patis Flavor 3785 mL (Gallon)', 'Lorins Patis Flavor 3785 mL (Gallon)', 'pcs', 1),
-- Soy Sauce Products
('Lorins Soy Sauce 350 mL PET bottle', 'Lorins Soy Sauce 350 mL PET bottle', 'pcs', 2),
('Lorins Soy Sauce 1 L', 'Lorins Soy Sauce 1 Liter', 'pcs', 2),
('Lorins Soy Sauce 3785 mL (Gallon)', 'Lorins Soy Sauce 3785 mL (Gallon)', 'pcs', 2),
-- Vinegar Products
('Lorins Coco Suka 150 mL', 'Lorins Coco Suka 150 mL', 'pcs', 3),
('Lorins Coco Suka 310 mL', 'Lorins Coco Suka 310 mL', 'pcs', 3),
('Lorins Coco Suka 800 mL', 'Lorins Coco Suka 800 mL', 'pcs', 3),
('Lorins Budget / Value Pack', 'Lorins Budget / Value Pack (Vinegar + Fish Sauce + Soy Sauce)', 'pcs', 3),
-- Alamang (Sauteed Shrimp Paste) Products
('Lorins Alamang Guisado Original 8 oz / 250 g', 'Lorins Alamang Guisado Original 8 oz / 250 g', 'pcs', 4),
('Lorins Alamang Guisado Sweet', 'Lorins Alamang Guisado Sweet', 'pcs', 4),
('Lorins Alamang Guisado Spicy', 'Lorins Alamang Guisado Spicy', 'pcs', 4),
-- Bagoong (Fermented Fish)
('Lorenzana Bagoong Isda Original 310 mL', 'Lorenzana Bagoong Isda Original 310 mL', 'pcs', 5),
-- Specialty Products
('Lorins Crab Paste 8 oz', 'Lorins Crab Paste 8 oz', 'pcs', 6),
('Lorins Coconut Milk 400 mL', 'Lorins Coconut Milk 400 mL tin', 'pcs', 6),
-- Premium Products
('Lorins Premium Extra-Virgin Anchovy Extract 200 mL', 'Lorins Premium Extra-Virgin Anchovy Extract 200 mL', 'pcs', 7),
-- Variants
('Lorins Fish Sauce 800 mL glass bottle', 'Lorins Fish Sauce 800 mL glass bottle', 'pcs', 8),
('Lorins Fish Sauce with Chili & Kalamansi 310 mL', 'Lorins Fish Sauce with Chili & Kalamansi 310 mL', 'pcs', 8),
('Lorins Coco Suka Spicy-Sweet 310 mL', 'Lorins Coco Suka Spicy-Sweet 310 mL', 'pcs', 8);

INSERT INTO raw_materials (material_name, category, quantity, unit, min_stock_level) VALUES
('Soybeans', 'Raw Material', 500.00, 'kg', 100.00),
('Salt', 'Raw Material', 200.00, 'kg', 50.00),
('Sugar', 'Raw Material', 150.00, 'kg', 50.00);
