<?php
session_start();
header('Content-Type: application/json');
include '../db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'production'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$batch_id = (int)($_POST['batch_id'] ?? 0);
$fermentation_status = trim((string)($_POST['fermentation_status'] ?? ''));
$allowed = ['Not Applicable', 'Not Started', 'Ongoing', 'Completed'];

if ($batch_id <= 0 || !in_array($fermentation_status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$info = $conn->query("
    SELECT pb.batch_id, pb.batch_number, pb.status, pb.product_id, COALESCE(p.fermentation_eligible, 1) AS fermentation_eligible
    FROM production_batches pb
    JOIN products p ON p.product_id = pb.product_id
    WHERE pb.batch_id = " . (int)$batch_id . "
    LIMIT 1
");
if (!$info || !($row = $info->fetch_assoc())) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Batch not found']);
    exit;
}

if ((int)$row['fermentation_eligible'] === 0) {
    $fermentation_status = 'Not Applicable';
}

$has_phase = (@$conn->query("SHOW COLUMNS FROM production_batches LIKE 'phase'")->num_rows > 0);
$new_batch_status = $row['status'];
$auto_ready = ($fermentation_status === 'Completed' && $row['status'] === 'Processing');
if ($auto_ready) {
    $new_batch_status = 'Ready';
}

$conn->begin_transaction();
try {
    if ($has_phase) {
        if ($auto_ready) {
            $phase = 'Output Pending QC';
            $u = $conn->prepare('UPDATE production_batches SET fermentation_status = ?, status = ?, phase = ? WHERE batch_id = ?');
            $u->bind_param('sssi', $fermentation_status, $new_batch_status, $phase, $batch_id);
        } else {
            $u = $conn->prepare('UPDATE production_batches SET fermentation_status = ? WHERE batch_id = ?');
            $u->bind_param('si', $fermentation_status, $batch_id);
        }
    } else {
        if ($auto_ready) {
            $u = $conn->prepare('UPDATE production_batches SET fermentation_status = ?, status = ? WHERE batch_id = ?');
            $u->bind_param('ssi', $fermentation_status, $new_batch_status, $batch_id);
        } else {
            $u = $conn->prepare('UPDATE production_batches SET fermentation_status = ? WHERE batch_id = ?');
            $u->bind_param('si', $fermentation_status, $batch_id);
        }
    }
    if (!$u->execute()) {
        throw new Exception($u->error);
    }
    $u->close();

    if ($auto_ready) {
        $rid = $conn->query("SELECT request_id FROM production_batches WHERE batch_id = " . (int)$batch_id . " LIMIT 1");
        if ($rid && ($r = $rid->fetch_assoc()) && !empty($r['request_id'])) {
            $reqId = (int)$r['request_id'];
            $conn->query("UPDATE production_requests SET status = 'For Inspection', updated_at = NOW() WHERE request_id = " . $reqId);
        }
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

if ($fermentation_status === 'Completed') {
    $bn = $row['batch_number'] ?? ('#' . $batch_id);
    $msg = 'Fermentation marked Completed for batch ' . $bn . '. Status set to Ready for QC when it was Processing (change status on Batch Records if needed).';
    notifyFermentationCompletedStakeholders($conn, $msg, 'production_records.php');
}

echo json_encode([
    'success' => true,
    'fermentation_status' => $fermentation_status,
    'batch_status' => $new_batch_status,
]);
