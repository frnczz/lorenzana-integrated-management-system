<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting')) {
    header("Location: login.php");
    exit;
}
$preselect_emp = intval($_GET['employee_id'] ?? 0);
$show_active = isset($_GET['show_active']) ? ($_GET['show_active'] === '0' ? false : true) : true;
$date_from = trim($_GET['from'] ?? '');
$date_to = trim($_GET['to'] ?? '');
include "includes/functions.php";
include "db_connect.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance | LORINIMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php include "layouts/sidebar.php"; ?>
    <div class="main">
        <?php include "layouts/header.php"; ?>
        <div class="content">
            <h2>Attendance</h2>
            <p>Record employee attendance.</p>
            <?php showMessage(); ?>
            <div class="card" style="margin-bottom: 12px;">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" id="filter-active" onchange="toggleShowActive(this)" <?php echo $show_active ? 'checked' : ''; ?>>
                    Show active employees only
                </label>
            </div>
            <div class="card">
                <h3>Quick Attendance Entry</h3>
                <form method="POST" action="api/save_attendance.php" data-loading-message="Saving attendance..." data-loading-subtext="Recording attendance entry.">
                    <table>
                        <tr>
                            <td>Employee</td>
                            <td>
                                <?php
                                $employee_where = $show_active ? "WHERE status = 'Active'" : "";
                                if ($preselect_emp) {
                                    $employee_where = $show_active ? "WHERE status = 'Active' OR employee_id = " . intval($preselect_emp) : "WHERE employee_id = " . intval($preselect_emp);
                                }
                                $active_employees = $conn->query("SELECT employee_id, first_name, last_name, employee_number, status FROM employees $employee_where ORDER BY last_name");
                                ?>
                                <select name="employee_id" id="attendance-employee" style="width:100%; padding:8px;" required onchange="onEmployeeChange(this)">
                                    <option value="">-- Select Employee --</option>
                                    <?php if ($active_employees): while ($emp = $active_employees->fetch_assoc()): ?>
                                        <option value="<?php echo $emp['employee_id']; ?>" data-status="<?php echo $emp['status']; ?>" <?php echo ($preselect_emp && $preselect_emp == $emp['employee_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($emp['employee_number'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['status'] . ')'); ?>
                                        </option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr><td>Date</td><td><input type="date" name="attendance_date" style="width:100%; padding:8px;" value="<?php echo date('Y-m-d'); ?>" required></td></tr>
                        <tr><td>Time In</td><td><input type="time" name="time_in" style="width:100%; padding:8px;"></td></tr>
                        <tr><td>Time Out</td><td><input type="time" name="time_out" style="width:100%; padding:8px;"></td></tr>
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
                        <tr><td colspan="2" style="text-align:right;"><button type="submit" class="btn">Save Attendance</button></td></tr>
                    </table>
                </form>
            </div>

            <?php if ($preselect_emp):
                // Load employee profile details
                $employee = null;
                $employee_stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
                $employee_stmt->bind_param('i', $preselect_emp);
                $employee_stmt->execute();
                $employee = $employee_stmt->get_result()->fetch_assoc();
                $employee_stmt->close();

                // Load attendance records (with optional date filtering)
                $attendance_query = "SELECT * FROM attendance WHERE employee_id = ?";
                $params = [$preselect_emp];
                $types = 'i';
                if ($date_from) {
                    $attendance_query .= " AND attendance_date >= ?";
                    $types .= 's';
                    $params[] = $date_from;
                }
                if ($date_to) {
                    $attendance_query .= " AND attendance_date <= ?";
                    $types .= 's';
                    $params[] = $date_to;
                }
                $attendance_query .= " ORDER BY attendance_date DESC LIMIT 100";

                $attendance_records_stmt = $conn->prepare($attendance_query);
                $attendance_records_stmt->bind_param($types, ...$params);
                $attendance_records_stmt->execute();
                $attendance_result = $attendance_records_stmt->get_result();

                $attendance_list = [];
                while ($row = $attendance_result->fetch_assoc()) {
                    $attendance_list[] = $row;
                }

                // Summary counts
                $attendance_summary = [
                    'Present' => 0,
                    'Late' => 0,
                    'Half Day' => 0,
                    'Absent' => 0,
                    'Leave' => 0,
                ];
                foreach ($attendance_list as $row) {
                    $status = trim($row['status']);
                    if (!isset($attendance_summary[$status])) {
                        $attendance_summary[$status] = 0;
                    }
                    $attendance_summary[$status]++;
                }
            ?>
            <div class="card" style="margin-top: 20px;">
                <h3>Employee Profile</h3>
                <table>
                    <tr><td>Name</td><td><?php echo htmlspecialchars($employee['first_name'] . ' ' . ($employee['middle_name'] ? $employee['middle_name'] . ' ' : '') . $employee['last_name']); ?></td></tr>
                    <tr><td>Employee No.</td><td><?php echo htmlspecialchars($employee['employee_number']); ?></td></tr>
                    <tr><td>Position</td><td><?php echo htmlspecialchars($employee['position'] ?? '-'); ?></td></tr>
                    <tr><td>Department</td><td><?php echo htmlspecialchars($employee['department'] ?? '-'); ?></td></tr>
                    <tr><td>Status</td><td><?php echo htmlspecialchars($employee['status']); ?></td></tr>
                    <tr><td>Salary</td><td><?php echo formatCurrency($employee['salary']); ?></td></tr>
                    <tr><td>Hire Date</td><td><?php echo htmlspecialchars($employee['hire_date']); ?></td></tr>
                </table>
            </div>
            <div class="card" style="margin-top: 20px;">
                <h3>Attendance Records</h3>
                <p style="color: var(--text-muted);">Showing up to 100 most recent records for the selected employee.</p>
                <form id="attendance-filter" style="margin-bottom:16px; display:flex; flex-wrap:wrap; gap:8px; align-items:flex-end;">
                    <div>
                        <label style="font-weight: 600; font-size: 0.9rem;">From</label><br>
                        <input type="date" id="filter-from" value="<?php echo htmlspecialchars($date_from); ?>" style="padding:8px; width:160px;">
                    </div>
                    <div>
                        <label style="font-weight: 600; font-size: 0.9rem;">To</label><br>
                        <input type="date" id="filter-to" value="<?php echo htmlspecialchars($date_to); ?>" style="padding:8px; width:160px;">
                    </div>
                    <button type="button" class="btn" onclick="applyDateFilter()" style="height:40px;">Apply</button>
                    <div style="margin-left:auto; display:flex; gap:12px; font-size:0.9rem; color: var(--text-muted);">
                        <?php foreach ($attendance_summary as $status => $count): ?>
                            <span><?php echo htmlspecialchars($status); ?>: <strong><?php echo $count; ?></strong></span>
                        <?php endforeach; ?>
                    </div>
                </form>
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                    <?php if (count($attendance_list) > 0): ?>
                        <?php foreach ($attendance_list as $att): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($att['attendance_date']); ?></td>
                                <td><?php echo htmlspecialchars($att['time_in']); ?></td>
                                <td><?php echo htmlspecialchars($att['time_out']); ?></td>
                                <td><?php echo htmlspecialchars($att['status']); ?></td>
                                <td><?php echo htmlspecialchars($att['remarks']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px; color:var(--text-muted);">No attendance records found.</td></tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h3>Department Attendance Summary</h3>
                <p style="color: var(--text-muted);">Showing attendance totals for other employees in the same department (filtered by the selected date range).</p>
                <?php
                // Build department attendance summary based on the selected employee's department (or all if none selected)
                $dept_filter_sql = '';
                $summary_params = [];
                $summary_types = '';

                if (!empty($employee['department'])) {
                    $dept_filter_sql = 'AND e.department = ?';
                    $summary_params[] = $employee['department'];
                    $summary_types .= 's';
                }

                if ($date_from) {
                    $dept_filter_sql .= ' AND a.attendance_date >= ?';
                    $summary_params[] = $date_from;
                    $summary_types .= 's';
                }
                if ($date_to) {
                    $dept_filter_sql .= ' AND a.attendance_date <= ?';
                    $summary_params[] = $date_to;
                    $summary_types .= 's';
                }

                $dept_query = "SELECT e.department, e.employee_id, e.first_name, e.last_name, "
                    . "SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present, "
                    . "SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) AS late, "
                    . "SUM(CASE WHEN a.status = 'Half Day' THEN 1 ELSE 0 END) AS half_day, "
                    . "SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) AS absent, "
                    . "SUM(CASE WHEN a.status = 'Leave' THEN 1 ELSE 0 END) AS leave_days "
                    . "FROM employees e "
                    . "LEFT JOIN attendance a ON e.employee_id = a.employee_id "
                    . "WHERE 1=1 "
                    . ($show_active ? "AND e.status = 'Active' " : "")
                    . $dept_filter_sql
                    . " GROUP BY e.employee_id "
                    . "ORDER BY e.department, e.last_name, e.first_name";

                $dept_stmt = $conn->prepare($dept_query);
                if ($summary_params) {
                    $dept_stmt->bind_param($summary_types, ...$summary_params);
                }
                $dept_stmt->execute();
                $dept_result = $dept_stmt->get_result();
                ?>

                <table>
                    <tr>
                        <th>Department</th>
                        <th>Employee</th>
                        <th>Present</th>
                        <th>Late</th>
                        <th>Half Day</th>
                        <th>Absent</th>
                        <th>Leave</th>
                    </tr>
                    <?php if ($dept_result && $dept_result->num_rows > 0): ?>
                        <?php while ($row = $dept_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo intval($row['present']); ?></td>
                                <td><?php echo intval($row['late']); ?></td>
                                <td><?php echo intval($row['half_day']); ?></td>
                                <td><?php echo intval($row['absent']); ?></td>
                                <td><?php echo intval($row['leave_days']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding:20px; color:var(--text-muted);">No attendance summary data available for this department.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php include "layouts/footer.php"; ?>
    </div>
</div>
<script src="assets/js/sidebar.js"></script>
<script>
function onEmployeeChange(select) {
    var empId = select.value;
    if (!empId) return;

    var url = new URL(window.location.href);
    url.searchParams.set('employee_id', empId);
    url.searchParams.set('show_active', document.getElementById('filter-active').checked ? '1' : '0');
    url.searchParams.set('from', document.getElementById('filter-from') ? document.getElementById('filter-from').value : '');
    url.searchParams.set('to', document.getElementById('filter-to') ? document.getElementById('filter-to').value : '');
    window.location.href = url.toString();
}

function toggleShowActive(checkbox) {
    var url = new URL(window.location.href);
    url.searchParams.set('show_active', checkbox.checked ? '1' : '0');
    url.searchParams.set('from', document.getElementById('filter-from') ? document.getElementById('filter-from').value : '');
    url.searchParams.set('to', document.getElementById('filter-to') ? document.getElementById('filter-to').value : '');
    window.location.href = url.toString();
}

function applyDateFilter() {
    var url = new URL(window.location.href);
    url.searchParams.set('from', document.getElementById('filter-from').value);
    url.searchParams.set('to', document.getElementById('filter-to').value);
    url.searchParams.set('employee_id', document.getElementById('attendance-employee').value);
    url.searchParams.set('show_active', document.getElementById('filter-active').checked ? '1' : '0');
    window.location.href = url.toString();
}
</script>
</body>
</html>
