<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement' && $_SESSION['role'] != 'accounting')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $po_id = !empty($_POST['po_id']) ? intval($_POST['po_id']) : null;
    $invoice_number = trim($_POST['invoice_number'] ?? '');
    $invoice_date = $_POST['invoice_date'] ?? date('Y-m-d');
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $tax_amount = floatval($_POST['tax_amount'] ?? 0);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $created_by = $_SESSION['user_id'];
    
    // If linked to a PO, prefer PO's supplier and totals when appropriate
    if (!empty($po_id)) {
        $po_stmt = $conn->prepare("SELECT supplier_id, total_amount FROM purchase_orders WHERE po_id = ? LIMIT 1");
        if ($po_stmt) {
            $po_stmt->bind_param("i", $po_id);
            $po_stmt->execute();
            $po_row = $po_stmt->get_result()->fetch_assoc();
            $po_stmt->close();
            if ($po_row) {
                // If supplier not provided, use PO's supplier
                if ($supplier_id <= 0) $supplier_id = intval($po_row['supplier_id']);
                // If subtotal/total not provided, use PO total
                if (empty($subtotal) || $subtotal <= 0) $subtotal = floatval($po_row['total_amount']);
            }
        }
    }

    // Auto-generate invoice number if missing
    if (empty($invoice_number)) {
        $generated = generateReferenceId($conn, 'SI');
        $invoice_number = $generated ?: ('SI-' . time());
    }

    if ($supplier_id <= 0 || $total_amount <= 0) {
        $_SESSION['error'] = "Supplier and total amount are required.";
        header("Location: ../procurement_invoices.php?new=1");
        exit;
    }
    
    $stmt = $conn->prepare(
        "INSERT INTO supplier_invoices 
        (invoice_number, supplier_id, po_id, invoice_date, due_date, subtotal, tax_amount, total_amount, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    // types: s (invoice_number), i (supplier_id), i (po_id), s (invoice_date), s (due_date), d (subtotal), d (tax_amount), d (total_amount), s (notes), i (created_by)
    $stmt->bind_param("siissdddsi", $invoice_number, $supplier_id, $po_id, $invoice_date, $due_date,
                     $subtotal, $tax_amount, $total_amount, $notes, $created_by);
    
    if ($stmt->execute()) {
        $supplier_name = '';
        $sn = $conn->query("SELECT supplier_name FROM suppliers WHERE supplier_id = " . (int)$supplier_id);
        if ($sn && $row = $sn->fetch_assoc()) $supplier_name = $row['supplier_name'];
        // Expense will be recorded when payment is made by accounting.
        $_SESSION['success'] = "Supplier invoice recorded successfully!";
    } else {
        $_SESSION['error'] = "Error saving invoice: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: ../procurement_invoices.php");
    exit;
} else {
    header("Location: ../procurement_invoices.php");
    exit;
}
?>
