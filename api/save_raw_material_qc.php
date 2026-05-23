<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_service.php';

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'qc')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qc_id = intval($_POST['qc_id'] ?? 0);
    $packaging_status = trim($_POST['packaging_status'] ?? 'Intact');
    $label_accuracy = trim($_POST['label_accuracy'] ?? 'Correct');
    $quantity_check = trim($_POST['quantity_check'] ?? 'Pass');
    $expiry_check = trim($_POST['expiry_check'] ?? 'Pass');
    $ph_level = !empty($_POST['ph_level']) ? floatval($_POST['ph_level']) : null;
    $salt_percentage = !empty($_POST['salt_percentage']) ? floatval($_POST['salt_percentage']) : null;
    $odor_test = trim($_POST['odor_test'] ?? 'Pass');
    $color_check = trim($_POST['color_check'] ?? 'Pass');
    $texture_check = trim($_POST['texture_check'] ?? 'Pass');
    $quantity_accepted = floatval($_POST['quantity_accepted'] ?? 0);
    $quantity_rejected = floatval($_POST['quantity_rejected'] ?? 0);
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $qc_status = trim($_POST['qc_status'] ?? 'Pending');
    $qc_remarks = trim($_POST['qc_remarks'] ?? '');
    $inspected_by = $_SESSION['user_id'];
    $inspection_date = date('Y-m-d');
    
    if ($qc_id <= 0) {
        $_SESSION['error'] = "Invalid QC record.";
        header("Location: ../qc_raw_materials.php");
        exit;
    }
    
    // Get existing QC record
    $qc_check = $conn->prepare("SELECT * FROM raw_material_qc WHERE qc_id = ?");
    $qc_check->bind_param("i", $qc_id);
    $qc_check->execute();
    $existing_qc = $qc_check->get_result()->fetch_assoc();
    $qc_check->close();
    
    if (!$existing_qc) {
        $_SESSION['error'] = "QC record not found.";
        header("Location: ../qc_raw_materials.php");
        exit;
    }
    
    $conn->begin_transaction();
    try {
        // Determine approval status based on QC status
        $approval_status = 'Pending';
        if ($qc_status === 'Passed') {
            $approval_status = 'Approved'; // Auto-approve passed items
        } elseif ($qc_status === 'Failed') {
            $approval_status = 'Rejected'; // Auto-reject failed items
        } elseif ($qc_status === 'Conditional') {
            $approval_status = 'Pending'; // Requires supervisor approval
        }
        
        // Update QC record
        $stmt = $conn->prepare("
            UPDATE raw_material_qc 
            SET packaging_status = ?, label_accuracy = ?, quantity_check = ?, expiry_check = ?,
                ph_level = ?, salt_percentage = ?, odor_test = ?, color_check = ?, texture_check = ?,
                quantity_accepted = ?, quantity_rejected = ?, expiry_date = ?,
                qc_status = ?, qc_remarks = ?, approval_status = ?,
                inspected_by = ?, inspection_date = ?
            WHERE qc_id = ?
        ");
        $stmt->bind_param("sssssddsssddsssssi", $packaging_status, $label_accuracy, $quantity_check, $expiry_check,
                         $ph_level, $salt_percentage, $odor_test, $color_check, $texture_check,
                         $quantity_accepted, $quantity_rejected, $expiry_date,
                         $qc_status, $qc_remarks, $approval_status,
                         $inspected_by, $inspection_date, $qc_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error saving QC: " . $stmt->error);
        }
        $stmt->close();
        
        // Update GRN item QC status
        $update_grn_item = $conn->prepare("
            UPDATE grn_items 
            SET qc_status = ?, quantity_accepted = ?, quantity_rejected = ?
            WHERE grn_item_id = ?
        ");
        $update_grn_item->bind_param("sddi", $qc_status, $quantity_accepted, $quantity_rejected, $existing_qc['grn_item_id']);
        $update_grn_item->execute();
        $update_grn_item->close();
        
        // If QC Passed and auto-approved: emit event, inventory service handles stock
        if ($qc_status === 'Passed' && $approval_status === 'Approved' && $quantity_accepted > 0) {
            $material_id = (int)($existing_qc['material_id'] ?? 0);
            $grn_item_id = (int)$existing_qc['grn_item_id'];
            $unit_row = $conn->query("SELECT unit, warehouse_location FROM grn_items WHERE grn_item_id = $grn_item_id")->fetch_assoc();
            $unit = $unit_row['unit'] ?? 'kg';
            $warehouse_location = $unit_row['warehouse_location'] ?? null;

            emitSystemEvent($conn, 'raw_material_qc', $qc_id, 'QC_APPROVED_RAW', [
                'items' => [[
                    'material_id' => $material_id,
                    'quantity' => $quantity_accepted,
                    'expiry_date' => $expiry_date,
                    'warehouse_location' => $warehouse_location,
                    'grn_item_id' => $grn_item_id,
                    'qc_id' => $qc_id,
                    'item_name' => $existing_qc['item_name'] ?? '',
                    'unit' => $unit,
                    'created_by' => $inspected_by
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
                        'item_name' => $existing_qc['item_name'] ?? '',
                        'unit' => $unit,
                        'created_by' => $inspected_by
                    ]]
                ]);
                $mp = $conn->prepare("UPDATE system_events SET processed = 1, processed_at = NOW() WHERE entity_type = 'raw_material_qc' AND entity_id = ? AND event_type = 'QC_APPROVED_RAW'");
                $mp->bind_param("i", $qc_id);
                $mp->execute();
            } catch (Exception $ex) {
                throw $ex;
            }
        }
        
        // Update GRN qc_status based on all items
        $grn_id = (int)$existing_qc['grn_id'];
        $qc_status_query = $conn->query("SELECT 
            SUM(CASE WHEN qc_status='Failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN qc_status='Conditional' THEN 1 ELSE 0 END) as conditional,
            COUNT(*) as total
            FROM raw_material_qc WHERE grn_id = $grn_id");
        $qc_status_row = $qc_status_query->fetch_assoc();
        $new_grn_qc_status = 'Passed';
        if ($qc_status_row['failed'] > 0) $new_grn_qc_status = 'Failed';
        elseif ($qc_status_row['conditional'] > 0) $new_grn_qc_status = 'Conditional';

        $conn->query("UPDATE goods_receiving_notes SET qc_status = '".$conn->real_escape_string($new_grn_qc_status)."' WHERE grn_id = $grn_id");

        // Update total_items_received in GRN
        $conn->query("UPDATE goods_receiving_notes SET total_items_received = (SELECT SUM(quantity_accepted) FROM grn_items WHERE grn_id = $grn_id) WHERE grn_id = $grn_id");

        $conn->commit();
        $_SESSION['success'] = "QC inspection saved successfully!" . 
            ($qc_status === 'Passed' && $approval_status === 'Approved' ? " Items have been added to inventory." : "");

        // AJAX support: if request is AJAX, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $grn_info = $conn->query("SELECT qc_status, total_items_received FROM goods_receiving_notes WHERE grn_id = $grn_id")->fetch_assoc();
            echo json_encode([
                'success' => true,
                'qc_id' => $qc_id,
                'grn_id' => $grn_id,
                'qc_status' => $grn_info['qc_status'],
                'total_items_received' => $grn_info['total_items_received'],
                'message' => $_SESSION['success']
            ]);
            exit;
        }
    } catch (Exception $e) {
        $conn->rollback();
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: ../qc_raw_material_form.php?id=$qc_id");
    exit;
} else {
    header("Location: ../qc_raw_materials.php");
    exit;
}
?>
