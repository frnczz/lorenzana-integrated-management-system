<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Get invoice ID from query string
$invoice_id = intval($_GET['invoice_id'] ?? 0);
$invoice = null;
if ($invoice_id > 0) {
    $inv_query = $conn->prepare("
        SELECT i.*, c.customer_name,
               COALESCE(SUM(p.amount), 0) as paid_amount,
               (i.amount - COALESCE(SUM(p.amount), 0)) as outstanding
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.customer_id
        LEFT JOIN payments p ON i.invoice_id = p.invoice_id
        WHERE i.invoice_id = ?
        GROUP BY i.invoice_id
    ");
    $inv_query->bind_param("i", $invoice_id);
    $inv_query->execute();
    $invoice = $inv_query->get_result()->fetch_assoc();
    $inv_query->close();
}

// Fetch all payments
$payments = [];
$payments_query = $conn->query("
    SELECT p.*, i.invoice_number, c.customer_name
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.invoice_id
    LEFT JOIN customers c ON i.customer_id = c.customer_id
    ORDER BY p.payment_date DESC, p.created_at DESC
    LIMIT 100
");
if ($payments_query) {
    while ($row = $payments_query->fetch_assoc()) {
        $payments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Recording | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .payment-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Payment Recording</h2>
            <p>Record customer payments for invoices.</p>
            <?php showMessage(); ?>
            
            <!-- Record Payment Form -->
            <div class="card">
                <h3><?php echo $invoice ? 'Record Payment for ' . htmlspecialchars($invoice['invoice_number']) : 'Record New Payment'; ?></h3>
                <?php if ($invoice): ?>
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($invoice['customer_name']); ?></p>
                    <p><strong>Invoice Amount:</strong> ₱<?php echo number_format($invoice['amount'], 2); ?></p>
                    <p><strong>Already Paid:</strong> ₱<?php echo number_format($invoice['paid_amount'], 2); ?></p>
                    <p><strong>Outstanding:</strong> ₱<?php echo number_format($invoice['outstanding'], 2); ?></p>
                <?php endif; ?>
                
                <form method="POST" action="api/save_payment.php">
                    <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
                    <table>
                        <?php if (!$invoice): ?>
                        <tr>
                            <td style="width: 180px;">Invoice</td>
                            <td>
                                <?php
                                $invoices_list = $conn->query("
                                    SELECT i.invoice_id, i.invoice_number, i.amount,
                                           (i.amount - COALESCE(SUM(p.amount), 0)) as outstanding
                                    FROM invoices i
                                    LEFT JOIN payments p ON i.invoice_id = p.invoice_id
                                    WHERE i.approval_status = 'Approved'
                                    GROUP BY i.invoice_id
                                    HAVING outstanding > 0
                                    ORDER BY i.invoice_date DESC
                                ");
                                ?>
                                <select name="invoice_id" id="invoice_select" style="width:100%; padding:8px;" required onchange="updateOutstanding()">
                                    <option value="">-- Select Invoice --</option>
                                    <?php if ($invoices_list): while ($inv = $invoices_list->fetch_assoc()): ?>
                                        <option value="<?php echo $inv['invoice_id']; ?>" 
                                                data-outstanding="<?php echo $inv['outstanding']; ?>">
                                            <?php echo htmlspecialchars($inv['invoice_number']); ?> 
                                            (Outstanding: ₱<?php echo number_format($inv['outstanding'], 2); ?>)
                                        </option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td>Payment Date</td>
                            <td><input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Payment Method</td>
                            <td>
                                <select name="payment_method" style="width:100%; padding:8px;" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Check">Check</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Other">Other</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Amount</td>
                            <td>
                                <input type="number" name="amount" id="payment_amount" 
                                       step="0.01" min="0.01" 
                                       value="<?php echo $invoice ? number_format($invoice['outstanding'], 2, '.', '') : ''; ?>"
                                       max="<?php echo $invoice ? $invoice['outstanding'] : ''; ?>"
                                       style="width:100%; padding:8px;" required>
                                <?php if ($invoice): ?>
                                    <small style="color: var(--text-muted);">Maximum: ₱<?php echo number_format($invoice['outstanding'], 2); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Reference Number</td>
                            <td>
                                <input type="text" name="reference_number" style="width:100%; padding:8px;" 
                                       placeholder="Check number, transaction ID, etc.">
                            </td>
                        </tr>
                        <tr>
                            <td>Notes</td>
                            <td>
                                <textarea name="notes" style="width:100%; padding:8px; min-height:80px;" 
                                          placeholder="Optional payment notes..."></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="button" class="btn" onclick="window.location.href='accounting_invoices.php'">Cancel</button>
                                <button type="submit" class="btn">Record Payment</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
            
            <!-- Payment History -->
            <div class="card">
                <h3>Payment History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Payment #</th>
                            <th>Date</th>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Reference</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($payments) > 0): ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($payment['payment_number']); ?></strong></td>
                                    <td><?php echo formatDate($payment['payment_date']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['invoice_number']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['customer_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                    <td><strong>₱<?php echo number_format($payment['amount'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($payment['reference_number'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['notes'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);">No payments recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>

<script src="assets/js/sidebar.js"></script>
<script>
function updateOutstanding() {
    var select = document.getElementById('invoice_select');
    var option = select.options[select.selectedIndex];
    var outstanding = parseFloat(option.getAttribute('data-outstanding')) || 0;
    document.getElementById('payment_amount').max = outstanding;
    document.getElementById('payment_amount').value = outstanding.toFixed(2);
}
</script>
</body>
</html>
