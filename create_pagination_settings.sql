CREATE TABLE IF NOT EXISTS pagination_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value VARCHAR(50) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT IGNORE INTO pagination_settings (setting_key, setting_value, description) VALUES
('items_per_page', '25', 'Default rows per page'),
('per_page_options', '10,25,50,100,200', 'Dropdown options');
