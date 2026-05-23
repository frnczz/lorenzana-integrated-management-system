<?php
session_start();
header('Content-Type: application/json');
include "../db_connect.php";

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','production'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$has_group = (@$conn->query("SHOW COLUMNS FROM production_requests LIKE 'request_group_id'")->num_rows > 0);

$sql = "
    SELECT pr.request_id, pr.customer_name, pr.requested_qty, pr.status, pr.priority, pr.created_at, pr.due_date,
           pr.product_id, p.product_name, p.image_path, COALESCE(p.fermentation_eligible, 1) AS fermentation_eligible
    " . ($has_group ? ", pr.request_group_id " : " ") . "
    FROM production_requests pr
    JOIN products p ON pr.product_id = p.product_id
    -- Only expose requests that are currently in 'In Progress'
    -- (i.e. user clicked 'Create Batch' in Production Requests)
    WHERE pr.status = 'In Progress'
    ORDER BY pr.created_at ASC, pr.request_id ASC
";
$q = $conn->query($sql);
$rows = [];
while ($r = $q->fetch_assoc()) {
    $rows[] = $r;
}

// Group by request_group_id when available
$groups = [];
foreach ($rows as $row) {
    $gid = $has_group && !empty($row['request_group_id']) ? $row['request_group_id'] : ('single-' . $row['request_id']);
    if (!isset($groups[$gid])) {
        $groups[$gid] = [
            'request_group_id' => $gid,
            'customer_name' => $row['customer_name'],
            'created_at' => $row['created_at'],
            'due_date' => $row['due_date'],
            'priority' => $row['priority'],
            'status' => $row['status'],
            'request_ids' => [],
            'lines' => [],
        ];
    }
    $groups[$gid]['request_ids'][] = (int)$row['request_id'];
    $groups[$gid]['lines'][] = [
        'request_id' => (int)$row['request_id'],
        'product_id' => (int)$row['product_id'],
        'product_name' => $row['product_name'],
        'image_path' => $row['image_path'] ?? '',
        'requested_qty' => (float)$row['requested_qty'],
        'fermentation_eligible' => (int)($row['fermentation_eligible'] ?? 1),
    ];
    if ($row['status'] !== 'Completed') $groups[$gid]['status'] = $row['status'];
}

echo json_encode(['success' => true, 'groups' => array_values($groups)]);
exit;
