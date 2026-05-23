<?php
session_start();
header('Content-Type: application/json');
include "../db_connect.php";

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'sales'])) {
    echo json_encode(['success' => false]);
    exit;
}

$driver_id = intval($_GET['driver_id'] ?? 0);
if ($driver_id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$has_vehicle_type = @$conn->query("SHOW COLUMNS FROM users LIKE 'vehicle_type'")->num_rows > 0;
$vehicle = null;
if ($has_vehicle_type) {
    $stmt = $conn->prepare("SELECT vehicle_type FROM users WHERE id = ? AND role IN ('delivery','driver')");
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && !empty(trim($row['vehicle_type'] ?? ''))) {
        $vehicle = trim($row['vehicle_type']);
    }
}
echo json_encode(['success' => true, 'vehicle' => $vehicle]);
