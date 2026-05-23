<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$invoice_id = intval($_GET['invoice_id'] ?? 0);
if ($invoice_id <= 0) {
    header("Location: procurement_invoices.php");
    exit;
}

$inv_q = $conn->prepare("SELECT si.*, s.supplier_name FROM supplier_invoices si LEFT JOIN suppliers s ON si.supplier_id = s.supplier_id WHERE si.invoice_id = ? LIMIT 1");
$inv_q->bind_param("i", $invoice_id);
$inv_q->execute();
$inv = $inv_q->get_result()->fetch_assoc();
$inv_q->close();
if (!$inv) {
    header("Location: procurement_invoices.php");
    exit;
}

$outstanding = floatval($inv['total_amount']) - floatval($inv['paid_amount']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Record Payment | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Record Payment for <?php echo htmlspecialchars($inv['invoice_number']); ?></h2>
            <?php showMessage(); ?>
            <div class="card">
                <form method="POST" action="api/pay_supplier_invoice.php">
                    <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
                    <table>
                        <tr><td>Supplier</td><td><?php echo htmlspecialchars($inv['supplier_name'] ?? '-'); ?></td></tr>
                        <tr><td>Outstanding</td><td>₱<?php echo number_format($outstanding,2); ?></td></tr>
                        <tr><td>Payment Date</td><td><input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required style="width:100%; padding:8px;"></td></tr>
                        <tr><td>Payment Method</td><td>
                            <select name="payment_method" style="width:100%; padding:8px;">
                                <option>Bank Transfer</option>
                                <option>Check</option>
                                <option>Cash</option>
                                <option>Other</option>
                            </select>
                        </td></tr>
                        <tr><td>Amount</td><td><input type="number" name="amount" step="0.01" min="0.01" max="<?php echo $outstanding; ?>" value="<?php echo number_format($outstanding,2,'.',''); ?>" required style="width:100%; padding:8px;"></td></tr>
                        <tr><td>Reference</td><td><input type="text" name="reference_number" style="width:100%; padding:8px;"></td></tr>
                        <tr><td>Notes</td><td><textarea name="notes" style="width:100%; padding:8px; min-height:80px;"></textarea></td></tr>
                        <tr><td colspan="2" style="text-align:right;"><button type="submit" class="btn">Record Payment</button></td></tr>
                    </table>
                </form>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
</body>
</html>
