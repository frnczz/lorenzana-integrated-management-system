<?php
include 'db_connect.php';

// Check raw materials total
$total_raw = $conn->query('SELECT COALESCE(SUM(quantity), 0) as total FROM raw_materials')->fetch_assoc()['total'] ?? 0;
echo 'Total raw materials quantity: ' . $total_raw . PHP_EOL;

// Check individual raw materials
$result = $conn->query('SELECT material_name, quantity FROM raw_materials ORDER BY quantity DESC LIMIT 10');
echo 'Top 10 raw materials:' . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo '  ' . $row['material_name'] . ': ' . $row['quantity'] . PHP_EOL;
}

// Check finished goods available vs total
$fg_result = $conn->query('SELECT p.product_name, SUM(fg.quantity) as total_qty, SUM(fg.quantity - COALESCE(fg.reserved_quantity, 0)) as available_qty FROM finished_goods fg JOIN products p ON fg.product_id = p.product_id WHERE fg.qc_approved = 1 GROUP BY fg.product_id ORDER BY available_qty DESC LIMIT 5');
echo PHP_EOL . 'Top 5 finished goods (total vs available):' . PHP_EOL;
while ($row = $fg_result->fetch_assoc()) {
    echo '  ' . $row['product_name'] . ': Total=' . $row['total_qty'] . ', Available=' . $row['available_qty'] . PHP_EOL;
}

// Check for reserved quantity issues
$reserved_check = $conn->query('SELECT SUM(reserved_quantity) as total_reserved, SUM(quantity) as total_qty FROM finished_goods WHERE qc_approved = 1');
$reserved_row = $reserved_check->fetch_assoc();
echo PHP_EOL . 'Reserved quantity check:' . PHP_EOL;
echo '  Total quantity: ' . $reserved_row['total_qty'] . PHP_EOL;
echo '  Total reserved: ' . $reserved_row['total_reserved'] . PHP_EOL;
echo '  Available: ' . ($reserved_row['total_qty'] - $reserved_row['total_reserved']) . PHP_EOL;

// Check for orders that might have stale reservations
$stale_orders = $conn->query("SELECT COUNT(*) as count FROM sales_orders WHERE status IN ('Pending', 'Confirmed') AND reservation_expires_at < NOW()");
$stale_count = $stale_orders->fetch_assoc()['count'];
echo PHP_EOL . 'Stale order reservations: ' . $stale_count . PHP_EOL;
?>