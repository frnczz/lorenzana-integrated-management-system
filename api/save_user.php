<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

// Check authentication - only admin can manage users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeEnum($_POST['action'] ?? 'create', ['create','update','delete'], 'create');
    $user_id = sanitizeInt($_POST['user_id'] ?? 0, 0);
    $username = sanitizeString($_POST['username'] ?? '', 50);
    $password = $_POST['password'] ?? '';
    $role = sanitizeEnum($_POST['role'] ?? '', ['admin','production','warehouse','qc','accounting','sales','delivery','procurement'], '');
    $full_name = sanitizeString($_POST['full_name'] ?? '', 100);
    $email = sanitizeEmail($_POST['email'] ?? '');

    // Validate required fields
    if (empty($username) || empty($role)) {
        $_SESSION['error'] = "Username and role are required.";
        header("Location: ../users.php");
        exit;
    }

    if ($action === 'create') {
        // Check if username already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $_SESSION['error'] = "Username already exists.";
            $check->close();
            header("Location: ../users.php");
            exit;
        }
        $check->close();

        // Create new user
        if (empty($password)) {
            $_SESSION['error'] = "Password is required for new users.";
            header("Location: ../users.php");
            exit;
        }

        $user_code = generateReferenceId($conn, 'USR');
        if (!$user_code) {
            $_SESSION['error'] = "Could not generate user code. Please try again.";
            header("Location: ../users.php");
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO users (user_code, username, password, role, full_name, email) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $user_code, $username, $password, $role, $full_name, $email);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User created successfully! Code: " . $user_code;
        } else {
            $_SESSION['error'] = "Error creating user: " . $stmt->error;
        }
        $stmt->close();
    } elseif ($action === 'update') {
        if ($user_id <= 0) {
            $_SESSION['error'] = "Invalid user ID.";
            header("Location: ../users.php");
            exit;
        }

        // Update user
        if (!empty($password)) {
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ?, full_name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $username, $password, $role, $full_name, $email, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, full_name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $username, $role, $full_name, $email, $user_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating user: " . $stmt->error;
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        if ($user_id <= 0) {
            $_SESSION['error'] = "Invalid user ID.";
            header("Location: ../users.php");
            exit;
        }

        // Prevent deleting own account
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error'] = "You cannot delete your own account.";
            header("Location: ../users.php");
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting user: " . $stmt->error;
        }
        $stmt->close();
    }

    header("Location: ../users.php");
    exit;
} else {
    header("Location: ../users.php");
    exit;
}
?>
