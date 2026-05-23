<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payroll_calculations.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','accounting'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$employee_id = intval($_GET['employee_id'] ?? 0);
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

if ($employee_id <= 0 || !$start || !$end) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// Load employee salary / settings
$emp = $conn->query("SELECT salary, sss_enabled, philhealth_enabled, pagibig_enabled FROM employees WHERE employee_id = " . intval($employee_id))->fetch_assoc();
if (!$emp) {
    http_response_code(404);
    echo json_encode(['error' => 'Employee not found']);
    exit;
}

$working_days = getPayrollSetting($conn, 'working_days', 26);

$attendanceSummary = getAttendanceSummary($conn, $employee_id, $start, $end, true);
$payroll = computePayrollFromAttendance(
    $conn,
    floatval($emp['salary']),
    $working_days,
    $attendanceSummary,
    floatval($_GET['overtime'] ?? 0),
    floatval($_GET['allowances'] ?? 0),
    [
        'sss_enabled' => $emp['sss_enabled'],
        'philhealth_enabled' => $emp['philhealth_enabled'],
        'pagibig_enabled' => $emp['pagibig_enabled'],
    ]
);

echo json_encode([
    'employee_salary' => floatval($emp['salary']),
    'working_days' => $working_days,
    'attendance' => $attendanceSummary,
    'payroll' => $payroll,
]);
