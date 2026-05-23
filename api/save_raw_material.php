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
    $material_id = sanitizeInt($_POST['material_id'] ?? 0, 0);
    $material_name = sanitizeString($_POST['material_name'] ?? '', 100);
    $category = sanitizeString($_POST['category'] ?? '', 50);
    $quantity = sanitizeFloat($_POST['quantity'] ?? 0, 0);
    $unit = sanitizeEnum($_POST['unit'] ?? 'kg', ['kg','g','liters','ml','pcs','boxes','bags','sacks'], 'kg');
    $min_stock_level = sanitizeFloat($_POST['min_stock_level'] ?? 0, 0);
    $expiry_date = !empty($_POST['expiry_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $warehouse_location = sanitizeString($_POST['warehouse_location'] ?? '', 100);
    $new_category = sanitizeString($_POST['new_category'] ?? '', 50);
    $created_by = $_SESSION['user_id'];
    
    // Handle new category
    if ($category === '__new__' && !empty($new_category)) {
        $category = $new_category;
    }
    
    if (empty($material_name)) {
        $_SESSION['error'] = "Material name is required.";
        header("Location: ../inventory_raw_materials.php");
        exit;
    }
    
    if (empty($category)) {
        $_SESSION['error'] = "Category is required.";
        header("Location: ../inventory_raw_materials.php");
        exit;
    }
    
    if ($quantity < 0) {
        $_SESSION['error'] = "Quantity cannot be negative.";
        header("Location: ../inventory_raw_materials.php");
        exit;
    }
    
    if ($material_id > 0) {
        // Update existing material
        $stmt = $conn->prepare("
            UPDATE raw_materials 
            SET material_name = ?, category = ?, quantity = ?, unit = ?, 
                min_stock_level = ?, expiry_date = ?, warehouse_location = ?
            WHERE material_id = ?
        ");
        $stmt->bind_param("ssdssssi", $material_name, $category, $quantity, $unit, 
                         $min_stock_level, $expiry_date, $warehouse_location, $material_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Raw material updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating material: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Insert new material
        $material_code = generateReferenceId($conn, 'MAT');
        if (!$material_code) {
            $_SESSION['error'] = "Could not generate material code. Please try again.";
            header("Location: ../inventory_raw_materials.php");
            exit;
        }
        
        $stmt = $conn->prepare("
            INSERT INTO raw_materials 
            (material_code, material_name, category, quantity, unit, min_stock_level, expiry_date, warehouse_location) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssdssss", $material_code, $material_name, $category, $quantity, 
                         $unit, $min_stock_level, $expiry_date, $warehouse_location);
        
        if ($stmt->execute()) {
            $item_id = $conn->insert_id;
            
            // Create batch record for the new material
            $batch_number = generateReferenceId($conn, 'RMB');
            if (!$batch_number) {
                $batch_number = 'RMB-' . date('Ymd') . '-' . $item_id;
            }
            
            $batch_stmt = $conn->prepare("
                INSERT INTO raw_material_batches 
                (batch_number, material_id, quantity_received, quantity_remaining, unit, 
                 expiry_date, received_date, warehouse_location, qc_approved, created_by)
                VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, 1, ?)
            ");
            $batch_stmt->bind_param("siddssss", $batch_number, $item_id, $quantity, $quantity, 
                                   $unit, $expiry_date, $warehouse_location, $created_by);
            $batch_stmt->execute();
            $batch_id = $conn->insert_id;
            $batch_stmt->close();
            
            // Update the material to link to the batch
            $link_stmt = $conn->prepare("UPDATE raw_materials SET batch_id = ? WHERE material_id = ?");
            $link_stmt->bind_param("ii", $batch_id, $item_id);
            $link_stmt->execute();
            $link_stmt->close();
            
            // Log inventory transaction
            if (function_exists('logActivity')) {
                logActivity($conn, $created_by, 'create', 'raw_material', $item_id, "Material $material_code");
            }
            
            $_SESSION['success'] = "Raw material added successfully! Code: $material_code";
        } else {
            $_SESSION['error'] = "Error adding material: " . $stmt->error;
        }
        $stmt->close();
    }
    
    header("Location: ../inventory_raw_materials.php");
    exit;
} else {
    header("Location: ../inventory_raw_materials.php");
    exit;
}
?>
