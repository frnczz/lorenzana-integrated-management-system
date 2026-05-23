<?php
/**
 * Customer portal guard. Sets $customer_id when logged in as customer.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function customerRequireLogin(): void {
    if (($_SESSION['role'] ?? '') !== 'customer' || empty($_SESSION['customer_id'])) {
        header('Location: login.php');
        exit;
    }
}

function customerId(): int {
    return (int)($_SESSION['customer_id'] ?? 0);
}
