<?php
require __DIR__ . '/db_connect.php';

$sql = "SELECT 
    p.product_name,
    GROUP_CONCAT(DISTINCT COALESCE(fg.warehouse_location, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas') SEPARATOR ', ') AS warehouse_location,
    SUM(GREATEST(fg.quantity - COALESCE(fg.reserved_quantity, 0), 0)) AS total_quantity,
    SUM(fg.quantity) AS total_stock,
    SUM(COALESCE(fg.reserved_quantity, 0)) AS total_reserved
FROM finished_goods fg
INNER JOIN products p ON fg.product_id = p.product_id
GROUP BY fg.product_id, p.product_name
ORDER BY p.product_name ASC";

$res = $conn->query($sql);
if (!$res) {
    echo "ERROR: " . $conn->error . "\n";
    exit(1);
}

echo "Rows: " . $res->num_rows . "\n";
while ($row = $res->fetch_assoc()) {
    echo $row['product_name'] . " | available=" . $row['total_quantity'] . " | reserved=" . $row['total_reserved'] . "\n";
}
