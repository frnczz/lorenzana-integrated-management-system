<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'qc')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qc_id = intval($_POST['qc_id'] ?? 0);
    
    if ($qc_id <= 0) {
        $_SESSION['error'] = "Invalid QC record.";
        header("Location: ../qc_raw_materials.php");
        exit;
    }
    
    // Get existing QC record to find grn_item_id
    $qc_check = $conn->prepare("SELECT grn_item_id FROM raw_material_qc WHERE qc_id = ?");
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
        // Delete the QC record
        $delete_stmt = $conn->prepare("DELETE FROM raw_material_qc WHERE qc_id = ?");
        $delete_stmt->bind_param("i", $qc_id);
        if (!$delete_stmt->execute()) {
            throw new Exception("Error deleting QC record: " . $delete_stmt->error);
        }
        $delete_stmt->close();
        
        // Reset the GRN item's QC status to allow re-inspection
        $reset_grn = $conn->prepare("
            UPDATE grn_items 
            SET qc_status = NULL, quantity_accepted = NULL, quantity_rejected = NULL, qc_record_id = NULL
            WHERE grn_item_id = ?
        ");
        $reset_grn->bind_param("i", $existing_qc['grn_item_id']);
        $reset_grn->execute();
        $reset_grn->close();
        
        $conn->commit();
        $_SESSION['success'] = "QC record deleted successfully. Item is available for re-inspection.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Failed to delete QC record: " . $e->getMessage();
    }
    
    header("Location: ../qc_raw_materials.php");
    exit;
} else {
    header("Location: ../qc_raw_materials.php");
    exit;
}
?>
