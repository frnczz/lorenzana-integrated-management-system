<?php
header('Content-Type: application/json');
ob_start();
@include __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method.']);
    exit;
}

$col = @$conn->query("SHOW COLUMNS FROM customers LIKE 'portal_username'");
if (!$col || $col->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Customer portal is not set up yet. Run the database migration migrations/004_customer_portal.sql.']);
    exit;
}

$customer_name = sanitizeString($_POST['customer_name'] ?? '', 100);
$contact_number = sanitizeString($_POST['contact_number'] ?? '', 20);
$address = sanitizeString($_POST['address'] ?? '', 500);
$email = sanitizeString($_POST['email'] ?? '', 100);
$portal_username = trim((string)($_POST['portal_username'] ?? ''));
$portal_password = (string)($_POST['portal_password'] ?? '');

if ($customer_name === '') {
    echo json_encode(['success' => false, 'message' => 'Customer name is required.']);
    exit;
}
if (strlen($portal_username) < 3) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters.']);
    exit;
}
if (strlen($portal_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}

$u = $conn->prepare('SELECT customer_id FROM customers WHERE portal_username = ? LIMIT 1');
$u->bind_param('s', $portal_username);
$u->execute();
$u->store_result();
if ($u->num_rows > 0) {
    $u->close();
    echo json_encode(['success' => false, 'message' => 'That username is already taken.']);
    exit;
}
$u->close();

$hash = password_hash($portal_password, PASSWORD_DEFAULT);
$contact_person = $customer_name;

$ins = $conn->prepare('INSERT INTO customers (customer_name, contact_person, contact_number, email, address, portal_username, portal_password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)');
$ins->bind_param('sssssss', $customer_name, $contact_person, $contact_number, $email, $address, $portal_username, $hash);

if ($ins->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Account created. You can sign in on the login page.',
        'customer_id' => (int)$conn->insert_id,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not create account: ' . $ins->error]);
}
$ins->close();
