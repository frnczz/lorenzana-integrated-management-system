<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'production')) {
    header("Location: login.php");
    exit;
}
header("Location: production_record.php");
exit;
