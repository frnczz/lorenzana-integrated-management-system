<?php
/**
 * Seed payroll employees (one per department) and generate random attendance
 * records for February 1 thru March 15.
 *
 * Run this once (e.g. via browser or CLI) to add sample payroll data.
 * It is safe to run multiple times (it will not duplicate employees or attendance entries).
 */

require_once __DIR__ . '/db_connect.php';

$departments = [
    'Production' => ['position' => 'Production Supervisor'],
    'Warehouse' => ['position' => 'Warehouse Lead'],
    'Quality Control' => ['position' => 'QC Inspector'],
    'Accounting' => ['position' => 'Accountant'],
    'Sales' => ['position' => 'Sales Representative'],
    'Logistics' => ['position' => 'Logistics Coordinator'],
    'Administration' => ['position' => 'Office Administrator'],
];

$firstNames = ['Miguel', 'Jessa', 'Erik', 'May', 'Rafael', 'Clara', 'Eduardo', 'Sofia', 'Daniel', 'Ana', 'Lucas', 'Marina', 'Jose', 'Liza', 'Mark'];
$lastNames = ['Santos', 'Reyes', 'Pineda', 'Alcantara', 'Diaz', 'Morales', 'Cruz', 'Lopez', 'Gonzales', 'Delos Santos', 'Navarro', 'Bautista', 'Fernandez', 'Rivera', 'Torres'];

$startDate = new DateTime('2026-02-01');
$endDate = new DateTime('2026-03-15');

// Prepare statements
$insertEmployeeStmt = $conn->prepare(
    "INSERT INTO employees (employee_number, first_name, last_name, position, department, hire_date, salary, status)\n" .
    "VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')\n" .
    "ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), position = VALUES(position), department = VALUES(department), hire_date = VALUES(hire_date), salary = VALUES(salary), status = VALUES(status)"
);

$selectIdStmt = $conn->prepare("SELECT employee_id FROM employees WHERE employee_number = ? LIMIT 1");

$insertAttendanceStmt = $conn->prepare(
    "INSERT INTO attendance (employee_id, attendance_date, time_in, time_out, hours_worked, status, remarks)
     VALUES (?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE time_in = VALUES(time_in), time_out = VALUES(time_out), hours_worked = VALUES(hours_worked), status = VALUES(status), remarks = VALUES(remarks)"
);

if (!$insertEmployeeStmt || !$selectIdStmt || !$insertAttendanceStmt) {
    die('Failed to prepare statements: ' . $conn->error);
}

$createdEmployees = 0;
$createdAttendance = 0;

foreach ($departments as $dept => $info) {
    $employeeNumber = 'EMP-' . strtoupper(substr($dept, 0, 3)) . '-001';
    $hireDate = '2025-01-15';

    // Randomize name and salary each run (keeps one record per department)
    $firstName = $firstNames[array_rand($firstNames)];
    $lastName = $lastNames[array_rand($lastNames)];
    // Seed with realistic daily pay (to match updated daily-rate payroll logic).
    $salary = mt_rand(500, 1200);

    $insertEmployeeStmt->bind_param(
        'ssssssd',
        $employeeNumber,
        $firstName,
        $lastName,
        $info['position'],
        $dept,
        $hireDate,
        $salary
    );
    $insertEmployeeStmt->execute();

    if ($insertEmployeeStmt->affected_rows > 0) {
        $createdEmployees++;
    }

    $selectIdStmt->bind_param('s', $employeeNumber);
    $selectIdStmt->execute();
    $selectIdStmt->bind_result($employeeId);
    $selectIdStmt->fetch();
    $selectIdStmt->free_result();
    $selectIdStmt->reset();

    if (!$employeeId) {
        continue;
    }

    // Seed attendance for each date in the range
    $current = clone $startDate;
    while ($current <= $endDate) {
        $date = $current->format('Y-m-d');

        // Randomize attendance status
        $rand = mt_rand(1, 100);
        if ($rand <= 75) {
            $status = 'Present';
        } elseif ($rand <= 85) {
            $status = 'Late';
        } elseif ($rand <= 92) {
            $status = 'Half Day';
        } elseif ($rand <= 97) {
            $status = 'Absent';
        } else {
            $status = 'Leave';
        }

        $timeIn = null;
        $timeOut = null;
        $hoursWorked = 0.0;
        $remarks = '';

        if ($status === 'Present' || $status === 'Late' || $status === 'Half Day') {
            // Generate times between 8:00-9:30 for time in and 17:00-18:30 for time out
            $baseIn = new DateTime('08:30');
            $baseOut = new DateTime('17:30');

            $inOffset = mt_rand(-30, 30); // minutes
            $outOffset = mt_rand(-30, 30);

            if ($status === 'Late') {
                $inOffset = mt_rand(15, 60);
                $remarks = 'Late arrival';
            }
            if ($status === 'Half Day') {
                $outOffset = mt_rand(-180, -90); // leave early
                $remarks = 'Half day';
            }

            $timeInDT = clone $baseIn;
            $timeInDT->modify("{$inOffset} minutes");
            $timeOutDT = clone $baseOut;
            $timeOutDT->modify("{$outOffset} minutes");

            $timeIn = $timeInDT->format('H:i:s');
            $timeOut = $timeOutDT->format('H:i:s');

            $diff = $timeOutDT->getTimestamp() - $timeInDT->getTimestamp();
            $hoursWorked = max(0, round($diff / 3600, 2));
        } else {
            // Absent or Leave
            $remarks = $status === 'Absent' ? 'Absent' : 'Leave';
        }

        $insertAttendanceStmt->bind_param(
            'isssdss',
            $employeeId,
            $date,
            $timeIn,
            $timeOut,
            $hoursWorked,
            $status,
            $remarks
        );
        $insertAttendanceStmt->execute();

        if ($insertAttendanceStmt->affected_rows > 0) {
            $createdAttendance++;
        }

        $current->modify('+1 day');
    }
}

echo "Done. Created/updated $createdEmployees employee(s) and $createdAttendance attendance record(s).\n";

$conn->close();
