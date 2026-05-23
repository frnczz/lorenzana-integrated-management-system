<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/inventory_service.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','sales'])) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
$created_by = (int)$_SESSION['user_id'];

if($order_id <= 0){
    echo json_encode(['success'=>false,'message'=>'Invalid order ID']);
    exit;
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("SELECT order_number, fulfillment_type, status FROM sales_orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $isPickupOrder = false;
    if ($order) {
        if ($order['fulfillment_type'] === 'Customer Pickup') {
            $isPickupOrder = true;
        } elseif (strpos($order['order_number'] ?? '', 'PUP-') === 0) {
            $isPickupOrder = true;
        }
    }

    if (!$order || !$isPickupOrder || $order['status'] === 'Picked Up') {
        throw new Exception('Order cannot be marked as Picked Up.');
    }

    if ($order['fulfillment_type'] !== 'Customer Pickup') {
        $updType = $conn->prepare("UPDATE sales_orders SET fulfillment_type = 'Customer Pickup' WHERE order_id = ?");
        $updType->bind_param("i", $order_id);
        $updType->execute();
        $updType->close();
    }

    // Get reserved items
    $items = [];
    $q = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ? AND reserved = 1");
    $q->bind_param("i", $order_id);
    $q->execute();
    $result = $q->get_result();
    while($row = $result->fetch_assoc()){
        $items[] = ['product_id'=>(int)$row['product_id'], 'quantity'=>(float)$row['quantity']];
    }
    $q->close();

    if(!empty($items)){
        processInventoryEvent($conn,'SALES_FULFILL',['items'=>$items,'order_id'=>$order_id,'created_by'=>$created_by]);
        $clear = $conn->prepare("UPDATE order_items SET reserved=0 WHERE order_id=?");
        $clear->bind_param("i",$order_id);
        $clear->execute();
        $clear->close();
    }

    $upd = $conn->prepare("UPDATE sales_orders SET status='Picked Up' WHERE order_id=?");
    $upd->bind_param("i",$order_id);
    $upd->execute();
    $upd->close();

    $conn->commit();

    // Return updated fields so the UI can update without a full page reload
    $stmt = $conn->prepare("SELECT invoice_generated FROM sales_orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $updated = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode(['success'=>true, 'invoice_generated' => $updated['invoice_generated'] ?? 0]);
}catch(Exception $e){
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}