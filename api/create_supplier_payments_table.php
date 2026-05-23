<?php
// Admin-only helper to create supplier_payments table if missing.
session_start();
include "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin')) {
    http_response_code(403);
    echo "Forbidden: admin only.";
    exit;
}

$sql = "CREATE TABLE IF NOT EXISTS supplier_payments (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "supplier_payments table created or already exists.";
} else {
    echo "Error creating table: " . $conn->error;
}

?>