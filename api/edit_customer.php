<?php
ob_start();
header('Content-Type: application/json');
session_start();

@include '../db_connect.php';
ob_end_clean();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
$customer_name = trim((string)($_POST['customer_name'] ?? ''));
$contact_person = trim((string)($_POST['contact_person'] ?? ''));
$contact_number = trim((string)($_POST['contact_number'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$address = trim((string)($_POST['address'] ?? ''));

if ($customer_id <= 0 || $customer_name === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

if ($contact_person === '') {
    $contact_person = $customer_name;
}

$upd = $conn->prepare('UPDATE customers SET customer_name = ?, contact_person = ?, contact_number = ?, email = ?, address = ? WHERE customer_id = ?');
if (!$upd) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$upd->bind_param('sssssi', $customer_name, $contact_person, $contact_number, $email, $address, $customer_id);
if ($upd->execute()) {
    echo json_encode([
        'success' => true,
        'customer_id' => $customer_id,
        'customer_name' => $customer_name,
        'contact_person' => $contact_person,
        'contact_number' => $contact_number,
        'email' => $email,
        'address' => $address,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $upd->error]);
}
$upd->close();
