<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'qc') {
    header("Location: login.php");
    exit;
}

include "includes/functions.php";
include "db_connect.php";

// Get QC metrics
$total_inspections = $conn->query("SELECT COUNT(*) as count FROM qc_records")->fetch_assoc()['count'] ?? 0;
$today_inspections = $conn->query("SELECT COUNT(*) as count FROM qc_records WHERE DATE(inspection_date) = CURDATE()")->fetch_assoc()['count'] ?? 0;
$pending_inspections = $conn->query("SELECT COUNT(*) as count FROM qc_records WHERE approval_status IN ('Pending', 'For Re-inspection')")->fetch_assoc()['count'] ?? 0;
$passed_inspections = $conn->query("SELECT COUNT(*) as count FROM qc_records WHERE test_result = 'Passed' AND approval_status = 'Approved'")->fetch_assoc()['count'] ?? 0;
$failed_inspections = $conn->query("SELECT COUNT(*) as count FROM qc_records WHERE test_result = 'Failed'")->fetch_assoc()['count'] ?? 0;
$pass_rate = $total_inspections > 0 ? round(($passed_inspections / $total_inspections) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quality Control Dashboard | LORINIMS</title>
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
        
        table {
            width: 100%;
            border-collapse: collapse;
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
            letter-spacing: 0.5px;
        }
        
        table tbody tr:hover {
            background-color: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .alert-box {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-left: 4px solid #10b981;
        }
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border-left: 4px solid #f59e0b;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }
        
        .action-grid .btn {
            text-decoration: none;
            padding: 12px;
            text-align: center;
            border-radius: 6px;
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
                <h2>Quality Control Dashboard</h2>
                <p>Welcome, <strong><?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'; ?></strong> — Inspection & quality assurance</p>
            </div>

            <?php showMessage(); ?>

            <!-- METRIC CARDS -->
            <div class="stat-grid">
                <div class="stat-card" onclick="window.location.href='qc.php'">
                    <div class="icon">📋</div>
                    <div class="label">Total Inspections</div>
                    <div class="value"><?php echo number_format($total_inspections); ?></div>
                    <div class="meta">All time</div>
                </div>
                <div class="stat-card" onclick="window.location.href='qc.php'">
                    <div class="icon">📅</div>
                    <div class="label">Today's Inspections</div>
                    <div class="value"><?php echo number_format($today_inspections); ?></div>
                    <div class="meta"><?php echo date('M d, Y'); ?></div>
                </div>
                <div class="stat-card" onclick="window.location.href='qc.php?status=Pending'">
                    <div class="icon">⏳</div>
                    <div class="label">Pending</div>
                    <div class="value"><?php echo number_format($pending_inspections); ?></div>
                    <div class="meta">Need review</div>
                </div>
                <div class="stat-card" onclick="window.location.href='qc.php?result=Passed'">
                    <div class="icon">✅</div>
                    <div class="label">Passed</div>
                    <div class="value"><?php echo number_format($passed_inspections); ?></div>
                    <div class="meta">Approved</div>
                </div>
                <div class="stat-card" onclick="window.location.href='qc.php?result=Failed'">
                    <div class="icon">❌</div>
                    <div class="label">Failed</div>
                    <div class="value"><?php echo number_format($failed_inspections); ?></div>
                    <div class="meta">Rejected</div>
                </div>
                <div class="stat-card">
                    <div class="icon">📊</div>
                    <div class="label">Pass Rate</div>
                    <div class="value"><?php echo number_format($pass_rate, 1); ?>%</div>
                    <div class="meta">Quality metric</div>
                </div>
            </div>

            <!-- ALERTS -->
            <?php if ($pending_inspections > 0): ?>
                <div class="alert-box alert-warning">
                    <strong>⚠️ Action Required:</strong> You have <?php echo $pending_inspections; ?> inspection(s) pending approval.
                </div>
            <?php endif; ?>

            <!-- RECENT QC RECORDS -->
            <div class="card">
                <h3>🔍 Recent QC Records</h3>
                <?php
                try {
                    $recent_qc = $conn->query("
                        SELECT * FROM qc_records 
                        ORDER BY inspection_date DESC, created_at DESC 
                        LIMIT 10
                    ");
                } catch (Exception $e) {
                    $recent_qc = null;
                }
                ?>
                <table>
                    <tr>
                        <th>Batch No</th>
                        <th>Inspector</th>
                        <th>Date</th>
                        <th>Result</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php if ($recent_qc && $recent_qc->num_rows > 0): ?>
                        <?php while ($qc = $recent_qc->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($qc['batch_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($qc['inspector_name']); ?></td>
                                <td><?php echo formatDate($qc['inspection_date']); ?></td>
                                <td>
                                    <span class="status-badge" style="background: <?php echo $qc['test_result'] == 'Passed' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(220, 38, 38, 0.1)'; ?>; color: <?php echo $qc['test_result'] == 'Passed' ? '#10b981' : '#dc2626'; ?>;">
                                        <?php echo htmlspecialchars($qc['test_result']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: <?php 
                                        echo $qc['approval_status'] == 'Approved' ? 'rgba(16, 185, 129, 0.1)' : 
                                             ($qc['approval_status'] == 'For Re-inspection' ? 'rgba(245, 158, 11, 0.1)' : 'rgba(107, 114, 128, 0.1)'); 
                                    ?>; color: <?php 
                                        echo $qc['approval_status'] == 'Approved' ? '#10b981' : 
                                             ($qc['approval_status'] == 'For Re-inspection' ? '#f59e0b' : '#6b7280'); 
                                    ?>;">
                                        <?php echo htmlspecialchars($qc['approval_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="qc.php" class="status-badge" style="text-decoration: none; background: #dbeafe; color: #1e40af;">Review</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">
                                <p style="margin: 0;">No QC records found.</p>
                                <a href="qc.php" class="btn" style="display: inline-block; margin-top: 10px; text-decoration: none;">Create First Inspection</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- PENDING INSPECTIONS -->
            <div class="card">
                <h3>⏳ Pending Inspections</h3>
                <?php
                try {
                    $pending_qc = $conn->query("
                        SELECT pb.batch_id, pb.batch_number, pb.product_id, pb.quantity, pb.warehouse_location, pb.status AS batch_status,
                               qc.record_id, qc.inspector_name, qc.inspection_date, qc.test_result, qc.approval_status
                        FROM production_batches pb
                        LEFT JOIN qc_records qc ON pb.batch_number = qc.batch_number 
                             AND qc.approval_status IN ('Pending', 'For Re-inspection')
                        WHERE pb.status != 'Completed'
                        ORDER BY qc.inspection_date ASC, pb.batch_id ASC
                        LIMIT 5
                    ");
                } catch (Exception $e) {
                    $pending_qc = null;
                }
                ?>

                <?php if ($pending_qc && $pending_qc->num_rows > 0): ?>
                    <table>
                        <tr>
                            <th>Batch No</th>
                            <th>Inspector</th>
                            <th>Date</th>
                            <th>Result</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <?php while ($qc = $pending_qc->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($qc['batch_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($qc['inspector_name'] ?? '-'); ?></td>
                                <td><?php echo !empty($qc['inspection_date']) ? formatDate($qc['inspection_date']) : '-'; ?></td>
                                <td>
                                    <span class="status-badge" style="background: <?php
                                        $result = $qc['test_result'] ?? 'Not Inspected';
                                        echo $result == 'Passed' ? 'rgba(16, 185, 129, 0.1)' : 
                                             ($result == 'Failed' ? 'rgba(220, 38, 38, 0.1)' : 'rgba(107, 114, 128, 0.1)');
                                    ?>; color: <?php
                                        echo $result == 'Passed' ? '#10b981' : ($result == 'Failed' ? '#dc2626' : '#6b7280');
                                    ?>;">
                                        <?php echo $result; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: <?php
                                        $status = $qc['approval_status'] ?? 'Not Inspected';
                                        echo $status == 'Approved' ? 'rgba(16, 185, 129, 0.1)' :
                                             ($status == 'For Re-inspection' ? 'rgba(245, 158, 11, 0.1)' : 'rgba(107, 114, 128, 0.1)');
                                    ?>; color: <?php
                                        echo $status == 'Approved' ? '#10b981' :
                                             ($status == 'For Re-inspection' ? '#f59e0b' : '#6b7280');
                                    ?>;">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="qc.php" class="status-badge" style="text-decoration: none; background: rgba(16, 185, 129, 0.1); color: #10b981;">Review</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p style="color: #10b981; font-weight: bold; padding: 20px; text-align: center; background: rgba(16, 185, 129, 0.1); border-radius: 8px;">
                        ✓ No pending inspections!
                    </p>
                <?php endif; ?>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="card">
                <h3>🚀 Quick Actions</h3>
                <div class="action-grid">
                    <a href="qc.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">✅ New Inspection</a>
                    <a href="production.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">🏭 View Batches</a>
                    <a href="qc_raw_materials.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">🧪 Raw Materials QC</a>
                    <a href="reports.php" class="btn" style="text-decoration: none; text-align: center; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">📊 View Reports</a>
                </div>
            </div>

        </div>

        <?php include "layouts/footer.php"; ?>

    </div>

</div>

<script src="assets/js/sidebar.js"></script>

</body>
</html>
