<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = intval($_POST['invoice_id'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $payment_method = trim($_POST['payment_method'] ?? 'Cash');
    $amount = floatval($_POST['amount'] ?? 0);
    $reference_number = trim($_POST['reference_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $created_by = $_SESSION['user_id'];
    
    if ($invoice_id <= 0 || $amount <= 0) {
        $_SESSION['error'] = "Invoice and amount are required.";
        header("Location: ../accounting_payments.php");
        exit;
    }
    
    // Check invoice exists and get outstanding amount
    $inv_check = $conn->prepare("
        SELECT i.invoice_id, i.amount, i.invoice_number,
               COALESCE(SUM(p.amount), 0) as paid_amount,
               (i.amount - COALESCE(SUM(p.amount), 0)) as outstanding
        FROM invoices i
        LEFT JOIN payments p ON i.invoice_id = p.invoice_id
        WHERE i.invoice_id = ? AND i.approval_status = 'Approved'
        GROUP BY i.invoice_id
    ");
    $inv_check->bind_param("i", $invoice_id);
    $inv_check->execute();
    $invoice = $inv_check->get_result()->fetch_assoc();
    $inv_check->close();
    
    if (!$invoice) {
        $_SESSION['error'] = "Invoice not found or not approved.";
        header("Location: ../accounting_payments.php");
        exit;
    }
    
    if ($amount > $invoice['outstanding']) {
        $_SESSION['error'] = "Payment amount (₱" . number_format($amount, 2) . ") exceeds outstanding balance (₱" . number_format($invoice['outstanding'], 2) . ").";
        header("Location: ../accounting_payments.php?invoice_id=" . $invoice_id);
        exit;
    }
    
    // Generate payment number
    $payment_number = generateReferenceId($conn, 'PAY');
    if (!$payment_number) {
        $_SESSION['error'] = "Could not generate payment number.";
        header("Location: ../accounting_payments.php");
        exit;
    }
    
    // Insert payment
    $stmt = $conn->prepare("
        INSERT INTO payments (payment_number, invoice_id, payment_date, payment_method, amount, reference_number, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sisdsssi", $payment_number, $invoice_id, $payment_date, $payment_method, 
                     $amount, $reference_number, $notes, $created_by);
    
    if ($stmt->execute()) {
        // Update invoice status based on payment
        $new_paid = $invoice['paid_amount'] + $amount;
        $new_outstanding = $invoice['outstanding'] - $amount;
        
        $new_status = 'Pending';
        if ($new_outstanding <= 0.01) { // Allow small rounding differences
            $new_status = 'Paid';
        } elseif ($new_paid > 0) {
            $new_status = 'Partially Paid';
        }
        
        // Check if due date passed
        $due_check = $conn->query("SELECT due_date FROM invoices WHERE invoice_id = $invoice_id");
        if ($due_check && $due_row = $due_check->fetch_assoc()) {
            if ($new_status === 'Pending' && strtotime($due_row['due_date']) < time()) {
                $new_status = 'Overdue';
            }
        }
        
        $update_inv = $conn->prepare("UPDATE invoices SET status = ? WHERE invoice_id = ?");
        $update_inv->bind_param("si", $new_status, $invoice_id);
        $update_inv->execute();
        $update_inv->close();
        
        $_SESSION['success'] = "Payment recorded successfully! Payment #$payment_number";
    } else {
        $_SESSION['error'] = "Error recording payment: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: ../accounting_payments.php?invoice_id=" . $invoice_id);
    exit;
} else {
    header("Location: ../accounting_payments.php");
    exit;
}
?>
