<?php
/**
 * Low Stock Alerts - Shared component for admin & warehouse dashboards
 * Data aligned with inventory_summary, inventory_raw_materials, inventory_items
 */
if (!isset($conn) || !$conn) {
    $total_low_stock = $low_stock_raw = $low_stock_fg = $out_of_stock_raw = 0;
    $low_stock_items = [];
    return;
}

$LOW_STOCK_FG_THRESHOLD = 50;
if (function_exists('getWarehouseSetting')) {
    $LOW_STOCK_FG_THRESHOLD = max(1, floatval(getWarehouseSetting($conn, 'low_stock_threshold', 50, true)));
} elseif (function_exists('getSetting')) {
    $LOW_STOCK_FG_THRESHOLD = max(1, floatval(getSetting($conn, 'warehouse_settings', 'low_stock_threshold', 50, true)));
}

$low_stock_raw = $conn->query("SELECT COUNT(*) as c FROM raw_materials WHERE (min_stock_level > 0 AND quantity <= min_stock_level) OR quantity <= 0")->fetch_assoc()['c'] ?? 0;
$out_of_stock_raw = $conn->query("SELECT COUNT(*) as c FROM raw_materials WHERE quantity <= 0")->fetch_assoc()['c'] ?? 0;

$low_stock_fg = 0;
$fg_low_q = $conn->prepare("SELECT COUNT(DISTINCT fg.product_id) as c FROM finished_goods fg WHERE (fg.quantity - COALESCE(fg.reserved_quantity, 0)) < ? AND fg.qc_approved = 1");
if ($fg_low_q) {
    $fg_low_q->bind_param("d", $LOW_STOCK_FG_THRESHOLD);
    $fg_low_q->execute();
    $r = $fg_low_q->get_result()->fetch_assoc();
    $low_stock_fg = $r ? (int)$r['c'] : 0;
    $fg_low_q->close();
}

$total_low_stock = (int)$low_stock_raw + (int)$low_stock_fg;

$thr = (float)$LOW_STOCK_FG_THRESHOLD;
$low_stock_pagination = function_exists('getPagination')
    ? getPagination(
        $conn,
        "SELECT (
            (SELECT COUNT(*) FROM raw_materials WHERE (min_stock_level > 0 AND quantity <= min_stock_level) OR quantity <= 0)
            +
            (SELECT COUNT(*) FROM finished_goods fg INNER JOIN products p ON fg.product_id = p.product_id WHERE fg.qc_approved = 1 AND (fg.quantity - COALESCE(fg.reserved_quantity, 0)) < $thr)
        ) as c",
        null,
        'low_stock_page',
        'low_stock_per_page'
    )
    : ['offset' => 0, 'per_page' => 25];

$low_stock_items_result = $conn->query("
    SELECT 'Raw Material' as item_type, material_name as item_name, quantity,
           GREATEST(min_stock_level, 1) as threshold, unit,
           COALESCE(warehouse_location, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas') as location,
           COALESCE(category, 'Raw Material') as category
    FROM raw_materials
    WHERE (min_stock_level > 0 AND quantity <= min_stock_level) OR quantity <= 0
    UNION ALL
    SELECT 'Finished Good' as item_type, p.product_name as item_name,
           (fg.quantity - COALESCE(fg.reserved_quantity, 0)) as quantity,
           $thr as threshold, COALESCE(p.unit, 'pcs') as unit,
           COALESCE(fg.warehouse_location, 'Lot 6720 Brgy San Joaquin Sto Tomas Batangas') as location,
           'Finished Goods' as category
    FROM finished_goods fg
    INNER JOIN products p ON fg.product_id = p.product_id
    WHERE fg.qc_approved = 1 AND (fg.quantity - COALESCE(fg.reserved_quantity, 0)) < $thr
    ORDER BY quantity ASC LIMIT " . $low_stock_pagination['offset'] . ", " . $low_stock_pagination['per_page'] . "
");
$low_stock_items = $low_stock_items_result ? $low_stock_items_result->fetch_all(MYSQLI_ASSOC) : [];
