<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','sales'])) {
    header("Location: ../login.php");
    exit;
}

include "../db_connect.php";
include "../includes/functions.php";

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMessage("Invalid request method.", "error");
    header("Location: ../sales_request_production.php");
    exit;
}

// Sanitize inputs
$sales_order_id = !empty($_POST['sales_order_id']) ? (int)$_POST['sales_order_id'] : 0;
$customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
$customer_name  = trim($_POST['customer_name'] ?? '');
$user_id        = (int)$_SESSION['user_id'];

// Raw arrays from form
$product_ids    = $_POST['product_id'] ?? [];
$requested_qtys = $_POST['requested_qty'] ?? [];
$reasons        = $_POST['reason'] ?? [];

// If customer_name not provided but customer_id is, fetch name
if ($customer_name === '' && $customer_id > 0) {
    $cr = $conn->prepare("SELECT customer_name FROM customers WHERE customer_id = ? LIMIT 1");
    if ($cr) {
        $cr->bind_param('i', $customer_id);
        $cr->execute();
        $res = $cr->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $customer_name = $row['customer_name'];
        }
        $cr->close();
    }
}

// Build valid lines array (filter out incomplete rows)
$lines = [];
foreach ($product_ids as $i => $pid_raw) {
    $pid = (int)$pid_raw;
    $qty = isset($requested_qtys[$i]) ? (float)$requested_qtys[$i] : 0;
    $reason = trim($reasons[$i] ?? 'Customer Order');
    if ($pid > 0 && $qty > 0) {
        $lines[] = ['product_id' => $pid, 'qty' => $qty, 'reason' => $reason];
    }
}

if ($customer_name === '' || count($lines) === 0) {
    setMessage("Please complete all required fields.", "error");
    header("Location: ../sales_request_production.php");
    exit;
}

// One group ID per submission
$request_group_id = 'PRG-' . date('YmdHis') . '-' . substr(uniqid(), -4);
$reason_allowed = ['Customer Order', 'Low Stock', 'Custom Order'];

// Check if request_group_id column exists
$has_group = false;
$col_check = $conn->query("SHOW COLUMNS FROM production_requests LIKE 'request_group_id'");
if ($col_check && $col_check->num_rows > 0) $has_group = true;

// Prepare statement
if ($has_group) {
    $stmt = $conn->prepare("INSERT INTO production_requests (sales_order_id, customer_name, product_id, requested_qty, reason, status, requested_by, request_group_id) VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?)");
} else {
    $stmt = $conn->prepare("INSERT INTO production_requests (sales_order_id, customer_name, product_id, requested_qty, reason, status, requested_by) VALUES (?, ?, ?, ?, ?, 'Pending', ?)");
}

if (!$stmt) {
    setMessage("Database error: " . $conn->error, "error");
    header("Location: ../sales_request_production.php");
    exit;
}

// Loop through validated lines and insert
foreach ($lines as $ln) {
    $pid = $ln['product_id'];
    $qty = $ln['qty'];
    $reason = in_array($ln['reason'], $reason_allowed, true) ? $ln['reason'] : 'Customer Order';

    if ($has_group) {
        // types: sales_order_id(i), customer_name(s), product_id(i), requested_qty(d), reason(s), requested_by(i), request_group_id(s)
        $stmt->bind_param("isidiss", $sales_order_id, $customer_name, $pid, $qty, $reason, $user_id, $request_group_id);
    } else {
        // types: sales_order_id(i), customer_name(s), product_id(i), requested_qty(d), reason(s), requested_by(i)
        $stmt->bind_param("isidsi", $sales_order_id, $customer_name, $pid, $qty, $reason, $user_id);
    }
    $stmt->execute();
}

$stmt->close();

setMessage("Production request successfully sent.", "success");
header("Location: ../sales_request_production.php");
exit;