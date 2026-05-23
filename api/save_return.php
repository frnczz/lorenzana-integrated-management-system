<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_service.php';

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse' && $_SESSION['role'] != 'procurement')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $po_id = !empty($_POST['po_id']) ? intval($_POST['po_id']) : null;
    $grn_id = !empty($_POST['grn_id']) ? intval($_POST['grn_id']) : null; // link back to GRN if provided
    $return_date = $_POST['return_date'] ?? date('Y-m-d');
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $created_by = $_SESSION['user_id'];
    
    if ($supplier_id <= 0 || empty($reason)) {
        $_SESSION['error'] = "Supplier and reason are required.";
        header("Location: ../procurement_return_form.php");
        exit;
    }
    
    $conn->begin_transaction();
    try {
        // Generate return number
        $return_number = generateReferenceId($conn, 'RET');
        if (!$return_number) {
            throw new Exception("Could not generate return number.");
        }
        
        // Calculate total amount
        $total_amount = 0;
        if (isset($_POST['subtotal']) && is_array($_POST['subtotal'])) {
            foreach ($_POST['subtotal'] as $subtotal) {
                $total_amount += floatval($subtotal);
            }
        }
        
        // Insert return
        $stmt = $conn->prepare("
            INSERT INTO supplier_returns 
            (return_number, po_id, grn_id, supplier_id, return_date, reason, total_amount, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("siiisdsdi", $return_number, $po_id, $grn_id, $supplier_id, 
                         $return_date, $reason, $total_amount, $notes, $created_by);
        
        if (!$stmt->execute()) {
            throw new Exception("Error saving return: " . $stmt->error);
        }
        
        $return_id = $conn->insert_id;
        $stmt->close();
        
        // Insert return items and emit event for inventory adjustment
        $return_items_payload = [];
        if (isset($_POST['material_id']) && is_array($_POST['material_id'])) {
            $item_stmt = $conn->prepare("
                INSERT INTO return_items 
                (return_id, material_id, item_name, quantity, unit, unit_price, subtotal, reason)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['material_id'] as $idx => $material_id) {
                $material_id = intval($material_id);
                $item_name = trim($_POST['item_name'][$idx] ?? '');
                $quantity = floatval($_POST['quantity'][$idx] ?? 0);
                $unit = trim($_POST['unit'][$idx] ?? 'kg');
                $unit_price = floatval($_POST['unit_price'][$idx] ?? 0);
                $subtotal = floatval($_POST['subtotal'][$idx] ?? 0);
                $item_reason = trim($_POST['item_reason'][$idx] ?? '');
                
                if ($quantity > 0 && $material_id > 0) {
                    $item_stmt->bind_param("iisdsdds", $return_id, $material_id, $item_name, $quantity, 
                                         $unit, $unit_price, $subtotal, $item_reason);
                    $item_stmt->execute();
                    $return_items_payload[] = ['material_id' => $material_id, 'quantity' => $quantity, 'created_by' => $created_by];
                }
            }
            $item_stmt->close();

            if (!empty($return_items_payload)) {
                emitSystemEvent($conn, 'supplier_return', $return_id, 'RETURN_PROCESSED', [
                    'return_id' => $return_id,
                    'items' => $return_items_payload
                ]);
                try {
                    processInventoryEvent($conn, 'RETURN_PROCESSED', [
                        'return_id' => $return_id,
                        'items' => $return_items_payload
                    ]);
                    $mp = $conn->prepare("UPDATE system_events SET processed = 1, processed_at = NOW() WHERE entity_type = 'supplier_return' AND entity_id = ? AND event_type = 'RETURN_PROCESSED'");
                    $mp->bind_param("i", $return_id);
                    $mp->execute();
                } catch (Exception $ex) {
                    throw $ex;
                }
            }
        }
        
        $conn->commit();
        $_SESSION['success'] = "Return created successfully! Return #$return_number. Inventory has been adjusted.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: ../procurement_returns.php");
    exit;
} else {
    header("Location: ../procurement_returns.php");
    exit;
}
?>
