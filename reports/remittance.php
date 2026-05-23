<?php
session_start();
include "../db_connect.php";

// ACCESS CONTROL
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','accounting'])) {
    header("Location: ../login.php");
    exit;
}

$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-t');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Government Remittance Report</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f26522; color: #fff; }
        h2 { margin-top: 40px; }
        form { margin-bottom: 20px; }
    </style>
</head>
<body>

<h1>Government Remittance Report</h1>

<form method="get">
    Period:
    <input type="date" name="start" value="<?= $start ?>">
    to
    <input type="date" name="end" value="<?= $end ?>">
    <button type="submit">Filter</button>
</form>

<!-- ===================== SSS ===================== -->
<h2>SSS Contributions</h2>
<table>
<tr>
    <th>Employee No</th>
    <th>Employee Name</th>
    <th>Amount</th>
</tr>
<?php
$stmt = $conn->prepare("
    SELECT e.employee_number,
           CONCAT(e.last_name, ', ', e.first_name) AS name,
           SUM(pb.amount) AS amount
    FROM payroll_breakdown pb
    JOIN payroll p ON pb.payroll_id = p.payroll_id
    JOIN employees e ON p.employee_id = e.employee_id
    WHERE pb.code = 'SSS'
      AND p.payroll_period_end BETWEEN ? AND ?
    GROUP BY e.employee_id
");
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$res = $stmt->get_result();
$total = 0;
while ($r = $res->fetch_assoc()):
    $total += $r['amount'];
?>
<tr>
    <td><?= $r['employee_number'] ?></td>
    <td><?= $r['name'] ?></td>
    <td>₱<?= number_format($r['amount'],2) ?></td>
</tr>
<?php endwhile; ?>
<tr>
    <th colspan="2">TOTAL</th>
    <th>₱<?= number_format($total,2) ?></th>
</tr>
</table>

<!-- ===================== PHILHEALTH ===================== -->
<h2>PhilHealth Contributions</h2>
<table>
<tr><th>Period</th><th>Total</th></tr>
<?php
$stmt = $conn->prepare("
    SELECT p.payroll_period_start, p.payroll_period_end,
           SUM(pb.amount) AS total
    FROM payroll p
    JOIN payroll_breakdown pb ON p.payroll_id = pb.payroll_id
    WHERE pb.code = 'PHILHEALTH'
      AND p.payroll_period_end BETWEEN ? AND ?
    GROUP BY p.payroll_period_start, p.payroll_period_end
");
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()):
?>
<tr>
    <td><?= $r['payroll_period_start'] ?> → <?= $r['payroll_period_end'] ?></td>
    <td>₱<?= number_format($r['total'],2) ?></td>
</tr>
<?php endwhile; ?>
</table>

<!-- ===================== PAG-IBIG ===================== -->
<h2>Pag-IBIG Contributions</h2>
<table>
<tr><th>Period</th><th>Total</th></tr>
<?php
$stmt = $conn->prepare("
    SELECT p.payroll_period_start, p.payroll_period_end,
           SUM(pb.amount) AS total
    FROM payroll p
    JOIN payroll_breakdown pb ON p.payroll_id = pb.payroll_id
    WHERE pb.code = 'PAGIBIG'
      AND p.payroll_period_end BETWEEN ? AND ?
    GROUP BY p.payroll_period_start, p.payroll_period_end
");
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()):
?>
<tr>
    <td><?= $r['payroll_period_start'] ?> → <?= $r['payroll_period_end'] ?></td>
    <td>₱<?= number_format($r['total'],2) ?></td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>
