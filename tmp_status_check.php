<?php
$c = new mysqli('localhost','root','','lorinims_db');
if ($c->connect_error) {
    echo "connfail\n";
    exit;
}

// Ensure the enum supports pickup statuses so updates persist.
if (!$c->query("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('Pending','Confirmed','Dispatched','Delivered','Cancelled','Ready for Pickup','Picked Up') DEFAULT 'Pending'")) {
    echo "ALTER ERROR: " . $c->error . "\n";
}

// Show current status values for pickup orders
$r = $c->query("SELECT DISTINCT status, COUNT(*) AS cnt FROM sales_orders WHERE order_number LIKE 'PUP-%' OR fulfillment_type='Customer Pickup' GROUP BY status");
while ($row = $r->fetch_assoc()) {
    echo ($row['status'] !== null && $row['status'] !== '' ? $row['status'] : '(empty)') . " -> " . $row['cnt'] . "\n";
}

// Show sample rows with empty status
$r2 = $c->query("SELECT order_id, order_number, fulfillment_type, status FROM sales_orders WHERE (order_number LIKE 'PUP-%' OR fulfillment_type='Customer Pickup') AND (status='' OR status IS NULL) LIMIT 5");
if ($r2->num_rows) {
    echo "\nSample rows with empty status:\n";
    while ($row = $r2->fetch_assoc()) {
        echo "id=".$row['order_id']." num=".$row['order_number']." fulfil=".$row['fulfillment_type']." status=['".$row['status']."']\n";
    }
}

$c->close();
