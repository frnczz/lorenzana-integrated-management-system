<?php
/**
 * Release expired sales order reservations (time-bound reservation safety).
 * Run via cron every hour: 0 * * * * php /path/to/lorinims/cron/release_expired_reservations.php
 */

$base = dirname(__DIR__);
require_once $base . '/db_connect.php';
require_once $base . '/includes/functions.php';
require_once $base . '/includes/inventory_service.php';

$has_col = @$conn->query("SHOW COLUMNS FROM sales_orders LIKE 'reservation_expires_at'");
if (!$has_col || $has_col->num_rows === 0) {
    exit(0);
}

$expired = $conn->query("
    SELECT so.order_id, so.order_number
    FROM sales_orders so
    WHERE so.reservation_expires_at IS NOT NULL
    AND so.reservation_expires_at < NOW()
    AND so.status NOT IN ('Delivered', 'Cancelled')
    AND EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = so.order_id)
");

if (!$expired || $expired->num_rows === 0) {
    exit(0);
}

$released = 0;
while ($row = $expired->fetch_assoc()) {
    try {
        $order_id = (int)$row['order_id'];
        $items_q = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $order_id");
        $items = [];
        while ($r = $items_q->fetch_assoc()) {
            $items[] = ['product_id' => (int)$r['product_id'], 'quantity' => (float)$r['quantity']];
        }
        if (!empty($items)) {
            processInventoryEvent($conn, 'SALES_RELEASE', ['items' => $items]);
            $conn->query("UPDATE sales_orders SET status = 'Cancelled' WHERE order_id = $order_id");
            emitSystemEvent($conn, 'sales_order', $order_id, 'RESERVATION_EXPIRED', ['order_items' => $items]);
            $released++;
        }
    } catch (Exception $e) {
        error_log("LORINIMS release_expired_reservations: " . $e->getMessage());
    }
}

if ($released > 0 && php_sapi_name() === 'cli') {
    echo "Released $released expired reservation(s)\n";
}
