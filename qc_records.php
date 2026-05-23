<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'qc')) {
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
    <title>QC Records | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Quality Control Records</h2>
            <p>View inspection history and reports.</p>
            <?php showMessage(); ?>
            <div class="card">
                <h3>Quality Control Records</h3>
                <?php
                $qc_result = $conn->query("SELECT * FROM qc_records ORDER BY inspection_date DESC, created_at DESC LIMIT 50");
                ?>
                <table>
                    <tr><th>Batch No</th><th>Inspector</th><th>Date</th><th>Result</th><th>Approval</th><th>Status</th><th>Actions</th></tr>
                    <?php if ($qc_result && $qc_result->num_rows > 0): ?>
                        <?php while ($row = $qc_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['batch_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['inspector_name']); ?></td>
                                <td><?php echo formatDate($row['inspection_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['test_result']); ?></td>
                                <td><?php echo htmlspecialchars($row['approval_status']); ?></td>
                                <td><?php echo ($row['test_result'] == 'Passed' && $row['approval_status'] == 'Approved') ? 'Completed' : 'Action Required'; ?></td>
                                <td><a href="api/generate_pdf.php?type=qc_report&id=<?php echo $row['qc_id']; ?>" target="_blank" class="btn" style="padding: 6px 12px; font-size: 12px; text-decoration: none; display: inline-block;">📄 Report</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted);">No QC records found.</td></tr>
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
