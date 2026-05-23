<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'production')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Auto-sync fermentation status by production date + configured duration days.
// If fermentation becomes Completed, production line status is set to Ready (unless already Completed/Rejected).
$ferm_duration_map = [];
$fd_res = @$conn->query("SELECT product_id, setting_value FROM production_settings WHERE setting_key = 'fermentation_duration_days'");
if ($fd_res) {
    while ($r = $fd_res->fetch_assoc()) {
        $ferm_duration_map[(int)$r['product_id']] = max(0, (int)$r['setting_value']);
    }
}

$sync_res = @$conn->query("
    SELECT pb.batch_id, pb.product_id, pb.batch_date, pb.status, pb.fermentation_status, COALESCE(p.fermentation_eligible, 1) AS fermentation_eligible
    FROM production_batches pb
    LEFT JOIN products p ON pb.product_id = p.product_id
");
if ($sync_res) {
    while ($b = $sync_res->fetch_assoc()) {
        $batch_id = (int)$b['batch_id'];
        $pid = (int)$b['product_id'];
        $eligible = (int)$b['fermentation_eligible'] === 1;
        $batch_date = (string)$b['batch_date'];
        $cur_ferm = (string)($b['fermentation_status'] ?? 'Not Applicable');
        $cur_status = (string)($b['status'] ?? 'Processing');

        $new_ferm = 'Not Applicable';
        if ($eligible) {
            $days = isset($ferm_duration_map[$pid]) ? max(0, (int)$ferm_duration_map[$pid]) : 0;
            if ($days <= 0) {
                $new_ferm = 'Not Started';
            } else {
                $start_ts = strtotime($batch_date . ' 00:00:00');
                $today_ts = strtotime(date('Y-m-d') . ' 00:00:00');
                if ($start_ts === false || $today_ts < $start_ts) {
                    $new_ferm = 'Not Started';
                } else {
                    $elapsed = (int)floor(($today_ts - $start_ts) / 86400);
                    if ($elapsed >= $days) {
                        $new_ferm = 'Completed';
                    } elseif ($elapsed <= 0) {
                        $new_ferm = 'Not Started';
                    } else {
                        $new_ferm = 'Ongoing';
                    }
                }
            }
        }

        $new_status = $cur_status;
        if ($new_ferm === 'Completed' && !in_array($cur_status, ['Completed', 'Rejected'], true)) {
            $new_status = 'Ready';
        }

        if ($new_ferm !== $cur_ferm || $new_status !== $cur_status) {
            $up = $conn->prepare("UPDATE production_batches SET fermentation_status = ?, status = ? WHERE batch_id = ?");
            $up->bind_param("ssi", $new_ferm, $new_status, $batch_id);
            $up->execute();
            $up->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Production Batch Records | LORINIMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
    .content h2 { color: #0f172a; font-weight: 700; letter-spacing: -0.02em; }
    .content > .card { border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 16px rgba(15,23,42,0.04); overflow: hidden; }
    .content .card h3 { color: #334155; font-size: 1.05rem; margin-bottom: 16px; }
    .content .card table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .content .card table > tr:first-child th,
    .content .card table thead th {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        color: #475569;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 12px 10px;
        border-bottom: 2px solid #e2e8f0;
    }
    .content .card table td { padding: 11px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    select.status-dropdown, select.fermentation-dropdown {
        padding: 6px 10px;
        border-radius: 8px;
        width: 100%;
        max-width: 160px;
        border: 1px solid #cbd5e1;
        background: #fff;
        font-size: 13px;
    }
    .status-processing { background-color: #f1f5f9 !important; }
    .status-ready { background-color: #fef9c3 !important; }
    .status-completed { background-color: #d1fae5 !important; }
    .status-rejected { background-color: #fee2e2 !important; }
    .qc-tooltip {
        cursor: help;
        border-bottom: 1px dotted #94a3b8;
        color: #475569;
    }
    tr[style*="background:#f3f4f6"] td {
        background: linear-gradient(90deg, #eef2ff 0%, #f8fafc 100%) !important;
        color: #312e81 !important;
        font-size: 13px !important;
        border-bottom: 1px solid #c7d2fe !important;
    }
</style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Production Batch Records</h2>
            <p>View and manage production batch history with QC status.</p>
            <?php showMessage(); ?>

            <div class="card">
                <h3>Production Batch Records</h3>
                <?php
                // Sorting: default newest first by batch_date
                $sort = getSortParams('batch_date', [
                    'batch_number',
                    'product_name',
                    'batch_date',
                    'quantity',
                    'status',
                    'qc_status'
                ]);

                // Map logical columns to actual DB columns
                $column_map = [
                    'batch_number' => 'pb.batch_number',
                    'product_name' => 'p.product_name',
                    'batch_date'   => 'pb.batch_date',
                    'quantity'     => 'pb.quantity',
                    'status'       => 'pb.status',
                    'qc_status'    => 'q.approval_status'
                ];

                $order_by = isset($column_map[$sort['column']])
                    ? $column_map[$sort['column']]
                    : 'pb.batch_date';

                // Detect whether production_batches has a request_id column so we can group by request/customer order
                $has_request_id = ($conn->query("SHOW COLUMNS FROM production_batches LIKE 'request_id'")->num_rows > 0);

                $pagination = function_exists('getPagination') ? getPagination($conn, "SELECT COUNT(*) as c FROM production_batches pb") : ['offset' => 0, 'per_page' => 25, 'total' => 0, 'total_pages' => 1];
                if (function_exists('renderPerPageSelector')) echo '<div class="pagination-toolbar">' . renderPerPageSelector($conn, $pagination['per_page']) . '</div>';
                ?>
                <?php
                $batches_query = "
                    SELECT 
                        pb.batch_id,
                        pb.batch_number,
                        pb.product_id,
                        pb.quantity,
                        pb.fermentation_status,
                        pb.packaging_status,
                        pb.status,
                        pb.batch_date,
                        " . ($has_request_id ? "pb.request_id," : "NULL AS request_id,") . "
                        p.product_name,
                        COALESCE(p.fermentation_eligible, 1) AS fermentation_eligible,
                        " . ($has_request_id ? "pr.customer_name," : "NULL AS customer_name,") . "
                        q.approval_status AS qc_status,
                        q.qc_number
                    FROM production_batches pb
                    LEFT JOIN products p ON pb.product_id = p.product_id
                    " . ($has_request_id ? "LEFT JOIN production_requests pr ON pb.request_id = pr.request_id" : "") . "
                    LEFT JOIN qc_records q ON q.batch_number = pb.batch_number
                    ORDER BY $order_by " . $sort['order'] . ",
                             " . ($has_request_id ? "pb.request_id ASC," : "") . " pb.batch_id ASC
                    LIMIT " . $pagination['offset'] . ", " . $pagination['per_page'] . "
                ";
                $batches_result = $conn->query($batches_query);

                $current_request_group = null;
                ?>
                <table>
                    <tr>
                        <th><?php echo sortHeader('batch_number', 'Batch No', $sort); ?></th>
                        <th><?php echo sortHeader('product_name', 'Product', $sort); ?></th>
                        <th><?php echo sortHeader('batch_date', 'Date', $sort); ?></th>
                        <th><?php echo sortHeader('quantity', 'Quantity', $sort); ?></th>
                        <th>Fermentation</th>
                        <th><?php echo sortHeader('status', 'Packaging', $sort); ?></th>
                        <th><?php echo sortHeader('status', 'Status', $sort); ?></th>
                        <th><?php echo sortHeader('qc_status', 'QC Status', $sort); ?></th>
                        <th>Actions</th>
                    </tr>
                    <?php if ($batches_result && $batches_result->num_rows > 0): ?>
                        <?php while ($batch = $batches_result->fetch_assoc()): ?>
                            <?php
                                // Group visually by linked production request / customer order when available
                                if (!empty($batch['request_id'])) {
                                    $group_key = 'REQ-' . (int)$batch['request_id'];
                                    if ($current_request_group !== $group_key) {
                                        $current_request_group = $group_key;
                                        $label = 'Production Request #' . (int)$batch['request_id'];
                                        if (!empty($batch['customer_name'])) {
                                            $label .= ' — ' . htmlspecialchars($batch['customer_name']);
                                        }
                                        echo '<tr style="background:#f3f4f6;"><td colspan="9" style="font-weight:600; padding:6px 10px; color:#374151;">' . $label . '</td></tr>';
                                    }
                                } else {
                                    $current_request_group = null;
                                }

                                $status_class = match($batch['status']){
                                    'Processing' => 'status-processing',
                                    'Ready' => 'status-ready',
                                    'Completed' => 'status-completed',
                                    'Rejected' => 'status-rejected',
                                    default => ''
                                };
                                $qc_display = $batch['qc_status'] ? htmlspecialchars($batch['qc_status']) : 'Pending';
                                $qc_tooltip = $batch['qc_number'] ? "QC#: {$batch['qc_number']}" : '';
                            ?>
                            <tr class="<?php echo $status_class; ?>">
                                <td><?php echo htmlspecialchars($batch['batch_number']); ?></td>
                                <td><?php echo htmlspecialchars($batch['product_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($batch['batch_date']); ?></td>
                                <td><?php echo number_format($batch['quantity'], 2); ?></td>
                                <td>
                                    <?php
                                    $fe = (int)($batch['fermentation_eligible'] ?? 1);
                                    $fs = $batch['fermentation_status'] ?? 'Not Applicable';
                                    $batch_done = ($batch['status'] === 'Completed');
                                    if ($fe === 0 || $batch_done) {
                                        echo htmlspecialchars($fs);
                                    } else {
                                        $f_opts = ['Not Applicable', 'Not Started', 'Ongoing', 'Completed'];
                                    ?>
                                        <select class="fermentation-dropdown" data-batch-id="<?php echo (int)$batch['batch_id']; ?>" data-prev="<?php echo htmlspecialchars($fs, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php foreach ($f_opts as $fo):
                                                $sel = ($fs === $fo) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo htmlspecialchars($fo); ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($fo); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php } ?>
                                </td>
                                <td><?php echo htmlspecialchars($batch['packaging_status']); ?></td>
                                <td>
                                    <?php if ($batch['status'] === 'Completed'): ?>
                                        <span class="status-completed" style="font-weight:600;">Completed</span>
                                    <?php else: ?>
                                        <select class="status-dropdown" data-batch-id="<?php echo (int)$batch['batch_id']; ?>">
                                            <?php
                                            $statuses = ['Processing','Ready'];
                                            foreach ($statuses as $status):
                                                $selected = ($batch['status'] === $status) ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $status; ?>" <?php echo $selected; ?>>
                                                    <?php echo $status; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="qc-tooltip" title="<?php echo $qc_tooltip; ?>">
                                        <?php echo $qc_display; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="api/generate_pdf.php?type=batch_report&id=<?php echo $batch['batch_id']; ?>" target="_blank" class="btn" style="padding: 6px 12px; font-size: 12px; text-decoration: none; display: inline-block;">📄 Report</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 20px; color: var(--text-muted);">No production batches found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
                <?php if (function_exists('renderPagination')) echo renderPagination($pagination); ?>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>

<script src="assets/js/sidebar.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    $('.status-dropdown').change(function(){
        var batchId = $(this).data('batch-id');
        var newStatus = $(this).val();

        $.ajax({
            url: 'api/update_batch_status.php',
            method: 'POST',
            data: { batch_id: batchId, status: newStatus },
            success: function(res){
                console.log(res);
                location.reload();
            },
            error: function(){
                alert('Failed to update status. Please try again.');
            }
        });
    });

    $('.fermentation-dropdown').change(function(){
        var $sel = $(this);
        var batchId = $sel.data('batch-id');
        var newFerm = $sel.val();
        $.ajax({
            url: 'api/update_batch_fermentation.php',
            method: 'POST',
            dataType: 'json',
            data: { batch_id: batchId, fermentation_status: newFerm },
            success: function(res){
                if (!res.success) {
                    alert(res.error || 'Update failed.');
                    $sel.val($sel.data('prev'));
                    return;
                }
                $sel.data('prev', newFerm);
                location.reload();
            },
            error: function(){
                alert('Failed to update fermentation. Please try again.');
                $sel.val($sel.data('prev'));
            }
        });
    });
});
</script>
</body>
</html>
