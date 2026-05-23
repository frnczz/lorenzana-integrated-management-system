<?php
session_start();
header('Content-Type: application/json');
include '../db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'production', 'qc'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$id = (int)($_POST['notification_id'] ?? $_GET['notification_id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false]);
    exit;
}

$ok = markAppNotificationRead($conn, (int)$_SESSION['user_id'], $id);
echo json_encode(['success' => $ok]);
