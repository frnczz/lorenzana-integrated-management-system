<?php
session_start();
include "../db_connect.php";

// Only allow admins and accounting to update employee status.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$employee_id = intval($input['employee_id'] ?? 0);
$status = trim($input['status'] ?? '');

$valid_status = ['Active', 'Inactive', 'Terminated'];
if ($employee_id <= 0 || !$status || !in_array($status, $valid_status, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$stmt = $conn->prepare("UPDATE employees SET status = ? WHERE employee_id = ?");
$stmt->bind_param('si', $status, $employee_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
?>