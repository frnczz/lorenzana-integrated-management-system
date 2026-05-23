<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['admin', 'warehouse', 'production']))) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Get expired batches (more than 0 days past expiry)
$expired_batches = $conn->query("
    SELECT 'raw' as type, rmb.batch_number, rm.material_name as item_name, rmb.quantity_remaining,
           rmb.expiry_date, rmb.warehouse_location,
           DATEDIFF(CURDATE(), rmb.expiry_date) as days_to_expiry
    FROM raw_material_batches rmb
    LEFT JOIN raw_materials rm ON rmb.material_id = rm.material_id
    WHERE rmb.expiry_date IS NOT NULL AND rmb.expiry_date < CURDATE() AND rmb.quantity_remaining > 0
    UNION ALL
    SELECT 'finished' as type, fgb.batch_number, p.product_name as item_name, fgb.quantity_remaining,
           fgb.expiry_date, fgb.warehouse_location,
           DATEDIFF(CURDATE(), fgb.expiry_date) as days_to_expiry
    FROM finished_goods_batches fgb
    LEFT JOIN products p ON fgb.product_id = p.product_id
    WHERE fgb.expiry_date IS NOT NULL AND fgb.expiry_date < CURDATE() AND fgb.quantity_remaining > 0
    ORDER BY expiry_date ASC
");

$batches = [];
if ($expired_batches) {
    while ($batch = $expired_batches->fetch_assoc()) {
        $batches[] = $batch;
    }
}

echo json_encode(['expired_batches' => $batches]);
?>