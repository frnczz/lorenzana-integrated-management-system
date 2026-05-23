<?php
// includes/payroll_calculations.php

function calculateSSS($monthlySalary) {
    // Estimate employee SSS share based on current SSS contribution rate (approx. 4.5% of monthly salary)
    // with a minimum and maximum cap.
    // Note: for accurate official values, replace this with the exact SSS contribution table.
    $monthlySalary = max(0, floatval($monthlySalary));
    $employeeShare = round($monthlySalary * 0.045, 2);

    // Minimum employee share (for very low salaries)
    $minShare = 135;
    // Maximum employee share (based on max salary credit)
    $maxShare = 1125;

    if ($employeeShare < $minShare) {
        return $minShare;
    }
    if ($employeeShare > $maxShare) {
        return $maxShare;
    }
    return $employeeShare;
}

function calculatePhilHealth($monthlySalary) {
    $rate = 0.05; // 5%
    return ($monthlySalary * $rate) / 2; // employee share
}

function calculateWithholdingTax($monthlySalary) {
    // Basic monthly withholding tax approximation using a simplified progressive table.
    // This is a rough estimate; for exact computation use current tax tables.
    $monthlySalary = max(0, floatval($monthlySalary));

    // Tax brackets (monthly) - approximate for 2023
    if ($monthlySalary <= 20833) {
        return 0;
    }
    if ($monthlySalary <= 33333) {
        return ($monthlySalary - 20833) * 0.20;
    }
    if ($monthlySalary <= 66667) {
        return 2500 + ($monthlySalary - 33333) * 0.25;
    }
    if ($monthlySalary <= 166667) {
        return 10417 + ($monthlySalary - 66667) * 0.30;
    }
    if ($monthlySalary <= 666667) {
        return 40417 + ($monthlySalary - 166667) * 0.32;
    }

    return 200417 + ($monthlySalary - 666667) * 0.35;
}

function calculatePagIbig($monthlySalary) {
    return min(100, $monthlySalary * 0.02);
}

/**
 * Returns attendance summary for a given employee and period.
 *
 * @return array [
 *   'present'=>int, 'late'=>int, 'half'=>int, 'absent'=>int, 'leave'=>int,
 *   'days_worked' => float
 * ]
 */
function getAttendanceSummary($conn, $employee_id, $start, $end, $leave_as_paid = true) {
    $stmt = $conn->prepare("SELECT status, COUNT(*) AS total FROM attendance WHERE employee_id = ? AND attendance_date BETWEEN ? AND ? GROUP BY status");
    if (!$stmt) return null;
    $stmt->bind_param("iss", $employee_id, $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();

    $summary = [
        'present' => 0,
        'late' => 0,
        'half' => 0,
        'absent' => 0,
        'leave' => 0,
        'days_worked' => 0.0,
    ];

    while ($row = $res->fetch_assoc()) {
        $status = strtolower(trim($row['status'] ?? ''));
        $count = intval($row['total']);
        switch ($status) {
            case 'present':
                $summary['present'] += $count;
                break;
            case 'late':
                $summary['late'] += $count;
                break;
            case 'half day':
            case 'half':
                $summary['half'] += $count;
                break;
            case 'absent':
                $summary['absent'] += $count;
                break;
            case 'leave':
                $summary['leave'] += $count;
                break;
        }
    }

    // Work day calculation:
    // Present = 1, Late = 1, Half Day = 0.5, Absent = 0, Leave = 1 (if paid, otherwise 0)
    $summary['days_worked'] = $summary['present'] + $summary['late'] + ($summary['half'] * 0.5);
    if ($leave_as_paid) {
        $summary['days_worked'] += $summary['leave'];
    }

    return $summary;
}

/**
 * Compute payroll numbers given salary and attendance summary.
 *
 * Returns array:
 *  - daily_rate, basic_pay, gross_pay, deductions, net_pay, breakdowns
 */
function computePayrollFromAttendance($conn, $basic_salary, $working_days, $attendanceSummary, $overtime = 0, $allowances = 0, $employeeOptions = []) {
    // basic_salary is now treated as a daily rate (not monthly).
    $working_days = $working_days > 0 ? $working_days : 26;
    $daily_rate = max(0, floatval($basic_salary));
    $monthly_salary = $daily_rate * $working_days; // used for statutory contribution calculations

    $days_worked = floatval($attendanceSummary['days_worked'] ?? 0);
    $basic_pay = $daily_rate * $days_worked;

    $gross_pay = $basic_pay + $overtime + $allowances;

    $deductions = 0;
    $breakdowns = [];

    // Determine proportion of the monthly salary that is being paid this period.
    $payProportion = ($monthly_salary > 0) ? min(1, max(0, $basic_pay / $monthly_salary)) : 0;

    // Government contributions (pro-rated based on actual paid salary portion)
    if (!empty($employeeOptions['sss_enabled'])) {
        $sss = round(calculateSSS($monthly_salary) * $payProportion, 2);
        $deductions += $sss;
        $breakdowns[] = ['deduction','SSS','SSS Contribution',$sss];
    }
    if (!empty($employeeOptions['philhealth_enabled'])) {
        $ph = round(calculatePhilHealth($monthly_salary) * $payProportion, 2);
        $deductions += $ph;
        $breakdowns[] = ['deduction','PHILHEALTH','PhilHealth Contribution',$ph];
    }
    if (!empty($employeeOptions['pagibig_enabled'])) {
        $pi = round(calculatePagIbig($monthly_salary) * $payProportion, 2);
        $deductions += $pi;
        $breakdowns[] = ['deduction','PAGIBIG','Pag-IBIG Contribution',$pi];
    }

    // (Optional) Provide a late penalty example (1/8 daily rate per late)
    $latePenalty = ($attendanceSummary['late'] ?? 0) * ($daily_rate / 8);
    if ($latePenalty > 0) {
        $deductions += $latePenalty;
        $breakdowns[] = ['deduction','LATE','Late Penalty',$latePenalty];
    }

    // Withholding tax (approximate). Applies proportionally based on the paid portion of the month.
    $monthlyTax = calculateWithholdingTax($monthly_salary);
    $withholdingTax = round($monthlyTax * $payProportion, 2);
    if ($withholdingTax > 0) {
        $deductions += $withholdingTax;
        $breakdowns[] = ['deduction','TAX','Withholding Tax',$withholdingTax];
    }

    $net_pay = $gross_pay - $deductions;

    return [
        'daily_rate' => $daily_rate,
        'days_worked' => $days_worked,
        'basic_pay' => $basic_pay,
        'gross_pay' => $gross_pay,
        'deductions' => $deductions,
        'net_pay' => $net_pay,
        'breakdowns' => $breakdowns,
    ];
}
