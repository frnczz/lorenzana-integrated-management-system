<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse')) {
    header("Location: ../procurement_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../procurement_dashboard.php");
    exit;
}

$supplier_id = (int)($_POST['supplier_id'] ?? 0);
$delivery_date = $_POST['delivery_date'] ?? date('Y-m-d');
$reference = trim($_POST['reference'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$delivery_items_raw = trim($_POST['delivery_items'] ?? '');
$received_by = (int)$_SESSION['user_id'];

if ($supplier_id <= 0) {
    $_SESSION['error'] = "Select a supplier.";
    header("Location: ../procurement_dashboard.php");
    exit;
}

$conn->query("INSERT INTO supplier_deliveries (supplier_id, delivery_date, reference, notes, received_by) VALUES ($supplier_id, '" . $conn->real_escape_string($delivery_date) . "', '" . $conn->real_escape_string($reference) . "', '" . $conn->real_escape_string($notes) . "', $received_by)");
$delivery_id = (int)$conn->insert_id;
if ($delivery_id <= 0) {
    $_SESSION['error'] = "Failed to create delivery record.";
    header("Location: ../procurement_dashboard.php");
    exit;
}

$lines = array_filter(array_map('trim', explode("\n", str_replace("\r", "\n", $delivery_items_raw))));
$ins = $conn->prepare("INSERT INTO supplier_delivery_items (delivery_id, item_name, quantity, unit) VALUES (?, ?, ?, ?)");
foreach ($lines as $line) {
    $parts = array_map('trim', explode(',', $line, 3));
    $item_name = $parts[0] ?? '';
    $qty = (float)($parts[1] ?? 0);
    $unit = $parts[2] ?? 'kg';
    if ($item_name !== '' && $qty > 0) {
        $ins->bind_param("isds", $delivery_id, $item_name, $qty, $unit);
        $ins->execute();
    }
}
$ins->close();

if (function_exists('logActivity')) {
    logActivity($conn, $received_by, 'create', 'supplier_delivery', $delivery_id, "Delivery from supplier #$supplier_id");
}
$_SESSION['success'] = "Delivery recorded successfully.";
header("Location: ../procurement_dashboard.php");
exit;
