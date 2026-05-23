<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $item_name = trim($_POST['item_name'] ?? '');
    $quantity = floatval($_POST['quantity'] ?? 0);
    $unit = $_POST['unit'] ?? 'kg';
    $expected_delivery_date = $_POST['expected_delivery_date'] ?? null;
    $requested_by = $_SESSION['user_id'];

    // Validate required fields
    if ($supplier_id <= 0 || empty($item_name) || $quantity <= 0) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: ../procurement_requests.php");
        exit;
    }

    // Auto-generate PR number
    $pr_number = generateReferenceId($conn, 'PR');
    if (!$pr_number) {
        $_SESSION['error'] = "Could not generate PR number. Please try again.";
        header("Location: ../procurement_requests.php");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO purchase_requests (pr_number, supplier_id, item_name, quantity, unit, expected_delivery_date, requested_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("sisdssi", $pr_number, $supplier_id, $item_name, $quantity, $unit, $expected_delivery_date, $requested_by);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Purchase request created successfully! PR Number: " . $pr_number;
        } else {
            $_SESSION['error'] = "Error creating purchase request: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error: " . $conn->error;
    }

    header("Location: ../procurement_requests.php");
    exit;
} else {
    header("Location: ../procurement_requests.php");
    exit;
}
?>
