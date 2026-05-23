-- Create QC Settings Table
CREATE TABLE IF NOT EXISTS `qc_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `setting_key` varchar(50) NOT NULL UNIQUE,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qc_settings` (`setting_key`, `setting_value`, `description`) VALUES
('min_pass_score', '85', 'Minimum pass score percentage'),
('mandatory_fields', 'Appearance,Weight,Seal', 'Mandatory inspection fields'),
('auto_reject', '1', 'Auto-reject below pass score (1=enabled, 0=disabled)');

-- Create Sales Settings Table
CREATE TABLE IF NOT EXISTS `sales_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `setting_key` varchar(50) NOT NULL UNIQUE,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales_settings` (`setting_key`, `setting_value`, `description`) VALUES
('default_price', '100.00', 'Default product price in PHP'),
('max_discount', '10', 'Maximum discount percentage'),
('vat_rate', '12', 'VAT rate percentage'),
('payment_terms', 'Cash,30 Days', 'Allowed payment terms');

-- Create Warehouse Settings Table
CREATE TABLE IF NOT EXISTS `warehouse_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `setting_key` varchar(50) NOT NULL UNIQUE,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `warehouse_settings` (`setting_key`, `setting_value`, `description`) VALUES
('low_stock_threshold', '50', 'Alert when stock below this number'),
('expiry_warning_days', '30', 'Days before expiry to warn'),
('default_location', 'Main Warehouse', 'Default storage location'),
('stock_method', 'FIFO', 'Stock handling method (FIFO or FEFO)');

-- Create Production Settings Table
CREATE TABLE IF NOT EXISTS `production_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `setting_key` varchar(50) NOT NULL UNIQUE,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `production_settings` (`setting_key`, `setting_value`, `description`) VALUES
('default_batch_size', '100', 'Default batch size in units'),
('production_time_hours', '8', 'Estimated production time in hours'),
('expected_yield', '95', 'Expected yield percentage');

-- Create Accounting Settings Table
CREATE TABLE IF NOT EXISTS `accounting_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `setting_key` varchar(50) NOT NULL UNIQUE,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `accounting_settings` (`setting_key`, `setting_value`, `description`) VALUES
('vat_rate', '12', 'VAT rate percentage'),
('invoice_prefix', 'INV-2026-', 'Invoice prefix'),
('default_revenue_account', 'Sales Revenue', 'Default revenue account'),
('cutoff_day', '30', 'Monthly cut-off day');
