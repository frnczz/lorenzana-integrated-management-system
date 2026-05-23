-- ============================================
-- QC MODULE FOR RAW MATERIALS (PROCUREMENT)
-- ============================================

-- QC Records for Raw Materials (from GRN)
CREATE TABLE IF NOT EXISTS raw_material_qc (
    qc_id INT AUTO_INCREMENT PRIMARY KEY,
    qc_number VARCHAR(50) UNIQUE NOT NULL,
    grn_id INT NOT NULL,
    grn_item_id INT NOT NULL,
    material_id INT,
    item_name VARCHAR(200) NOT NULL,
    lot_number VARCHAR(100),
    quantity_received DECIMAL(10,2) NOT NULL,
    quantity_accepted DECIMAL(10,2) DEFAULT 0.00,
    quantity_rejected DECIMAL(10,2) DEFAULT 0.00,
    
    -- QC Checklist Fields
    packaging_status ENUM('Intact', 'Damaged', 'Partial') DEFAULT 'Intact',
    label_accuracy ENUM('Correct', 'Incorrect', 'Missing') DEFAULT 'Correct',
    quantity_check ENUM('Pass', 'Fail', 'Conditional') DEFAULT 'Pass',
    expiry_check ENUM('Pass', 'Fail', 'Conditional') DEFAULT 'Pass',
    expiry_date DATE,
    
    -- Custom QC Fields (for food industry)
    ph_level DECIMAL(5,2) NULL,
    salt_percentage DECIMAL(5,2) NULL,
    odor_test ENUM('Pass', 'Fail', 'Conditional') DEFAULT 'Pass',
    color_check ENUM('Pass', 'Fail', 'Conditional') DEFAULT 'Pass',
    texture_check ENUM('Pass', 'Fail', 'Conditional') DEFAULT 'Pass',
    
    -- Overall QC Status
    qc_status ENUM('Pending', 'Passed', 'Failed', 'Conditional') DEFAULT 'Pending',
    qc_remarks TEXT,
    
    -- Approval Workflow
    approval_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    supervisor_remarks TEXT,
    
    -- Inspector Info
    inspected_by INT NOT NULL,
    inspection_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (grn_id) REFERENCES goods_receiving_notes(grn_id) ON DELETE CASCADE,
    FOREIGN KEY (grn_item_id) REFERENCES grn_items(grn_item_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE SET NULL,
    FOREIGN KEY (inspected_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- QC Rules Configuration (for automated QC)
CREATE TABLE IF NOT EXISTS qc_rules (
    rule_id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    rule_type ENUM('Quantity', 'Expiry', 'Custom') DEFAULT 'Quantity',
    condition_field VARCHAR(50),
    operator ENUM('>', '<', '>=', '<=', '==', '!=') DEFAULT '>=',
    threshold_value DECIMAL(10,2),
    action ENUM('Auto Pass', 'Auto Fail', 'Flag Conditional') DEFAULT 'Flag Conditional',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default QC rules
INSERT INTO qc_rules (rule_name, category, rule_type, condition_field, operator, threshold_value, action) VALUES
('Quantity Check - 90%', 'All', 'Quantity', 'quantity_received', '>=', 90.00, 'Auto Pass'),
('Quantity Check - Below 90%', 'All', 'Quantity', 'quantity_received', '<', 90.00, 'Flag Conditional'),
('Expiry Check - Less than 1 month', 'All', 'Expiry', 'days_to_expiry', '<', 30.00, 'Flag Conditional'),
('Expiry Check - Expired', 'All', 'Expiry', 'days_to_expiry', '<', 0.00, 'Auto Fail');

-- Update grn_items to track QC status
ALTER TABLE grn_items 
ADD COLUMN IF NOT EXISTS qc_record_id INT NULL,
ADD FOREIGN KEY (qc_record_id) REFERENCES raw_material_qc(qc_id) ON DELETE SET NULL;

-- Indexes
CREATE INDEX idx_qc_status ON raw_material_qc(qc_status);
CREATE INDEX idx_qc_approval ON raw_material_qc(approval_status);
CREATE INDEX idx_qc_grn ON raw_material_qc(grn_id);
CREATE INDEX idx_qc_pending ON raw_material_qc(qc_status, approval_status);
