<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}
include "includes/functions.php";
include "db_connect.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payroll | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Process Payroll</h2>
            <p>Calculate and process employee payroll.</p>
            <?php showMessage(); ?>
            <div class="card">
                <h3>Payroll Processing</h3>
                <form method="POST" action="api/process_payroll.php" data-loading-message="Processing payroll..." data-loading-subtext="Calculating and recording payroll.">
                    <table>
                        <tr>
                            <td>Employee</td>
                            <td>
                                <?php $payroll_employees = $conn->query("SELECT employee_id, first_name, last_name, employee_number, salary FROM employees WHERE status = 'Active' ORDER BY last_name"); ?>
                                <select name="employee_id" id="payroll-employee" style="width:100%; padding:8px;" required onchange="loadEmployeeSalary(this)">
                                    <option value="">-- Select Employee --</option>
                                    <?php if ($payroll_employees): while ($emp = $payroll_employees->fetch_assoc()): ?>
                                        <option value="<?php echo $emp['employee_id']; ?>" data-salary="<?php echo $emp['salary']; ?>">
                                            <?php echo htmlspecialchars($emp['employee_number'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']); ?>
                                        </option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr><td>Payroll Period Start</td><td><input type="date" name="payroll_period_start" id="payroll-period-start" style="width:100%; padding:8px;" required onchange="refreshPayrollPreview()"></td></tr>
                        <tr><td>Payroll Period End</td><td><input type="date" name="payroll_period_end" id="payroll-period-end" style="width:100%; padding:8px;" required onchange="refreshPayrollPreview()"></td></tr>
                        <tr><td>Daily Salary</td><td><input type="number" name="basic_salary" id="basic-salary" step="0.01" style="width:100%; padding:8px;" readonly required></td></tr>
                        <tr><td>Daily Rate</td><td><input type="number" id="daily-rate" step="0.01" style="width:100%; padding:8px;" readonly></td></tr>
                        <tr><td>Days Worked</td><td><input type="number" id="days-worked" step="0.01" style="width:100%; padding:8px;" readonly></td></tr>
                        <tr><td>Basic Pay</td><td><input type="number" id="basic-pay" step="0.01" style="width:100%; padding:8px;" readonly></td></tr>
                        <tr><td>Overtime Pay</td><td><input type="number" name="overtime_pay" id="overtime-pay" step="0.01" style="width:100%; padding:8px;" value="0" onchange="refreshPayrollPreview()"></td></tr>
                        <tr><td>Allowances</td><td><input type="number" name="allowances" id="allowances" step="0.01" style="width:100%; padding:8px;" value="0" onchange="refreshPayrollPreview()"></td></tr>
                        <tr><td>Gross Pay</td><td><input type="number" id="gross-pay" step="0.01" style="width:100%; padding:8px;" readonly></td></tr>
                        <tr><td>Deductions</td><td><input type="number" id="deductions" name="deductions" step="0.01" readonly style="width:100%; padding:8px;" value="0"></td></tr>
                        <tr><td>Net Pay</td><td><input type="number" id="net-pay" name="net_pay" step="0.01" style="width:100%; padding:8px;" readonly></td></tr>
                        <tr><td colspan="2" style="text-align:right;"><button type="submit" class="btn">Process Payroll</button></td></tr>
                    </table>
                </form>
            </div>
            <div class="card">
                <h3>Generate Payroll by Department</h3>
                <p style="margin-top:0; margin-bottom:10px; color: var(--text-muted);">Generate payroll for all active employees within a department for the selected period.</p>
                <form method="POST" action="api/process_payroll_department.php" data-loading-message="Generating payroll..." data-loading-subtext="Processing payroll for multiple employees.">
                    <table>
                        <tr>
                            <td>Department</td>
                            <td>
                                <?php $departments = $conn->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department <> '' ORDER BY department"); ?>
                                <select name="department" style="width:100%; padding:8px;">
                                    <option value="">-- All Departments --</option>
                                    <?php if ($departments): while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($dept['department']); ?>"><?php echo htmlspecialchars($dept['department']); ?></option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr><td>Payroll Period Start</td><td><input type="date" name="payroll_period_start" style="width:100%; padding:8px;" required></td></tr>
                        <tr><td>Payroll Period End</td><td><input type="date" name="payroll_period_end" style="width:100%; padding:8px;" required></td></tr>
                        <tr><td>Overtime Pay (per employee)</td><td><input type="number" name="overtime_pay" step="0.01" style="width:100%; padding:8px;" value="0"></td></tr>
                        <tr><td>Allowances (per employee)</td><td><input type="number" name="allowances" step="0.01" style="width:100%; padding:8px;" value="0"></td></tr>
                        <tr><td colspan="2" style="text-align:right;"><button type="submit" class="btn">Generate Payroll for Department</button></td></tr>
                    </table>
                </form>
            </div>
            <div class="card">
                <h3>Payroll Records</h3>
                <?php
                $payroll_result = $conn->query("SELECT p.*, e.first_name, e.last_name, e.employee_number FROM payroll p LEFT JOIN employees e ON p.employee_id = e.employee_id ORDER BY p.payroll_period_end DESC, p.created_at DESC LIMIT 50");
                ?>
                <table>
                    <tr><th>Employee</th><th>Period</th><th>Gross Pay</th><th>Deductions</th><th>Net Pay</th><th>Status</th><th>Actions</th></tr>
                    <?php if ($payroll_result && $payroll_result->num_rows > 0): ?>
                        <?php while ($pay = $payroll_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pay['employee_number'] . ' - ' . $pay['first_name'] . ' ' . $pay['last_name']); ?></td>
                                <td><?php echo formatDate($pay['payroll_period_start']) . ' to ' . formatDate($pay['payroll_period_end']); ?></td>
                                <td><?php echo formatCurrency($pay['gross_pay']); ?></td>
                                <td><?php echo formatCurrency($pay['deductions']); ?></td>
                                <td style="font-weight: bold; color: #10b981;"><?php echo formatCurrency($pay['net_pay']); ?></td>
                                <td><span style="padding: 4px 8px; background: rgba(255, 107, 53, 0.1); color: #FF6B35; border-radius: 4px; font-weight: 600;"><?php echo htmlspecialchars($pay['status']); ?></span></td>
                                <td><a href="api/generate_pdf.php?type=payroll&id=<?php echo $pay['payroll_id']; ?>" target="_blank" class="btn" style="padding: 6px 12px; font-size: 12px; text-decoration: none; display: inline-block;">📄 Payslip</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted);">No payroll records found.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
<script>
function loadEmployeeSalary(selectEl) {
    var select = selectEl || document.getElementById('payroll-employee');
    var opt = select.options[select.selectedIndex];
    var salary = opt ? (opt.getAttribute('data-salary') || 0) : 0;
    document.getElementById('basic-salary').value = salary;
    refreshPayrollPreview();
}

function refreshPayrollPreview() {
    var employeeId = document.getElementById('payroll-employee').value;
    var start = document.getElementById('payroll-period-start').value;
    var end = document.getElementById('payroll-period-end').value;
    var overtime = parseFloat(document.getElementById('overtime-pay').value) || 0;
    var allowances = parseFloat(document.getElementById('allowances').value) || 0;

    if (!employeeId || !start || !end) {
        return;
    }

    var url = 'api/payroll_attendance_summary.php?employee_id=' + encodeURIComponent(employeeId)
            + '&start=' + encodeURIComponent(start)
            + '&end=' + encodeURIComponent(end)
            + '&overtime=' + encodeURIComponent(overtime)
            + '&allowances=' + encodeURIComponent(allowances);

    fetch(url)
        .then(function(res){ return res.json(); })
        .then(function(data){
            if (data.error) {
                console.warn('Payroll preview error', data.error);
                return;
            }

            var payroll = data.payroll || {};
            document.getElementById('basic-salary').value = (data.employee_salary || 0).toFixed(2);
            document.getElementById('daily-rate').value = (payroll.daily_rate || 0).toFixed(2);
            document.getElementById('days-worked').value = (payroll.days_worked || 0).toFixed(2);
            document.getElementById('basic-pay').value = (payroll.basic_pay || 0).toFixed(2);
            document.getElementById('gross-pay').value = (payroll.gross_pay || 0).toFixed(2);
            document.getElementById('deductions').value = (payroll.deductions || 0).toFixed(2);
            document.getElementById('net-pay').value = (payroll.net_pay || 0).toFixed(2);

            // Also keep the computed values in hidden inputs to store with payroll record if needed
            document.querySelector('input[name="basic_salary"]').value = (data.employee_salary || 0).toFixed(2);
        })
        .catch(function(err){
            console.error('Payroll preview request failed', err);
        });
}

// Refresh preview when the page loads (if defaults are set)
document.addEventListener('DOMContentLoaded', function() {
    refreshPayrollPreview();
});
</script>
</body>
</html>
