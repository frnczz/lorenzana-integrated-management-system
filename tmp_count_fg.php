<?php
require __DIR__ . '/db_connect.php';
$res = $conn->query('SELECT COUNT(*) AS c FROM finished_goods');
if (!$res) {
    echo "ERROR: " . $conn->error . "\n";
    exit(1);
}
$row = $res->fetch_assoc();
echo "finished_goods count: " . ($row['c'] ?? 'NULL') . "\n";
