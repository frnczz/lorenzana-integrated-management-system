<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$invoice_id = intval($_GET['id'] ?? 0);
if ($invoice_id <= 0) {
    header("Location: procurement_invoices.php");
    exit;
}

$inv_query = $conn->prepare("
    SELECT si.*, s.supplier_name, s.contact_person, s.contact_number, s.email,
           po.po_number, u.username as created_by_name
    FROM supplier_invoices si
    LEFT JOIN suppliers s ON si.supplier_id = s.supplier_id
    LEFT JOIN purchase_orders po ON si.po_id = po.po_id
    LEFT JOIN users u ON si.created_by = u.id
    WHERE si.invoice_id = ?
");
$inv_query->bind_param("i", $invoice_id);
$inv_query->execute();
$invoice = $inv_query->get_result()->fetch_assoc();
$inv_query->close();

if (!$invoice) {
    header("Location: procurement_invoices.php");
    exit;
}

$outstanding = $invoice['total_amount'] - $invoice['paid_amount'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Supplier Invoice | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Supplier Invoice: <?php echo htmlspecialchars($invoice['invoice_number']); ?></h2>
            <?php showMessage(); ?>
            
            <div class="card">
                <h3>Invoice Details</h3>
                <table>
                    <tr><td style="width:200px;"><strong>Invoice Number:</strong></td><td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td></tr>
                    <tr><td><strong>Supplier:</strong></td><td><?php echo htmlspecialchars($invoice['supplier_name']); ?></td></tr>
                    <tr><td><strong>Contact:</strong></td><td><?php echo htmlspecialchars($invoice['contact_person'] ?? '-'); ?></td></tr>
                    <tr><td><strong>Contact Number:</strong></td><td><?php echo htmlspecialchars($invoice['contact_number'] ?? $invoice['phone'] ?? '-'); ?></td></tr>
                    <tr><td><strong>Email:</strong></td><td><?php echo htmlspecialchars($invoice['email'] ?? '-'); ?></td></tr>
                    <tr><td><strong>PO Number:</strong></td><td><?php echo htmlspecialchars($invoice['po_number'] ?? '-'); ?></td></tr>
                    <tr><td><strong>Invoice Date:</strong></td><td><?php echo formatDate($invoice['invoice_date']); ?></td></tr>
                    <tr><td><strong>Due Date:</strong></td><td><?php echo $invoice['due_date'] ? formatDate($invoice['due_date']) : '-'; ?></td></tr>
                    <tr><td><strong>Subtotal:</strong></td><td>₱<?php echo number_format($invoice['subtotal'], 2); ?></td></tr>
                    <tr><td><strong>Tax Amount:</strong></td><td>₱<?php echo number_format($invoice['tax_amount'], 2); ?></td></tr>
                    <tr><td><strong>Total Amount:</strong></td><td><strong>₱<?php echo number_format($invoice['total_amount'], 2); ?></strong></td></tr>
                    <tr><td><strong>Paid Amount:</strong></td><td>₱<?php echo number_format($invoice['paid_amount'], 2); ?></td></tr>
                    <tr><td><strong>Outstanding:</strong></td><td><strong style="color:#dc2626;">₱<?php echo number_format($outstanding, 2); ?></strong></td></tr>
                    <tr><td><strong>Payment Status:</strong></td><td>
                        <span style="padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;
                            <?php 
                            if ($invoice['payment_status'] === 'Paid') echo 'background:#d1fae5; color:#065f46;';
                            elseif ($invoice['payment_status'] === 'Partially Paid') echo 'background:#fef3c7; color:#92400e;';
                            else echo 'background:#fee2e2; color:#991b1b;';
                            ?>">
                            <?php echo htmlspecialchars($invoice['payment_status']); ?>
                        </span>
                    </td></tr>
                    <?php if ($invoice['notes']): ?>
                        <tr><td><strong>Notes:</strong></td><td><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
</body>
</html>
