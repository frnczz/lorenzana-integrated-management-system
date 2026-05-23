

CREATE TABLE IF NOT EXISTS id_sequences (
    prefix   VARCHAR(10) NOT NULL,
    seq_date DATE        NOT NULL,
    last_seq INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (prefix, seq_date)
) ENGINE=InnoDB;

-- Optional reference columns for consistency (nullable so existing rows are valid)
ALTER TABLE suppliers ADD COLUMN supplier_code VARCHAR(50) NULL UNIQUE;
ALTER TABLE raw_materials ADD COLUMN material_code VARCHAR(50) NULL UNIQUE;
ALTER TABLE customers ADD COLUMN customer_code VARCHAR(50) NULL UNIQUE;
ALTER TABLE expenses ADD COLUMN expense_ref VARCHAR(50) NULL UNIQUE;
ALTER TABLE qc_records ADD COLUMN qc_number VARCHAR(50) NULL UNIQUE;
ALTER TABLE delivery_assignments ADD COLUMN assignment_number VARCHAR(50) NULL UNIQUE;
ALTER TABLE users ADD COLUMN user_code VARCHAR(50) NULL UNIQUE;
ALTER TABLE attendance ADD COLUMN attendance_ref VARCHAR(50) NULL UNIQUE;
ALTER TABLE payroll ADD COLUMN payroll_ref VARCHAR(50) NULL UNIQUE;
