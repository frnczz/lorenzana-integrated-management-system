<?php
session_start();
include 'db_connect.php';

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header('Location: login.php?error=' . urlencode('Please enter username and password.'));
    exit;
}

$stmt = $conn->prepare('SELECT id, username, password, role, full_name, email FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $row = $res->fetch_assoc()) {
    $stmt->close();
    if (!hash_equals((string)$row['password'], $password)) {
        header('Location: login.php?error=' . urlencode('Invalid username or password.'));
        exit;
    }

    $_SESSION['user_id'] = (int)$row['id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['role'] = $row['role'];
    $_SESSION['full_name'] = $row['full_name'] ?? $row['username'];
    $_SESSION['email'] = $row['email'] ?? '';
    unset($_SESSION['customer_id']);

    $last_login_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'last_login'");
    if ($last_login_check && mysqli_num_rows($last_login_check) > 0) {
        mysqli_query($conn, 'UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ' . (int)$row['id']);
    }

    switch ($row['role']) {
        case 'admin':
            header('Location: admin_dashboard.php');
            exit;
        case 'production':
            header('Location: production_dashboard.php');
            exit;
        case 'warehouse':
            header('Location: warehouse_dashboard.php');
            exit;
        case 'qc':
            header('Location: quality_dashboard.php');
            exit;
        case 'accounting':
            header('Location: accounting_dashboard.php');
            exit;
        case 'sales':
            header('Location: sales_dashboard.php');
            exit;
        case 'delivery':
            header('Location: driver_gps.php');
            exit;
        case 'procurement':
            header('Location: procurement_dashboard.php');
            exit;
        default:
            header('Location: dashboard.php');
            exit;
    }
}
$stmt->close();

$pc = @$conn->query("SHOW COLUMNS FROM customers LIKE 'portal_username'");
if ($pc && $pc->num_rows > 0) {
    $cs = $conn->prepare('SELECT customer_id, customer_name, portal_password_hash FROM customers WHERE portal_username = ? LIMIT 1');
    $cs->bind_param('s', $username);
    $cs->execute();
    $cr = $cs->get_result();
    if ($cr && $cust = $cr->fetch_assoc()) {
        $hash = $cust['portal_password_hash'] ?? '';
        if ($hash !== '' && password_verify($password, $hash)) {
            $_SESSION['user_id'] = 0;
            $_SESSION['customer_id'] = (int)$cust['customer_id'];
            $_SESSION['role'] = 'customer';
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $cust['customer_name'];
            $_SESSION['email'] = '';
            $cs->close();
            header('Location: customer_home.php');
            exit;
        }
    }
    $cs->close();
}

header('Location: login.php?error=' . urlencode('Invalid username or password.'));
exit;
