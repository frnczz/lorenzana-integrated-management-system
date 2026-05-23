<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting' && $_SESSION['role'] != 'sales')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $vat_rate = floatval($_POST['vat_rate'] ?? 12); // Default 12% VAT (store as percentage e.g. 12)
    $discount_amount = floatval($_POST['discount_amount'] ?? 0);
    $payment_terms = trim($_POST['payment_terms'] ?? 'Cash');
    $due_days = intval($_POST['due_days'] ?? 0); // For credit terms
    $notes = trim($_POST['notes'] ?? '');
    $created_by = $_SESSION['user_id'];
    
    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
        exit;
    }
    
    // Get order details
    $order_query = $conn->prepare("
        SELECT so.order_id, so.order_number, so.customer_id, so.order_date, so.total_amount,
               c.customer_name, c.contact_number, c.address
        FROM sales_orders so
        LEFT JOIN customers c ON so.customer_id = c.customer_id
        WHERE so.order_id = ? AND so.invoice_generated = 0
    ");
    $order_query->bind_param("i", $order_id);
    $order_query->execute();
    $order = $order_query->get_result()->fetch_assoc();
    $order_query->close();
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found or invoice already generated']);
        exit;
    }
    
    // Get order items
    $items_query = $conn->prepare("
        SELECT oi.product_id, oi.quantity, oi.unit_price, oi.subtotal,
               p.product_name, p.unit
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $items_query->bind_param("i", $order_id);
    $items_query->execute();
    $items_result = $items_query->get_result();
    $items = [];
    $subtotal = 0;
    
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
        $subtotal += (float)$item['subtotal'];
    }
    $items_query->close();
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'error' => 'No items found in order']);
        exit;
    }
    
    // Calculate amounts
    $discount = (float)$discount_amount;
    $subtotal_after_discount = $subtotal - $discount;
    $vat_amount = $subtotal_after_discount * ($vat_rate / 100);
    $total_amount = $subtotal_after_discount + $vat_amount;
    
    // Calculate due date
    $invoice_date = date('Y-m-d');
    $due_date = $invoice_date;
    if ($payment_terms === 'Credit' && $due_days > 0) {
        $due_date = date('Y-m-d', strtotime("+$due_days days"));
    }
    
    // Generate invoice number
    $invoice_number = generateReferenceId($conn, 'INV');
    if (!$invoice_number) {
        echo json_encode(['success' => false, 'error' => 'Could not generate invoice number']);
        exit;
    }
    
    // Generate delivery receipt number
    $dr_number = generateReferenceId($conn, 'DR');
    
    $conn->begin_transaction();
    try {
        // Insert invoice
        $invoice_stmt = $conn->prepare("
            INSERT INTO invoices 
            (invoice_number, customer_id, order_id, subtotal, discount_amount, vat_rate, vat_amount, 
             amount, invoice_date, due_date, payment_terms, status, approval_status, notes, 
             delivery_receipt_number, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending', ?, ?, ?)
        ");
        $invoice_stmt->bind_param("siidddddsssssi", 
            $invoice_number, $order['customer_id'], $order_id, $subtotal, $discount, 
            $vat_rate, $vat_amount, $total_amount, $invoice_date, $due_date, 
            $payment_terms, $notes, $dr_number, $created_by);
        $invoice_stmt->execute();
        $invoice_id = $conn->insert_id;
        $invoice_stmt->close();
        
        // Insert invoice items
        $item_stmt = $conn->prepare("
            INSERT INTO invoice_items (invoice_id, product_id, product_name, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($items as $item) {
            $item_stmt->bind_param("iisddd", 
                $invoice_id, $item['product_id'], $item['product_name'], 
                $item['quantity'], $item['unit_price'], $item['subtotal']);
            $item_stmt->execute();
        }
        $item_stmt->close();
        
        // Create delivery receipt
        $dr_stmt = $conn->prepare("
            INSERT INTO delivery_receipts (dr_number, order_id, invoice_id, delivery_date, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $delivery_date = date('Y-m-d');
        $dr_stmt->bind_param("siisi", $dr_number, $order_id, $invoice_id, $delivery_date, $created_by);
        $dr_stmt->execute();
        $dr_stmt->close();
        
        // Update order to mark invoice as generated
        $update_order = $conn->prepare("UPDATE sales_orders SET invoice_id = ?, invoice_generated = 1 WHERE order_id = ?");
        $update_order->bind_param("ii", $invoice_id, $order_id);
        $update_order->execute();
        $update_order->close();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'invoice_id' => $invoice_id,
            'invoice_number' => $invoice_number,
            'dr_number' => $dr_number,
            'message' => "Invoice generated successfully! Invoice #$invoice_number"
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
