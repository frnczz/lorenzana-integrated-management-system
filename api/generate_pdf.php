<?php
session_start();
include "../db_connect.php";
include "../includes/pdf_generator.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

// Some document types (like system summary) do not require an ID.
if ($type !== 'system_summary' && $id <= 0) {
    die("Invalid ID");
}

switch ($type) {
    case 'invoice':
        $html = generateInvoicePDF($id, $conn);
        if ($html) {
            printPDF($html, 'invoice_' . $id . '.html');
        } else {
            die("Invoice not found");
        }
        break;
        
    case 'purchase_order':
        $html = generatePurchaseOrderPDF($id, $conn);
        if ($html) {
            printPDF($html, 'purchase_order_' . $id . '.html');
        } else {
            die("Purchase order not found");
        }
        break;
    case 'po':
        $html = generatePoPDF($id, $conn);
        if ($html) {
            printPDF($html, 'po_' . $id . '.html');
        } else {
            die("PO not found");
        }
        break;
    case 'grn':
        $html = generateGrnPDF($id, $conn);
        if ($html) {
            printPDF($html, 'grn_' . $id . '.html');
        } else {
            die("GRN not found");
        }
        break;
    
    case 'qc_report':
        $html = generateQcReportPDF($id, $conn);
        if ($html) {
            printPDF($html, 'qc_report_' . $id . '.html');
        } else {
            die("QC record not found");
        }
        break;
        
    case 'batch_report':
        $html = generateBatchReportPDF($id, $conn);
        if ($html) {
            printPDF($html, 'batch_report_' . $id . '.html');
        } else {
            die("Batch not found");
        }
        break;
        
    case 'payroll':
        $html = generatePayrollPDF($id, $conn);
        if ($html) {
            printPDF($html, 'payslip_' . $id . '.html');
        } else {
            die("Payroll not found");
        }
        break;

    case 'system_summary':
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $groupBy = $_GET['group_by'] ?? 'day';
        $html = generateSystemSummaryPDF($startDate, $endDate, $groupBy, $conn);
        if ($html) {
            $fileName = 'system_summary_' . ($startDate ?: 'all') . '_' . ($endDate ?: 'all') . '.html';
            printPDF($html, $fileName);
        } else {
            die("Summary not found");
        }
        break;

    default:
        die("Invalid document type");
    
    case 'sales_receipt':
        $html = generateSalesReceiptPDF($id, $conn);
        if ($html) {
            printPDF($html, 'receipt_' . $id . '.html');
        } else {
            die("Receipt not found");
        }
        break;
}
?>
