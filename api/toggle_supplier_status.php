<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include "../db_connect.php";
include "../includes/functions.php";

if (!isset($_POST['supplier_id'], $_POST['status'])) {
    header("Location: ../procurement_suppliers.php");
    exit;
}

$supplier_id = intval($_POST['supplier_id']);
$status = ($_POST['status'] === 'active') ? 'active' : 'inactive';

$stmt = $conn->prepare("
    UPDATE suppliers
    SET status = ?
    WHERE supplier_id = ?
");
$stmt->bind_param("si", $status, $supplier_id);

if ($stmt->execute()) {
    if ($status === 'active') {
        setMessage("Supplier reactivated successfully.", "success");
    } else {
        setMessage("Supplier deactivated successfully.", "success");
    }
} else {
    setMessage("Failed to update supplier status.", "error");
}

header("Location: ../procurement_suppliers.php");
exit;
