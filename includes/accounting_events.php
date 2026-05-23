<?php
/**
 * LORINIMS Accounting Event Handlers
 * Called by event processor when SALES_ORDER_DELIVERED is processed.
 */

/**
 * Auto-generate invoice for a delivered order (event-triggered).
 * @return int|false Invoice ID or false on failure
 */
function autoGenerateInvoiceFromEvent($conn, $order_id) {
    $order_id = (int)$order_id;
    $order = $conn->prepare("
        SELECT so.order_id, so.order_number, so.customer_id, so.order_date, so.total_amount
        FROM sales_orders so
        WHERE so.order_id = ? AND so.status = 'Delivered' AND so.invoice_generated = 0
    ");
    $order->bind_param("i", $order_id);
    $order->execute();
    $order_row = $order->get_result()->fetch_assoc();
    $order->close();

    if (!$order_row) return false;

    $items_q = $conn->prepare("
        SELECT oi.product_id, oi.quantity, oi.unit_price, oi.subtotal, p.product_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $items_q->bind_param("i", $order_id);
    $items_q->execute();
    $items_result = $items_q->get_result();
    $items = [];
    $subtotal = 0;
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
        $subtotal += (float)$item['subtotal'];
    }
    $items_q->close();

    if (empty($items)) return false;

    $vat_rate = 0.12;
    $discount = 0;
    $subtotal_after_discount = $subtotal - $discount;
    $vat_amount = $subtotal_after_discount * $vat_rate;
    $total_amount = $subtotal_after_discount + $vat_amount;

    $invoice_number = generateReferenceId($conn, 'INV');
    $dr_number = generateReferenceId($conn, 'DR');
    if (!$invoice_number) return false;

    $invoice_date = date('Y-m-d');
    $conn->begin_transaction();
    try {
        $has_extended = @$conn->query("SHOW COLUMNS FROM invoices LIKE 'subtotal'");
        $has_extended = $has_extended && $has_extended->num_rows > 0;

        if ($has_extended) {
            $invoice_stmt = $conn->prepare("
                INSERT INTO invoices 
                (invoice_number, customer_id, order_id, subtotal, discount_amount, vat_rate, vat_amount, 
                 amount, invoice_date, due_date, payment_terms, status, approval_status, notes, 
                 delivery_receipt_number, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Cash', 'Pending', 'Pending', '', ?, 0)
            ");
            $invoice_stmt->bind_param("siiddddddssss",
                $invoice_number, $order_row['customer_id'], $order_id, $subtotal, $discount,
                $vat_rate, $vat_amount, $total_amount, $invoice_date, $invoice_date,
                '', $dr_number);
        } else {
            $invoice_stmt = $conn->prepare("
                INSERT INTO invoices (invoice_number, customer_id, order_id, amount, invoice_date, due_date, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, 'Pending', 0)
            ");
            $invoice_stmt->bind_param("siidds", $invoice_number, $order_row['customer_id'], $order_id, $total_amount, $invoice_date, $invoice_date);
        }
        $invoice_stmt->execute();
        $invoice_id = (int)$conn->insert_id;
        $invoice_stmt->close();

        if ($invoice_id <= 0) throw new Exception("Invoice insert failed");

        $has_invoice_items = @$conn->query("SHOW TABLES LIKE 'invoice_items'");
        if ($has_invoice_items && $has_invoice_items->num_rows > 0) {
            $item_stmt = $conn->prepare("
                INSERT INTO invoice_items (invoice_id, product_id, product_name, quantity, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($items as $item) {
                $item_stmt->bind_param("iisddd", $invoice_id, $item['product_id'], $item['product_name'],
                    $item['quantity'], $item['unit_price'], $item['subtotal']);
                $item_stmt->execute();
            }
            $item_stmt->close();
        }

        $dr_exists = @$conn->query("SHOW TABLES LIKE 'delivery_receipts'");
        if ($dr_exists && $dr_exists->num_rows > 0 && $dr_number) {
            $dr_stmt = $conn->prepare("
                INSERT INTO delivery_receipts (dr_number, order_id, invoice_id, delivery_date, created_by)
                VALUES (?, ?, ?, ?, 0)
            ");
            $dr_stmt->bind_param("siisi", $dr_number, $order_id, $invoice_id, $invoice_date);
            $dr_stmt->execute();
            $dr_stmt->close();
        }

        $has_invoice_gen = @$conn->query("SHOW COLUMNS FROM sales_orders LIKE 'invoice_generated'");
        if ($has_invoice_gen && $has_invoice_gen->num_rows > 0) {
            $update_order = $conn->prepare("UPDATE sales_orders SET invoice_id = ?, invoice_generated = 1 WHERE order_id = ?");
            $update_order->bind_param("ii", $invoice_id, $order_id);
            $update_order->execute();
            $update_order->close();
        }

        $conn->commit();
        return $invoice_id;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("LORINIMS autoGenerateInvoiceFromEvent: " . $e->getMessage());
        return false;
    }
}
