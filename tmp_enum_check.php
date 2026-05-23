<?php
$c=new mysqli('localhost','root','','lorinims_db');
if($c->connect_error){echo "connfail\n"; exit;}
$r=$c->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='lorinims_db' AND TABLE_NAME='sales_orders' AND COLUMN_NAME='status'");
$row=$r->fetch_assoc();
echo $row['COLUMN_TYPE'] . "\n";
$c->close();
