<?php
session_start();
include "../db_connect.php";

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $return_id = intval($_POST['return_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');
    $approved_by = $_SESSION['user_id'];
    
    if ($return_id <= 0 || !in_array($action, ['approve', 'cancel'])) {
        $_SESSION['error'] = "Invalid parameters.";
        header("Location: ../procurement_returns.php");
        exit;
    }
    
    $status = $action === 'approve' ? 'Approved' : 'Cancelled';
    
    $stmt = $conn->prepare("
        UPDATE supplier_returns 
        SET status = ?, approved_by = ?, approved_at = NOW()
        WHERE return_id = ? AND status = 'Pending'
    ");
    $stmt->bind_param("sii", $status, $approved_by, $return_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Return " . ($action === 'approve' ? 'approved' : 'cancelled') . " successfully!";
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: ../procurement_return_view.php?id=$return_id");
    exit;
} else {
    header("Location: ../procurement_returns.php");
    exit;
}
?>
