<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','accounting'])) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>13th Month Pay Report</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f26522; color: #fff; }
    </style>
</head>
<body>

<h1>13th Month Pay Computation</h1>

<table>
<tr>
    <th>Employee No</th>
    <th>Name</th>
    <th>Total Basic Salary</th>
    <th>13th Month Pay</th>
</tr>

<?php
$q = $conn->query("
    SELECT e.employee_number,
           CONCAT(e.first_name,' ',e.last_name) AS name,
           SUM(p.basic_salary) AS total_basic
    FROM payroll p
    JOIN employees e ON p.employee_id = e.employee_id
    GROUP BY p.employee_id
");

while ($r = $q->fetch_assoc()):
    $thirteenth = $r['total_basic'] / 12;
?>
<tr>
    <td><?= $r['employee_number'] ?></td>
    <td><?= $r['name'] ?></td>
    <td>₱<?= number_format($r['total_basic'],2) ?></td>
    <td><strong>₱<?= number_format($thirteenth,2) ?></strong></td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
