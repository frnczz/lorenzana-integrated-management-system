<?php
session_start();

// Allow admin and accounting roles
if (!isset($_SESSION['user_id']) || 
   ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dash-hero { margin-bottom: 24px; }
        .dash-hero h2 { font-size: 1.75rem; font-weight: 700; margin-bottom: 4px; }
        .dash-hero p { color: var(--text-muted); font-size: 0.95rem; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 28px; }
        .stat-card { background: var(--bg-secondary); border-radius: var(--border-radius-lg); padding: 22px; box-shadow: var(--shadow); border: 1px solid var(--border-color); transition: transform var(--transition-fast), box-shadow var(--transition-fast); }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .stat-card .label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 6px; font-weight: 600; }
        .stat-card .value { font-size: 1.65rem; font-weight: 700; }
        .stat-card.revenue { border-left: 4px solid #10b981; } .stat-card.revenue .value { color: #059669; }
        .stat-card.expenses { border-left: 4px solid #ef4444; } .stat-card.expenses .value { color: #dc2626; }
        .stat-card.net { border-left: 4px solid #3b82f6; } .stat-card.net .value { color: #2563eb; }
    </style>
</head>
<body>

<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <div class="dash-hero">
                <h2>Accounting Module</h2>
                <p>Manage financial records, invoices, payments, and reports.</p>
            </div>

            <?php include "includes/functions.php"; showMessage(); ?>
            <?php include "db_connect.php"; ?>

            <?php
            $total_revenue = $conn->query("SELECT SUM(amount) as total FROM invoices WHERE status = 'Paid'")->fetch_assoc()['total'] ?? 0;
            $total_expenses = $conn->query("SELECT SUM(amount) as total FROM expenses")->fetch_assoc()['total'] ?? 0;
            $net_profit = $total_revenue - $total_expenses;
            ?>
            <div class="stat-grid">
                <div class="stat-card revenue">
                    <div class="label">Total Revenue</div>
                    <div class="value"><?php echo formatCurrency($total_revenue); ?></div>
                </div>
                <div class="stat-card expenses">
                    <div class="label">Total Expenses</div>
                    <div class="value"><?php echo formatCurrency($total_expenses); ?></div>
                </div>
                <div class="stat-card net">
                    <div class="label">Net Profit</div>
                    <div class="value"><?php echo formatCurrency($net_profit); ?></div>
                </div>
            </div>

            <div class="card">
                <h3>Create Invoice</h3>
                <form method="POST" action="api/save_invoice.php" data-loading-message="Saving invoice..." data-loading-subtext="Recording invoice.">
                    <table>
                        <tr>
                            <td>Customer</td>
                            <td>
                                <?php
                                $customers_query = "SELECT customer_id, customer_name FROM customers ORDER BY customer_name";
                                $customers_result = $conn->query($customers_query);
                                ?>
                                <select name="customer_id" style="width:100%; padding:8px;" required>
                                    <option value="">-- Select Customer --</option>
                                    <?php if ($customers_result && $customers_result->num_rows > 0): ?>
                                        <?php while ($cust = $customers_result->fetch_assoc()): ?>
                                            <option value="<?php echo $cust['customer_id']; ?>"><?php echo htmlspecialchars($cust['customer_name']); ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Order (Optional)</td>
                            <td>
                                <?php
                                $orders_query = "SELECT order_id, order_number FROM sales_orders ORDER BY order_date DESC";
                                $orders_result = $conn->query($orders_query);
                                ?>
                                <select name="order_id" style="width:100%; padding:8px;">
                                    <option value="">-- Select Order (Optional) --</option>
                                    <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                                        <?php while ($ord = $orders_result->fetch_assoc()): ?>
                                            <option value="<?php echo $ord['order_id']; ?>"><?php echo htmlspecialchars($ord['order_number']); ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Invoice Date</td>
                            <td><input type="date" name="invoice_date" style="width:100%; padding:8px;" value="<?php echo date('Y-m-d'); ?>" required></td>
                        </tr>
                        <tr>
                            <td>Due Date</td>
                            <td><input type="date" name="due_date" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Amount</td>
                            <td><input type="number" name="amount" step="0.01" style="width:100%; padding:8px;" placeholder="0.00" required></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <select name="status" style="width:100%; padding:8px;" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Overdue">Overdue</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Save Invoice</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Invoices List -->
            <div class="card">
                <h3>Recent Invoices</h3>
                <?php
                $invoices_query = "SELECT i.*, c.customer_name FROM invoices i 
                                 LEFT JOIN customers c ON i.customer_id = c.customer_id 
                                 ORDER BY i.invoice_date DESC, i.created_at DESC LIMIT 50";
                $invoices_result = $conn->query($invoices_query);
                ?>
                <table>
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <?php if ($invoices_result && $invoices_result->num_rows > 0): ?>
                        <?php while ($inv = $invoices_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($inv['invoice_number']); ?></td>
                                <td><?php echo htmlspecialchars($inv['customer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($inv['invoice_date']); ?></td>
                                <td><?php echo formatCurrency($inv['amount']); ?></td>
                                <td><?php echo htmlspecialchars($inv['status']); ?></td>
                                <td>
                                    <a href="api/generate_pdf.php?type=invoice&id=<?php echo $inv['invoice_id']; ?>" target="_blank" class="btn" style="padding: 6px 12px; font-size: 12px; text-decoration: none; display: inline-block;">📄 Invoice</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">No invoices found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Expense Records -->
            <div class="card">
                <h3>Expense Records</h3>
                <form method="POST" action="api/save_expense.php" data-loading-message="Saving expense..." data-loading-subtext="Recording expense.">
                    <table>
                        <tr>
                            <td>Expense Category</td>
                            <td>
                                <select name="category" style="width:100%; padding:8px;" required>
                                    <option value="Raw Materials">Raw Materials</option>
                                    <option value="Labor">Labor</option>
                                    <option value="Utilities">Utilities</option>
                                    <option value="Transportation">Transportation</option>
                                    <option value="Other">Other</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Amount</td>
                            <td><input type="number" name="amount" step="0.01" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Description</td>
                            <td><textarea name="description" style="width:100%; padding:8px;" rows="3"></textarea></td>
                        </tr>
                        <tr>
                            <td>Date</td>
                            <td><input type="date" name="expense_date" style="width:100%; padding:8px;" value="<?php echo date('Y-m-d'); ?>" required></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Record Expense</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Expenses List -->
            <div class="card">
                <h3>Recent Expenses</h3>
                <?php
                $expenses_query = "SELECT * FROM expenses ORDER BY expense_date DESC, created_at DESC LIMIT 50";
                $expenses_result = $conn->query($expenses_query);
                ?>
                <table>
                    <tr>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Date</th>
                    </tr>
                    <?php if ($expenses_result && $expenses_result->num_rows > 0): ?>
                        <?php while ($exp = $expenses_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exp['category']); ?></td>
                                <td><?php echo formatCurrency($exp['amount']); ?></td>
                                <td><?php echo htmlspecialchars($exp['description'] ?? '-'); ?></td>
                                <td><?php echo formatDate($exp['expense_date']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted);">No expenses found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

        </div>

        <!-- Footer -->
        <?php include "layouts/footer.php"; ?>

    </div>

</div>

<script src="assets/js/sidebar.js"></script>

</body>
</html>
