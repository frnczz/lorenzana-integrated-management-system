<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'qc')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Fetch pending QC items from GRN and raw_material_qc
$pending_qc = [];
$status_filter = $_GET['status'] ?? 'Pending';
$status_filter_clean = strtolower($status_filter);

// Combined query: Get both QC records AND GRN items without QC records
$qc_where_clause = "WHERE LOWER(qc.qc_status) = '" . $conn->real_escape_string($status_filter_clean) . "'";

// Query for existing QC records
$qc_query = $conn->query("
    SELECT qc.qc_id, qc.qc_number, qc.grn_id, qc.grn_item_id, qc.item_name, qc.lot_number,
           qc.quantity_received, qc.quantity_accepted, qc.quantity_rejected,
           qc.qc_status, qc.approval_status, qc.inspection_date, qc.created_at,
           grn.grn_number, po.po_number, s.supplier_name,
           gi.quantity_received as gi_qty_received, gi.lot_number as grn_lot, gi.expiry_date as grn_expiry,
           u.username as inspected_by_name
    FROM raw_material_qc qc
    LEFT JOIN goods_receiving_notes grn ON qc.grn_id = grn.grn_id
    LEFT JOIN purchase_orders po ON grn.po_id = po.po_id
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN grn_items gi ON qc.grn_item_id = gi.grn_item_id
    LEFT JOIN users u ON qc.inspected_by = u.id
    $qc_where_clause
    ORDER BY qc.created_at DESC
    LIMIT 100
");

if ($qc_query) {
    while ($row = $qc_query->fetch_assoc()) {
        $pending_qc[] = $row;
    }
}

// If showing Pending filter, also fetch GRN items that don't have QC records yet
if (strtolower($status_filter) === 'pending') {
    $grn_items_query = $conn->query("
        SELECT NULL as qc_id, NULL as qc_number, gi.grn_id, gi.grn_item_id, gi.item_name, gi.lot_number,
               gi.quantity_received, 0 as quantity_accepted, 0 as quantity_rejected,
               'Pending' as qc_status, 'Pending' as approval_status, 
               CURDATE() as inspection_date, grn.created_at,
               grn.grn_number, po.po_number, s.supplier_name,
               gi.quantity_received as gi_qty_received, gi.lot_number as grn_lot, gi.expiry_date as grn_expiry,
               NULL as inspected_by_name
        FROM grn_items gi
        LEFT JOIN goods_receiving_notes grn ON gi.grn_id = grn.grn_id
        LEFT JOIN purchase_orders po ON grn.po_id = po.po_id
        LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
        WHERE gi.qc_record_id IS NULL OR gi.qc_record_id = 0
        ORDER BY grn.created_at DESC
        LIMIT 100
    ");
    
    if ($grn_items_query) {
        while ($row = $grn_items_query->fetch_assoc()) {
            $pending_qc[] = $row;
        }
    }
    
    // Sort combined results by date
    usort($pending_qc, function($a, $b) {
        $timeA = strtotime($a['created_at'] ?? 'now');
        $timeB = strtotime($b['created_at'] ?? 'now');
        return $timeB - $timeA;
    });
}

// Statistics
$stats = [];
$stats['pending'] = $conn->query("SELECT COUNT(*) as count FROM raw_material_qc WHERE LOWER(qc_status) = 'pending'")->fetch_assoc()['count'];
$stats['conditional'] = $conn->query("SELECT COUNT(*) as count FROM raw_material_qc WHERE LOWER(qc_status) = 'conditional'")->fetch_assoc()['count'];
$stats['passed'] = $conn->query("SELECT COUNT(*) as count FROM raw_material_qc WHERE LOWER(qc_status) = 'passed' AND approval_status = 'Approved'")->fetch_assoc()['count'];
$stats['failed'] = $conn->query("SELECT COUNT(*) as count FROM raw_material_qc WHERE LOWER(qc_status) = 'failed'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QC - Raw Materials | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .summary-card p {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .summary-card:nth-child(1) { background: linear-gradient(135deg, #fef3c7 0%, #f59e0b 100%); }
        .summary-card:nth-child(2) { background: linear-gradient(135deg, #fef3c7 0%, #f59e0b 100%); }
        .summary-card:nth-child(3) { background: linear-gradient(135deg, #d1fae5 0%, #10b981 100%); }
        .summary-card:nth-child(4) { background: linear-gradient(135deg, #fee2e2 0%, #ef4444 100%); }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-passed { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-conditional { background: #dbeafe; color: #1e40af; }
        
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>QC Inspection - Raw Materials</h2>
            <p>Inspect raw materials received from suppliers. Items appear here automatically when goods are received.</p>
            <?php showMessage(); ?>
            
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Pending QC</h3>
                    <p><?php echo $stats['pending']; ?></p>
                </div>
                <div class="summary-card">
                    <h3>Conditional</h3>
                    <p><?php echo $stats['conditional']; ?></p>
                </div>
                <div class="summary-card">
                    <h3>Passed</h3>
                    <p><?php echo $stats['passed']; ?></p>
                </div>
                <div class="summary-card">
                    <h3>Failed</h3>
                    <p><?php echo $stats['failed']; ?></p>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filter-bar">
                <a href="qc_raw_materials.php" class="btn <?php echo $status_filter === 'Pending' ? 'btn-primary' : ''; ?>">Pending</a>
                <a href="?status=Conditional" class="btn <?php echo $status_filter === 'Conditional' ? 'btn-primary' : ''; ?>">Conditional</a>
                <a href="?status=Passed" class="btn <?php echo $status_filter === 'Passed' ? 'btn-primary' : ''; ?>">Passed</a>
                <a href="?status=Failed" class="btn <?php echo $status_filter === 'Failed' ? 'btn-primary' : ''; ?>">Failed</a>
            </div>
            
            <!-- QC Items Table -->
            <div class="card">
                <h3>QC Inspection Items</h3>
                <table style="width:100%;">
                    <thead>
                        <tr>
                            <th>QC Number</th>
                            <th>GRN Number</th>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Item Name</th>
                            <th>Lot Number</th>
                            <th>Qty Received</th>
                            <th>Qty Accepted</th>
                            <th>Qty Rejected</th>
                            <th>Inspection <br>Result <span style="font-size:9px; color:#666;"></span></th>
                            <th>Approval <br>Status <span style="font-size:9px; color:#666;"></span></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pending_qc) > 0): ?>
                            <?php foreach ($pending_qc as $qc): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars(!empty($qc['qc_number']) ? $qc['qc_number'] : 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($qc['grn_number'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($qc['po_number'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($qc['supplier_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($qc['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($qc['lot_number'] ?? $qc['grn_lot'] ?? '-'); ?></td>
                                    <td><?php echo number_format($qc['quantity_received'], 2); ?></td>
                                    <td><?php echo number_format($qc['quantity_accepted'], 2); ?></td>
                                    <td><?php echo number_format($qc['quantity_rejected'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($qc['qc_status']); ?>">
                                            <?php echo htmlspecialchars($qc['qc_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="padding:4px 8px; border-radius:8px; font-size:11px; font-weight:600;
                                            <?php 
                                            if ($qc['approval_status'] === 'Approved') echo 'background:#d1fae5; color:#065f46;';
                                            elseif ($qc['approval_status'] === 'Rejected') echo 'background:#fee2e2; color:#991b1b;';
                                            else echo 'background:#fef3c7; color:#92400e;';
                                            ?>">
                                            <?php echo htmlspecialchars($qc['approval_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="qc_raw_material_form.php?<?php echo !empty($qc['qc_id']) ? 'id=' . $qc['qc_id'] : 'grn_item_id=' . $qc['grn_item_id']; ?>" class="btn" style="padding:4px 8px; font-size:12px;">
                                            <?php echo empty($qc['qc_id']) ? 'Create QC' : ($qc['qc_status'] === 'Pending' ? 'Inspect' : 'View'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="12" style="text-align:center;padding:30px;color:var(--text-muted);">
                                No QC items found. Items will appear here automatically when goods are received via GRN.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
</body>
</html>
