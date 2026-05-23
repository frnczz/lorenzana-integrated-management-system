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
    $grn_id = intval($_POST['grn_id'] ?? 0);
    $po_id = intval($_POST['po_id'] ?? 0);
    $received_date = $_POST['received_date'] ?? date('Y-m-d');
    $qc_status = $_POST['qc_status'] ?? 'Pending';
    $qc_remarks = trim($_POST['qc_remarks'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $received_by = $_SESSION['user_id'];
    $qc_checked_by = ($qc_status !== 'Pending') ? $_SESSION['user_id'] : null;
    
    if ($po_id <= 0) {
        $_SESSION['error'] = "Purchase Order is required.";
        header("Location: ../procurement_receiving.php" . ($po_id > 0 ? "?po_id=$po_id" : "?new=1"));
        exit;
    }
    
    $conn->begin_transaction();
    try {
        // Generate GRN number
        $grn_number = generateReferenceId($conn, 'GRN');
        if (!$grn_number) {
            throw new Exception("Could not generate GRN number.");
        }
        
        // Determine status based on QC
        $status = 'Draft';
        if ($qc_status === 'Passed') {
            $status = 'QC Passed';
        } elseif ($qc_status === 'Failed') {
            $status = 'QC Failed';
        } elseif ($qc_status === 'Partial') {
            $status = 'Partially Received';
        }
        
        // Insert or update GRN
        if ($grn_id > 0) {
            $grn_stmt = $conn->prepare("
                UPDATE goods_receiving_notes 
                SET received_date = ?, qc_status = ?, qc_checked_by = ?, qc_checked_at = NOW(), 
                    qc_remarks = ?, status = ?, notes = ?
                WHERE grn_id = ?
            ");
            $grn_stmt->bind_param("ssisssi", $received_date, $qc_status, $qc_checked_by, $qc_remarks, $status, $notes, $grn_id);
        } else {
            $grn_stmt = $conn->prepare("
                INSERT INTO goods_receiving_notes 
                (grn_number, po_id, received_date, received_by, qc_status, qc_checked_by, qc_checked_at, qc_remarks, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
            ");
            $grn_stmt->bind_param("sisisssss", $grn_number, $po_id, $received_date, $received_by, 
                                 $qc_status, $qc_checked_by, $qc_remarks, $status, $notes);
        }
        
        if (!$grn_stmt->execute()) {
            throw new Exception("Error saving GRN: " . $grn_stmt->error);
        }
        
        if ($grn_id == 0) {
            $grn_id = $conn->insert_id;
        }
        $grn_stmt->close();
        
        // Process GRN items
        if (isset($_POST['po_item_id']) && is_array($_POST['po_item_id'])) {
            // Delete existing items if updating
            if ($grn_id > 0) {
                $conn->query("DELETE FROM grn_items WHERE grn_id = $grn_id");
                $conn->query("DELETE FROM raw_material_qc WHERE grn_id = $grn_id");
            }
            
            $total_items = 0;
            $item_stmt = $conn->prepare("
                INSERT INTO grn_items 
                (grn_id, po_item_id, material_id, product_id, item_name, quantity_received, quantity_accepted, 
                 quantity_rejected, unit, lot_number, expiry_date, warehouse_location, qc_status, qc_remarks, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['po_item_id'] as $idx => $po_item_id) {
                $po_item_id = intval($po_item_id);
                $quantity_received = floatval($_POST['quantity_received'][$idx] ?? 0);
                $quantity_accepted = floatval($_POST['quantity_accepted'][$idx] ?? $quantity_received);
                $quantity_rejected = floatval($_POST['quantity_rejected'][$idx] ?? 0);
                $item_name = trim($_POST['item_name'][$idx] ?? '');
                $unit = trim($_POST['unit'][$idx] ?? 'kg');
                $lot_number = trim($_POST['lot_number'][$idx] ?? '');
                $expiry_date = !empty($_POST['expiry_date'][$idx]) ? $_POST['expiry_date'][$idx] : null;
                $warehouse_location = trim($_POST['warehouse_location'][$idx] ?? '');
                $item_qc_status = trim($_POST['item_qc_status'][$idx] ?? 'Pending');
                $item_qc_remarks = trim($_POST['item_qc_remarks'][$idx] ?? '');
                $unit_price = floatval($_POST['unit_price'][$idx] ?? 0);
                $subtotal = $quantity_accepted * $unit_price;
                
                $material_id = !empty($_POST['material_id'][$idx]) ? intval($_POST['material_id'][$idx]) : null;
                $product_id = !empty($_POST['product_id'][$idx]) ? intval($_POST['product_id'][$idx]) : null;
                
                if ($quantity_received > 0) {
                    $item_stmt->bind_param("iiisdddsdsssssdd", $grn_id, $po_item_id, $material_id, $product_id, 
                                         $item_name, $quantity_received, $quantity_accepted, $quantity_rejected,
                                         $unit, $lot_number, $expiry_date, $warehouse_location, 
                                         $item_qc_status, $item_qc_remarks, $unit_price, $subtotal);
                    $item_stmt->execute();
                    $grn_item_id = $conn->insert_id; // Get inserted grn_item_id
                    $total_items++;
                    
                    // Update PO item received quantity
                    $update_po_item = $conn->prepare("
                        UPDATE po_items 
                        SET quantity_received = quantity_received + ? 
                        WHERE po_item_id = ?
                    ");
                    $update_po_item->bind_param("di", $quantity_accepted, $po_item_id);
                    $update_po_item->execute();
                    $update_po_item->close();
                    
                    // AUTO-CREATE QC RECORD for all items (create QC record for inspection)
                    if ($quantity_received > 0) {
                        // Generate QC number
                        $qc_number = generateReferenceId($conn, 'QC');
                        if (!$qc_number) {
                            // fallback id so we always have something non-null
                            $qc_number = 'QC-'.date('YmdHis').'-'.mt_rand(1000,9999);
                        }
                        // always create QC record below
                            // Create QC record (Pending status - will be inspected by QC module)
                            // If QC was already passed during receiving, set initial values
                            $qc_quantity_accepted = ($item_qc_status === 'Passed') ? $quantity_accepted : 0;
                            $qc_quantity_rejected = ($item_qc_status === 'Failed') ? $quantity_received : 0;
                            $initial_qc_status = ($item_qc_status === 'Passed') ? 'Passed' : (($item_qc_status === 'Failed') ? 'Failed' : 'Pending');
                            
                            $inspection_date = date('Y-m-d');
                            $qc_stmt = $conn->prepare("
                                INSERT INTO raw_material_qc 
                                (qc_number, grn_id, grn_item_id, material_id, item_name, lot_number, quantity_received, 
                                 quantity_accepted, quantity_rejected, expiry_date, qc_status, qc_remarks, inspected_by, inspection_date, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                            ");
                            
                            $qc_stmt->bind_param("siiisdddsdsssi", $qc_number, $grn_id, $grn_item_id, $material_id, 
                                                $item_name, $lot_number, $quantity_received, 
                                                $qc_quantity_accepted, $qc_quantity_rejected, $expiry_date,
                                                $initial_qc_status, $item_qc_remarks, $received_by, $inspection_date);
                            
                            if (!$qc_stmt->execute()) {
                                throw new Exception("Error creating QC record: " . $qc_stmt->error);
                            }
                            
                            $qc_id = $conn->insert_id;
                            $qc_stmt->close();
                            
                            // Update grn_item with qc_record_id
                            $update_grn_qc = $conn->prepare("UPDATE grn_items SET qc_record_id = ? WHERE grn_item_id = ?");
                            $update_grn_qc->bind_param("ii", $qc_id, $grn_item_id);
                            $update_grn_qc->execute();
                            $update_grn_qc->close();
                            
                            // If QC was passed during receiving: auto-approve and emit event (event-driven stock-in)
                            if ($item_qc_status === 'Passed' && $quantity_accepted > 0 && $material_id > 0) {
                                $auto_approve = $conn->prepare("UPDATE raw_material_qc SET approval_status = 'Approved', approved_by = ?, approved_at = NOW() WHERE qc_id = ?");
                                $auto_approve->bind_param("ii", $received_by, $qc_id);
                                $auto_approve->execute();
                                $auto_approve->close();

                                // Emit event - event processor handles stock (or process inline for instant effect)
                                emitSystemEvent($conn, 'raw_material_qc', $qc_id, 'QC_APPROVED_RAW', [
                                    'items' => [[
                                        'material_id' => $material_id,
                                        'quantity' => $quantity_accepted,
                                        'expiry_date' => $expiry_date,
                                        'warehouse_location' => $warehouse_location,
                                        'grn_item_id' => $grn_item_id,
                                        'qc_id' => $qc_id,
                                        'item_name' => $item_name,
                                        'unit' => $unit,
                                        'created_by' => $received_by
                                    ]]
                                ]);
                                try {
                                    processInventoryEvent($conn, 'QC_APPROVED_RAW', [
                                        'items' => [[
                                            'material_id' => $material_id,
                                            'quantity' => $quantity_accepted,
                                            'expiry_date' => $expiry_date,
                                            'warehouse_location' => $warehouse_location,
                                            'grn_item_id' => $grn_item_id,
                                            'qc_id' => $qc_id,
                                            'item_name' => $item_name,
                                            'unit' => $unit,
                                            'created_by' => $received_by
                                        ]]
                                    ]);
                                    $mp = $conn->prepare("UPDATE system_events SET processed = 1, processed_at = NOW() WHERE entity_type = 'raw_material_qc' AND entity_id = ? AND event_type = 'QC_APPROVED_RAW'");
                                    $mp->bind_param("i", $qc_id);
                                    $mp->execute();
                                } catch (Exception $ex) { /* log if needed */ }
                            }
                    }
                }
            }
            $item_stmt->close();
            
            // Update GRN total items
            $update_grn = $conn->prepare("UPDATE goods_receiving_notes SET total_items_received = ? WHERE grn_id = ?");
            $update_grn->bind_param("ii", $total_items, $grn_id);
            $update_grn->execute();
            $update_grn->close();
            
            // Update PO status
            $po_status_check = $conn->query("
                SELECT SUM(quantity_ordered) as total_ordered, SUM(quantity_received) as total_received
                FROM po_items WHERE po_id = $po_id
            ");
            if ($po_status_check && $po_row = $po_status_check->fetch_assoc()) {
                $new_po_status = 'Open';
                if ($po_row['total_received'] >= $po_row['total_ordered']) {
                    $new_po_status = 'Received';
                } elseif ($po_row['total_received'] > 0) {
                    $new_po_status = 'Partially Received';
                }
                
                $update_po = $conn->prepare("UPDATE purchase_orders SET status = ? WHERE po_id = ?");
                $update_po->bind_param("si", $new_po_status, $po_id);
                $update_po->execute();
                $update_po->close();
            }
        }
        
        $conn->commit();
        $success_msg = "GRN saved successfully! ";
        if ($qc_status === 'Passed') {
            $success_msg .= "Items have been added to inventory.";
        } else {
            $success_msg .= "QC records created. Please inspect items in QC module.";
        }
        $_SESSION['success'] = $success_msg;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: ../procurement_receiving.php");
    exit;
} else {
    header("Location: ../procurement_receiving.php");
    exit;
}
?>
