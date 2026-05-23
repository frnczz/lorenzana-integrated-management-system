<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'procurement' && $_SESSION['role'] != 'warehouse')) {
    header("Location: login.php");
    exit;
}

include "includes/functions.php";
include "db_connect.php";

// Fetch statistics
$stats = [];

// Total suppliers
$stats['total_suppliers'] = $conn->query("SELECT COUNT(*) as count FROM suppliers")->fetch_assoc()['count'] ?? 0;
$stats['active_suppliers'] = $conn->query("SELECT COUNT(*) as count FROM suppliers WHERE status = 'Active'")->fetch_assoc()['count'] ?? 0;

// Purchase Requisitions
$stats['pending_prs'] = $conn->query("SELECT COUNT(*) as count FROM purchase_requisitions WHERE status = 'Submitted'")->fetch_assoc()['count'] ?? 0;
$stats['approved_prs'] = $conn->query("SELECT COUNT(*) as count FROM purchase_requisitions WHERE status = 'Approved'")->fetch_assoc()['count'] ?? 0;

// Purchase Orders
$stats['open_pos'] = $conn->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'Open'")->fetch_assoc()['count'] ?? 0;
$stats['partial_pos'] = $conn->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'Partially Received'")->fetch_assoc()['count'] ?? 0;

// GRNs
$stats['pending_grns'] = $conn->query("SELECT COUNT(*) as count FROM goods_receiving_notes WHERE qc_status = 'Pending'")->fetch_assoc()['count'] ?? 0;

// Supplier Invoices
$stats['unpaid_invoices'] = $conn->query("SELECT COUNT(*) as count FROM supplier_invoices WHERE payment_status = 'Unpaid'")->fetch_assoc()['count'] ?? 0;
$stats['total_unpaid'] = $conn->query("SELECT SUM(total_amount - paid_amount) as total FROM supplier_invoices WHERE payment_status IN ('Unpaid', 'Partially Paid')")->fetch_assoc()['total'] ?? 0;

// Recent POs
$recent_pos = [];
$recent_query = $conn->query("    
    SELECT po.po_id, po.po_number, po.order_date, s.supplier_name, po.status, po.total_amount
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    ORDER BY po.created_at DESC
    LIMIT 10
");
if ($recent_query) {
    while ($row = $recent_query->fetch_assoc()) {
        $recent_pos[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procurement Dashboard | LORINIMS</title>
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
        .stat-card:nth-child(6) { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        
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
        
        .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .quick-actions .btn { text-decoration: none; padding: 10px 16px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
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
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        
        table tbody tr:hover {
            background-color: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .pending-actions {
            list-style: none;
            padding: 0;
        }
        
        .pending-actions li {
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 6px;
            border-left: 4px solid;
        }
        
        .pending-actions li a {
            text-decoration: none;
            font-weight: 600;
            color: inherit;
        }
        
        .pending-actions li:hover {
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .stat-card .value { font-size: 1.5rem; }
            .dashboard-grid { grid-template-columns: 1fr; }
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
                <h2>Procurement Dashboard</h2>
                <p>Welcome, <strong><?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'; ?></strong> — Supplier management & purchase orders</p>
            </div>

            <?php showMessage(); ?>

            <!-- METRIC CARDS -->
            <div class="stat-grid">
                <div class="stat-card" onclick="window.location.href='procurement_suppliers.php'">
                    <div class="icon">🏢</div>
                    <div class="label">Active Suppliers</div>
                    <div class="value"><?php echo number_format($stats['active_suppliers']); ?></div>
                    <div class="meta">of <?php echo number_format($stats['total_suppliers']); ?> total</div>
                </div>
                <div class="stat-card" onclick="window.location.href='procurement_requisitions.php?status=Submitted'">
                    <div class="icon">📝</div>
                    <div class="label">Pending PR Approvals</div>
                    <div class="value"><?php echo number_format($stats['pending_prs']); ?></div>
                    <div class="meta">Need action</div>
                </div>
                <div class="stat-card" onclick="window.location.href='procurement_orders.php?status=Open'">
                    <div class="icon">📦</div>
                    <div class="label">Open Purchase Orders</div>
                    <div class="value"><?php echo number_format($stats['open_pos']); ?></div>
                    <div class="meta">Active</div>
                </div>
                <div class="stat-card" onclick="window.location.href='procurement_receiving.php'">
                    <div class="icon">📥</div>
                    <div class="label">Pending QC (GRN)</div>
                    <div class="value"><?php echo number_format($stats['pending_grns']); ?></div>
                    <div class="meta">To inspect</div>
                </div>
                <div class="stat-card" onclick="window.location.href='procurement_invoices.php'">
                    <div class="icon">💳</div>
                    <div class="label">Unpaid Invoices</div>
                    <div class="value"><?php echo number_format($stats['unpaid_invoices']); ?></div>
                    <div class="meta">Outstanding</div>
                </div>
                <div class="stat-card">
                    <div class="icon">💰</div>
                    <div class="label">Total Outstanding</div>
                    <div class="value" style="font-size: 1.5rem;">₱<?php echo number_format($stats['total_unpaid'], 0); ?></div>
                    <div class="meta">To pay</div>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="card">
                <h3>🚀 Quick Actions</h3>
                <div class="quick-actions">
                    <a href="procurement_requisitions.php" class="btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">+ New PR</a>
                    <a href="procurement_orders.php" class="btn" style="background: var(--bg-tertiary); color: var(--text-primary);">+ New PO</a>
                    <a href="procurement_receiving.php" class="btn" style="background: var(--bg-tertiary); color: var(--text-primary);">+ Receive Goods</a>
                    <a href="procurement_invoices.php" class="btn" style="background: var(--bg-tertiary); color: var(--text-primary);">+ Record Invoice</a>
                    <a href="procurement_suppliers.php" class="btn" style="background: var(--bg-tertiary); color: var(--text-primary);">+ Add Supplier</a>
                </div>
            </div>

            <!-- PENDING ACTIONS -->
            <div class="card">
                <h3>⚠️ Pending Actions</h3>
                <ul class="pending-actions">
                    <?php if ($stats['pending_prs'] > 0): ?>
                        <li style="background: rgba(245, 158, 11, 0.1); border-color: #f59e0b; color: #92400e;">
                            <a href="procurement_requisitions.php?status=Submitted">
                                <strong><?php echo $stats['pending_prs']; ?> PR(s) pending approval</strong>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($stats['pending_grns'] > 0): ?>
                        <li style="background: rgba(245, 158, 11, 0.1); border-color: #f59e0b; color: #92400e;">
                            <a href="procurement_receiving.php">
                                <strong><?php echo $stats['pending_grns']; ?> GRN(s) pending QC</strong>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($stats['open_pos'] > 0): ?>
                        <li style="background: rgba(59, 130, 246, 0.1); border-color: #3b82f6; color: #1e40af;">
                            <a href="procurement_orders.php?status=Open">
                                <strong><?php echo $stats['open_pos']; ?> Open PO(s) waiting for delivery</strong>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($stats['unpaid_invoices'] > 0): ?>
                        <li style="background: rgba(220, 38, 38, 0.1); border-color: #dc2626; color: #991b1b;">
                            <a href="procurement_invoices.php">
                                <strong><?php echo $stats['unpaid_invoices']; ?> Unpaid invoice(s) - ₱<?php echo number_format($stats['total_unpaid'], 0); ?> due</strong>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($stats['pending_prs'] == 0 && $stats['pending_grns'] == 0 && $stats['open_pos'] == 0 && $stats['unpaid_invoices'] == 0): ?>
                        <li style="background: rgba(16, 185, 129, 0.1); border-color: #10b981; color: #065f46;">
                            <strong>✓ All clear! No pending actions needed.</strong>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- RECENT PURCHASE ORDERS -->
            <div class="card">
                <h3>📋 Recent Purchase Orders</h3>
                <table>
                    <tr>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <?php if (count($recent_pos) > 0): ?>
                        <?php foreach ($recent_pos as $po): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($po['po_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($po['supplier_name']); ?></td>
                                <td><?php echo formatDate($po['order_date']); ?></td>
                                <td><?php echo formatCurrency($po['total_amount']); ?></td>
                                <td>
                                    <span class="status-badge" style="<?php 
                                        if ($po['status'] === 'Received') echo 'background: #d1fae5; color: #065f46;';
                                        elseif ($po['status'] === 'Partially Received') echo 'background: #fef3c7; color: #92400e;';
                                        else echo 'background: #dbeafe; color: #1e40af;';
                                    ?>">
                                        <?php echo htmlspecialchars($po['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="procurement_order_view.php?id=<?php echo $po['po_id']; ?>" class="status-badge" style="text-decoration: none; background: #dbeafe; color: #1e40af;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">
                                No recent purchase orders.
                            </td>
                        </tr>
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
