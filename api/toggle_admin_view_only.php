<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$enabled_raw = $_POST['enabled'] ?? null;
$enabled = ($enabled_raw === '1' || $enabled_raw === 1 || $enabled_raw === true || $enabled_raw === 'true');
$_SESSION['admin_view_only_mode'] = $enabled ? 1 : 0;

echo json_encode([
    'success' => true,
    'enabled' => (int)$_SESSION['admin_view_only_mode']
]);

