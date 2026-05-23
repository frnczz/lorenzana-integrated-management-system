<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include "../db_connect.php";
include "../includes/functions.php";

if (!isset($_POST['supplier_id'])) {
    header("Location: ../procurement_suppliers.php");
    exit;
}

$supplier_id = intval($_POST['supplier_id']);

$stmt = $conn->prepare("
    UPDATE suppliers
    SET status = 'inactive'
    WHERE supplier_id = ?
");
$stmt->bind_param("i", $supplier_id);

if ($stmt->execute()) {
    setMessage("Supplier deactivated successfully.", "success");
} else {
    setMessage("Failed to deactivate supplier.", "error");
}

header("Location: ../procurement_suppliers.php");
exit;
