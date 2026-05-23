<?php
session_start();

// Allow admin and accounting roles
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="wrapper">

    <!-- Sidebar -->
    <?php include "layouts/sidebar.php"; ?>

    <!-- Main Area -->
    <div class="main">

        <!-- Header -->
        <?php include "layouts/header.php"; ?>

        <!-- Content -->
        <div class="content">
            <h2>Payroll & Employee Management</h2>
            <p>Manage employee records, attendance, and payroll processing.</p>

            <?php include "includes/functions.php"; showMessage(); ?>
            <?php include "db_connect.php"; ?>

            <!-- Employee Management -->
            <div class="card">
                <h3>Employee Management</h3>
                <form method="POST" action="api/save_employee.php" id="employeeForm" data-loading-message="Saving employee..." data-loading-subtext="Creating or updating employee record.">
                    <input type="hidden" name="action" id="emp-action" value="create">
                    <input type="hidden" name="employee_id" id="emp-id" value="">
                    <table>
                        <tr>
                            <td>Employee Number</td>
                            <td><input type="text" name="employee_number" id="emp-number" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>First Name</td>
                            <td><input type="text" name="first_name" id="emp-first-name" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Last Name</td>
                            <td><input type="text" name="last_name" id="emp-last-name" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Middle Name</td>
                            <td><input type="text" name="middle_name" id="emp-middle-name" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td>Position</td>
                            <td><input type="text" name="position" id="emp-position" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td>Department</td>
                            <td>
                                <select name="department" id="emp-department" style="width:100%; padding:8px;">
                                    <option value="">-- Select Department --</option>
                                    <option value="Production">Production</option>
                                    <option value="Warehouse">Warehouse</option>
                                    <option value="Quality Control">Quality Control</option>
                                    <option value="Accounting">Accounting</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Logistics">Logistics</option>
                                    <option value="Administration">Administration</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Hire Date</td>
                            <td><input type="date" name="hire_date" id="emp-hire-date" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td>Basic Salary</td>
                            <td><input type="number" name="salary" step="0.01" id="emp-salary" style="width:100%; padding:8px;" placeholder="0.00"></td>
                        </tr>
                        <tr>
                            <td>Link to User Account (Optional)</td>
                            <td>
                                <?php
                                $users_query = "SELECT id, username, full_name FROM users WHERE id NOT IN (SELECT user_id FROM employees WHERE user_id IS NOT NULL) ORDER BY username";
                                $users_result = $conn->query($users_query);
                                ?>
                                <select name="user_id" id="emp-user-id" style="width:100%; padding:8px;">
                                    <option value="">-- Select User (Optional) --</option>
                                    <?php if ($users_result && $users_result->num_rows > 0): ?>
                                        <?php while ($user = $users_result->fetch_assoc()): ?>
                                            <option value="<?php echo $user['id']; ?>">
                                                <?php echo htmlspecialchars($user['username'] . ' - ' . ($user['full_name'] ?? 'N/A')); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="button" class="btn" onclick="resetEmployeeForm()" style="margin-right: 10px; background: var(--text-muted);">Cancel</button>
                                <button type="submit" class="btn">Save Employee</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Employees List -->
            <div class="card">
                <h3>Employee Records</h3>
                <?php
                $employees_query = "SELECT e.*, u.username, u.role FROM employees e 
                                   LEFT JOIN users u ON e.user_id = u.id 
                                   ORDER BY e.last_name, e.first_name";
                $employees_result = $conn->query($employees_query);
                ?>
                <table>
                    <tr>
                        <th>Emp. No</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php if ($employees_result && $employees_result->num_rows > 0): ?>
                        <?php while ($emp = $employees_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['employee_number']); ?></td>
                                <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . ($emp['middle_name'] ? $emp['middle_name'] . ' ' : '') . $emp['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['position'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($emp['department'] ?? '-'); ?></td>
                                <td><?php echo formatCurrency($emp['salary']); ?></td>
                                <td>
                                    <span style="padding: 4px 8px; background: <?php echo $emp['status'] == 'Active' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(220, 38, 38, 0.1)'; ?>; color: <?php echo $emp['status'] == 'Active' ? '#10b981' : '#dc2626'; ?>; border-radius: 4px; font-weight: 600;">
                                        <?php echo htmlspecialchars($emp['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn" style="padding: 6px 12px; font-size: 12px; margin-right: 5px;" onclick="editEmployee(<?php echo htmlspecialchars(json_encode($emp)); ?>)">Edit</button>
                                    <a href="payroll_attendance.php?employee_id=<?php echo $emp['employee_id']; ?>" class="btn" style="padding: 6px 12px; font-size: 12px; text-decoration: none; display: inline-block; background: #3b82f6;">Attendance</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: var(--text-muted);">No employees found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Attendance Quick Entry -->
            <div class="card">
                <h3>Quick Attendance Entry</h3>
                <form method="POST" action="api/save_attendance.php" data-loading-message="Saving attendance..." data-loading-subtext="Recording attendance entry.">
                    <table>
                        <tr>
                            <td>Employee</td>
                            <td>
                                <?php
                                $active_employees = $conn->query("SELECT employee_id, first_name, last_name, employee_number FROM employees WHERE status = 'Active' ORDER BY last_name");
                                ?>
                                <select name="employee_id" style="width:100%; padding:8px;" required>
                                    <option value="">-- Select Employee --</option>
                                    <?php if ($active_employees && $active_employees->num_rows > 0): ?>
                                        <?php while ($emp = $active_employees->fetch_assoc()): ?>
                                            <option value="<?php echo $emp['employee_id']; ?>">
                                                <?php echo htmlspecialchars($emp['employee_number'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Date</td>
                            <td><input type="date" name="attendance_date" style="width:100%; padding:8px;" value="<?php echo date('Y-m-d'); ?>" required></td>
                        </tr>
                        <tr>
                            <td>Time In</td>
                            <td><input type="time" name="time_in" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td>Time Out</td>
                            <td><input type="time" name="time_out" style="width:100%; padding:8px;"></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <select name="status" style="width:100%; padding:8px;" required>
                                    <option value="Present">Present</option>
                                    <option value="Absent">Absent</option>
                                    <option value="Late">Late</option>
                                    <option value="Half Day">Half Day</option>
                                    <option value="Leave">Leave</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Save Attendance</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Payroll Processing -->
            <div class="card">
                <h3>Payroll Processing</h3>
                <form method="POST" action="api/process_payroll.php" data-loading-message="Processing payroll..." data-loading-subtext="Calculating and recording payroll.">
                    <table>
                        <tr>
                            <td>Employee</td>
                            <td>
                                <?php
                                $payroll_employees = $conn->query("SELECT employee_id, first_name, last_name, employee_number, salary FROM employees WHERE status = 'Active' ORDER BY last_name");
                                ?>
                                <select name="employee_id" id="payroll-employee" style="width:100%; padding:8px;" required onchange="loadEmployeeSalary()">
                                    <option value="">-- Select Employee --</option>
                                    <?php if ($payroll_employees && $payroll_employees->num_rows > 0): ?>
                                        <?php while ($emp = $payroll_employees->fetch_assoc()): ?>
                                            <option value="<?php echo $emp['employee_id']; ?>" data-salary="<?php echo $emp['salary']; ?>">
                                                <?php echo htmlspecialchars($emp['employee_number'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Payroll Period Start</td>
                            <td><input type="date" name="payroll_period_start" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Payroll Period End</td>
                            <td><input type="date" name="payroll_period_end" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Basic Salary</td>
                            <td><input type="number" name="basic_salary" id="basic-salary" step="0.01" style="width:100%; padding:8px;" required></td>
                        </tr>
                        <tr>
                            <td>Overtime Pay</td>
                            <td><input type="number" name="overtime_pay" id="overtime-pay" step="0.01" style="width:100%; padding:8px;" value="0" onchange="calculatePayroll()"></td>
                        </tr>
                        <tr>
                            <td>Allowances</td>
                            <td><input type="number" name="allowances" id="allowances" step="0.01" style="width:100%; padding:8px;" value="0" onchange="calculatePayroll()"></td>
                        </tr>
                        <tr>
                            <td>Deductions</td>
                            <td><input type="number" name="deductions" id="deductions" step="0.01" style="width:100%; padding:8px;" value="0" onchange="calculatePayroll()"></td>
                        </tr>
                        <tr>
                            <td>Gross Pay</td>
                            <td><input type="number" name="gross_pay" id="gross-pay" step="0.01" style="width:100%; padding:8px;" readonly></td>
                        </tr>
                        <tr>
                            <td>Net Pay</td>
                            <td><input type="number" name="net_pay" id="net-pay" step="0.01" style="width:100%; padding:8px; font-weight: bold; font-size: 16px; color: #10b981;" readonly></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;">
                                <button type="submit" class="btn">Process Payroll</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <!-- Payroll Records -->
            <div class="card">
                <h3>Payroll Records</h3>
                <?php
                $payroll_query = "SELECT p.*, e.first_name, e.last_name, e.employee_number, u.full_name as processed_by_name
                                FROM payroll p
                                LEFT JOIN employees e ON p.employee_id = e.employee_id
                                LEFT JOIN users u ON p.processed_by = u.id
                                ORDER BY p.payroll_period_end DESC, p.created_at DESC LIMIT 50";
                $payroll_result = $conn->query($payroll_query);
                ?>
                <table>
                    <tr>
                        <th>Employee</th>
                        <th>Period</th>
                        <th>Gross Pay</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php if ($payroll_result && $payroll_result->num_rows > 0): ?>
                        <?php while ($pay = $payroll_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pay['employee_number'] . ' - ' . $pay['first_name'] . ' ' . $pay['last_name']); ?></td>
                                <td><?php echo formatDate($pay['payroll_period_start']) . ' to ' . formatDate($pay['payroll_period_end']); ?></td>
                                <td><?php echo formatCurrency($pay['gross_pay']); ?></td>
                                <td><?php echo formatCurrency($pay['deductions']); ?></td>
                                <td style="font-weight: bold; color: #10b981;"><?php echo formatCurrency($pay['net_pay']); ?></td>
                                <td>
                                    <span style="padding: 4px 8px; background: rgba(255, 107, 53, 0.1); color: #FF6B35; border-radius: 4px; font-weight: 600;">
                                        <?php echo htmlspecialchars($pay['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="api/generate_pdf.php?type=payroll&id=<?php echo $pay['payroll_id']; ?>" target="_blank" class="btn" style="padding: 6px 12px; font-size: 12px; text-decoration: none; display: inline-block;">📄 Payslip</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: var(--text-muted);">No payroll records found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

        </div>

        <!-- Footer -->
        <?php include "layouts/footer.php"; ?>

    </div>

</div>

<script src="assets/js/sidebar.js"></script>
<script>
function editEmployee(emp) {
    document.getElementById('emp-action').value = 'update';
    document.getElementById('emp-id').value = emp.employee_id;
    document.getElementById('emp-number').value = emp.employee_number;
    document.getElementById('emp-first-name').value = emp.first_name;
    document.getElementById('emp-last-name').value = emp.last_name;
    document.getElementById('emp-middle-name').value = emp.middle_name || '';
    document.getElementById('emp-position').value = emp.position || '';
    document.getElementById('emp-department').value = emp.department || '';
    document.getElementById('emp-hire-date').value = emp.hire_date || '';
    document.getElementById('emp-salary').value = emp.salary || '';
    document.getElementById('emp-user-id').value = emp.user_id || '';
    
    document.querySelector('h3').textContent = 'Edit Employee';
    document.querySelector('h3').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetEmployeeForm() {
    document.getElementById('employeeForm').reset();
    document.getElementById('emp-action').value = 'create';
    document.getElementById('emp-id').value = '';
    document.querySelector('h3').textContent = 'Employee Management';
}

function loadEmployeeSalary() {
    const select = document.getElementById('payroll-employee');
    const selectedOption = select.options[select.selectedIndex];
    const salary = selectedOption.getAttribute('data-salary') || 0;
    document.getElementById('basic-salary').value = salary;
    calculatePayroll();
}

function calculatePayroll() {
    const basic = parseFloat(document.getElementById('basic-salary').value) || 0;
    const overtime = parseFloat(document.getElementById('overtime-pay').value) || 0;
    const allowances = parseFloat(document.getElementById('allowances').value) || 0;
    const deductions = parseFloat(document.getElementById('deductions').value) || 0;
    
    const gross = basic + overtime + allowances;
    const net = gross - deductions;
    
    document.getElementById('gross-pay').value = gross.toFixed(2);
    document.getElementById('net-pay').value = net.toFixed(2);
}
</script>

</body>
</html>
