<?php
// api/check_production_request_status.php
// Accepts GET or POST parameter `request_ids` (comma-separated or array)
header('Content-Type: application/json');
include_once __DIR__ . '/../db_connect.php';

$raw = [];
if (isset($_REQUEST['request_ids'])) {
    $raw = $_REQUEST['request_ids'];
}

$ids = [];
if (is_array($raw)) {
    foreach ($raw as $r) {
        $parts = explode(',', $r);
        foreach ($parts as $p) {
            $n = intval(trim($p)); if ($n) $ids[$n] = $n;
        }
    }
} else {
    $parts = explode(',', $raw);
    foreach ($parts as $p) { $n = intval(trim($p)); if ($n) $ids[$n] = $n; }
}

if (count($ids) === 0) {
    echo json_encode(['success' => false, 'message' => 'no ids provided']);
    exit;
}

$ids_list = implode(',', array_keys($ids));
$sql = "SELECT request_id, status FROM production_requests WHERE request_id IN ($ids_list)";
$res = $conn->query($sql);
$statuses = [];
$all_completed = true;
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $statuses[$row['request_id']] = $row['status'];
        if ($row['status'] !== 'Completed') $all_completed = false;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'query failed']);
    exit;
}

echo json_encode(['success' => true, 'statuses' => $statuses, 'all_completed' => $all_completed]);
