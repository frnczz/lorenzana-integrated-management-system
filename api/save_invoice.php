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
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $order_id = intval($_POST['order_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $invoice_date = $_POST['invoice_date'] ?? date('Y-m-d');
    $due_date = $_POST['due_date'] ?? null;
    $status = $_POST['status'] ?? 'Pending';
    $created_by = $_SESSION['user_id'];

    // Validate required fields
    if ($customer_id <= 0 || $amount <= 0) {
        $_SESSION['error'] = "Customer and amount are required.";
        header("Location: ../accounting_invoices.php");
        exit;
    }

    // Auto-generate invoice number
    $invoice_number = generateReferenceId($conn, 'INV');
    if (!$invoice_number) {
        $_SESSION['error'] = "Could not generate invoice number. Please try again.";
        header("Location: ../accounting_invoices.php");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO invoices (invoice_number, customer_id, order_id, amount, invoice_date, due_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $order_id_null = $order_id > 0 ? $order_id : null;
        $stmt->bind_param("siidsssi", $invoice_number, $customer_id, $order_id_null, $amount, $invoice_date, $due_date, $status, $created_by);
        
        if ($stmt->execute()) {
            $invoice_id = $conn->insert_id;
            // if the invoice is linked to an order, mark that order as invoiced and store the invoice reference
            if ($order_id > 0) {
                $upd = $conn->prepare("UPDATE sales_orders SET invoice_generated = 1, invoice_id = ? WHERE order_id = ?");
                if ($upd) {
                    $upd->bind_param("ii", $invoice_id, $order_id);
                    $upd->execute();
                    $upd->close();
                }
            }
            $_SESSION['success'] = "Invoice created successfully! Invoice Number: " . $invoice_number;
        } else {
            $_SESSION['error'] = "Error creating invoice: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error: " . $conn->error;
    }

    header("Location: ../accounting_invoices.php");
    exit;
} else {
    header("Location: ../accounting_invoices.php");
    exit;
}
?>
