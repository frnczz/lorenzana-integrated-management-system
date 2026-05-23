<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

// --- Authentication ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../inventory_items.php");
    exit;
}

// --- Get POST data ---
$item_name = trim($_POST['item_name'] ?? '');
$material_id = (int)($_POST['material_id'] ?? 0);
$category = $_POST['category'] ?? 'Raw Material';
$quantity = floatval($_POST['quantity'] ?? 0);
$unit = $_POST['unit'] ?? 'kg';
$expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
$warehouse_location = trim($_POST['warehouse_location'] ?? '');
$created_by = $_SESSION['user_id'];

// --- Validation ---
if ($quantity <= 0) {
    $_SESSION['error'] = "Quantity is required and must be greater than zero.";
    header("Location: ../inventory_items.php");
    exit;
}
if ($material_id <= 0 && empty($item_name)) {
    $_SESSION['error'] = "Select an existing item or enter a new material name.";
    header("Location: ../inventory_items.php");
    exit;
}

// Allowed warehouse locations
$valid_locations = [
    'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',
    'Royal GoldCraft Warehouse 4, Lower MagsaysayRoad, Brgy. San Antonio San Pedro 4023 Laguna'
];
if (!empty($warehouse_location) && !in_array($warehouse_location, $valid_locations)) {
    $_SESSION['error'] = "Invalid warehouse location selected.";
    header("Location: ../inventory_items.php");
    exit;
}

// --- Add to existing raw material (add stock) ---
if ($material_id > 0 && $category === 'Raw Material') {
    $upd = $conn->prepare("UPDATE raw_materials SET quantity = quantity + ?, expiry_date = COALESCE(?, expiry_date), updated_at = NOW() WHERE material_id = ?");
    $upd->bind_param("dsi", $quantity, $expiry_date, $material_id);
    if ($upd->execute()) {
        $trans_stmt = $conn->prepare("
            INSERT INTO inventory_transactions 
            (item_type, item_id, transaction_type, quantity, notes, created_by) 
            VALUES ('Raw Material', ?, 'In', ?, 'Stock added from inventory form', ?)
        ");
        $trans_stmt->bind_param("idi", $material_id, $quantity, $created_by);
        $trans_stmt->execute();
        $trans_stmt->close();
        $_SESSION['success'] = "Stock added successfully to existing raw material.";
    } else {
        $_SESSION['error'] = "Error updating raw material: " . $conn->error;
    }
    $upd->close();
    header("Location: ../inventory_items.php");
    exit;
}

// --- Only Raw Materials can be added manually (new material) ---
if ($category === 'Raw Material' && !empty($item_name)) {
    $material_code = generateReferenceId($conn, 'MAT');
    if (!$material_code) {
        $_SESSION['error'] = "Could not generate material code. Please try again.";
        header("Location: ../inventory_items.php");
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO raw_materials 
        (material_code, material_name, category, quantity, unit, expiry_date, warehouse_location) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssdsss", $material_code, $item_name, $category, $quantity, $unit, $expiry_date, $warehouse_location);

    if ($stmt->execute()) {
        $item_id = $conn->insert_id;

        // Log inventory transaction
        $trans_stmt = $conn->prepare("
            INSERT INTO inventory_transactions 
            (item_type, item_id, transaction_type, quantity, notes, created_by) 
            VALUES ('Raw Material', ?, 'In', ?, 'Initial stock entry', ?)
        ");
        $trans_stmt->bind_param("idi", $item_id, $quantity, $created_by);
        $trans_stmt->execute();
        $trans_stmt->close();

        $_SESSION['success'] = "Raw material added successfully! Code: $material_code";
    } else {
        $_SESSION['error'] = "Error adding raw material: " . $stmt->error;
    }
    $stmt->close();
} else {
    // Finished products should NOT be added directly
    $_SESSION['error'] = "Finished products must be added via Production → QC workflow only.";
}

header("Location: ../inventory_items.php");
exit;
