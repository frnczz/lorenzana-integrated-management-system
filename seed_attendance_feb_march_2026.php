<?php
/**
 * Seed script: create one employee per department and populate attendance
 * from 2026-02-01 through 2026-03-15.
 *
 * Run via CLI: php seed_attendance_feb_march_2026.php
 *
 * NOTE: This script is idempotent: it will not create duplicate employees for
 * the same department and will upsert attendance records (avoid duplicates).
 */

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

$departments = [
    'Production',
    'Warehouse',
    'Quality Control',
    'Accounting',
    'Sales',
    'Logistics',
    'Administration'
];

$firstNames = [
    'Miguel', 'Jose', 'Anna', 'Maria', 'Juan', 'Gabriel', 'Elena', 'Carlos', 'Isabel', 'Rafael',
    'Sofia', 'Daniel', 'Lucia', 'Marco', 'Clara', 'Mateo', 'Amelia', 'Andres', 'Valeria', 'Diego'
];

$lastNames = [
    'Garcia', 'Reyes', 'Santos', 'Cruz', 'Dela Cruz', 'Mercado', 'Torres', 'Ramos', 'Lopez', 'Gonzales',
    'Flores', 'Mendoza', 'Santiago', 'Vega', 'Navarro', 'Ramirez', 'Ortiz', 'Ramos', 'Paredes', 'Silva'
];

function randomName(&$firstNames, &$lastNames) {
    $first = $firstNames[array_rand($firstNames)];
    $last = $lastNames[array_rand($lastNames)];
    return [$first, $last];
}

function buildEmployeeNumber($deptSlug, $index) {
    return strtoupper(substr($deptSlug, 0, 3)) . '-' . date('Y') . '-' . str_pad($index, 3, '0', STR_PAD_LEFT);
}

function randomStatus() {
    // Weighted statuses
    $choices = [
        'Present' => 60,
        'Late' => 10,
        'Half Day' => 8,
        'Leave' => 12,
        'Absent' => 10,
    ];
    $rand = rand(1, array_sum($choices));
    $cursor = 0;
    foreach ($choices as $status => $weight) {
        $cursor += $weight;
        if ($rand <= $cursor) return $status;
    }
    return 'Present';
}

function randomTime($start, $end) {
    $t1 = strtotime($start);
    $t2 = strtotime($end);
    $rand = rand($t1, $t2);
    return date('H:i:s', $rand);
}

function hoursBetween($in, $out) {
    if (!$in || !$out) return 0;
    $t1 = strtotime($in);
    $t2 = strtotime($out);
    if ($t2 <= $t1) return 0;
    $h = ($t2 - $t1) / 3600;
    return round($h, 2);
}

// Ensure we have a single employee per department.
$employees = [];
foreach ($departments as $idx => $dept) {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE department = ? LIMIT 1");
    $stmt->bind_param('s', $dept);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $employees[] = $existing;
        continue;
    }

    list($first, $last) = randomName($firstNames, $lastNames);
    $middle = chr(ord('A') + rand(0, 25));

    $empNum = buildEmployeeNumber($dept, $idx + 1);

    $position = match ($dept) {
        'Production' => 'Production Supervisor',
        'Warehouse' => 'Warehouse Coordinator',
        'Quality Control' => 'QC Inspector',
        'Accounting' => 'Accountant',
        'Sales' => 'Sales Associate',
        'Logistics' => 'Logistics Coordinator',
        'Administration' => 'Office Administrator',
        default => 'Staff',
    };

    $salary = rand(18000, 28000);
    $hireDate = '2024-01-15';
    $status = 'Active';

    $insert = $conn->prepare("INSERT INTO employees (employee_number, first_name, last_name, middle_name, position, department, hire_date, salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param('ssssssdss', $empNum, $first, $last, $middle, $position, $dept, $hireDate, $salary, $status);
    $insert->execute();
    $newId = $conn->insert_id;
    $insert->close();

    if ($newId) {
        $employees[] = [
            'employee_id' => $newId,
            'employee_number' => $empNum,
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'department' => $dept,
        ];
    }
}

// Build date range from 2026-02-01 through 2026-03-15
$start = new DateTime('2026-02-01');
$end = new DateTime('2026-03-15');
$end->setTime(0, 0, 0);

$period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));

$insertAtt = $conn->prepare("INSERT INTO attendance 
    (employee_id, attendance_date, time_in, time_out, hours_worked, status, remarks) 
    VALUES (?, ?, ?, ?, ?, ?, ?) 
    ON DUPLICATE KEY UPDATE time_in = VALUES(time_in), time_out = VALUES(time_out), hours_worked = VALUES(hours_worked), status = VALUES(status), remarks = VALUES(remarks)");

foreach ($employees as $emp) {
    foreach ($period as $dt) {
        // skip weekend days
        $weekday = (int)$dt->format('N');
        if ($weekday >= 6) {
            continue;
        }

        $date = $dt->format('Y-m-d');
        $status = randomStatus();

        $time_in = null;
        $time_out = null;
        $remarks = null;

        switch ($status) {
            case 'Present':
                $time_in = randomTime('08:00:00', '09:15:00');
                $time_out = randomTime('17:00:00', '18:30:00');
                break;
            case 'Late':
                $time_in = randomTime('09:30:00', '10:30:00');
                $time_out = randomTime('17:00:00', '18:30:00');
                $remarks = 'Late arrival';
                break;
            case 'Half Day':
                $time_in = randomTime('08:00:00', '09:30:00');
                $time_out = randomTime('12:00:00', '13:00:00');
                $remarks = 'Half day';
                break;
            case 'Leave':
                $time_in = null;
                $time_out = null;
                $remarks = 'Leave';
                break;
            case 'Absent':
                $time_in = null;
                $time_out = null;
                $remarks = 'Absent';
                break;
        }

        $hours_worked = hoursBetween($time_in, $time_out);

        $insertAtt->bind_param(
            'isssdss',
            $emp['employee_id'],
            $date,
            $time_in,
            $time_out,
            $hours_worked,
            $status,
            $remarks
        );
        $insertAtt->execute();
    }
}

$insertAtt->close();

// Report
echo "Seeded attendance for " . count($employees) . " employees (" . implode(', ', array_map(fn($e) => $e['department'], $employees)) . ") from 2026-02-01 to 2026-03-15.\n";

?>