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
    <title>Employee Management | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Employee Management</h2>
            <p>Manage employee records.</p>
            <?php showMessage(); ?>
            <div class="card">
                <h3 id="empFormTitle">Employee Management</h3>
                <form method="POST" action="api/save_employee.php" id="employeeForm" data-loading-message="Saving employee..." data-loading-subtext="Creating or updating employee record.">
                    <input type="hidden" name="action" id="emp-action" value="create">
                    <input type="hidden" name="employee_id" id="emp-id" value="">
                    <table>
                        <tr><td>Employee Number</td><td><input type="text" name="employee_number" id="emp-number" style="width:100%; padding:8px;" placeholder="Auto-generated when creating" readonly></td></tr>
                        <tr><td>First Name</td><td><input type="text" name="first_name" id="emp-first-name" style="width:100%; padding:8px;" required></td></tr>
                        <tr><td>Last Name</td><td><input type="text" name="last_name" id="emp-last-name" style="width:100%; padding:8px;" required></td></tr>
                        <tr><td>Middle Name</td><td><input type="text" name="middle_name" id="emp-middle-name" style="width:100%; padding:8px;"></td></tr>
                        <tr><td>Position</td><td><input type="text" name="position" id="emp-position" style="width:100%; padding:8px;"></td></tr>
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
                        <tr><td>Hire Date</td><td><input type="date" name="hire_date" id="emp-hire-date" style="width:100%; padding:8px;"></td></tr>
                        <tr><td>Daily Salary</td><td><input type="number" name="salary" step="0.01" id="emp-salary" style="width:100%; padding:8px;" placeholder="0.00"></td></tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <select name="status" id="emp-status" style="width:100%; padding:8px;">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Terminated">Terminated</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Link to User Account (Optional)</td>
                            <td>
                                <?php $users_result = $conn->query("SELECT id, username, full_name FROM users WHERE id NOT IN (SELECT user_id FROM employees WHERE user_id IS NOT NULL) ORDER BY username"); ?>
                                <select name="user_id" id="emp-user-id" style="width:100%; padding:8px;">
                                    <option value="">-- Select User (Optional) --</option>
                                    <?php if ($users_result): while ($user = $users_result->fetch_assoc()): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username'] . ' - ' . ($user['full_name'] ?? 'N/A')); ?></option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr><td colspan="2" style="text-align:right;">
                            <button type="button" class="btn" onclick="resetEmployeeForm()" style="margin-right: 10px; background: var(--text-muted);">Cancel</button>
                            <button type="submit" class="btn">Save Employee</button>
                        </td></tr>
                    </table>
                </form>
            </div>
            <div class="card">
                <h3>Employee Records</h3>
                <?php
                $sort = getSortParams('last_name', ['employee_number', 'first_name', 'last_name', 'position', 'department', 'salary', 'status']);
                $column_map = ['employee_number' => 'e.employee_number', 'first_name' => 'e.first_name', 'last_name' => 'e.last_name', 'position' => 'e.position', 'department' => 'e.department', 'salary' => 'e.salary', 'status' => 'e.status'];
                $order_by = isset($column_map[$sort['column']]) ? $column_map[$sort['column']] : 'e.last_name';
                $employees_result = $conn->query("SELECT e.*, u.username FROM employees e LEFT JOIN users u ON e.user_id = u.id ORDER BY " . $order_by . " " . $sort['order'] . ", e.first_name ASC");
                ?>
                <table>
                    <tr>
                        <th><?php echo sortHeader('employee_number', 'Emp. No', $sort); ?></th>
                        <th><?php echo sortHeader('last_name', 'Name', $sort); ?></th>
                        <th><?php echo sortHeader('position', 'Position', $sort); ?></th>
                        <th><?php echo sortHeader('department', 'Department', $sort); ?></th>
                        <th><?php echo sortHeader('salary', 'Daily Rate', $sort); ?></th>
                        <th><?php echo sortHeader('status', 'Status', $sort); ?></th>
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
                                <td><span style="padding: 4px 8px; background: <?php echo $emp['status'] == 'Active' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(220, 38, 38, 0.1)'; ?>; color: <?php echo $emp['status'] == 'Active' ? '#10b981' : '#dc2626'; ?>; border-radius: 4px; font-weight: 600;"><?php echo htmlspecialchars($emp['status']); ?></span></td>
                                <td>
                                    <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                                        <button class="btn" style="padding: 6px 12px; font-size: 12px; margin: 0; cursor: pointer;" onclick="editEmployee(<?php echo htmlspecialchars(json_encode($emp)); ?>)">Edit</button>
                                        <a href="payroll_attendance.php?employee_id=<?php echo $emp['employee_id']; ?>" class="btn" style="padding: 6px 12px; font-size: 12px; text-decoration: none; display: inline-block; background: #3b82f6; margin: 0;">Attendance</a>
                                        <?php if ($emp['status'] !== 'Inactive'): ?>
                                            <button class="btn" style="padding: 6px 12px; font-size: 12px; margin: 0; background: #f59e0b;" onclick="updateEmployeeStatus(<?php echo $emp['employee_id']; ?>, 'Inactive')">Set Inactive</button>
                                        <?php endif; ?>
                                        <?php if ($emp['status'] !== 'Terminated'): ?>
                                            <button class="btn" style="padding: 6px 12px; font-size: 12px; margin: 0; background: #ef4444;" onclick="updateEmployeeStatus(<?php echo $emp['employee_id']; ?>, 'Terminated')">Set Terminated</button>
                                        <?php endif; ?>
                                        <?php if ($emp['status'] !== 'Active'): ?>
                                            <button class="btn" style="padding: 6px 12px; font-size: 12px; margin: 0; background: #10b981;" onclick="updateEmployeeStatus(<?php echo $emp['employee_id']; ?>, 'Active')">Reactivate</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-muted);">No employees found.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
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
    document.getElementById('emp-status').value = emp.status || 'Active';
    document.getElementById('emp-user-id').value = emp.user_id || '';
    document.getElementById('empFormTitle').textContent = 'Edit Employee';
    document.getElementById('empFormTitle').scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function resetEmployeeForm() {
    document.getElementById('employeeForm').reset();
    document.getElementById('emp-action').value = 'create';
    document.getElementById('emp-id').value = '';
    document.getElementById('empFormTitle').textContent = 'Employee Management';
}

function updateEmployeeStatus(employeeId, newStatus) {
    if (!confirm('Change employee status to "' + newStatus + '"?')) {
        return;
    }

    fetch('api/update_employee_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ employee_id: employeeId, status: newStatus })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Failed to update status: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(function(err) {
        console.error('Status update failed', err);
        alert('Failed to update status. Check console for details.');
    });
}
</script>
</body>
</html>
