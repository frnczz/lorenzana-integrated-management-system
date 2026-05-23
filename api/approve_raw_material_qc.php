<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/inventory_service.php';

// Check authentication (admin or supervisor)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'qc')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qc_id = intval($_POST['qc_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');
    $supervisor_remarks = trim($_POST['supervisor_remarks'] ?? '');
    $approved_by = $_SESSION['user_id'];
    
    if ($qc_id <= 0 || !in_array($action, ['approve', 'reject'])) {
        $_SESSION['error'] = "Invalid parameters.";
        header("Location: ../qc_raw_materials.php");
        exit;
    }
    
    // Get QC record
    $qc_check = $conn->prepare("SELECT * FROM raw_material_qc WHERE qc_id = ? AND approval_status = 'Pending'");
    $qc_check->bind_param("i", $qc_id);
    $qc_check->execute();
    $qc = $qc_check->get_result()->fetch_assoc();
    $qc_check->close();
    
    if (!$qc) {
        $_SESSION['error'] = "QC record not found or already processed.";
        header("Location: ../qc_raw_materials.php");
        exit;
    }
    
    $approval_status = $action === 'approve' ? 'Approved' : 'Rejected';
    
    $conn->begin_transaction();
    try {
        // Update approval status
        $stmt = $conn->prepare("
            UPDATE raw_material_qc 
            SET approval_status = ?, approved_by = ?, approved_at = NOW(), supervisor_remarks = ?
            WHERE qc_id = ?
        ");
        $stmt->bind_param("sisi", $approval_status, $approved_by, $supervisor_remarks, $qc_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating approval: " . $stmt->error);
        }
        $stmt->close();
        
        // If approved and QC status is Passed: emit event, inventory service handles stock
        if ($approval_status === 'Approved' && $qc['qc_status'] === 'Passed' && $qc['quantity_accepted'] > 0) {
            $unit_row = $conn->query("SELECT unit, warehouse_location FROM grn_items WHERE grn_item_id = " . (int)$qc['grn_item_id'])->fetch_assoc();
            $unit = $unit_row['unit'] ?? 'kg';
            $warehouse_location = $unit_row['warehouse_location'] ?? null;

            emitSystemEvent($conn, 'raw_material_qc', $qc_id, 'QC_APPROVED_RAW', [
                'items' => [[
                    'material_id' => (int)$qc['material_id'],
                    'quantity' => (float)$qc['quantity_accepted'],
                    'expiry_date' => $qc['expiry_date'],
                    'warehouse_location' => $warehouse_location,
                    'grn_item_id' => $qc['grn_item_id'],
                    'qc_id' => $qc_id,
                    'item_name' => $qc['item_name'] ?? '',
                    'unit' => $unit,
                    'created_by' => $approved_by
                ]]
            ]);
            try {
                processInventoryEvent($conn, 'QC_APPROVED_RAW', [
                    'items' => [[
                        'material_id' => (int)$qc['material_id'],
                        'quantity' => (float)$qc['quantity_accepted'],
                        'expiry_date' => $qc['expiry_date'],
                        'warehouse_location' => $warehouse_location,
                        'grn_item_id' => $qc['grn_item_id'],
                        'qc_id' => $qc_id,
                        'item_name' => $qc['item_name'] ?? '',
                        'unit' => $unit,
                        'created_by' => $approved_by
                    ]]
                ]);
                $mp = $conn->prepare("UPDATE system_events SET processed = 1, processed_at = NOW() WHERE entity_type = 'raw_material_qc' AND entity_id = ? AND event_type = 'QC_APPROVED_RAW'");
                $mp->bind_param("i", $qc_id);
                $mp->execute();
            } catch (Exception $ex) {
                throw $ex;
            }
        }
        
        $conn->commit();
        $_SESSION['success'] = "QC " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully!" . 
            ($approval_status === 'Approved' && $qc['qc_status'] === 'Passed' ? " Items have been added to inventory." : "");
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: ../qc_raw_material_form.php?id=$qc_id");
    exit;
} else {
    header("Location: ../qc_raw_materials.php");
    exit;
}
?>
