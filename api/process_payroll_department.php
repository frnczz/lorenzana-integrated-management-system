<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payroll_calculations.php';

/* ===============================
   AUTH CHECK
   =============================== */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','accounting'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../payroll_process.php");
    exit;
}

$department   = trim($_POST['department'] ?? '');
$start        = $_POST['payroll_period_start'] ?? '';
$end          = $_POST['payroll_period_end'] ?? '';
$overtime     = floatval($_POST['overtime_pay'] ?? 0);
$allowances   = floatval($_POST['allowances'] ?? 0);
$processed_by = $_SESSION['user_id'];

if (!$start || !$end) {
    $_SESSION['error'] = "Payroll period start/end are required.";
    header("Location: ../payroll_process.php");
    exit;
}

$working_days = getPayrollSetting($conn, 'working_days', 26);

// Build employee query
$sql = "SELECT * FROM employees WHERE status = 'Active'";
$params = [];
if ($department !== '') {
    $sql .= " AND department = ?";
    $params[] = $department;
}
$sql .= " ORDER BY department, last_name, first_name";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $_SESSION['error'] = "Failed to prepare employee query: " . $conn->error;
    header("Location: ../payroll_process.php");
    exit;
}

if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $_SESSION['error'] = "No active employees found for the selected department.";
    header("Location: ../payroll_process.php");
    exit;
}

$processed = 0;
$skipped = 0;
$errors = [];

// Prepare insert statements once
$insertPayroll = $conn->prepare("INSERT INTO payroll
    (payroll_ref, employee_id, payroll_period_start, payroll_period_end,
     basic_salary, overtime_pay, allowances,
     gross_pay, deductions, net_pay,
     processed_by, status)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,'Processed')");

$insertBreakdown = $conn->prepare("INSERT INTO payroll_breakdown
    (payroll_id, type, code, description, amount)
    VALUES (?, ?, ?, ?, ?)");

while ($emp = $result->fetch_assoc()) {
    // Avoid duplicate payroll for same period
    $check = $conn->prepare("SELECT payroll_id FROM payroll WHERE employee_id=? AND payroll_period_start=? AND payroll_period_end=? AND status='Processed' LIMIT 1");
    $check->bind_param("iss", $emp['employee_id'], $start, $end);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $skipped++;
        $check->close();
        continue;
    }
    $check->close();

    $attendanceSummary = getAttendanceSummary($conn, $emp['employee_id'], $start, $end, true);
    $payrollCalc = computePayrollFromAttendance(
        $conn,
        floatval($emp['salary']),
        $working_days,
        $attendanceSummary,
        $overtime,
        $allowances,
        [
            'sss_enabled' => $emp['sss_enabled'],
            'philhealth_enabled' => $emp['philhealth_enabled'],
            'pagibig_enabled' => $emp['pagibig_enabled'],
        ]
    );

    $payroll_ref = generateReferenceId($conn, 'PAY');

    $insertPayroll->bind_param(
        "sissddddddi",
        $payroll_ref,
        $emp['employee_id'],
        $start,
        $end,
        $emp['salary'],
        $overtime,
        $allowances,
        $payrollCalc['gross_pay'],
        $payrollCalc['deductions'],
        $payrollCalc['net_pay'],
        $processed_by
    );

    if (!$insertPayroll->execute()) {
        $errors[] = "{$emp['employee_number']} ({$emp['first_name']} {$emp['last_name']}): " . $insertPayroll->error;
        continue;
    }

    $payroll_id = $insertPayroll->insert_id;

    foreach ($payrollCalc['breakdowns'] as $b) {
        $insertBreakdown->bind_param(
            "isssd",
            $payroll_id,
            $b[0],
            $b[1],
            $b[2],
            $b[3]
        );
        $insertBreakdown->execute();
    }

    $processed++;
}

if ($insertPayroll) $insertPayroll->close();
if ($insertBreakdown) $insertBreakdown->close();
$stmt->close();

$messageParts = [];
if ($processed) {
    $messageParts[] = "Payroll generated for $processed employee" . ($processed === 1 ? '' : 's') . ".";
}
if ($skipped) {
    $messageParts[] = "$skipped already had payroll for that period and were skipped.";
}
if (!empty($errors)) {
    $messageParts[] = "Some records failed: " . implode(' | ', $errors);
    $_SESSION['error'] = implode(' ', $messageParts);
} else {
    $_SESSION['success'] = implode(' ', $messageParts);
}

header("Location: ../payroll_process.php");
exit;
