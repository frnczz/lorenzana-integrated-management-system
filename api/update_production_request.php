<?php
session_start();
include "../db_connect.php";
include "../includes/functions.php";

// Only production or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['production','admin'])) {
    setMessage('Unauthorized access.', 'error');
    header("Location: /lorinims/production_requests.php");
    exit;
}

$request_id = intval($_GET['id'] ?? $_POST['request_id'] ?? 0);
$ids_raw = $_POST['ids'] ?? null;
$status_param = $_GET['status'] ?? $_POST['status'] ?? $_POST['action'] ?? '';

$priority = $_POST['priority'] ?? null;
$due_date = $_POST['due_date'] ?? null;

$request_ids = [];
if (!empty($ids_raw)) {
    $request_ids = is_array($ids_raw) ? array_map('intval', $ids_raw) : array_map('intval', array_filter(explode(',', $ids_raw)));
}
if ($request_id > 0 && empty($request_ids)) {
    $request_ids = [$request_id];
}

$valid_statuses = [
    'Pending'        => 'Pending',
    'In Progress'    => 'In Progress',
    'For Inspection' => 'For Inspection',
    'Completed'      => 'Completed',
    'start'          => 'In Progress',
    'complete'       => 'Completed'
];

if (empty($request_ids)) {
    setMessage('Invalid request.', 'error');
    header("Location: /lorinims/production_requests.php");
    exit;
}

if ($status_param !== '' && !isset($valid_statuses[$status_param])) {
    setMessage('Invalid status.', 'error');
    header("Location: /lorinims/production_requests.php");
    exit;
}

$new_status = $status_param !== '' ? $valid_statuses[$status_param] : null;

// Build dynamic update query
$fields = [];
$params = [];
$types = '';

if ($new_status) { $fields[] = "status = ?"; $params[] = $new_status; $types .= "s"; }
if ($priority !== null) { $fields[] = "priority = ?"; $params[] = $priority; $types .= "s"; }
if ($due_date !== null && $due_date !== '') { $fields[] = "due_date = ?"; $params[] = $due_date; $types .= "s"; }

if (empty($fields)) {
    setMessage('Nothing to update.', 'error');
    header("Location: /lorinims/production_requests.php");
    exit;
}

$fields[] = "updated_at = NOW()";
$sql = "UPDATE production_requests SET " . implode(", ", $fields) . " WHERE request_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    setMessage("Database error: ".$conn->error, 'error');
    header("Location: /lorinims/production_requests.php");
    exit;
}

$types .= "i"; // for request_id

$updated = 0;
foreach ($request_ids as $rid) {
    if ($rid <= 0) continue;
    $stmt_params = array_merge($params, [$rid]);
    $stmt->bind_param($types, ...$stmt_params);
    if ($stmt->execute()) $updated += $stmt->affected_rows;
}
$stmt->close();

// Update sales order if completed
if ($updated > 0 && $new_status === 'Completed') {
    foreach ($request_ids as $rid) {
        if ($rid <= 0) continue;
        $ord = $conn->query("SELECT sales_order_id FROM production_requests WHERE request_id = $rid AND sales_order_id IS NOT NULL LIMIT 1");
        if ($ord && ($row = $ord->fetch_assoc()) && !empty($row['sales_order_id'])) {
            $oid = (int)$row['sales_order_id'];
            $conn->query("UPDATE sales_orders SET status = 'Confirmed' WHERE order_id = $oid AND status = 'Pending'");
        }
    }
}

if ($updated > 0) {
    setMessage(count($request_ids) > 1 ? "Updated $updated request(s)." : "Production request #{$request_ids[0]} updated successfully.", 'success');
} else {
    setMessage("Failed to update the request(s).", 'error');
}

header("Location: /lorinims/production_requests.php");
exit;