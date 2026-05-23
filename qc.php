<?php
session_start();

// Allow admin and quality control roles
if (!isset($_SESSION['user_id']) || 
   ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'qc')) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quality Control | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="wrapper">

    <!-- Sidebar -->
    <?php include "layouts/sidebar.php"; ?>

    <!-- Main Area -->
    <div class="main">

        <!-- Header -->
        <?php include "layouts/header.php"; ?>

        <!-- Content -->
        <div class="content">

            <h2>Quality Control Module</h2>
            <p>Record batch inspections, test results, and corrective actions.</p>

            <?php include "includes/functions.php"; showMessage(); ?>

            <!-- Batch Search -->
            <div class="card">
                <h3>Search Batch for Inspection</h3>
                <form>
                    <table>
                        <tr>
                            <td>Batch Number</td>
                            <td><input type="text" placeholder="Enter Batch No" style="width:100%; padding:8px;"></td>
                            <td>
                                <button type="button" class="btn">Search</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>


            <!-- QC Record Form -->
            <div class="card">
                <h3>Quality Inspection Record</h3>
                <form method="POST" action="api/save_qc.php" data-loading-message="Saving QC record..." data-loading-subtext="Recording quality inspection.">
                    <table>
                        <tr>
                            <td>Batch Number</td>
                            <td><input type="text" name="batch_number" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Inspector Name</td>
                            <td><input type="text" name="inspector_name" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Inspection Date</td>
                            <td><input type="date" name="inspection_date" style="width:100%; padding:8px;" value="<?php echo date('Y-m-d'); ?>" required></td>
                        </tr>
                        <tr>
                            <td>Test Result</td>
                            <td>
                                <select name="test_result" style="width:100%; padding:8px;" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Passed">Passed</option>
                                    <option value="Failed">Failed</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Non-Conformance Details</td>
                            <td><textarea name="non_conformance" style="width:100%; padding:8px;" rows="3"></textarea></td>
                        </tr>
                        <tr>
                            <td>Corrective Action</td>
                            <td><textarea name="corrective_action" style="width:100%; padding:8px;" rows="3"></textarea></td>
                        </tr>
                        <tr>
                            <td>Approval Status</td>
                            <td>
                                <select name="approval_status" style="width:100%; padding:8px;" required>
                                    <option value="For Re-inspection">For Re-inspection</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Save QC Record</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- QC Records Table -->
            <div class="card">
                <h3>Quality Control Records</h3>
                <?php
                include "db_connect.php";
                $qc_query = "SELECT * FROM qc_records ORDER BY inspection_date DESC, created_at DESC LIMIT 50";
                $qc_result = $conn->query($qc_query);
                ?>
                <table>
                    <tr>
                        <th>Batch No</th>
                        <th>Inspector</th>
                        <th>Date</th>
                        <th>Result</th>
                        <th>Approval</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php if ($qc_result && $qc_result->num_rows > 0): ?>
                        <?php while ($row = $qc_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['batch_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['inspector_name']); ?></td>
                                <td><?php echo formatDate($row['inspection_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['test_result']); ?></td>
                                <td><?php echo htmlspecialchars($row['approval_status']); ?></td>
                                <td><?php echo $row['test_result'] == 'Passed' && $row['approval_status'] == 'Approved' ? 'Completed' : 'Action Required'; ?></td>
                                <td>
                                    <a href="api/generate_pdf.php?type=qc_report&id=<?php echo $row['qc_id']; ?>" target="_blank" class="btn" style="padding: 6px 12px; font-size: 12px; text-decoration: none; display: inline-block;">📄 Report</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: var(--text-muted);">No QC records found.</td>
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
