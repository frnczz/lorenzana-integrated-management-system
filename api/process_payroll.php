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

/* ===============================
   INPUTS
   =============================== */
$employee_id   = intval($_POST['employee_id'] ?? 0);
$start         = $_POST['payroll_period_start'] ?? '';
$end           = $_POST['payroll_period_end'] ?? '';
$overtime      = floatval($_POST['overtime_pay'] ?? 0);
$allowances    = floatval($_POST['allowances'] ?? 0);
$processed_by  = $_SESSION['user_id'];

if ($employee_id <= 0 || !$start || !$end) {
    $_SESSION['error'] = "Employee and payroll period are required.";
    header("Location: ../payroll_process.php");
    exit;
}

/* ===============================
   1. LOAD EMPLOYEE & SALARY
   =============================== */
$emp = $conn->query(
    "SELECT salary, sss_enabled, philhealth_enabled, pagibig_enabled FROM employees WHERE employee_id = " . intval($employee_id)
)->fetch_assoc();

if (!$emp) {
    $_SESSION['error'] = "Employee not found.";
    header("Location: ../payroll_process.php");
    exit;
}

$basic_salary = floatval($emp['salary']);
$working_days = getPayrollSetting($conn, 'working_days', 26);

/* ===============================
   2. ATTENDANCE SUMMARY (AUTOMATIC)
   =============================== */
$attendanceSummary = getAttendanceSummary($conn, $employee_id, $start, $end, true);

/* ===============================
   3. PAYROLL CALCULATION
   =============================== */
$payrollCalc = computePayrollFromAttendance(
    $conn,
    $basic_salary,
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

$daily_rate = $payrollCalc['daily_rate'];
$days_worked = $payrollCalc['days_worked'];
$basic_pay = $payrollCalc['basic_pay'];
$gross_pay = $payrollCalc['gross_pay'];
$deductions = $payrollCalc['deductions'];
$net_pay = $payrollCalc['net_pay'];
$breakdowns = $payrollCalc['breakdowns'];

/* ===============================
   4. INSERT PAYROLL
   =============================== */
$payroll_ref = generateReferenceId($conn, 'PAY');

$stmt = $conn->prepare("
    INSERT INTO payroll
    (payroll_ref, employee_id, payroll_period_start, payroll_period_end,
     basic_salary, overtime_pay, allowances,
     gross_pay, deductions, net_pay,
     processed_by, status)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,'Processed')
");

$stmt->bind_param(
    "sissddddddi",
    $payroll_ref,
    $employee_id,
    $start,
    $end,
    $basic_salary,
    $overtime,
    $allowances,
    $gross_pay,
    $deductions,
    $net_pay,
    $processed_by
);

if (!$stmt->execute()) {
    $_SESSION['error'] = "Payroll failed: ".$stmt->error;
    header("Location: ../payroll_process.php");
    exit;
}

$payroll_id = $stmt->insert_id;
$stmt->close();

/* ===============================
   5. INSERT PAYROLL BREAKDOWN (SAFE)
   =============================== */
$stmt = $conn->prepare("
    INSERT INTO payroll_breakdown
    (payroll_id, type, code, description, amount)
    VALUES (?, ?, ?, ?, ?)
");

foreach ($breakdowns as $b) {
    $stmt->bind_param(
        "isssd",
        $payroll_id,
        $b[0],
        $b[1],
        $b[2],
        $b[3]
    );
    $stmt->execute();
}
$stmt->close();

/* ===============================
   DONE
   =============================== */
$_SESSION['success'] = "Payroll processed successfully! Ref: $payroll_ref";
header("Location: ../payroll_process.php");
exit;
