<?php
session_start();
include "../db_connect.php";

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = intval($_POST['invoice_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $approved_by = $_SESSION['user_id'];
    
    if ($invoice_id <= 0 || !in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }
    
    $approval_status = $action === 'approve' ? 'Approved' : 'Rejected';
    $notes = $action === 'reject' && $reason ? $reason : null;
    
    $stmt = $conn->prepare("
        UPDATE invoices 
        SET approval_status = ?, approved_by = ?, approved_at = NOW(), notes = COALESCE(?, notes)
        WHERE invoice_id = ?
    ");
    $stmt->bind_param("sisi", $approval_status, $approved_by, $notes, $invoice_id);
    
    if ($stmt->execute()) {
        // If approved and invoice is linked to an order, mark that order as invoiced to avoid duplication
        if ($action === 'approve') {
            $q = $conn->prepare("SELECT order_id FROM invoices WHERE invoice_id = ? LIMIT 1");
            if ($q) {
                $q->bind_param("i", $invoice_id);
                $q->execute();
                $res = $q->get_result();
                if ($row = $res->fetch_assoc()) {
                    $order_id = intval($row['order_id']);
                    if ($order_id > 0) {
                        $u = $conn->prepare("UPDATE sales_orders SET invoice_generated = 1, invoice_id = ? WHERE order_id = ?");
                        if ($u) {
                            $u->bind_param("ii", $invoice_id, $order_id);
                            $u->execute();
                            $u->close();
                        }
                    }
                }
                $q->close();
            }
        }

        echo json_encode(['success' => true, 'message' => "Invoice $action" . "d successfully"]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
