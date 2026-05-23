<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','qc'])) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Fetch QC records with status filtering
$pending_qc = [];
$status_filter = $_GET['status'] ?? 'Pending';
$status_filter_clean = strtolower($status_filter);

$qc_where_clause = "WHERE LOWER(qc.test_result) = '" . 
    $conn->real_escape_string($status_filter_clean) . "'";

$qc_query = $conn->query("
    SELECT 
        qc.qc_id,
        qc.qc_number,
        qc.batch_number,
        pb.quantity,
        qc.test_result,
        qc.approval_status,
        qc.inspection_date,
        qc.created_at,
        p.product_name
    FROM qc_records qc
    LEFT JOIN production_batches pb 
        ON qc.batch_number = pb.batch_number
    LEFT JOIN products p 
        ON pb.product_id = p.product_id
    $qc_where_clause
    ORDER BY qc.created_at DESC
    LIMIT 100
");

if ($qc_query) {
    while ($row = $qc_query->fetch_assoc()) {
        $pending_qc[] = $row;
    }
}

// Statistics
$stats = [];
$stats['pending'] = $conn->query("SELECT COUNT(*) as count FROM qc_records WHERE LOWER(test_result) = 'pending'")->fetch_assoc()['count'];
$stats['passed'] = $conn->query("SELECT COUNT(*) as count FROM qc_records WHERE LOWER(test_result) = 'passed' AND approval_status = 'Approved'")->fetch_assoc()['count'];
$stats['failed'] = $conn->query("SELECT COUNT(*) as count FROM qc_records WHERE LOWER(test_result) = 'failed'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QC - Finished Products | LORINIMS</title>
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
        .summary-card:nth-child(2) { background: linear-gradient(135deg, #d1fae5 0%, #10b981 100%); }
        .summary-card:nth-child(3) { background: linear-gradient(135deg, #fee2e2 0%, #ef4444 100%); }
        
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
            <h2>QC Inspection - Finished Products</h2>
            <p>Inspect finished product batches before release. Record test results and approval status.</p>
            <?php showMessage(); ?>
            
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Pending QC</h3>
                    <p><?php echo $stats['pending']; ?></p>
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
                <a href="qc_inspection.php" class="btn <?php echo $status_filter === 'Pending' ? 'btn-primary' : ''; ?>">Pending</a>
                <a href="?status=Passed" class="btn <?php echo $status_filter === 'Passed' ? 'btn-primary' : ''; ?>">Passed</a>
                <a href="?status=Failed" class="btn <?php echo $status_filter === 'Failed' ? 'btn-primary' : ''; ?>">Failed</a>
            </div>
            
            <!-- Action Button -->
            <div style="margin-bottom: 15px;">
                <a href="qc_inspection.php" class="btn" style="background:#3b82f6;">+ New QC Inspection</a>
            </div>
            
            <!-- QC Items Table -->
            <div class="card">
                <h3>QC Inspection Records</h3>
                <table style="width:100%;">
                    <thead>
                        <tr>
                            <th>QC Number</th>
                            <th>Batch Number</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Inspection Date</th>
                            <th>Test Result</th>
                            <th>Approval</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pending_qc) > 0): ?>
                            <?php foreach ($pending_qc as $qc): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($qc['qc_number'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($qc['batch_number'] ?? $qc['batch_num'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($qc['product_name'] ?? $qc['prod_name'] ?? '-'); ?></td>
                                    <td><?php echo number_format($qc['quantity'] ?? 0, 2); ?></td>
                                    <td><?php echo formatDate($qc['inspection_date']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($qc['test_result']); ?>">
                                            <?php echo htmlspecialchars($qc['test_result']); ?>
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
                                        <a href="qc_inspection.php?id=<?php echo $qc['qc_id']; ?>" class="btn" style="padding:4px 12px; font-size:12px;">
                                            <?php echo ($qc['test_result'] === 'Pending') ? 'Review' : 'View'; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);">
                                No QC inspection records found.
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
