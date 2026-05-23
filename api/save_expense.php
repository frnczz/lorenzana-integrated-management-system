<?php
session_start();
include "../db_connect.php";
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'accounting')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? 'Other';
    $amount = floatval($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $created_by = $_SESSION['user_id'];

    // Validate required fields
    if ($amount <= 0) {
        $_SESSION['error'] = "Amount is required and must be greater than 0.";
        header("Location: ../accounting_expenses.php");
        exit;
    }

    $expense_ref = generateReferenceId($conn, 'EXP');
    if (!$expense_ref) {
        $_SESSION['error'] = "Could not generate expense reference. Please try again.";
        header("Location: ../accounting_expenses.php");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO expenses (expense_ref, category, amount, description, expense_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("ssdssi", $expense_ref, $category, $amount, $description, $expense_date, $created_by);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Expense recorded successfully! Ref: " . $expense_ref;
        } else {
            $_SESSION['error'] = "Error recording expense: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error: " . $conn->error;
    }

    header("Location: ../accounting_expenses.php");
    exit;
} else {
    header("Location: ../accounting_expenses.php");
    exit;
}
?>
