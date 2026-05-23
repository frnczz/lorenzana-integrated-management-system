<?php
// PDF Generator Functions using TCPDF
// Note: You need to download TCPDF library and place it in includes/tcpdf/

require_once __DIR__ . '/payroll_calculations.php';

// Simple PDF generation without external library (using basic HTML to PDF approach)
// For production, consider using TCPDF, FPDF, or DomPDF

// Ensure helper functions are available (payroll settings, formatting, etc.)
require_once __DIR__ . '/functions.php';

/**
 * Load and return the HTML for the company logo.
 * This ensures logo.php stays in use and its styling is preserved.
 */
function getPdfLogoHtml() {
    $logoPath = __DIR__ . '/../layouts/logo.php';
    if (!file_exists($logoPath)) {
        return '';
    }

    ob_start();
    include $logoPath;
    return ob_get_clean();
}

/**
 * Wraps a body HTML fragment in a consistent orange-themed layout for PDF output.
 */
function renderPdfTemplate($title, $subtitle, $bodyHtml, $footerHtml = '') {
    $logoHtml = getPdfLogoHtml();

    $styles = '
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f6f8; color: #2c2c2c; }
        .pdf-container { max-width: 960px; margin: 0 auto; padding: 24px; background: #fff; box-shadow: 0 0 20px rgba(0,0,0,0.05); border-left: 12px solid #FF6B35; }
        .pdf-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; margin-bottom: 18px; gap: 16px; }
        .pdf-logo { flex: 0 0 280px; }
        .pdf-title { flex: 1 1 auto; padding-left: 12px; }
        .pdf-title h1 { margin: 0 0 6px; font-size: 26px; letter-spacing: 1px; color: #FF6B35; }
        .pdf-title p { margin: 0; font-size: 14px; color: #4a4a4a; }
        .pdf-body { margin-top: 18px; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th, td { border: 1px solid #e0e0e0; padding: 10px; font-size: 13px; }
        th { background: #FF6B35; color: #fff; text-align: left; }
        tr:nth-child(even) td { background: rgba(255, 107, 53, 0.05); }
        .section-header { margin-top: 26px; margin-bottom: 8px; font-size: 16px; font-weight: 600; color: #333; border-bottom: 2px solid rgba(255, 107, 53, 0.4); padding-bottom: 4px; }
        .small { font-size: 12px; color: #666; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .label { font-weight: 600; color: #333; }
        .footer { margin-top: 32px; padding-top: 18px; border-top: 1px solid #e0e0e0; font-size: 12px; color: #555; }
    </style>';

    $footerBlock = '';
    if ($footerHtml !== '') {
        $footerBlock = '<div class="footer">' . $footerHtml . '</div>';
    }

    return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>' . htmlspecialchars($title) . '</title>
' . $styles . '
</head>
<body>
<div class="pdf-container">
    <div class="pdf-header">
        <div class="pdf-logo">' . $logoHtml . '</div>
        <div class="pdf-title">
            <h1>' . htmlspecialchars($title) . '</h1>
            <p>' . htmlspecialchars($subtitle) . '</p>
        </div>
    </div>
    <div class="pdf-body">
        ' . $bodyHtml . '
    </div>
    ' . $footerBlock . '
</div>
</body>
</html>';
}

function generateInvoicePDF($invoice_id, $conn) {
    $query = "SELECT i.*, c.customer_name, c.address as customer_address, c.contact_number, 
              u.full_name as created_by_name
              FROM invoices i
              LEFT JOIN customers c ON i.customer_id = c.customer_id
              LEFT JOIN users u ON i.created_by = u.id
              WHERE i.invoice_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$invoice) {
        return false;
    }

    // Load order items (if linked to an order)
    $invoice['items'] = [];
    if (!empty($invoice['order_id'])) {
        $items_stmt = $conn->prepare(
            "SELECT oi.quantity, oi.unit_price, p.product_name, p.unit
             FROM order_items oi
             JOIN products p ON oi.product_id = p.product_id
             WHERE oi.order_id = ?"
        );
        $items_stmt->bind_param("i", $invoice['order_id']);
        $items_stmt->execute();
        $invoice['items'] = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $items_stmt->close();
    }

    $html = generateInvoiceHTML($invoice);
    
    // For now, return HTML that can be printed
    // In production, use TCPDF or similar library
    return $html;
}

function generatePurchaseOrderPDF($pr_id, $conn) {
    $query = "SELECT pr.*, s.supplier_name, s.contact_person, s.contact_number, s.address as supplier_address,
              u.full_name as requested_by_name
              FROM purchase_requests pr
              LEFT JOIN suppliers s ON pr.supplier_id = s.supplier_id
              LEFT JOIN users u ON pr.requested_by = u.id
              WHERE pr.pr_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $pr_id);
    $stmt->execute();
    $pr = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$pr) {
        return false;
    }

    $html = generatePurchaseOrderHTML($pr);
    return $html;
}

function generateBatchReportPDF($batch_id, $conn) {
    $query = "SELECT pb.*, p.product_name, u.full_name as created_by_name
              FROM production_batches pb
              LEFT JOIN products p ON pb.product_id = p.product_id
              LEFT JOIN users u ON pb.created_by = u.id
              WHERE pb.batch_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $batch = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$batch) {
        return false;
    }

    // Get materials used
    $materials_query = "SELECT bd.*, rm.material_name, rm.unit
                       FROM batch_details bd
                       LEFT JOIN raw_materials rm ON bd.material_id = rm.material_id
                       WHERE bd.batch_id = ?";
    $materials_stmt = $conn->prepare($materials_query);
    $materials_stmt->bind_param("i", $batch_id);
    $materials_stmt->execute();
    $materials = $materials_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $materials_stmt->close();

    $html = generateBatchReportHTML($batch, $materials);
    return $html;
}

function generateQcReportPDF($qc_id, $conn) {
    $stmt = $conn->prepare("
        SELECT qc_id, batch_number, inspector_name, inspection_date,
               test_result, approval_status, remarks, non_conformance_details, corrective_action, created_at
        FROM qc_records
        WHERE qc_id = ?
    ");
    $stmt->bind_param("i", $qc_id);
    $stmt->execute();
    $qc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$qc) return false;

    return generateQcReportHTML($qc);
}

function generateQcReportHTML($qc) {
    $status = ($qc['test_result'] === 'Passed' && $qc['approval_status'] === 'Approved')
        ? 'COMPLETED'
        : 'ACTION REQUIRED';

    $body = '<div class="section-header">Quality Control Inspection</div>' .
        '<table>' .
            '<tr><th>Batch Number</th><td>' . htmlspecialchars($qc['batch_number']) . '</td></tr>' .
            '<tr><th>Inspector</th><td>' . htmlspecialchars($qc['inspector_name']) . '</td></tr>' .
            '<tr><th>Inspection Date</th><td>' . date('F d, Y', strtotime($qc['inspection_date'])) . '</td></tr>' .
            '<tr><th>Test Result</th><td>' . htmlspecialchars($qc['test_result']) . '</td></tr>' .
            '<tr><th>Approval Status</th><td>' . htmlspecialchars($qc['approval_status']) . '</td></tr>' .
            '<tr><th>Overall Status</th><td><span class="label">' . $status . '</span></td></tr>' .
            '<tr><th>Non-Conformance</th><td>' . htmlspecialchars($qc['non_conformance_details'] ?? 'N/A') . '</td></tr>' .
            '<tr><th>Corrective Action</th><td>' . htmlspecialchars($qc['corrective_action'] ?? 'N/A') . '</td></tr>' .
            '<tr><th>Remarks</th><td>' . nl2br(htmlspecialchars($qc['remarks'] ?? 'N/A')) . '</td></tr>' .
        '</table>';

    $footer = 'Generated on: ' . date('F d, Y h:i A');

    return renderPdfTemplate('QC Report', 'Batch ' . htmlspecialchars($qc['batch_number']), $body, $footer);
}

function generateInvoiceHTML($invoice) {
    $body = '<div class="section-header">Invoice</div>' .
        '<div>' .
            '<p><span class="label">Invoice #:</span> ' . htmlspecialchars($invoice['invoice_number']) . '</p>' .
            '<p><span class="label">Invoice Date:</span> ' . date('F d, Y', strtotime($invoice['invoice_date'])) . '</p>' .
            '<p><span class="label">Due Date:</span> ' . date('F d, Y', strtotime($invoice['due_date'])) . '</p>' .
            '<p><span class="label">Status:</span> ' . htmlspecialchars($invoice['status']) . '</p>' .
        '</div>' .
        '<div class="section-header">Bill To</div>' .
        '<div>' .
            '<p><strong>' . htmlspecialchars($invoice['customer_name'] ?? '') . '</strong></p>' .
            '<p>' . htmlspecialchars($invoice['customer_address'] ?? '') . '</p>' .
            '<p>' . htmlspecialchars($invoice['contact_number'] ?? '') . '</p>' .
        '</div>';

    // Include delivered products if invoice is linked to an order
    if (!empty($invoice['items'])) {
        $body .= '<div class="section-header">Products Delivered</div>' .
            '<table>' .
                '<tr><th>Product</th><th class="text-right">Qty</th><th>Unit</th></tr>';

        foreach ($invoice['items'] as $item) {
            $body .= '<tr>' .
                '<td>' . htmlspecialchars($item['product_name']) . '</td>' .
                '<td class="text-right">' . number_format($item['quantity'], 2) . '</td>' .
                '<td>' . htmlspecialchars($item['unit'] ?? '') . '</td>' .
            '</tr>';
        }

        $body .= '</table>';
    }

    $body .= '<table>' .
            '<tr><th>Description</th><th class="text-right">Amount</th></tr>' .
            '<tr><td>Invoice Amount</td><td class="text-right">₱' . number_format($invoice['amount'], 2) . '</td></tr>' .
        '</table>' .
        '<div class="text-right" style="margin-top: 12px;"><strong>Total Amount: ₱' . number_format($invoice['amount'], 2) . '</strong></div>';

    $footer = 'Prepared by: ' . htmlspecialchars($invoice['created_by_name'] ?? 'System') . ' | Date: ' . date('F d, Y', strtotime($invoice['created_at']));

    return renderPdfTemplate('Invoice', 'Invoice #' . htmlspecialchars($invoice['invoice_number']), $body, $footer);
}

function generatePurchaseOrderHTML($pr) {
    $body = '<div class="section-header">Purchase Request</div>' .
        '<div>' .
            '<p><span class="label">PR Number:</span> ' . htmlspecialchars($pr['pr_number']) . '</p>' .
            '<p><span class="label">Date:</span> ' . date('F d, Y', strtotime($pr['created_at'])) . '</p>' .
            '<p><span class="label">Status:</span> ' . htmlspecialchars($pr['status']) . '</p>' .
        '</div>' .
        '<div class="section-header">Supplier Information</div>' .
        '<div>' .
            '<p><strong>' . htmlspecialchars($pr['supplier_name'] ?? '') . '</strong></p>' .
            '<p>' . nl2br(htmlspecialchars($pr['supplier_address'] ?? '')) . '</p>' .
            '<p><span class="label">Contact:</span> ' . htmlspecialchars($pr['contact_person'] ?? '') . ' - ' . htmlspecialchars($pr['contact_number'] ?? '') . '</p>' .
        '</div>' .
        '<table>' .
            '<tr><th>Item</th><th>Quantity</th><th>Unit</th><th>Expected Delivery</th></tr>' .
            '<tr><td>' . htmlspecialchars($pr['item_name'] ?? '-') . '</td><td>' . number_format($pr['quantity'] ?? 0, 2) . '</td><td>' . htmlspecialchars($pr['unit'] ?? '-') . '</td><td>' . ($pr['expected_delivery_date'] ? date('F d, Y', strtotime($pr['expected_delivery_date'])) : 'N/A') . '</td></tr>' .
        '</table>';

    $footer = 'Requested by: ' . htmlspecialchars($pr['requested_by_name'] ?? 'System');

    return renderPdfTemplate('Purchase Request', 'PR #' . htmlspecialchars($pr['pr_number']), $body, $footer);
}

function generateBatchReportHTML($batch, $materials) {
    $body = '<div class="section-header">Production Batch Report</div>' .
        '<div>' .
            '<p><span class="label">Batch Number:</span> ' . htmlspecialchars($batch['batch_number'] ?? '') . '</p>' .
            '<p><span class="label">Product:</span> ' . htmlspecialchars($batch['product_name'] ?? 'N/A') . '</p>' .
            '<p><span class="label">Production Date:</span> ' . (isset($batch['batch_date']) ? date('F d, Y', strtotime($batch['batch_date'])) : 'N/A') . '</p>' .
            '<p><span class="label">Quantity Produced:</span> ' . number_format($batch['quantity'] ?? 0, 2) . '</p>' .
            '<p><span class="label">Fermentation Status:</span> ' . htmlspecialchars($batch['fermentation_status'] ?? 'N/A') . '</p>' .
            '<p><span class="label">Packaging Status:</span> ' . htmlspecialchars($batch['packaging_status'] ?? 'N/A') . '</p>' .
            '<p><span class="label">Status:</span> ' . htmlspecialchars($batch['status'] ?? 'N/A') . '</p>' .
        '</div>';

    if (count($materials) > 0) {
        $body .= '<div class="section-header">Raw Materials Used</div>' .
            '<table>' .
                '<tr><th>Material</th><th class="text-right">Quantity Used</th><th>Unit</th></tr>';

        foreach ($materials as $mat) {
            $body .= '<tr>' .
                '<td>' . htmlspecialchars($mat['material_name']) . '</td>' .
                '<td class="text-right">' . number_format($mat['quantity_used'] ?? 0, 2) . '</td>' .
                '<td>' . htmlspecialchars($mat['unit'] ?? '') . '</td>' .
            '</tr>';
        }

        $body .= '</table>';
    }

    $body .= '<div class="section-header">Record</div>' .
        '<div>' .
            '<p><span class="label">Created by:</span> ' . htmlspecialchars($batch['created_by_name'] ?? 'System') . '</p>' .
            '<p><span class="label">Date:</span> ' . (isset($batch['created_at']) ? date('F d, Y', strtotime($batch['created_at'])) : 'N/A') . '</p>' .
        '</div>';

    $footer = 'Generated on: ' . date('F d, Y h:i A');

    return renderPdfTemplate('Batch Report', 'Batch ' . htmlspecialchars($batch['batch_number'] ?? ''), $body, $footer);
}

/*
 * Generate Purchase Order (PO) PDF for purchase_orders table
 */
function generatePoPDF($po_id, $conn) {
    $stmt = $conn->prepare("SELECT po.*, s.supplier_name, s.contact_person, s.contact_number, s.address as supplier_address, u.username as created_by_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id LEFT JOIN users u ON po.created_by = u.id WHERE po.po_id = ?");
    $stmt->bind_param("i", $po_id);
    $stmt->execute();
    $po = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$po) return false;

    $items_stmt = $conn->prepare("SELECT * FROM po_items WHERE po_id = ? ORDER BY po_item_id");
    $items_stmt->bind_param("i", $po_id);
    $items_stmt->execute();
    $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $items_stmt->close();

    $body = '<div class="section-header">Purchase Order</div>' .
        '<div>' .
            '<p><span class="label">PO Number:</span> ' . htmlspecialchars($po['po_number'] ?? '') . '</p>' .
            '<p><span class="label">Supplier:</span> ' . htmlspecialchars($po['supplier_name'] ?? '') . '</p>' .
            '<p>' . nl2br(htmlspecialchars($po['supplier_address'] ?? '')) . '</p>' .
            '<p><span class="label">Contact:</span> ' . htmlspecialchars($po['contact_person'] ?? '') . ' ' . htmlspecialchars($po['contact_number'] ?? '') . '</p>' .
        '</div>' .
        '<table>' .
            '<tr><th>Item</th><th class="text-right">Qty</th><th>Unit</th><th class="text-right">Unit Price</th><th class="text-right">Subtotal</th></tr>';

    foreach ($items as $it) {
        $body .= '<tr>' .
            '<td>' . htmlspecialchars($it['item_name']) . '</td>' .
            '<td class="text-right">' . number_format($it['quantity_ordered'], 2) . '</td>' .
            '<td>' . htmlspecialchars($it['unit']) . '</td>' .
            '<td class="text-right">₱' . number_format($it['unit_price'], 2) . '</td>' .
            '<td class="text-right">₱' . number_format($it['subtotal'], 2) . '</td>' .
        '</tr>';
    }

    $body .= '</table>' .
        '<div class="text-right"><strong>Total: ₱' . number_format($po['total_amount'] ?? 0, 2) . '</strong></div>';

    $footer = 'Created by: ' . htmlspecialchars($po['created_by_name'] ?? '') . ' | Generated on: ' . date('F d, Y h:i A');

    return renderPdfTemplate('Purchase Order', 'PO #' . htmlspecialchars($po['po_number'] ?? ''), $body, $footer);
}

/*
 * Generate GRN PDF for goods_receiving_notes
 */
function generateGrnPDF($grn_id, $conn) {
    $stmt = $conn->prepare("SELECT grn.*, po.po_number, s.supplier_name, s.contact_number, s.address as supplier_address, u1.username as received_by_name, u2.username as qc_checked_by_name FROM goods_receiving_notes grn LEFT JOIN purchase_orders po ON grn.po_id = po.po_id LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id LEFT JOIN users u1 ON grn.received_by = u1.id LEFT JOIN users u2 ON grn.qc_checked_by = u2.id WHERE grn.grn_id = ?");
    $stmt->bind_param("i", $grn_id);
    $stmt->execute();
    $grn = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$grn) return false;

    $items_stmt = $conn->prepare("SELECT * FROM grn_items WHERE grn_id = ? ORDER BY grn_item_id");
    $items_stmt->bind_param("i", $grn_id);
    $items_stmt->execute();
    $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $items_stmt->close();

    $body = '<div class="section-header">Goods Receiving Note</div>' .
        '<div>' .
            '<p><span class="label">GRN Number:</span> ' . htmlspecialchars($grn['grn_number'] ?? '') . '</p>' .
            '<p><span class="label">PO Number:</span> ' . htmlspecialchars($grn['po_number'] ?? '') . '</p>' .
            '<p><span class="label">Supplier:</span> ' . htmlspecialchars($grn['supplier_name'] ?? '') . '</p>' .
            '<p>' . nl2br(htmlspecialchars($grn['supplier_address'] ?? '')) . '</p>' .
            '<p><span class="label">Received Date:</span> ' . htmlspecialchars($grn['received_date'] ?? '') . '</p>' .
        '</div>' .
        '<table>' .
            '<tr><th>Item</th><th class="text-right">Qty Received</th><th class="text-right">Qty Accepted</th><th>Unit</th><th>Lot</th><th>Expiry</th><th>QC Status</th></tr>';

    foreach ($items as $it) {
        $body .= '<tr>' .
            '<td>' . htmlspecialchars($it['item_name'] ?? '') . '</td>' .
            '<td class="text-right">' . number_format($it['quantity_received'] ?? 0, 2) . '</td>' .
            '<td class="text-right">' . number_format($it['quantity_accepted'] ?? 0, 2) . '</td>' .
            '<td>' . htmlspecialchars($it['unit'] ?? '') . '</td>' .
            '<td>' . htmlspecialchars($it['lot_number'] ?? '-') . '</td>' .
            '<td>' . ($it['expiry_date'] ? htmlspecialchars($it['expiry_date']) : '-') . '</td>' .
            '<td>' . htmlspecialchars($it['qc_status'] ?? 'Pending') . '</td>' .
        '</tr>';
    }

    $body .= '</table>' .
        '<div>' .
            '<p><span class="label">QC Status:</span> ' . htmlspecialchars($grn['qc_status'] ?? '') . '</p>' .
            '<p><span class="label">Notes:</span> ' . nl2br(htmlspecialchars($grn['notes'] ?? '')) . '</p>' .
            '<p><span class="label">Received by:</span> ' . htmlspecialchars($grn['received_by_name'] ?? '') . '</p>' .
        '</div>';

    $footer = 'Generated on: ' . date('F d, Y h:i A');

    return renderPdfTemplate('Goods Receiving Note', 'GRN #' . htmlspecialchars($grn['grn_number'] ?? ''), $body, $footer);
}

function generatePayrollPDF($payroll_id, $conn) {
    $query = "SELECT p.*, e.first_name, e.last_name, e.middle_name, e.employee_number, e.position, e.department,
              e.sss_enabled, e.philhealth_enabled, e.pagibig_enabled,
              u.full_name as processed_by_name
              FROM payroll p
              LEFT JOIN employees e ON p.employee_id = e.employee_id
              LEFT JOIN users u ON p.processed_by = u.id
              WHERE p.payroll_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $payroll_id);
    $stmt->execute();
    $payroll = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payroll) {
        return false;
    }

    // Get deductions
    $deductions_query = "
    SELECT type, description, amount
    FROM payroll_breakdown
    WHERE payroll_id = ?
    ORDER BY type DESC
    ";
    $deductions_stmt = $conn->prepare($deductions_query);
    $deductions_stmt->bind_param("i", $payroll_id);
    $deductions_stmt->execute();
    $deductions = $deductions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $deductions_stmt->close();

    $attendanceSummary = getAttendanceSummary($conn, $payroll['employee_id'], $payroll['payroll_period_start'], $payroll['payroll_period_end'], true);
    $payrollCalc = computePayrollFromAttendance(
        $conn,
        floatval($payroll['basic_salary']),
        getPayrollSetting($conn, 'working_days', 26),
        $attendanceSummary,
        floatval($payroll['overtime_pay']),
        floatval($payroll['allowances']),
        [
            'sss_enabled' => $payroll['sss_enabled'],
            'philhealth_enabled' => $payroll['philhealth_enabled'],
            'pagibig_enabled' => $payroll['pagibig_enabled'],
        ]
    );

    $html = generatePayrollHTML($payroll, $deductions, $attendanceSummary, $payrollCalc);
    return $html;
}

function generatePayrollHTML($payroll, $deductions, $attendanceSummary, $payrollCalc) {
    $employee_name = trim(($payroll['first_name'] ?? '') . ' ' . ($payroll['middle_name'] ? ($payroll['middle_name'] . ' ') : '') . ($payroll['last_name'] ?? ''));

    // Build deductions rows as HTML
    $deductions_html = '';
    foreach ($deductions as $d) {
        $deductions_html .= '<tr>' .
            '<td>' . htmlspecialchars($d['description']) . '</td>' .
            '<td class="text-right">' . ($d['type'] === 'deduction' ? '-' : '') . '₱' . number_format($d['amount'], 2) . '</td>' .
        '</tr>';
    }

    // Prepare common values for the payslip.
    $payDate = isset($payroll['created_at']) ? date('F d, Y', strtotime($payroll['created_at'])) : date('F d, Y');
    $periodFrom = isset($payroll['payroll_period_start']) ? date('F d, Y', strtotime($payroll['payroll_period_start'])) : 'N/A';
    $periodTo = isset($payroll['payroll_period_end']) ? date('F d, Y', strtotime($payroll['payroll_period_end'])) : 'N/A';
    $payType = 'Monthly';
    $taxCode = '1250L';
    $payrollNumber = $payroll['payroll_id'] ?? '';
    $niNumber = $payroll['employee_number'] ?? 'N/A';

    // Build earnings table rows
    $standardHours = round(($payrollCalc['days_worked'] ?? 0) * 8, 2);
    $standardRate = number_format($payrollCalc['daily_rate'] ?? 0, 2);
    $standardCurrent = number_format($payrollCalc['basic_pay'] ?? 0, 2);
    $standardYtd = $standardCurrent;

    $overtimeCurrent = number_format($payroll['overtime_pay'] ?? 0, 2);
    $overtimeYtd = $overtimeCurrent;

    $allowancesCurrent = number_format($payroll['allowances'] ?? 0, 2);
    $allowancesYtd = $allowancesCurrent;

    $netPayCurrent = number_format($payroll['net_pay'] ?? 0, 2);
    $netPayYtd = $netPayCurrent;

    // Build deduction table rows (current and YTD use same values if no YTD data exists)
    $deductionsRows = '';
    foreach ($deductions as $d) {
        $desc = htmlspecialchars($d['description']);
        $amt = number_format($d['amount'] ?? 0, 2);
        $deductionsRows .= '<tr><td>' . $desc . '</td><td class="text-right">₱' . $amt . '</td><td class="text-right">₱' . $amt . '</td></tr>';
    }

    // Template for the payslip (single-page layout)
    $body = '<style>' .
        '.payslip-row{display:flex; justify-content:space-between; gap:18px; flex-wrap:wrap; margin-bottom:16px;}' .
        '.payslip-col{flex:1 1 250px; min-width:250px;}' .
        '.info-table{width:100%; border:none; margin-top:8px;}' .
        '.info-table td{border:none; padding:4px 6px;}' .
        '.details-table{width:100%; margin-top:12px;}' .
        '.details-table th, .details-table td{padding:8px; font-size:12px;}' .
        '.details-table th{background:#f3f4f6; border:1px solid #e0e0e0;}' .
        '.details-table td{border:1px solid #e0e0e0;}' .
        '.totals-table{width:100%; margin-top:12px;}' .
        '.totals-table td{padding:8px; font-size:13px; border:none;}' .
        '.totals-label{font-weight:700;}' .
        '.totals-value{font-weight:700; text-align:right;}' .
        '.pay-header{font-size:14px; margin:0 0 4px; color:#1F2937;}' .
        '.pay-sub{font-size:11px; margin:0; color:#4a4a4a;}' .
    '</style>' .

    '<div class="payslip-row">' .
        '<div class="payslip-col">' .
            '<h2 class="pay-header">Company Name</h2>' .
            '<p class="pay-sub">Lorenzana Food Corporation</p>' .
            '<p class="pay-sub">Contacts</p>' .
            '<p class="pay-sub">Phone: +63 998 570 5492 / +63 928 521 4228,<br> Email: email@example.com</p>' .
        '</div>' .
        '<div class="payslip-col" style="text-align:right;">' .
            '<table class="info-table">' .
                '<tr><td class="label">Pay Date</td><td>' . $payDate . '</td></tr>' .
                '<tr><td class="label">Pay Type</td><td>' . $payType . '</td></tr>' .
                '<tr><td class="label">Payroll Period</td><td>' . $periodFrom . ' – ' . $periodTo . '</td></tr>' .
                '<tr><td class="label">Payroll #</td><td>' . htmlspecialchars($payrollNumber) . '</td></tr>' .
                '<tr><td class="label">Employee ID</td><td>' . htmlspecialchars($niNumber) . '</td></tr>' .
                '<tr><td class="label">Tax Code</td><td>' . $taxCode . '</td></tr>' .
                '<tr><td class="label">Payment Method</td><td>Check</td></tr>' .
            '</table>' .
        '</div>' .
    '</div>' .

    '<div class="payslip-row">' .
        '<div class="payslip-col">' .
            '<h3 class="pay-header">Employee Information</h3>' .
            '<table class="info-table">' .
                '<tr><td class="label">Full Name</td><td>' . htmlspecialchars($employee_name) . '</td></tr>' .
                '<tr><td class="label">Position</td><td>' . htmlspecialchars($payroll['position'] ?? 'N/A') . '</td></tr>' .
                '<tr><td class="label">Department</td><td>' . htmlspecialchars($payroll['department'] ?? 'N/A') . '</td></tr>' .
            '</table>' .
        '</div>' .
        '<div class="payslip-col"></div>' .
    '</div>' .

    '<div class="payslip-row">' .
        '<div class="payslip-col" style="flex:1 1 48%;">' .
            '<h4 class="pay-header">Earnings</h4>' .
            '<table class="details-table">' .
                '<tr><th>Description</th><th>Hours</th><th>Rate</th><th>Current</th><th>YTD</th></tr>' .
                '<tr><td>Standard Pay</td><td class="text-right">' . number_format($standardHours, 2) . '</td><td class="text-right">₱' . $standardRate . '</td><td class="text-right">₱' . $standardCurrent . '</td><td class="text-right">₱' . $standardYtd . '</td></tr>' .
                '<tr><td>Overtime Pay</td><td class="text-right">' . number_format($payroll['overtime_hours'] ?? 0, 2) . '</td><td class="text-right">-</td><td class="text-right">₱' . $overtimeCurrent . '</td><td class="text-right">₱' . $overtimeYtd . '</td></tr>' .
                '<tr><td>Holiday Pay</td><td class="text-right">0.00</td><td class="text-right">-</td><td class="text-right">₱0.00</td><td class="text-right">₱0.00</td></tr>' .
                '<tr><td>Basic Pay</td><td class="text-right">-</td><td class="text-right">-</td><td class="text-right">₱' . $standardCurrent . '</td><td class="text-right">₱' . $standardYtd . '</td></tr>' .
                '<tr><td>Commission & Bonus</td><td class="text-right">-</td><td class="text-right">-</td><td class="text-right">₱0.00</td><td class="text-right">₱0.00</td></tr>' .
                '<tr><td>Sick Pay</td><td class="text-right">-</td><td class="text-right">-</td><td class="text-right">₱0.00</td><td class="text-right">₱0.00</td></tr>' .
                '<tr><td>Expenses</td><td class="text-right">-</td><td class="text-right">-</td><td class="text-right">₱0.00</td><td class="text-right">₱0.00</td></tr>' .
            '</table>' .
        '</div>' .
        '<div class="payslip-col" style="flex:1 1 48%;">' .
            '<h4 class="pay-header">Deductions</h4>' .
            '<table class="details-table">' .
                '<tr><th>Description</th><th>Current</th><th>YTD</th></tr>' .
                $deductionsRows .
                '<tr><td class="totals-label">Total Deductions</td><td class="text-right"><strong>₱' . number_format(array_sum(array_column($deductions, 'amount')), 2) . '</strong></td><td class="text-right"><strong>₱' . number_format(array_sum(array_column($deductions, 'amount')), 2) . '</strong></td></tr>' .
            '</table>' .
        '</div>' .
    '</div>' .

    '<div class="totals-table">' .
        '<table>' .
            '<tr><td class="totals-label">Gross Pay</td><td class="totals-value">₱' . number_format($payrollCalc['gross_pay'] ?? 0, 2) . '</td><td class="totals-value">₱' . number_format($payrollCalc['gross_pay'] ?? 0, 2) . '</td></tr>' .
            '<tr><td class="totals-label">Net Pay</td><td class="totals-value">₱' . $netPayCurrent . '</td><td class="totals-value">₱' . $netPayYtd . '</td></tr>' .
        '</table>' .
    '</div>' .

    '<div style="margin-top:24px; font-size:11px; color:#555;">If you have any questions about this payslip, please contact: [Name, Phone, email@address.com]</div>';

    $footer = 'Generated on: ' . date('F d, Y h:i A');

    return renderPdfTemplate('Payslip', 'Employee #' . htmlspecialchars($payroll['employee_number'] ?? ''), $body, $footer);
}

function printPDF($html, $filename) {
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    echo '<script>window.onload = function() { window.print(); }</script>';
}

function generateSystemSummaryPDF($startDate, $endDate, $groupBy, $conn) {
    $dateFmt = $groupBy === 'month' ? "DATE_FORMAT(%s, '%Y-%m')" : "DATE(%s)";
    $invoiceGroup = sprintf($dateFmt, 'invoice_date');
    $expenseGroup = sprintf($dateFmt, 'expense_date');
    $deliveryGroup = sprintf($dateFmt, 'created_at');

    $dateCondition = function($col, $start, $end) {
        $conds = [];
        if ($start) $conds[] = "$col >= '" . $start . "'";
        if ($end) $conds[] = "$col <= '" . $end . "'";
        return $conds ? ' AND ' . implode(' AND ', $conds) : '';
    };
    $dateCondInvoices = $dateCondition('invoice_date', $startDate, $endDate);
    $dateCondExpenses = $dateCondition('expense_date', $startDate, $endDate);
    // Use fully qualified column for delivery assignment to avoid ambiguous column name errors
    $dateCondDeliveries = $dateCondition('created_at', $startDate, $endDate);
    $dateCondDeliveriesAlias = $dateCondition('da.created_at', $startDate, $endDate);

    $invoiceSql = "SELECT $invoiceGroup AS period, COUNT(*) AS invoice_count, COALESCE(SUM(amount),0) AS invoice_total FROM invoices WHERE status = 'Paid' $dateCondInvoices GROUP BY period ORDER BY period DESC";
    $expenseSql = "SELECT $expenseGroup AS period, COUNT(*) AS expense_count, COALESCE(SUM(amount),0) AS expense_total FROM expenses WHERE 1=1 $dateCondExpenses GROUP BY period ORDER BY period DESC";
    $deliverySql = "SELECT $deliveryGroup AS period, COUNT(*) AS deliveries_completed FROM delivery_assignments WHERE status = 'Delivered' $dateCondDeliveries GROUP BY period ORDER BY period DESC";

    $invoicesRes = $conn->query($invoiceSql);
    $expensesRes = $conn->query($expenseSql);
    $deliveriesRes = $conn->query($deliverySql);

    $summary = [];
    while ($row = $invoicesRes ? $invoicesRes->fetch_assoc() : null) {
        $period = $row['period'] ?? '';
        if (!$period) continue;
        $summary[$period]['invoice_count'] = (int)$row['invoice_count'];
        $summary[$period]['invoice_total'] = (float)$row['invoice_total'];
    }
    while ($row = $expensesRes ? $expensesRes->fetch_assoc() : null) {
        $period = $row['period'] ?? '';
        if (!$period) continue;
        $summary[$period]['expense_count'] = (int)$row['expense_count'];
        $summary[$period]['expense_total'] = (float)$row['expense_total'];
    }
    while ($row = $deliveriesRes ? $deliveriesRes->fetch_assoc() : null) {
        $period = $row['period'] ?? '';
        if (!$period) continue;
        $summary[$period]['deliveries_completed'] = (int)$row['deliveries_completed'];
    }

    krsort($summary);

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>System Summary</title><style>body{font-family:Arial;margin:20px;}table{width:100%;border-collapse:collapse;margin-bottom:20px;}th,td{border:1px solid #ddd;padding:10px;text-align:left;}th{background:#FF6B35;color:#fff;}h1,h2,h3{margin:0;}</style></head><body>';
    $html .= '<div style="text-align:center;margin-bottom:20px;"><h1>LORINS</h1><p>System Transaction Summary</p><p><strong>Period:</strong> ' . ($startDate ? htmlspecialchars($startDate) : 'All') . ' - ' . ($endDate ? htmlspecialchars($endDate) : 'All') . '</p><p><strong>Group By:</strong> ' . htmlspecialchars(ucfirst($groupBy)) . '</p></div>';

    $html .= '<h2>Summary</h2>';
    $html .= '<table><tr><th>' . ($groupBy === 'month' ? 'Month' : 'Date') . '</th><th>Invoices (Paid)</th><th>Income</th><th>Expenses</th><th>Deliveries</th></tr>';

    if (empty($summary)) {
        $html .= '<tr><td colspan="5" style="text-align:center; padding: 16px; color: #555;">No data found for the selected period.</td></tr>';
    } else {
        foreach ($summary as $period => $values) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($period) . '</td>';
            $html .= '<td>' . number_format($values['invoice_count'] ?? 0) . '</td>';
            $html .= '<td>₱' . number_format($values['invoice_total'] ?? 0, 2) . '</td>';
            $html .= '<td>₱' . number_format($values['expense_total'] ?? 0, 2) . '</td>';
            $html .= '<td>' . number_format($values['deliveries_completed'] ?? 0) . '</td>';
            $html .= '</tr>';
        }
    }
    $html .= '</table>';

    // Details sections
    $html .= '<h2>Details</h2>';

    // Paid invoices
    $invoiceListSql = "SELECT i.*, c.customer_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.customer_id WHERE i.status = 'Paid' $dateCondInvoices ORDER BY i.invoice_date DESC";
    $invoiceListRes = $conn->query($invoiceListSql);
    $html .= '<h3>Paid Invoices</h3>';
    $html .= '<table><tr><th>Invoice #</th><th>Date</th><th>Customer</th><th>Amount</th><th>Status</th></tr>';
    if ($invoiceListRes && $invoiceListRes->num_rows > 0) {
        while ($row = $invoiceListRes->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['invoice_number'] ?? $row['invoice_id']) . '</td>';
            $html .= '<td>' . date('Y-m-d', strtotime($row['invoice_date'])) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['customer_name'] ?? '') . '</td>';
            $html .= '<td>₱' . number_format($row['amount'] ?? 0, 2) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['status'] ?? '') . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="5" style="text-align:center; padding: 16px; color: #555;">No paid invoices found.</td></tr>';
    }
    $html .= '</table>';

    // Expenses
    $expenseListSql = "SELECT * FROM expenses WHERE 1=1 $dateCondExpenses ORDER BY expense_date DESC";
    $expenseListRes = $conn->query($expenseListSql);
    $html .= '<h3>Expenses</h3>';
    $html .= '<table><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th></tr>';
    if ($expenseListRes && $expenseListRes->num_rows > 0) {
        while ($row = $expenseListRes->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . date('Y-m-d', strtotime($row['expense_date'])) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['category'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['description'] ?? '') . '</td>';
            $html .= '<td>₱' . number_format($row['amount'] ?? 0, 2) . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="4" style="text-align:center; padding: 16px; color: #555;">No expenses found.</td></tr>';
    }
    $html .= '</table>';

    // Completed deliveries
    $deliveryListSql = "SELECT da.*, so.order_number FROM delivery_assignments da LEFT JOIN sales_orders so ON da.order_id = so.order_id WHERE da.status = 'Delivered' $dateCondDeliveriesAlias ORDER BY da.created_at DESC";
    $deliveryListRes = $conn->query($deliveryListSql);
    $html .= '<h3>Completed Deliveries</h3>';
    $html .= '<table><tr><th>Date</th><th>Delivery #</th><th>Order #</th><th>Status</th></tr>';
    if ($deliveryListRes && $deliveryListRes->num_rows > 0) {
        while ($row = $deliveryListRes->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . date('Y-m-d', strtotime($row['created_at'] ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['assignment_number'] ?? $row['assignment_id']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['order_number'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['status'] ?? '') . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="4" style="text-align:center; padding: 16px; color: #555;">No completed deliveries found.</td></tr>';
    }
    $html .= '</table>';

    $html .= '</body></html>';
    return $html;
}

function generateSalesReceiptPDF($order_id, $conn) {

    $stmt = $conn->prepare("
        SELECT so.*, c.customer_name, c.address, u.full_name as created_by_name
        FROM sales_orders so
        LEFT JOIN customers c ON so.customer_id = c.customer_id
        LEFT JOIN users u ON so.created_by = u.id
        WHERE so.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) return false;

    $items_stmt = $conn->prepare("
        SELECT oi.*, p.product_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $items_stmt->close();

    $body = '<div class="section-header">Sales Receipt</div>' .
        '<div>' .
            '<p><span class="label">Order #:</span> ' . htmlspecialchars($order['order_number'] ?? '') . '</p>' .
            '<p><span class="label">Date:</span> ' . (isset($order['order_date']) ? date('F d, Y', strtotime($order['order_date'])) : '') . '</p>' .
            '<p><span class="label">Customer:</span> ' . htmlspecialchars($order['customer_name'] ?? '') . '</p>' .
            '<p><span class="label">Fulfillment:</span> ' . htmlspecialchars($order['fulfillment_type'] ?? '') . '</p>' .
            '<p><span class="label">Status:</span> ' . htmlspecialchars($order['status'] ?? '') . '</p>' .
        '</div>' .
        '<table>' .
            '<tr><th>Product</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Subtotal</th></tr>';

    foreach ($items as $it) {
        $body .= '<tr>' .
            '<td>' . htmlspecialchars($it['product_name'] ?? '') . '</td>' .
            '<td class="text-right">' . number_format($it['quantity'] ?? 0, 2) . '</td>' .
            '<td class="text-right">₱' . number_format($it['unit_price'] ?? 0, 2) . '</td>' .
            '<td class="text-right">₱' . number_format($it['subtotal'] ?? 0, 2) . '</td>' .
        '</tr>';
    }

    $body .= '</table>' .
        '<div class="text-right"><strong>Total: ₱' . number_format($order['total_amount'] ?? 0, 2) . '</strong></div>';

    $footer = 'Processed by: ' . htmlspecialchars($order['created_by_name'] ?? 'System') . ' | Generated on: ' . date('F d, Y h:i A');

    return renderPdfTemplate('Sales Receipt', 'Order #' . htmlspecialchars($order['order_number'] ?? ''), $body, $footer);
}
?>