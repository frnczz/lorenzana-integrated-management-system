<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','production'])) {
    header("Location: login.php");
    exit;
}

include "includes/functions.php";
include "db_connect.php";

/* Check for request_group_id column for grouped display */
$has_request_group = false;
$col_check = @$conn->query("SHOW COLUMNS FROM production_requests LIKE 'request_group_id'");
if ($col_check && $col_check->num_rows > 0) $has_request_group = true;

/* Fetch production requests with product_id for Create Batch link */
$requests_raw = [];
$select_group = $has_request_group ? ', pr.request_group_id' : '';

// Sorting: default by created_at (newest first)
$sort = getSortParams('created_at', [
    'request_id',
    'customer_name',
    'status',
    'priority',
    'created_at',
    'due_date'
]);

$column_map = [
    'request_id'    => 'pr.request_id',
    'customer_name' => 'pr.customer_name',
    'status'        => 'pr.status',
    'priority'      => 'pr.priority',
    'created_at'    => 'pr.created_at',
    'due_date'      => 'pr.due_date'
];

$order_by = isset($column_map[$sort['column']]) ? $column_map[$sort['column']] : 'pr.created_at';

$q = $conn->query("
    SELECT pr.request_id, pr.customer_name, pr.requested_qty, pr.status, pr.priority, pr.created_at, pr.due_date,
           pr.product_id {$select_group},
           p.product_name
    FROM production_requests pr
    JOIN products p ON pr.product_id = p.product_id
    ORDER BY {$order_by} {$sort['order']}
");

if ($q && $q->num_rows > 0) {
    while ($row = $q->fetch_assoc()) {
        $requests_raw[] = $row;
    }
}

/* One row per customer order: group by request_group_id when available */
$requests = [];
if ($has_request_group) {
    $groups = [];
    foreach ($requests_raw as $row) {
        $gid = $row['request_group_id'] ?? ('single-' . $row['request_id']);
        if (!isset($groups[$gid])) {
            $groups[$gid] = [
                'customer_name' => $row['customer_name'],
                'created_at' => $row['created_at'],
                'due_date' => $row['due_date'],
                'priority' => $row['priority'],
                'status' => $row['status'],
                'request_ids' => [],
                'lines' => [],
            ];
        }
        $groups[$gid]['request_ids'][] = $row['request_id'];
        $groups[$gid]['lines'][] = $row;
        /* Group status: Completed only if all lines completed */
        if ($row['status'] !== 'Completed') $groups[$gid]['status'] = $row['status'];
    }
    foreach ($groups as $gid => $g) {
        $requests[] = [
            'is_group' => true,
            'group_id' => $gid,
            'customer_name' => $g['customer_name'],
            'created_at' => $g['created_at'],
            'due_date' => $g['due_date'],
            'priority' => $g['priority'],
            'status' => $g['status'],
            'request_ids' => $g['request_ids'],
            'lines' => $g['lines'],
        ];
    }
} else {
    foreach ($requests_raw as $row) {
        $requests[] = ['is_group' => false, 'lines' => [$row], 'request_ids' => [$row['request_id']]];
    }
}

/* Status options for dropdowns (aligned with sales_request_production) */
$status_options = ['Pending', 'In Progress', 'For Inspection'];
$priority_options = ['Normal', 'High'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Production Requests | LORINIMS</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.table-actions select, .table-actions input { width: 100%; padding: 4px; }
.status-pending { color: #d97706; font-weight: 600; }
.status-progress { color: #2563eb; font-weight: 600; }
.status-inspection { color: #7c3aed; font-weight: 600; }
.status-completed { color: #16a34a; font-weight: 600; }
.completed-row { opacity: 0.6; }
.filter-bar { display:flex; gap:10px; margin-bottom:10px; flex-wrap:wrap; }
.filter-bar select, .filter-bar input { padding:6px; }
/* Completed badges */
.status-badge { display:inline-block; padding:4px 8px; border-radius:8px; font-weight:700; font-size:12px; }
.status-badge.completed { background: #ecfdf5; color: #065f46; border: 1px solid #bbf7d0; }
.action-badge { display:inline-block; padding:6px 10px; border-radius:8px; font-weight:700; font-size:12px; }
.action-badge.completed { background: #16a34a; color: #fff; }
</style>
</head>
<body>
<div class="wrapper">
<?php include "layouts/sidebar.php"; ?>
<div class="main">
<?php include "layouts/header.php"; ?>

<div class="content">
<h2>Production Requests</h2>
<p>Manage production requests from Sales. One row per customer order; update status, priority, or due date. When a batch is completed, inventory and sales are updated automatically.</p>
<p style="margin-bottom:16px;">
    <a href="sales_request_production.php" class="btn" style="text-decoration:none;">← Request Production (Sales)</a>
</p>

<?php showMessage(); ?>

<!-- Filters -->
<div class="filter-bar">
    <input type="text" id="searchCustomer" placeholder="Search Customer...">
    <select id="filterStatus">
        <option value="">All Status</option>
        <?php foreach ($status_options as $s): ?>
            <option value="<?= $s ?>"><?= $s ?></option>
        <?php endforeach; ?>
    </select>
    <select id="filterPriority">
        <option value="">All Priority</option>
        <?php foreach ($priority_options as $p): ?>
            <option value="<?= $p ?>"><?= $p ?></option>
        <?php endforeach; ?>
    </select>
    <button id="resetFilters" class="btn">Reset</button>
</div>

<!-- Batch Actions -->
<div style="margin-bottom:10px;">
    <button id="batchInProgress" class="btn">Mark Selected In Progress</button>
    <button id="batchCompleted" class="btn">Mark Selected Completed</button>
</div>

<div class="card">
<table style="width:100%;" id="requestsTable">
    <thead>
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th><?php echo sortHeader('request_id', '#', $sort); ?></th>
            <th><?php echo sortHeader('customer_name', 'Customer', $sort); ?></th>
            <th>Product</th>
            <th>Qty</th>
            <th><?php echo sortHeader('status', 'Status', $sort); ?></th>
            <th><?php echo sortHeader('priority', 'Priority', $sort); ?></th>
            <th><?php echo sortHeader('created_at', 'Requested Date', $sort); ?></th>
            <th><?php echo sortHeader('due_date', 'Due Date', $sort); ?></th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($requests as $entry):
        $lines = $entry['lines'];
        $first = $lines[0];
        $status = $entry['status'] ?? $first['status'];
        $statusClass = match($status) {
            'Pending' => 'status-pending',
            'In Progress' => 'status-progress',
            'For Inspection' => 'status-inspection',
            'Completed' => 'status-completed',
            default => ''
        };
        $rowClass = $status === 'Completed' ? 'completed-row' : '';
        $request_ids = $entry['request_ids'];
        $ids_attr = implode(',', $request_ids);
    ?>
        <tr class="<?= $rowClass ?>">
            <td><input type="checkbox" class="rowCheckbox" data-ids="<?= htmlspecialchars($ids_attr) ?>"></td>
            <td><?= (int)$first['request_id'] ?><?= count($lines) > 1 ? ' <small>(' . count($lines) . ')</small>' : '' ?></td>
            <td><?= htmlspecialchars($first['customer_name']) ?></td>
            <td>
                <?php foreach ($lines as $line): ?>
                    <div><?= htmlspecialchars($line['product_name']) ?></div>
                <?php endforeach; ?>
            </td>
            <td>
                <?php foreach ($lines as $line): ?>
                    <div><?= number_format($line['requested_qty'], 2) ?></div>
                <?php endforeach; ?>
            </td>
            <td class="table-actions">
                <?php if ($status === 'Completed'): ?>
                    <span class="status-badge completed"><?= htmlspecialchars($status) ?></span>
                <?php else: ?>
                    <select class="status-dropdown" data-ids="<?= htmlspecialchars($ids_attr) ?>">
                        <?php foreach ($status_options as $s): ?>
                            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </td>
            <td class="table-actions">
                <select class="priority-dropdown" data-ids="<?= htmlspecialchars($ids_attr) ?>">
                    <?php foreach ($priority_options as $p): ?>
                        <option value="<?= $p ?>" <?= ($first['priority'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><?= date('Y-m-d', strtotime($first['created_at'])) ?></td>
            <td><input type="date" class="due-date" data-ids="<?= htmlspecialchars($ids_attr) ?>" value="<?= htmlspecialchars($first['due_date'] ?? '') ?>"></td>
            <td>
                <?php
                    $ids_attr = implode(',', $request_ids);
                    if ($status === 'Completed'):
                ?>
                    <span class="action-badge completed">✓ Completed</span>
                <?php elseif ($status === 'For Inspection'): ?>
                    <span class="action-badge" style="background:#ddd6fe; color:#5b21b6;">For Inspection</span>
                <?php elseif ($status === 'In Progress'): ?>
                    <span class="action-badge" style="background:#e5e7eb; color:#374151;">Creating Batch</span>
                <?php else: ?>
                    <a href="api/start_production_batch.php?request_ids=<?= urlencode($ids_attr) ?>"
                       class="btn btn-create-batch"
                       style="padding:6px 12px; font-size:12px; text-decoration:none; display:inline-block;">
                        Create Batch
                    </a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

</div>
<?php include "layouts/footer.php"; ?>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/js/sidebar.js"></script>
<script src="assets/js/production_requests.js"></script>
</body>
</html>
