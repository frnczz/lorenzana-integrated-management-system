<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Expenses</h2>
            <p>Record and view expense records. Expenses are automatically created from <strong>Procurement</strong> (supplier invoices) and can be entered manually below.</p>
            <?php showMessage(); ?>
            <?php 
            $total_expenses = $conn->query("SELECT SUM(amount) as total FROM expenses")->fetch_assoc()['total'] ?? 0; 
            $auto_expenses = $conn->query("SELECT COUNT(*) as c, COALESCE(SUM(amount),0) as total FROM expenses WHERE description LIKE 'Auto:%'")->fetch_assoc();
            $auto_count = $auto_expenses['c'] ?? 0;
            $auto_total = $auto_expenses['total'] ?? 0;
            ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div class="card">
                    <h3>Total Expenses</h3>
                    <p style="font-size: 20px; font-weight: bold; color: #dc2626;"><?php echo formatCurrency($total_expenses); ?></p>
                </div>
                <div class="card" style="border-left: 4px solid #3b82f6;">
                    <h3>From Other Modules</h3>
                    <p style="font-size: 18px; font-weight: bold; margin: 0;"><?php echo formatCurrency($auto_total); ?></p>
                    <small style="color: var(--text-muted);"><?php echo (int)$auto_count; ?> auto-recorded (e.g. Procurement)</small>
                </div>
            </div>
            <div class="card">
                <h3>Record Expense</h3>
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
                        <tr><td>Amount</td><td><input type="number" name="amount" step="0.01" style="width:100%; padding:8px;" required></td></tr>
                        <tr><td>Description</td><td><textarea name="description" style="width:100%; padding:8px;" rows="3"></textarea></td></tr>
                        <tr><td>Date</td><td><input type="date" name="expense_date" style="width:100%; padding:8px;" value="<?php echo date('Y-m-d'); ?>" required></td></tr>
                        <tr><td colspan="2" style="text-align:right;"><button type="submit" class="btn">Record Expense</button></td></tr>
                    </table>
                </form>
            </div>
            <div class="card">
                <h3>Recent Expenses</h3>
                <p style="color: var(--text-muted); margin-top: -5px; margin-bottom: 10px;">Manual entries and auto-recorded expenses from Procurement and other modules.</p>
                <p style="margin-bottom:10px;">
                    <a href="generate_expense_pdf.php" target="_blank" class="btn" style="font-size:14px;">Download PDF</a>
                </p>
                <?php
            // Sorting setup
            $sort = getSortParams('expense_date', ['category','amount','department','source','expense_date']);
            $column_map = [
                'category' => 'category',
                'amount' => 'amount',
                'expense_date' => 'expense_date',
                // department and source will be sorted in PHP
            ];
            $order_by = isset($column_map[$sort['column']]) ? $column_map[$sort['column']] : 'expense_date';
            $expenses_result = $conn->query("SELECT * FROM expenses ORDER BY " . $order_by . " " . $sort['order'] . " LIMIT 50");
            ?>
                <table>
                    <tr>
                        <th><?php echo sortHeader('category','Category',$sort); ?></th>
                        <th><?php echo sortHeader('amount','Amount',$sort); ?></th>
                        <th>Description</th>
                        <th><?php echo sortHeader('department','Department',$sort); ?></th>
                        <th><?php echo sortHeader('source','Source',$sort); ?></th>
                        <th><?php echo sortHeader('expense_date','Date',$sort); ?></th>
                    </tr>
                    <?php
                    // Build expense array for sorting and formatting
                    $expenses = [];
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
                            $exp['invoice_link'] = $invoice_link;

                            $expenses[] = $exp;
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
                    ?>
                    <?php if (!empty($expenses)): ?>
                        <?php foreach ($expenses as $exp): 
                            $desc = $exp['desc'];
                            $source = $exp['source_label'];
                            $dept = $exp['dept_label'];
                            $invoice_link = $exp['invoice_link'];
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exp['category']); ?></td>
                                <td><?php echo formatCurrency($exp['amount']); ?></td>
                                <td>
                                    <?php if ($invoice_link): ?>
                                        <a href="<?php echo $invoice_link; ?>"><?php echo htmlspecialchars($desc ?: '-'); ?></a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($desc ?: '-'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($dept); ?></td>
                                <td><span style="padding:2px 8px; border-radius:6px; font-size:11px; background:<?php echo $source === 'Procurement' ? '#dbeafe' : '#f3f4f6'; ?>; color:<?php echo $source === 'Procurement' ? '#1d4ed8' : '#374151'; ?>;">
                                    <?php echo $source; ?></span></td>
                                <td><?php echo formatDate($exp['expense_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted);">No expenses found.</td></tr>
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
