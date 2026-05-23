<?php
session_start();
include "../db_connect.php";

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pr_id = intval($_POST['pr_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    $approved_by = $_SESSION['user_id'];
    
    if ($pr_id <= 0 || !in_array($action, ['approve', 'reject'])) {
        $_SESSION['error'] = "Invalid parameters.";
        header("Location: ../procurement_requisitions.php");
        exit;
    }
    
    $status = $action === 'approve' ? 'Approved' : 'Rejected';
    
    $stmt = $conn->prepare("
        UPDATE purchase_requisitions 
        SET status = ?, approved_by = ?, approved_at = NOW(), rejection_reason = ?
        WHERE pr_id = ? AND status = 'Submitted'
    ");
    $rejection_reason = $action === 'reject' ? $rejection_reason : null;
    $stmt->bind_param("sisi", $status, $approved_by, $rejection_reason, $pr_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "PR " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully!";
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: ../procurement_requisition_view.php?id=$pr_id");
    exit;
} else {
    header("Location: ../procurement_requisitions.php");
    exit;
}
?>
