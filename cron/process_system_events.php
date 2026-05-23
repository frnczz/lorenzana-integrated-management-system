<?php
/**
 * LORINIMS Event Processor - Background Job
 * Run via cron every minute: * * * * * php /path/to/lorinims/cron/process_system_events.php
 * Or run manually for testing.
 */

$base = dirname(__DIR__);
require_once $base . '/db_connect.php';
require_once $base . '/includes/functions.php';
require_once $base . '/includes/accounting_events.php';

// Ensure system_events table exists
$check = @$conn->query("SHOW TABLES LIKE 'system_events'");
if (!$check || $check->num_rows === 0) {
    echo "system_events table not found. Run database_refactor_event_driven.sql first.\n";
    exit(1);
}

$events = $conn->query("
    SELECT id, entity_type, entity_id, event_type, payload
    FROM system_events
    WHERE processed = 0
    ORDER BY id ASC
    LIMIT 50
");

if (!$events || $events->num_rows === 0) {
    exit(0);
}

$processed = 0;
while ($row = $events->fetch_assoc()) {
    try {
        $payload = !empty($row['payload']) ? json_decode($row['payload'], true) : [];
        if (!is_array($payload)) $payload = [];

        switch ($row['event_type']) {
            case 'SALES_ORDER_DELIVERED':
                $order_id = (int)$row['entity_id'];
                if (empty($payload['order_items'])) {
                    // Only fulfill items that are still reserved (to avoid double fulfillment)
                $items_q = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $order_id AND reserved = 1");
                    $payload['order_items'] = [];
                    while ($r = $items_q->fetch_assoc()) {
                        $payload['order_items'][] = ['product_id' => (int)$r['product_id'], 'quantity' => (float)$r['quantity']];
                    }
                }
                $payload['order_id'] = $order_id;
                $payload['items'] = $payload['order_items'] ?? [];
                if (function_exists('processInventoryEvent')) {
                    processInventoryEvent($conn, 'SALES_FULFILL', $payload);
                }
                if (function_exists('autoGenerateInvoiceFromEvent')) {
                    autoGenerateInvoiceFromEvent($conn, $order_id);
                }
                break;

            case 'GRN_RECEIVED':
            case 'QC_APPROVED_RAW':
                if (function_exists('processInventoryEvent')) {
                    processInventoryEvent($conn, $row['event_type'], $payload);
                }
                break;

            case 'QC_APPROVED_FG':
                if (function_exists('processInventoryEvent')) {
                    processInventoryEvent($conn, 'PRODUCTION_OUTPUT', $payload);
                }
                break;

            case 'PRODUCTION_CONSUME':
            case 'PRODUCTION_OUTPUT':
            case 'RETURN_PROCESSED':
            case 'SALES_RESERVE':
            case 'SALES_RELEASE':
                if (function_exists('processInventoryEvent')) {
                    processInventoryEvent($conn, $row['event_type'], $payload);
                }
                break;

            default:
                // Unknown event - log but don't fail
                error_log("LORINIMS: Unknown event type " . $row['event_type']);
        }

        $upd = $conn->prepare("UPDATE system_events SET processed = 1, processed_at = NOW() WHERE id = ?");
        $upd->bind_param("i", $row['id']);
        $upd->execute();
        $upd->close();
        $processed++;
    } catch (Exception $e) {
        error_log("LORINIMS event processor error (event_id={$row['id']}): " . $e->getMessage());
        // Don't mark as processed - will retry next run
    }
}

if ($processed > 0 && php_sapi_name() === 'cli') {
    echo "Processed $processed event(s)\n";
}
