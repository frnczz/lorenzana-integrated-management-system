<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

// Only procurement/admin/accounting can auto-generate supplier invoices
if (!isset($_SESSION['user_id']) || (
    $_SESSION['role'] != 'admin' && 
    $_SESSION['role'] != 'procurement' && 
    $_SESSION['role'] != 'accounting'
)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $grn_id = intval($input['grn_id'] ?? 0);
    if ($grn_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid GRN ID']);
        exit;
    }

    // Fetch GRN with PO and supplier info
    $stmt = $conn->prepare("
        SELECT grn.grn_id, grn.po_id, grn.invoice_id,
               po.supplier_id, po.total_amount
        FROM goods_receiving_notes grn
        LEFT JOIN purchase_orders po ON grn.po_id = po.po_id
        WHERE grn.grn_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $grn_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $grn = $res->fetch_assoc();
    $stmt->close();

    if (!$grn) {
        echo json_encode(['success' => false, 'error' => 'GRN not found']);
        exit;
    }

    if (!empty($grn['invoice_id'])) {
        echo json_encode(['success' => false, 'error' => 'Invoice already generated for this GRN']);
        exit;
    }

    $supplier_id = intval($grn['supplier_id']);
    $po_id = intval($grn['po_id']);

    // Determine total amount (prefer PO total)
    $total = floatval($grn['total_amount'] ?? 0);
    if ($total <= 0 && $po_id > 0) {
        $stmt = $conn->prepare("SELECT total_amount FROM purchase_orders WHERE po_id = ?");
        $stmt->bind_param("i", $po_id);
        $stmt->execute();
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
    }

    if ($total <= 0) {
        echo json_encode(['success' => false, 'error' => 'Cannot determine invoice amount']);
        exit;
    }

    // Generate invoice number
    $invoice_number = generateReferenceId($conn, 'SI');
    if (!$invoice_number) {
        $invoice_number = 'SI-' . time();
    }

    $invoice_date = date('Y-m-d');
    $due_date = null;
    $subtotal = $total;
    $tax_amount = 0;
    $notes = '';
    $created_by = $_SESSION['user_id'];

    $stmt = $conn->prepare(
        "INSERT INTO supplier_invoices
            (invoice_number, supplier_id, po_id, invoice_date, due_date, subtotal, tax_amount, total_amount, notes, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("siissdddsi",
        $invoice_number, $supplier_id, $po_id, $invoice_date, $due_date,
        $subtotal, $tax_amount, $total, $notes, $created_by
    );

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
        exit;
    }

    $invoice_id = $conn->insert_id;
    $stmt->close();

    // Update GRN with link to invoice
    $upd = $conn->prepare("UPDATE goods_receiving_notes SET invoice_id = ? WHERE grn_id = ?");
    $upd->bind_param("ii", $invoice_id, $grn_id);
    $upd->execute();
    $upd->close();

    echo json_encode(['success' => true, 'invoice_id' => $invoice_id, 'invoice_number' => $invoice_number]);
    exit;
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}
