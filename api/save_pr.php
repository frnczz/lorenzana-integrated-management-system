<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement' && $_SESSION['role'] != 'warehouse')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pr_id = intval($_POST['pr_id'] ?? 0);
    $department = trim($_POST['department'] ?? '');
    $request_date = $_POST['request_date'] ?? date('Y-m-d');
    $required_date = !empty($_POST['required_date']) ? $_POST['required_date'] : null;
    $justification = trim($_POST['justification'] ?? '');
    $action = $_POST['action'] ?? 'save_draft';
    $requested_by = $_SESSION['user_id'];
    
    if (empty($justification) || empty($department)) {
        $_SESSION['error'] = "Department and justification are required.";
        header("Location: ../procurement_requisitions.php" . ($pr_id > 0 ? "?id=$pr_id" : "?new=1"));
        exit;
    }
    
    $status = $action === 'submit' ? 'Submitted' : 'Draft';
    
    $conn->begin_transaction();
    try {
        if ($pr_id > 0) {
            // Update existing PR
            $stmt = $conn->prepare("
                UPDATE purchase_requisitions 
                SET department = ?, request_date = ?, required_date = ?, justification = ?, status = ?
                WHERE pr_id = ? AND status = 'Draft'
            ");
            $stmt->bind_param("sssssi", $department, $request_date, $required_date, $justification, $status, $pr_id);
        } else {
            // Generate PR number
            $pr_number = generateReferenceId($conn, 'PR');
            if (!$pr_number) {
                throw new Exception("Could not generate PR number.");
            }
            
            // Insert new PR
            $stmt = $conn->prepare("
                INSERT INTO purchase_requisitions 
                (pr_number, department, requested_by, request_date, required_date, justification, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssissss", $pr_number, $department, $requested_by, $request_date, $required_date, $justification, $status);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error saving PR: " . $stmt->error);
        }
        
        if ($pr_id == 0) {
            $pr_id = $conn->insert_id;
        }
        $stmt->close();
        
        // Delete existing items if updating
        if ($pr_id > 0) {
            $conn->query("DELETE FROM pr_items WHERE pr_id = $pr_id");
        }
        
        // Insert items
        if (isset($_POST['item_name']) && is_array($_POST['item_name'])) {
            $total_cost = 0;
            $item_stmt = $conn->prepare("
                INSERT INTO pr_items 
                (pr_id, material_id, item_name, item_type, quantity, unit, estimated_unit_price, estimated_total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['item_name'] as $idx => $item_name) {
                $item_type = 'Raw Material';
                $quantity = floatval($_POST['quantity'][$idx] ?? 0);
                $unit = trim($_POST['unit'][$idx] ?? 'kg');
                $estimated_price = floatval($_POST['estimated_price'][$idx] ?? 0);
                $estimated_total = $quantity * $estimated_price;
                
                $material_id = !empty($_POST['material_id'][$idx]) ? intval($_POST['material_id'][$idx]) : null;
                
                $item_stmt->bind_param("iissdsdd", $pr_id, $material_id, $item_name, $item_type, $quantity, $unit, $estimated_price, $estimated_total);
                $item_stmt->execute();
                
                $total_cost += $estimated_total;
            }
            $item_stmt->close();
            
            // Update total cost
            $update_total = $conn->prepare("UPDATE purchase_requisitions SET total_estimated_cost = ? WHERE pr_id = ?");
            $update_total->bind_param("di", $total_cost, $pr_id);
            $update_total->execute();
            $update_total->close();
        }
        
        $conn->commit();
        $_SESSION['success'] = "Purchase requisition " . ($pr_id > 0 ? "updated" : "created") . " successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: ../procurement_requisition_view.php?id=$pr_id");
    exit;
} else {
    header("Location: ../procurement_requisitions.php");
    exit;
}
?>
