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
    $po_id = intval($_POST['po_id'] ?? 0);
    $pr_id = !empty($_POST['pr_id']) ? intval($_POST['pr_id']) : null;
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $order_date = $_POST['order_date'] ?? date('Y-m-d');
    $expected_delivery_date = !empty($_POST['expected_delivery_date']) ? $_POST['expected_delivery_date'] : null;
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $payment_terms = trim($_POST['payment_terms'] ?? 'Net 30');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $tax_amount = floatval($_POST['tax_amount'] ?? 0);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $created_by = $_SESSION['user_id'];
    
    if ($supplier_id <= 0) {
        $_SESSION['error'] = "Supplier is required.";
        header("Location: ../procurement_orders.php" . ($po_id > 0 ? "?id=$po_id" : "?new=1"));
        exit;
    }
    
    $conn->begin_transaction();
    try {
        if ($po_id > 0) {
            // Update existing PO
            $stmt = $conn->prepare("
                UPDATE purchase_orders 
                SET supplier_id = ?, order_date = ?, expected_delivery_date = ?, delivery_address = ?, 
                    payment_terms = ?, contact_number = ?, subtotal = ?, tax_amount = ?, total_amount = ?, notes = ?
                WHERE po_id = ? AND status = 'Open'
            ");
            $stmt->bind_param("isssssdddsi", $supplier_id, $order_date, $expected_delivery_date, $delivery_address,
                             $payment_terms, $contact_number, $subtotal, $tax_amount, $total_amount, $notes, $po_id);
        } else {
            // Generate PO number for new PO
            $po_number = generateReferenceId($conn, 'PO');
            if (!$po_number) {
                throw new Exception("Could not generate PO number.");
            }
            
            // Insert new PO
            $stmt = $conn->prepare("
                INSERT INTO purchase_orders 
                (po_number, pr_id, supplier_id, order_date, expected_delivery_date, delivery_address, 
                 payment_terms, contact_number, subtotal, tax_amount, total_amount, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            // types: s=po_number, i=pr_id, i=supplier_id, s=order_date, s=expected_delivery_date,
            // s=delivery_address, s=payment_terms, s=contact_number, d=subtotal, d=tax_amount, d=total_amount,
            // s=notes, i=created_by
            $stmt->bind_param("siisssssdddsi", $po_number, $pr_id, $supplier_id, $order_date, $expected_delivery_date,
                             $delivery_address, $payment_terms, $contact_number, $subtotal, $tax_amount, $total_amount, $notes, $created_by);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error saving PO: " . $stmt->error);
        }
        
        if ($po_id == 0) {
            $po_id = $conn->insert_id;
        }
        $stmt->close();
        
        // Insert items
        if (isset($_POST['item_name']) && is_array($_POST['item_name'])) {
            $item_stmt = $conn->prepare("
                INSERT INTO po_items 
                (po_id, material_id, item_name, item_type, quantity_ordered, unit, unit_price, subtotal, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['item_name'] as $idx => $item_name) {
                $notes_value = trim($_POST['notes'][$idx] ?? '');
                $item_type = 'Raw Material';
                $quantity = floatval($_POST['quantity'][$idx] ?? 0);
                $unit = trim($_POST['unit'][$idx] ?? 'kg');
                $unit_price = floatval($_POST['unit_price'][$idx] ?? 0);
                $item_subtotal = floatval($_POST['subtotal'][$idx] ?? 0);
                
                $material_id = !empty($_POST['material_id'][$idx]) ? intval($_POST['material_id'][$idx]) : null;

                // Bind parameters (types: i=int, d=decimal/float, s=string)
                // po_id (i), material_id (i), item_name (s), item_type (s), quantity (d), unit (s), unit_price (d), item_subtotal (d), notes (s)
                $item_stmt->bind_param(
                    "iissdsdds",
                    $po_id,
                    $material_id,
                    $item_name,
                    $item_type,
                    $quantity,
                    $unit,
                    $unit_price,
                    $item_subtotal,
                    $notes_value
                );
                
                $item_stmt->execute();
            }
            
            $item_stmt->close();
        }
        
        $conn->commit();
        $final_po_number = $po_id > 0 ? ($conn->query("SELECT po_number FROM purchase_orders WHERE po_id = $po_id")->fetch_assoc()['po_number'] ?? '') : $po_number;
        $_SESSION['success'] = "Purchase Order " . ($po_id > 0 ? "updated" : "created") . " successfully! PO: " . $final_po_number;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: ../procurement_order_view.php?id=$po_id");
    exit;
} else {
    header("Location: ../procurement_orders.php");
    exit;
}
?>
