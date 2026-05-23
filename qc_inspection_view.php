<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','qc'])) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

$qc_id = intval($_GET['id'] ?? 0);
if ($qc_id <= 0) {
    header("Location: qc_inspection.php");
    exit;
}

// Fetch QC record and related batch/product/request info
$stmt = $conn->prepare(
    "SELECT qc.*, pb.batch_id, pb.product_id, pb.quantity AS batch_quantity, pb.warehouse_location, pb.request_id,
            p.product_name, pr.customer_name
     FROM qc_records qc
     LEFT JOIN production_batches pb ON qc.batch_number = pb.batch_number
     LEFT JOIN products p ON pb.product_id = p.product_id
     LEFT JOIN production_requests pr ON pb.request_id = pr.request_id
     WHERE qc.qc_id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $qc_id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    $_SESSION['error'] = "QC record not found.";
    header("Location: qc_inspection.php");
    exit;
}

$stockDestination = !empty($record['request_id']) ? 'Reserved (Production Request)' : 'Available';

// Determine back link based on approval status
$backStatus = 'Pending';
if (strtolower($record['test_result'] ?? '') === 'passed' || strtolower($record['approval_status'] ?? '') === 'approved') {
    $backStatus = 'Passed';
} elseif (strtolower($record['test_result'] ?? '') === 'failed' || strtolower($record['approval_status'] ?? '') === 'rejected') {
    $backStatus = 'Failed';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QC Record View | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .detail-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .detail-table th, .detail-table td { padding: 10px 12px; text-align: left; vertical-align: top; }
        .detail-table th { width: 220px; background: #f3f4f6; }
        .detail-table tr:nth-child(even) td { background: #fafafa; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-weight: 600; }
        .badge-pass { background: #d1fae5; color: #065f46; }
        .badge-fail { background: #fee2e2; color: #991b1b; }
        .badge-pending { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>QC Record Details</h2>
            <p>Viewing QC record <strong><?php echo htmlspecialchars($record['qc_number'] ?? 'N/A'); ?></strong>.</p>
            <?php showMessage(); ?>

            <div class="card">
                <h3>QC Summary</h3>
                <table class="detail-table">
                    <tr><th>QC Number</th><td><?php echo htmlspecialchars($record['qc_number'] ?? '-'); ?></td></tr>
                    <tr><th>Batch Number</th><td><?php echo htmlspecialchars($record['batch_number'] ?? '-'); ?></td></tr>
                    <tr><th>Product</th><td><?php echo htmlspecialchars($record['product_name'] ?? '-'); ?></td></tr>
                    <tr><th>Quantity</th><td><?php echo number_format($record['quantity'] ?? 0, 2); ?></td></tr>
                    <tr><th>Warehouse</th><td><?php echo htmlspecialchars($record['warehouse_location'] ?? '-'); ?></td></tr>
                    <tr><th>Stock Destination</th><td><?php echo htmlspecialchars($stockDestination); ?></td></tr>
                    <tr><th>Inspection Date</th><td><?php echo formatDate($record['inspection_date']); ?></td></tr>
                    <tr><th>Inspector</th><td><?php echo htmlspecialchars($record['inspector_name'] ?? '-'); ?></td></tr>
                    <tr><th>Test Result</th><td>
                        <?php
                            $resultLower = strtolower($record['test_result'] ?? '');
                            $badgeClass = $resultLower === 'passed' ? 'badge-pass' : ($resultLower === 'failed' ? 'badge-fail' : 'badge-pending');
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($record['test_result'] ?? '-'); ?></span>
                    </td></tr>
                    <tr><th>Approval Status</th><td><?php echo htmlspecialchars($record['approval_status'] ?? '-'); ?></td></tr>
                    <tr><th>Non-Conformance</th><td><?php echo nl2br(htmlspecialchars($record['non_conformance_details'] ?? '-')); ?></td></tr>
                    <tr><th>Corrective Action</th><td><?php echo nl2br(htmlspecialchars($record['corrective_action'] ?? '-')); ?></td></tr>
                    <?php if (!empty($record['request_id'])): ?>
                        <tr><th>Production Request</th><td>Request #<?php echo (int)$record['request_id']; ?><?php echo !empty($record['customer_name']) ? ' ('.htmlspecialchars($record['customer_name']).')' : ''; ?></td></tr>
                    <?php endif; ?>
                    <tr><th>Created</th><td><?php echo formatDate($record['created_at'] ?? null); ?></td></tr>
                </table>

                <div style="margin-top:20px; text-align:right;">
                    <a href="qc_inspection.php?status=<?php echo urlencode($backStatus); ?>" class="btn">Back to list</a>
                </div>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>

<script src="assets/js/sidebar.js"></script>
</body>
</html>
