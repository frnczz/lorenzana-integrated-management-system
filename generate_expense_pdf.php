<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include "db_connect.php";
include "includes/functions.php";

$sort = getSortParams('expense_date', ['category','amount','department','source','expense_date']);
$column_map = [
    'category' => 'category',
    'amount' => 'amount',
    'expense_date' => 'expense_date',
];
$order_by = isset($column_map[$sort['column']]) ? $column_map[$sort['column']] : 'expense_date';
$expenses_result = $conn->query("SELECT * FROM expenses ORDER BY " . $order_by . " " . $sort['order'] . " LIMIT 50");

// Build expense array with labels
$expenses = [];
$total_amount = 0;

if ($expenses_result && $expenses_result->num_rows > 0) {
    while ($exp = $expenses_result->fetch_assoc()) {
        $desc = $exp['description'] ?? '';
        $source = 'Manual';
        $invoice_link = null;
        $dept = 'Inventory/Warehouse';

        if (!empty($exp['supplier_invoice_id'])) {
            $source = 'Procurement';
            $invoice_link = 'procurement_invoice_view.php?id=' . intval($exp['supplier_invoice_id']);

            $dept_q = $conn->prepare("
                SELECT pr.department 
                FROM supplier_invoices si
                LEFT JOIN purchase_orders po ON si.po_id = po.po_id
                LEFT JOIN purchase_requisitions pr ON po.pr_id = pr.pr_id
                WHERE si.invoice_id = ?
                LIMIT 1
            ");
            $dept_q->bind_param("i", $exp['supplier_invoice_id']);
            $dept_q->execute();
            $drow = $dept_q->get_result()->fetch_assoc();
            $dept_q->close();

            if ($drow && !empty($drow['department'])) {
                $dept = $drow['department'];
            }
        }

        $exp['desc'] = $desc;
        $exp['source_label'] = $source;
        $exp['dept_label'] = $dept;
        $expenses[] = $exp;
        $total_amount += $exp['amount'];
    }
}

// Sort by department or source in PHP
if (in_array($sort['column'], ['department','source'])) {
    usort($expenses, function($a,$b) use($sort) {
        $field = $sort['column'] === 'department' ? 'dept_label' : 'source_label';
        $va = strtolower($a[$field] ?? '');
        $vb = strtolower($b[$field] ?? '');
        if ($va === $vb) return 0;
        if ($sort['order'] === 'ASC') {
            return $va < $vb ? -1 : 1;
        } else {
            return $va > $vb ? -1 : 1;
        }
    });
}

// Generate HTML for PDF
$html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.4; margin: 20px; }
        h1 { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #1e40af; color: white; padding: 10px; text-align: left; font-weight: bold; font-size: 12px; }
        td { border-bottom: 1px solid #ddd; padding: 8px; font-size: 12px; }
        tr:nth-child(even) { background: #f9fafb; }
        .total-row { background: #eff6ff; font-weight: bold; }
        .amount-right { text-align: right; }
        .source-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; }
        .procurement { background: #dbeafe; color: #1d4ed8; }
        .manual { background: #f3f4f6; color: #374151; }
        .footer { font-size: 10px; color: #6b7280; text-align: center; margin-top: 40px; }
    </style>
</head>
<body>
    <h1>Recent Expenses Report</h1>
    <p style='text-align:center; color:#6b7280;'>Generated on " . date('F d, Y H:i') . "</p>
    <table>
        <tr>
            <th>Category</th>
            <th>Amount</th>
            <th>Description</th>
            <th>Department</th>
            <th>Source</th>
            <th>Date</th>
        </tr>";

if (!empty($expenses)) {
    foreach ($expenses as $exp) {
        $badge_class = $exp['source_label'] === 'Procurement' ? 'procurement' : 'manual';
        $html .= "
        <tr>
            <td>" . htmlspecialchars($exp['category']) . "</td>
            <td class='amount-right'>" . formatCurrency($exp['amount']) . "</td>
            <td>" . htmlspecialchars($exp['desc'] ?: '-') . "</td>
            <td>" . htmlspecialchars($exp['dept_label']) . "</td>
            <td><span class='source-badge {$badge_class}'>" . $exp['source_label'] . "</span></td>
            <td>" . formatDate($exp['expense_date']) . "</td>
        </tr>";
    }
} else {
    $html .= "
        <tr>
            <td colspan='6' style='text-align:center; padding:20px;'>No expenses found.</td>
        </tr>";
}

$html .= "
        <tr class='total-row'>
            <td colspan='1'>Total</td>
            <td class='amount-right'>" . formatCurrency($total_amount) . "</td>
            <td colspan='4'></td>
        </tr>
    </table>
    <div class='footer'>
        <p>LORINIMS - Expense Management System</p>
    </div>
</body>
</html>";

// Generate PDF using DOMPDF or inline display
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="expenses_' . date('Y-m-d_H-i-s') . '.pdf"');

// Simple HTML to PDF (using output buffer or external library)
// For now, we'll output HTML viewable in browser as a printable document
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="expenses_' . date('Y-m-d_H-i-s') . '.html"');
echo $html;
?>
