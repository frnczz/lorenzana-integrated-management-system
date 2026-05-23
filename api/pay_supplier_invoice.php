<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || 
   ($_SESSION['role'] != 'admin' && 
    $_SESSION['role'] != 'procurement' && 
    $_SESSION['role'] != 'accounting')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../procurement_invoices.php");
    exit;
}

$invoice_id = intval($_POST['invoice_id'] ?? 0);
$payment_date = $_POST['payment_date'] ?? date('Y-m-d');
$payment_method = trim($_POST['payment_method'] ?? 'Bank Transfer');
$amount = floatval($_POST['amount'] ?? 0);
$reference_number = trim($_POST['reference_number'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$created_by = $_SESSION['user_id'];

if ($invoice_id <= 0 || $amount <= 0) {
    $_SESSION['error'] = 'Invoice and amount are required.';
    header("Location: ../procurement_invoices.php");
    exit;
}

// Get invoice
$inv = $conn->prepare("
    SELECT si.*, COALESCE(si.paid_amount,0) as paid_amount
    FROM supplier_invoices si
    WHERE si.invoice_id = ?
    LIMIT 1
");
$inv->bind_param("i", $invoice_id);
$inv->execute();
$invoice = $inv->get_result()->fetch_assoc();
$inv->close();

if (!$invoice) {
    $_SESSION['error'] = 'Supplier invoice not found.';
    header("Location: ../procurement_invoices.php");
    exit;
}

$outstanding = floatval($invoice['total_amount']) - floatval($invoice['paid_amount']);

if ($amount > $outstanding) {
    $_SESSION['error'] = 'Payment amount exceeds outstanding balance.';
    header("Location: ../procurement_invoice_pay.php?invoice_id=" . $invoice_id);
    exit;
}

$conn->begin_transaction();

try {

    // -------------------------
    // 1. UPDATE INVOICE
    // -------------------------
    $new_paid = floatval($invoice['paid_amount']) + $amount;
    $new_outstanding = floatval($invoice['total_amount']) - $new_paid;

    $new_status = 'Unpaid';
    if ($new_outstanding <= 0.01) {
        $new_status = 'Paid';
    } elseif ($new_paid > 0) {
        $new_status = 'Partially Paid';
    }

    $u = $conn->prepare("
        UPDATE supplier_invoices 
        SET paid_amount = ?, payment_status = ?
        WHERE invoice_id = ?
    ");
    $u->bind_param("dsi", $new_paid, $new_status, $invoice_id);
    if (!$u->execute()) {
        throw new Exception("Failed to update invoice.");
    }
    $u->close();


    // -------------------------
    // 2. INSERT PAYMENT RECORD
    // -------------------------
    $sp = $conn->prepare("
        INSERT INTO supplier_payments
        (invoice_id, payment_date, payment_method, amount, reference_number, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    if ($sp) {
        $sp->bind_param("issdssi", 
            $invoice_id, 
            $payment_date, 
            $payment_method, 
            $amount, 
            $reference_number, 
            $notes, 
            $created_by
        );
        $sp->execute();
        $sp->close();
    }


    // -------------------------
    // 3. GET DEPARTMENT
    // -------------------------
    $dept = null;
    if (!empty($invoice['po_id'])) {
        $dq = $conn->prepare("
            SELECT pr.department 
            FROM purchase_orders po
            LEFT JOIN purchase_requisitions pr ON po.pr_id = pr.pr_id
            WHERE po.po_id = ?
            LIMIT 1
        ");
        if ($dq) {
            $dq->bind_param("i", $invoice['po_id']);
            $dq->execute();
            $dres = $dq->get_result()->fetch_assoc();
            $dq->close();
            if ($dres && !empty($dres['department'])) {
                $dept = $dres['department'];
            }
        }
    }


    // -------------------------
    // 4. RECORD EXPENSE PER PAYMENT
    // -------------------------
    $supplier_name = '';
    $sn = $conn->query("SELECT supplier_name FROM suppliers WHERE supplier_id = " . (int)$invoice['supplier_id']);
    if ($sn && $r = $sn->fetch_assoc()) {
        $supplier_name = $r['supplier_name'];
    }

    $desc = 'Auto: Procurement - Supplier Invoice #' 
          . $invoice['invoice_number'] 
          . ($supplier_name ? ' - ' . $supplier_name : '');

    $expense_ok = recordExpenseFromModule(
        $conn,
        'Raw Materials',
        $amount,                     // <-- PER PAYMENT (not total_amount)
        $desc,
        $payment_date,
        $created_by,
        'supplier_invoice',
        $invoice_id,
        $dept
    );

    if (!$expense_ok) {
        throw new Exception("Failed to record expense.");
    }


    $conn->commit();
    $_SESSION['success'] = 'Payment recorded successfully. Expense created per payment.';

} catch (Exception $e) {

    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
}

header("Location: ../procurement_invoice_view.php?id=" . $invoice_id);
exit;