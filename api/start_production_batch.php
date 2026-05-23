<?php
session_start();
include "../db_connect.php";
include "../includes/functions.php";

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','production'])) {
    setMessage('Unauthorized.', 'error');
    header("Location: ../production_requests.php");
    exit;
}

// request_ids = comma-separated or single id
$request_ids_raw = $_GET['request_ids'] ?? $_POST['request_ids'] ?? '';
$request_ids = array_filter(array_map('intval', preg_split('/[\s,]+/', $request_ids_raw)));

if (empty($request_ids)) {
    setMessage('No request selected.', 'error');
    header("Location: ../production_requests.php");
    exit;
}

// Set all to In Progress
$placeholders = implode(',', array_fill(0, count($request_ids), '?'));
$stmt = $conn->prepare("UPDATE production_requests SET status = 'In Progress', updated_at = NOW() WHERE request_id IN ($placeholders)");
$types = str_repeat('i', count($request_ids));
$stmt->bind_param($types, ...$request_ids);
$stmt->execute();
$stmt->close();

$ids_param = implode(',', $request_ids);
setMessage('Production request(s) set to In Progress. Complete the batch in Record Production.', 'success');
header("Location: ../production_record.php?request_ids=" . urlencode($ids_param));
exit;
