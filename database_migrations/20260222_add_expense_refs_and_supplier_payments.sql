-- Migration: add reference fields to expenses and create supplier_payments table

ALTER TABLE expenses
    ADD COLUMN reference_type VARCHAR(50) NULL AFTER description,
    ADD COLUMN reference_id INT NULL AFTER reference_type,
    ADD COLUMN department VARCHAR(100) NULL AFTER reference_id;

CREATE TABLE IF NOT EXISTS supplier_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NOT NULL,
  payment_date DATE NOT NULL,
  payment_method VARCHAR(64) DEFAULT NULL,
  amount DECIMAL(18,2) NOT NULL,
  reference_number VARCHAR(128) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
