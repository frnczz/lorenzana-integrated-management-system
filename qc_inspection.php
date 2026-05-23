<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','qc'])) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

// Load QC settings
$min_pass_score = floatval(getQCSetting($conn, 'min_pass_score', 85, true));
$mandatory_fields = getQCSetting($conn, 'mandatory_fields', 'Appearance, Weight, Seal');
$auto_reject = getQCSetting($conn, 'auto_reject', '1');
$show_form = isset($_GET['new']) || isset($_GET['edit']);
$qc_id = intval($_GET['edit'] ?? 0);

// Fetch QC records with status filtering
$pending_qc = [];
$status_filter = $_GET['status'] ?? 'Pending';
$status_filter_clean = strtolower($status_filter);

$sort_qc = getSortParams('created_at', ['qc_number', 'batch_number', 'product_name', 'quantity', 'inspection_date', 'test_result', 'approval_status', 'created_at']);
$column_map_qc = ['qc_number' => 'qc.qc_number', 'batch_number' => 'qc.batch_number', 'product_name' => 'p.product_name', 'quantity' => 'pb.quantity', 'inspection_date' => 'qc.inspection_date', 'test_result' => 'qc.test_result', 'approval_status' => 'qc.approval_status', 'created_at' => 'qc.created_at'];

// Pagination setup
$pagination = function_exists('getPagination') ? getPagination($conn, "SELECT COUNT(*) as c FROM qc_records", null, 'qc_page', 'qc_per_page') : ['offset' => 0, 'per_page' => 25, 'total' => 0];

$order_by_qc = isset($column_map_qc[$sort_qc['column']]) ? $column_map_qc[$sort_qc['column']] : 'qc.created_at';

if (!$show_form) {
    // Detect request_id column to enable grouping by production request / order
    $has_request_id = ($conn->query("SHOW COLUMNS FROM production_batches LIKE 'request_id'")->num_rows > 0);

    if ($status_filter_clean === 'pending') {
        // Pending QC should show production batches that are ready for inspection
        $qc_query = $conn->query("
            SELECT 
                NULL AS qc_id, 
                NULL AS qc_number, 
                pb.batch_id,
                pb.batch_number, 
                pb.quantity, 
                'Pending' AS test_result, 
                'For Re-inspection' AS approval_status, 
                NULL AS inspection_date, 
                pb.created_at, 
                p.product_name, 
                " . ($has_request_id ? "pb.request_id," : "NULL AS request_id,") . " 
                " . ($has_request_id ? "pr.customer_name" : "NULL AS customer_name") . " 
            FROM production_batches pb 
            LEFT JOIN products p ON pb.product_id = p.product_id 
            " . ($has_request_id ? "LEFT JOIN production_requests pr ON pb.request_id = pr.request_id" : "") . " 
            LEFT JOIN qc_records qc ON qc.batch_number = pb.batch_number AND LOWER(qc.test_result) = 'pending' 
            WHERE pb.status = 'Ready'
              AND (qc.qc_id IS NULL OR LOWER(qc.test_result) = 'pending') 
            ORDER BY " . ($has_request_id ? "pb.request_id ASC, " : "") . $order_by_qc . " " . $sort_qc['order'] . " 
            LIMIT " . $pagination['per_page'] . " OFFSET " . $pagination['offset'] . "
        ");
    } else {
        $qc_where_clause = "WHERE LOWER(qc.test_result) = '" . $conn->real_escape_string($status_filter_clean) . "'";

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
                p.product_name,
                " . ($has_request_id ? "pb.request_id," : "NULL AS request_id,") . "
                " . ($has_request_id ? "pr.customer_name" : "NULL AS customer_name") . "
            FROM qc_records qc
            LEFT JOIN production_batches pb 
                ON qc.batch_number = pb.batch_number
            LEFT JOIN products p 
                ON pb.product_id = p.product_id
            " . ($has_request_id ? "LEFT JOIN production_requests pr ON pb.request_id = pr.request_id" : "") . "
            $qc_where_clause
            ORDER BY " . ($has_request_id ? "pb.request_id ASC, " : "") . $order_by_qc . " " . $sort_qc['order'] . "
            LIMIT " . $pagination['per_page'] . " OFFSET " . $pagination['offset'] . "
        ");
    }

    if ($qc_query) {
        while ($row = $qc_query->fetch_assoc()) {
            $pending_qc[] = $row;
        }
    }
}

// Statistics
$stats = [];
// Pending QC counts should reflect batches ready for inspection
$stats['pending'] = $conn->query("SELECT COUNT(*) as count FROM production_batches WHERE status = 'Ready'")->fetch_assoc()['count'];
$stats['passed'] = $conn->query("SELECT COUNT(*) as count FROM qc_records WHERE LOWER(test_result) = 'passed' AND approval_status = 'Approved'")->fetch_assoc()['count'];
$stats['failed'] = $conn->query("SELECT COUNT(*) as count FROM qc_records WHERE LOWER(test_result) = 'failed'")->fetch_assoc()['count'];
?>
<?php
// QC Dynamic Options (Global Definition)
$non_conformance_failed = [
    'Contamination',
    'Off-Flavor / Odor',
    'Incorrect Viscosity / Consistency',
    'Separation / Settling',
    'Wrong Label',
    'Damaged Packaging',
    'Incorrect Net Weight',
    'Expired Raw Material',
    'Foreign Particles',
    'Other'
];

$non_conformance_passed = [
    'None',
    'Minor Deviation (Acceptable)',
    'Minor Color Variation',
    'Label Print Slightly Off',
    'Slight Separation (Within Spec)',
    'Trace Sediment (Acceptable)',
    'Packaging Crease / Dent',
    'Batch Variation Within Tolerance',
    'Aroma Slightly Different',
    'Other'
];

$corrective_action_failed = [
    'Reprocess',
    'Repackage',
    'Adjust Formulation',
    'Hold for Investigation',
    'Return to Supplier',
    'Discard',
    'Relabel',
    'Quarantine Batch',
    'Adjust Mixing / Blending',
    'Other'
];

$corrective_action_passed = [
    'None',
    'Release for Distribution',
    'Seal & Ship',
    'Monitor Shelf-Life',
    'Notify QC Team',
    'Minor Rework',
    'Update Documentation',
    'Archive Batch',
    'Confirm Packaging Integrity',
    'Other'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QC - Finished Products | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single { height: 38px !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px !important; }
        
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
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <?php if (!$show_form): ?>
                <!-- QC List View -->
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
                    <a href="qc_inspection.php?<?php echo !empty($sort_qc['query_string']) ? trim($sort_qc['query_string'], '&') . '&' : ''; ?>qc_page=1&qc_per_page=<?php echo $pagination['per_page']; ?>" class="btn <?php echo $status_filter === 'Pending' ? 'btn-primary' : ''; ?>">Pending</a>
                    <a href="qc_inspection.php?status=Passed&<?php echo !empty($sort_qc['query_string']) ? trim($sort_qc['query_string'], '&') . '&' : ''; ?>qc_page=1&qc_per_page=<?php echo $pagination['per_page']; ?>" class="btn <?php echo $status_filter === 'Passed' ? 'btn-primary' : ''; ?>">Passed</a>
                    <a href="qc_inspection.php?status=Failed&<?php echo !empty($sort_qc['query_string']) ? trim($sort_qc['query_string'], '&') . '&' : ''; ?>qc_page=1&qc_per_page=<?php echo $pagination['per_page']; ?>" class="btn <?php echo $status_filter === 'Failed' ? 'btn-primary' : ''; ?>">Failed</a>
                    <a href="?new=1" class="btn" style="background:#3b82f6; margin-left: auto;">+ New QC Record</a>
                </div>
                
                <!-- QC Items Table -->
                <div class="card">
                    <h3>QC Inspection Records</h3>
                    <table style="width:100%;">
                        <thead>
                            <tr>
                                <th><?php echo sortHeader('qc_number', 'QC Number', $sort_qc); ?></th>
                                <th><?php echo sortHeader('batch_number', 'Batch Number', $sort_qc); ?></th>
                                <th><?php echo sortHeader('product_name', 'Product Name', $sort_qc); ?></th>
                                <th><?php echo sortHeader('quantity', 'Quantity', $sort_qc); ?></th>
                                <th><?php echo sortHeader('inspection_date', 'Inspection Date', $sort_qc); ?></th>
                                <th><?php echo sortHeader('test_result', 'Test Result', $sort_qc); ?></th>
                                <th><?php echo sortHeader('approval_status', 'Approval', $sort_qc); ?></th>
                                <th>Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pending_qc) > 0): ?>
                                <?php 
                                    $current_group = null;
                                    foreach ($pending_qc as $qc):
                                        // Group QC rows visually by linked production request when available
                                        if (!empty($qc['request_id'])) {
                                            $group_key = 'REQ-' . (int)$qc['request_id'];
                                            if ($current_group !== $group_key) {
                                                $current_group = $group_key;
                                                $label = 'Production Request #' . (int)$qc['request_id'];
                                                if (!empty($qc['customer_name'])) {
                                                    $label .= ' — ' . htmlspecialchars($qc['customer_name']);
                                                }
                                                echo '<tr style="background:#f3f4f6;"><td colspan="10" style="font-weight:600; padding:6px 10px; color:#374151;">' . $label . '</td></tr>';
                                            }
                                        } else {
                                            $current_group = null;
                                        }
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($qc['qc_number'] ?? 'N/A'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($qc['batch_number'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($qc['product_name'] ?? '-'); ?></td>
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
                                            <?php
                                                $stockDest = !empty($qc['request_id']) ? 'Reserved' : 'Available';
                                            ?>
                                            <span style="font-weight:600; color:<?php echo $stockDest === 'Reserved' ? '#b45309' : '#065f46'; ?>;">
                                                <?php echo $stockDest; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($status_filter_clean === 'pending'): ?>
                                                <a href="qc_inspection.php?new=1&batch_id=<?php echo (int)$qc['batch_id']; ?>" class="btn" style="background:#3b82f6; color:#fff; padding:6px 12px;">Inspect</a>
                                            <?php else: ?>
                                                <a href="qc_inspection_view.php?id=<?php echo (int)$qc['qc_id']; ?>" class="btn" style="background:#6b7280; color:#fff; padding:6px 12px;">View</a>
                                            <?php endif; ?>
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
                    <?php if (function_exists('renderPagination')): ?>
                        <div style="margin-top: 20px; text-align: center;">
                            <?php renderPagination($pagination, 'qc_page', 'qc_per_page'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- QC Form View -->
                <h2>Record Quality Inspection</h2>
                <p>Record batch inspections and test results.</p>
                <?php showMessage(); ?>
                
                <div class="card">
                    <h3>Quality Inspection Record</h3>

                    <form method="POST" action="api/save_qc.php" data-loading-message="Saving QC record..." data-loading-subtext="Recording quality inspection.">

                    <?php
                    // Fetch batches that are Ready for QC
                    $batches = [];
                    $batch_q = $conn->query("
                        SELECT pb.batch_id, pb.batch_number, pb.quantity, p.product_name, p.product_id
                        FROM production_batches pb
                        LEFT JOIN products p ON pb.product_id = p.product_id
                        WHERE pb.status = 'Ready'
                        ORDER BY pb.batch_date DESC, pb.created_at DESC
                    ");
                    if($batch_q){
                        while($row = $batch_q->fetch_assoc()) $batches[] = $row;
                    }
                    ?>

                    <?php $prefillBatchId = intval($_GET['batch_id'] ?? 0); ?>
                    <div class="form-grid">
                        <div>
                            <label>Select Batch *</label>
                            <select name="batch_id" id="batch_id" style="width:100%;" required>
                                <option value="">-- Select Batch --</option>
                                <?php foreach($batches as $b): ?>
                                    <option value="<?php echo $b['batch_id']; ?>"
                                        <?php echo $prefillBatchId === (int)$b['batch_id'] ? 'selected' : ''; ?>
                                        data-batch-number="<?php echo htmlspecialchars($b['batch_number']); ?>"
                                        data-product-name="<?php echo htmlspecialchars($b['product_name']); ?>"
                                        data-quantity="<?php echo (float)$b['quantity']; ?>">
                                        <?php echo htmlspecialchars($b['batch_number']); ?> — <?php echo htmlspecialchars($b['product_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Inspection Date *</label>
                            <input type="date" name="inspection_date" style="width:100%; padding:8px;" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top:20px;">
                        <div>
                            <label>Batch Number</label>
                            <input type="text" id="batch_number" readonly style="width:100%; padding:8px; background:#f3f4f6;">
                        </div>
                        <div>
                            <label>Product Name</label>
                            <input type="text" id="product_name" readonly style="width:100%; padding:8px; background:#f3f4f6;">
                        </div>
                        <div>
                            <label>Quantity</label>
                            <input type="number" id="quantity" readonly style="width:100%; padding:8px; background:#f3f4f6;">
                        </div>
                        <div>
                            <label>Inspector Name *</label>
                            <input type="text" name="inspector_name" style="width:100%; padding:8px;" readonly value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top:20px;">
                        <div>
                            <label>Test Result *</label>
                            <select name="test_result" id="test_result" style="width:100%; padding:8px;" required onchange="updateOptions()">
                                <option value="Pending">Pending</option>
                                <option value="Passed">Passed</option>
                                <option value="Failed">Failed</option>
                            </select>
                        </div>
                        <div>
                            <label>Non-Conformance Details</label>
                            <select name="non_conformance" id="non_conformance" style="width:100%; padding:8px;" required></select>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top:20px;">
                        <div>
                            <label>Corrective Action</label>
                            <select name="corrective_action" id="corrective_action" style="width:100%; padding:8px;" required></select>
                        </div>
                        <div>
                            <label>Approval Status *</label>
                            <select name="approval_status" style="width:100%; padding:8px;" required>
                                <option value="For Re-inspection">For Re-inspection</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top:20px;">
                        <label>Remarks</label>
                        <textarea name="remarks" style="width:100%; padding:8px; min-height:60px;" placeholder="Optional remarks"></textarea>
                    </div>

                    <div style="text-align:right; margin-top:20px;">
                        <a href="qc_inspection.php" class="btn" style="margin-right:10px;">Cancel</a>
                        <button type="submit" class="btn">Save QC Record</button>
                    </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>

<script src="assets/js/sidebar.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){

    var nc_failed = <?php echo json_encode($non_conformance_failed ?? []); ?>;
    var nc_passed = <?php echo json_encode($non_conformance_passed ?? []); ?>;
    var ca_failed = <?php echo json_encode($corrective_action_failed ?? []); ?>;
    var ca_passed = <?php echo json_encode($corrective_action_passed ?? []); ?>;

    function updateOptions(result){
        if(result === 'Passed'){
            nc = nc_passed;
            ca = ca_passed;
        } else if(result === 'Failed'){
            nc = nc_failed;
            ca = ca_failed;
        } else {
            nc = ['-'];
            ca = ['-'];
        }

        $('#non_conformance').empty();
        $.each(nc,function(i,v){
            $('#non_conformance').append('<option value="'+v+'">'+v+'</option>');
        });

        $('#corrective_action').empty();
        $.each(ca,function(i,v){
            $('#corrective_action').append('<option value="'+v+'">'+v+'</option>');
        });
    }

    $('#test_result').on('change', function(){
        updateOptions($(this).val());
    });

    if($('#test_result').length){
        updateOptions($('#test_result').val());
    }

    $('#batch_id').on('change', function(){
        var opt = $(this).find(':selected');
        $('#batch_number').val(opt.data('batch-number') || '');
        $('#product_name').val(opt.data('product-name') || '');
        $('#quantity').val(opt.data('quantity') || '');
    });

    // If a batch is pre-selected (e.g. from the Pending list), ensure the fields are populated.
    if ($('#batch_id').val()) {
        $('#batch_id').trigger('change');
    }

});
</script>
<script>
$('#non_conformance').on('change', function () {
    $('#non_conformance_other').toggle(this.value === 'Other');
}).trigger('change');

$('#corrective_action').on('change', function () {
    $('#corrective_action_other').toggle(this.value === 'Other');
}).trigger('change');
</script>
</body>
</html>
