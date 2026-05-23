<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}

include "includes/functions.php";
include "db_connect.php";

// Get accounting metrics
$total_revenue = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'Paid'")->fetch_assoc()['total'] ?? 0;
$pending_inv = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'Pending'")->fetch_assoc()['total'] ?? 0;
$total_expenses = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses")->fetch_assoc()['total'] ?? 0;
$net = $total_revenue - $total_expenses;
$inv_count = $conn->query("SELECT COUNT(*) as c FROM invoices")->fetch_assoc()['c'] ?? 0;
$exp_count = $conn->query("SELECT COUNT(*) as c FROM expenses")->fetch_assoc()['c'] ?? 0;
$pending_count = $conn->query("SELECT COUNT(*) as c FROM invoices WHERE status = 'Pending'")->fetch_assoc()['c'] ?? 0;

// Pagination for dashboard tables
$inv_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM invoices", null, 'inv_page', 'inv_per_page')
    : ['offset'=>0,'per_page'=>10,'total'=>0,'total_pages'=>1,'page'=>1,'prev_page'=>null,'next_page'=>null];

$exp_pagination = function_exists('getPagination')
    ? getPagination($conn, "SELECT COUNT(*) as c FROM expenses", null, 'exp_page', 'exp_per_page')
    : ['offset'=>0,'per_page'=>10,'total'=>0,'total_pages'=>1,'page'=>1,'prev_page'=>null,'next_page'=>null];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Dashboard | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dash-hero { margin-bottom: 24px; }
        .dash-hero h2 { font-size: 1.75rem; font-weight: 700; margin-bottom: 4px; }
        .dash-hero p { color: var(--text-muted); font-size: 0.95rem; }
        
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 28px; }
        
        .stat-card { 
            padding: 22px; 
            border-radius: 12px; 
            color: white; 
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.1);
            transform: translateX(-100%);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card:hover::before {
            transform: translateX(0);
        }
        
        .stat-card .label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; font-weight: 600; opacity: 0.9; }
        .stat-card .value { font-size: 2rem; font-weight: 700; margin: 10px 0; }
        .stat-card .meta { font-size: 0.85rem; opacity: 0.85; }
        .stat-card .icon { font-size: 2.5rem; margin-bottom: 8px; }
        
        .stat-card:nth-child(1) { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card:nth-child(2) { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card:nth-child(3) { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card:nth-child(4) { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-card:nth-child(5) { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
        }
        
        .card h3 {
            margin: 0 0 20px 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 12px;
        }
        
        .quick-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .quick-actions .btn { text-decoration: none; padding: 12px 20px; border-radius: 6px; font-weight: 600; font-size: 0.9rem; }
        
        .alert-box {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border-left: 4px solid #f59e0b;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-left: 4px solid #10b981;
        }
        
        @media (max-width: 768px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .stat-card .value { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <div class="dash-hero">
                <h2>Accounting Dashboard</h2>
                <p>Welcome, <strong><?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'; ?></strong> — Financial overview and invoice management</p>
            </div>

            <?php showMessage(); ?>

            <!-- METRIC CARDS -->
            <div class="stat-grid">
                <div class="stat-card" onclick="window.location.href='accounting_invoices.php'">
                    <div class="icon">💵</div>
                    <div class="label">Revenue (Paid)</div>
                    <div class="value" style="font-size: 1.5rem;"><?php echo formatCurrency($total_revenue); ?></div>
                    <div class="meta">Confirmed income</div>
                </div>
                <div class="stat-card" onclick="window.location.href='accounting_invoices.php?status=Pending'">
                    <div class="icon">⏳</div>
                    <div class="label">Pending Invoices</div>
                    <div class="value" style="font-size: 1.5rem;"><?php echo formatCurrency($pending_inv); ?></div>
                    <div class="meta"><?php echo (int)$pending_count; ?> invoice(s)</div>
                </div>
                <div class="stat-card" onclick="window.location.href='accounting_expenses.php'">
                    <div class="icon">💸</div>
                    <div class="label">Total Expenses</div>
                    <div class="value" style="font-size: 1.5rem;"><?php echo formatCurrency($total_expenses); ?></div>
                    <div class="meta">All expenses</div>
                </div>
                <div class="stat-card">
                    <div class="icon">📊</div>
                    <div class="label">Net Income</div>
                    <div class="value" style="font-size: 1.5rem; color: <?php echo $net >= 0 ? '#10b981' : '#dc2626'; ?>"><?php echo formatCurrency($net); ?></div>
                    <div class="meta">Revenue - Expenses</div>
                </div>
                <div class="stat-card">
                    <div class="icon">📋</div>
                    <div class="label">Total Invoices</div>
                    <div class="value"><?php echo number_format($inv_count); ?></div>
                    <div class="meta">All records</div>
                </div>
            </div>

            <!-- ALERTS -->
            <?php if ($pending_count > 0): ?>
                <div class="alert-box alert-warning">
                    <strong>⚠️ Action Required:</strong> You have <?php echo $pending_count; ?> pending invoice(s) totaling <?php echo formatCurrency($pending_inv); ?> awaiting payment.
                </div>
            <?php else: ?>
                <div class="alert-box alert-success">
                    <strong>✓ All Clear:</strong> All invoices are either paid or pending collection.
                </div>
            <?php endif; ?>

            <!-- QUICK ACTIONS -->
            <div class="card">
                <h3>🚀 Quick Actions</h3>
                <div class="quick-actions">
                    <a href="accounting_invoices.php" class="btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">📋 Invoices (<?php echo (int)$inv_count; ?>)</a>
                    <a href="accounting_expenses.php" class="btn" style="background: var(--bg-tertiary); color: var(--text-primary);">💸 Expenses (<?php echo (int)$exp_count; ?>)</a>
                    <a href="accounting_payments.php" class="btn" style="background: var(--bg-tertiary); color: var(--text-primary);">💳 Payments</a>
                    <a href="accounting_dashboard.php" class="btn" style="background: var(--bg-tertiary); color: var(--text-primary);">📊 Reports</a>
                </div>
            </div>

            <!-- RECENT INVOICES -->
            <div class="card">
                <h3>📄 Recent Invoices</h3>                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin-bottom:12px;">' . renderPerPageSelector($conn, $inv_pagination['per_page'], 'inv_per_page', 'inv_page') . '</div>'; ?>                <?php
                try {
                    $recent_inv = $conn->query("
                        SELECT i.*, c.customer_name, so.order_number
                        FROM invoices i
                        LEFT JOIN customers c ON i.customer_id = c.customer_id
                        LEFT JOIN sales_orders so ON i.order_id = so.order_id
                        ORDER BY i.invoice_date DESC
                        LIMIT " . $inv_pagination['offset'] . ", " . $inv_pagination['per_page'] . "
                    ");
                } catch (Exception $e) {
                    $recent_inv = null;
                }
                ?>
                <table style="width: 100%; font-size: 14px;">
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <?php if ($recent_inv && $recent_inv->num_rows > 0): ?>
                        <?php while ($inv = $recent_inv->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($inv['invoice_number'] ?? $inv['id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($inv['customer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatCurrency($inv['amount']); ?></td>
                                <td><?php echo formatDate($inv['invoice_date']); ?></td>
                                <td>
                                    <span style="padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; background: <?php 
                                        echo $inv['status'] == 'Paid' ? 'rgba(16, 185, 129, 0.1); color: #10b981;' : 
                                            'rgba(245, 158, 11, 0.1); color: #f59e0b;'; 
                                    ?>">
                                        <?php echo htmlspecialchars($inv['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="accounting_invoices.php" style="text-decoration: none; padding: 4px 8px; border-radius: 6px; background: #dbeafe; color: #1e40af; font-size: 11px; font-weight: 600;">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">
                                No invoices found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($inv_pagination, 'inv_page'); ?>
            </div>

            <!-- RECENT EXPENSES -->
            <div class="card">
                <h3>💸 Recent Expenses</h3>
                <?php if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar" style="margin-bottom:12px;">' . renderPerPageSelector($conn, $exp_pagination['per_page'], 'exp_per_page', 'exp_page') . '</div>'; ?>
                <?php
                try {
                    $recent_exp = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC LIMIT " . $exp_pagination['offset'] . ", " . $exp_pagination['per_page']);
                } catch (Exception $e) {
                    $recent_exp = null;
                }
                ?>
                <table style="width: 100%; font-size: 14px;">
                    <tr>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                    <?php if ($recent_exp && $recent_exp->num_rows > 0): ?>
                        <?php while ($exp = $recent_exp->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($exp['description'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($exp['category'] ?? 'N/A'); ?></td>
                                <td><?php echo formatCurrency($exp['amount']); ?></td>
                                <td><?php echo formatDate($exp['expense_date'] ?? $exp['created_at']); ?></td>
                                <td>
                                    <a href="accounting_expenses.php" style="text-decoration: none; padding: 4px 8px; border-radius: 6px; background: #dbeafe; color: #1e40af; font-size: 11px; font-weight: 600;">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: var(--text-muted);">
                                No expenses found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($exp_pagination, 'exp_page'); ?>
            </div>

        </div>

        <?php include "layouts/footer.php"; ?>

    </div>

</div>

<script src="assets/js/sidebar.js"></script>

</body>
</html>
