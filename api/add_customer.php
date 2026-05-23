<?php
ob_start();
header('Content-Type: application/json');
session_start();

@include '../db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
ob_end_clean();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'sales')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$customer_name = sanitizeString($_POST['customer_name'] ?? '', 100);
$contact_person = sanitizeString($_POST['contact_person'] ?? '', 100);
$contact_number = sanitizeString($_POST['contact_number'] ?? '', 20);
$email = sanitizeString($_POST['email'] ?? '', 100);
$address = sanitizeString($_POST['address'] ?? '', 500);
$portal_username = trim((string)($_POST['portal_username'] ?? ''));
$portal_password = (string)($_POST['portal_password'] ?? '');

if ($customer_name === '') {
    echo json_encode(['success' => false, 'message' => 'Customer name is required.']);
    exit;
}

$check = $conn->prepare('SELECT customer_id FROM customers WHERE customer_name = ?');
if (!$check) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$check->bind_param('s', $customer_name);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Customer already exists.']);
    $check->close();
    exit;
}
$check->close();

$has_portal = (@$conn->query("SHOW COLUMNS FROM customers LIKE 'portal_username'")->num_rows > 0);
$portal_hash = null;
if ($has_portal && $portal_username !== '') {
    if (strlen($portal_username) < 3) {
        echo json_encode(['success' => false, 'message' => 'Portal username must be at least 3 characters.']);
        exit;
    }
    if (strlen($portal_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Portal password must be at least 6 characters if username is set.']);
        exit;
    }
    $u = $conn->prepare('SELECT customer_id FROM customers WHERE portal_username = ? LIMIT 1');
    $u->bind_param('s', $portal_username);
    $u->execute();
    $u->store_result();
    if ($u->num_rows > 0) {
        $u->close();
        echo json_encode(['success' => false, 'message' => 'That portal username is already taken.']);
        exit;
    }
    $u->close();
    $portal_hash = password_hash($portal_password, PASSWORD_DEFAULT);
}

if ($contact_person === '') {
    $contact_person = $customer_name;
}

if ($has_portal && $portal_hash !== null) {
    $insert = $conn->prepare('INSERT INTO customers (customer_name, contact_person, contact_number, email, address, portal_username, portal_password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $insert->bind_param('sssssss', $customer_name, $contact_person, $contact_number, $email, $address, $portal_username, $portal_hash);
} else {
    $insert = $conn->prepare('INSERT INTO customers (customer_name, contact_person, contact_number, email, address) VALUES (?, ?, ?, ?, ?)');
    $insert->bind_param('sssss', $customer_name, $contact_person, $contact_number, $email, $address);
}

if ($insert->execute()) {
    $new_id = $conn->insert_id;
    echo json_encode([
        'success' => true,
        'customer_id' => $new_id,
        'customer_name' => $customer_name,
        'contact_person' => $contact_person,
        'contact_number' => $contact_number,
        'email' => $email,
        'address' => $address,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $insert->error]);
}
$insert->close();
