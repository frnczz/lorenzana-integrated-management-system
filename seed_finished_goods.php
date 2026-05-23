<?php
/**
 * Seed finished_goods table with entries for existing products.
 * Each product will get a random quantity between 100 and 1000.
 *
 * Run this once to populate the finished_goods table.
 */

require_once __DIR__ . '/db_connect.php';

// Load product IDs (based on products inserted by the sample data)
$productIds = [];
$result = $conn->query("SELECT product_id FROM products");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productIds[] = intval($row['product_id']);
    }
    $result->free();
}

if (empty($productIds)) {
    die("No products found in products table.\n");
}

$selectStmt = $conn->prepare("SELECT fg_id FROM finished_goods WHERE product_id = ? LIMIT 1");
$insertStmt = $conn->prepare(
    "INSERT INTO finished_goods (product_id, quantity, expiry_date, warehouse_location)
     VALUES (?, ?, NULL, NULL)"
);
$updateStmt = $conn->prepare(
    "UPDATE finished_goods SET quantity = ?, expiry_date = NULL, warehouse_location = NULL WHERE fg_id = ?"
);

if (!$selectStmt || !$insertStmt || !$updateStmt) {
    die('Failed to prepare statements: ' . $conn->error . "\n");
}

$created = 0;
$updated = 0;

foreach ($productIds as $productId) {
    $qty = mt_rand(100, 1000);

    $selectStmt->bind_param('i', $productId);
    $selectStmt->execute();
    $selectStmt->bind_result($fgId);
    $selectStmt->fetch();
    $selectStmt->free_result();
    $selectStmt->reset();

    if ($fgId) {
        $updateStmt->bind_param('ii', $qty, $fgId);
        $updateStmt->execute();
        if ($updateStmt->affected_rows >= 0) {
            $updated++;
        }
    } else {
        $insertStmt->bind_param('ii', $productId, $qty);
        $insertStmt->execute();
        if ($insertStmt->affected_rows > 0) {
            $created++;
        }
    }
}

// Mark a few items as low stock and near expiry so they appear in alert tables
$fgIds = [];
$res = $conn->query("SELECT fg_id FROM finished_goods ORDER BY fg_id");
while ($row = $res->fetch_assoc()) {
    $fgIds[] = $row['fg_id'];
}

shuffle($fgIds);
$lowStockIds = array_slice($fgIds, 0, min(3, count($fgIds)));
$nearExpiryIds = array_slice($fgIds, 3, min(3, max(0, count($fgIds) - 3)));

$lowStockStmt = $conn->prepare("UPDATE finished_goods SET quantity = ?, qc_approved = 1 WHERE fg_id = ?");
$nearExpiryStmt = $conn->prepare("UPDATE finished_goods SET expiry_date = ?, qc_approved = 1 WHERE fg_id = ?");

// Ensure alerts are visible: mark these items as QC-approved and make quantity low/expiry near
foreach ($lowStockIds as $id) {
    $qty = mt_rand(1, 15);
    $lowStockStmt->bind_param('ii', $qty, $id);
    $lowStockStmt->execute();
}

$today = new DateTime();
foreach ($nearExpiryIds as $id) {
    $days = mt_rand(1, 14);
    $expiry = $today->modify("+{$days} days")->format('Y-m-d');
    $nearExpiryStmt->bind_param('si', $expiry, $id);
    $nearExpiryStmt->execute();
    $today = new DateTime();
}

// Also flag a few raw materials as low stock / expiring soon (these are what the dashboard tables use)
$rawIds = [];
$rawRes = $conn->query("SELECT material_id, min_stock_level FROM raw_materials WHERE min_stock_level > 0 ORDER BY material_id");
while ($row = $rawRes->fetch_assoc()) {
    $rawIds[] = $row;
}
shuffle($rawIds);
$rawLow = array_slice($rawIds, 0, min(3, count($rawIds)));
$rawNearExpiry = array_slice($rawIds, 3, min(3, max(0, count($rawIds) - 3)));

$updateRawQty = $conn->prepare("UPDATE raw_materials SET quantity = ? WHERE material_id = ?");
$updateRawExpiry = $conn->prepare("UPDATE raw_materials SET expiry_date = ? WHERE material_id = ?");

foreach ($rawLow as $row) {
    $qty = max(0, intval($row['min_stock_level']) - mt_rand(1, 5));
    $updateRawQty->bind_param('ii', $qty, $row['material_id']);
    $updateRawQty->execute();
}

$today = new DateTime();
foreach ($rawNearExpiry as $row) {
    $days = mt_rand(1, 14);
    $expiry = $today->modify("+{$days} days")->format('Y-m-d');
    $updateRawExpiry->bind_param('si', $expiry, $row['material_id']);
    $updateRawExpiry->execute();
    $today = new DateTime();
}

echo "Done. Created $created finished_goods rows, updated $updated existing rows.\n";

$conn->close();
