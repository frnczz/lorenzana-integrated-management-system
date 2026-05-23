<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

include "../db_connect.php";
include "../includes/functions.php";

$supplier_id     = intval($_POST['supplier_id']);
$supplier_name   = trim($_POST['supplier_name']);
$contact_person  = trim($_POST['contact_person']);
$contact_number  = trim($_POST['contact_number']);
$email           = trim($_POST['email']);
$address         = trim($_POST['address']);

$stmt = $conn->prepare("
    UPDATE suppliers
    SET supplier_name = ?, contact_person = ?, contact_number = ?, email = ?, address = ?
    WHERE supplier_id = ?
");
$stmt->bind_param(
    "sssssi",
    $supplier_name,
    $contact_person,
    $contact_number,
    $email,
    $address,
    $supplier_id
);

if ($stmt->execute()) {
    setMessage("Supplier updated successfully.", "success");
} else {
    setMessage("Failed to update supplier.", "error");
}

header("Location: ../procurement_suppliers.php");
exit;
