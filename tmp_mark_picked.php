<?php
// Run this script from CLI to simulate marking an order picked up.
// It requires a valid session user id / role.

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Simulate POST request
$_POST['order_id'] = 2;

// Ensure the script uses POST
$_SERVER['REQUEST_METHOD'] = 'POST';

// Ensure relative includes work the same as when called via web
chdir(__DIR__ . '/api');

ob_start();
require __DIR__ . '/api/mark_picked_up.php';
$output = ob_get_clean();

echo "API response:\n" . $output . "\n\n";

// Verify DB now
$c = new mysqli('localhost','root','','lorinims_db');
$r = $c->prepare('SELECT order_id, order_number, fulfillment_type, status FROM sales_orders WHERE order_id = ? LIMIT 1');
$r->bind_param('i', $_POST['order_id']);
$r->execute();
$res = $r->get_result()->fetch_assoc();
$r->close();

echo "DB row after update:\n";
echo json_encode($res) . "\n";

$r2 = $c->query("SELECT DISTINCT status, COUNT(*) AS cnt FROM sales_orders WHERE order_number LIKE 'PUP-%' OR fulfillment_type='Customer Pickup' GROUP BY status");
while ($row = $r2->fetch_assoc()) {
    echo ($row['status'] !== null && $row['status'] !== '' ? $row['status'] : '(empty)') . " -> " . $row['cnt'] . "\n";
}

$c->close();
