<?php
session_start();
include "../db_connect.php";

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validate required fields
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = "All password fields are required.";
        header("Location: ../profile.php");
        exit;
    }

    // Validate password length
    if (strlen($new_password) < 6) {
        $_SESSION['error'] = "New password must be at least 6 characters long.";
        header("Location: ../profile.php");
        exit;
    }

    // Validate password confirmation
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New password and confirm password do not match.";
        header("Location: ../profile.php");
        exit;
    }

    // Verify current password
    $check_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $user = $result->fetch_assoc();
    $check_stmt->close();

    if (!$user || $user['password'] !== $current_password) {
        $_SESSION['error'] = "Current password is incorrect.";
        header("Location: ../profile.php");
        exit;
    }

    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("si", $new_password, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Password changed successfully!";
    } else {
        $_SESSION['error'] = "Error changing password: " . $stmt->error;
    }
    $stmt->close();

    header("Location: ../profile.php");
    exit;
} else {
    header("Location: ../profile.php");
    exit;
}
?>
