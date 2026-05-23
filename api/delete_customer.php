<?php
ob_start(); // Buffer output to prevent HTML leaking into JSON
header('Content-Type: application/json');
session_start();

// Suppress errors and include safely
@include "../db_connect.php";
ob_end_clean(); // Clear any buffered output from includes

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer id']);
    exit;
}

// Attempt delete
$del = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
if (!$del) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$del->bind_param('i', $customer_id);
if ($del->execute()) {
    if ($del->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Customer not found or could not be deleted']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $del->error]);
}
$del->close();
?>