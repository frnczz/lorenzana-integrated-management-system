<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $supplier_name = trim($_POST['supplier_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? trim($_POST['phone'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment_terms = trim($_POST['payment_terms'] ?? 'Net 30');
    $status = trim($_POST['status'] ?? 'Active');
    // notes column removed from suppliers table; ignore any posted notes
    $created_by = $_SESSION['user_id'];
    
    if (empty($supplier_name)) {
        $_SESSION['error'] = "Supplier name is required.";
        header("Location: ../procurement_suppliers.php" . ($supplier_id > 0 ? "?id=$supplier_id" : "?new=1"));
        exit;
    }
    
    $conn->begin_transaction();
    try {
        if ($supplier_id > 0) {
            // Update existing supplier - use contact_number column
                $stmt = $conn->prepare(
                    "UPDATE suppliers 
                    SET supplier_name = ?, contact_person = ?, contact_number = ?, email = ?, 
                        address = ?, payment_terms = ?, status = ?
                    WHERE supplier_id = ?"
                );
                $stmt->bind_param("sssssssi", $supplier_name, $contact_person, $contact_number, 
                                 $email, $address, $payment_terms, $status, $supplier_id);
        } else {
            // Generate supplier code for new supplier
            $supplier_code = generateReferenceId($conn, 'SUP');
            if (!$supplier_code) {
                throw new Exception("Could not generate supplier code.");
            }
            
            // Insert new supplier
                $stmt = $conn->prepare(
                    "INSERT INTO suppliers 
                    (supplier_code, supplier_name, contact_person, contact_number, email, address, payment_terms, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("ssssssss", $supplier_code, $supplier_name, $contact_person, $contact_number, $email, $address, $payment_terms, $status);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error saving supplier: " . $stmt->error);
        }
        
        if ($supplier_id == 0) {
            $supplier_id = $conn->insert_id;
        }
        $stmt->close();
        
        // Handle product deletions
        if (isset($_POST['delete_product']) && is_array($_POST['delete_product'])) {
            foreach ($_POST['delete_product'] as $sp_id) {
                $sp_id = intval($sp_id);
                $delete_stmt = $conn->prepare("DELETE FROM supplier_products WHERE sp_id = ? AND supplier_id = ?");
                $delete_stmt->bind_param("ii", $sp_id, $supplier_id);
                $delete_stmt->execute();
                $delete_stmt->close();
            }
        }
        
        // Update existing product prices
        if (isset($_POST['product_price']) && is_array($_POST['product_price'])) {
            foreach ($_POST['product_price'] as $sp_id => $price) {
                $sp_id = intval($sp_id);
                $price = floatval($price);
                $unit = trim($_POST['product_unit'][$sp_id] ?? 'kg');
                
                $update_stmt = $conn->prepare("UPDATE supplier_products SET unit_price = ?, unit = ? WHERE sp_id = ? AND supplier_id = ?");
                $update_stmt->bind_param("dsii", $price, $unit, $sp_id, $supplier_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        
        // Add new products
        if (isset($_POST['new_product_name']) && is_array($_POST['new_product_name'])) {
            $insert_stmt = $conn->prepare("
                INSERT INTO supplier_products 
                (supplier_id, material_id, product_id, item_name, item_type, unit_price, unit)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['new_product_name'] as $idx => $item_name) {
                $item_type = $_POST['new_product_type'][$idx] ?? 'Raw Material';
                $unit_price = floatval($_POST['new_product_price'][$idx] ?? 0);
                $unit = trim($_POST['new_product_unit'][$idx] ?? 'kg');
                
                $material_id = null;
                $product_id = null;
                
                if ($item_type === 'Raw Material' && isset($_POST['new_product_material_id'][$idx])) {
                    $material_id = intval($_POST['new_product_material_id'][$idx]);
                } elseif ($item_type === 'Product' && isset($_POST['new_product_product_id'][$idx])) {
                    $product_id = intval($_POST['new_product_product_id'][$idx]);
                }
                
                $insert_stmt->bind_param("iiissds", $supplier_id, $material_id, $product_id, $item_name, $item_type, $unit_price, $unit);
                 $insert_stmt->execute();
            }
            $insert_stmt->close();
        }
        
        $conn->commit();
        $_SESSION['success'] = "Supplier " . ($supplier_id > 0 ? "updated" : "created") . " successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: ../procurement_suppliers.php" . ($supplier_id > 0 ? "?id=$supplier_id" : ""));
    exit;
} else {
    header("Location: ../procurement_suppliers.php");
    exit;
}
?>
