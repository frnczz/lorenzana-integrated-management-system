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
    $action = $_POST['action'] ?? 'create';
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $employee_number = trim($_POST['employee_number'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $hire_date = !empty($_POST['hire_date']) ? $_POST['hire_date'] : null;
    $salary = floatval($_POST['salary'] ?? 0);
    $status = trim($_POST['status'] ?? 'Active');
    $user_id = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;

    // Validate required fields
    if (empty($first_name) || empty($last_name)) {
        $_SESSION['error'] = "First name and last name are required.";
        header("Location: ../payroll_employees.php");
        exit;
    }
    if ($action === 'update' && empty($employee_number)) {
        $_SESSION['error'] = "Employee number is required when updating.";
        header("Location: ../payroll_employees.php");
        exit;
    }

    if ($action === 'create') {
        // Auto-generate employee number
        $employee_number = generateReferenceId($conn, 'EMP');
        if (!$employee_number) {
            $_SESSION['error'] = "Could not generate employee number. Please try again.";
            header("Location: ../payroll_employees.php");
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO employees (employee_number, first_name, last_name, middle_name, position, department, hire_date, salary, status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssdsi", $employee_number, $first_name, $last_name, $middle_name, $position, $department, $hire_date, $salary, $status, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Employee created successfully!";
        } else {
            $_SESSION['error'] = "Error creating employee: " . $stmt->error;
        }
        $stmt->close();
    } elseif ($action === 'update') {
        if ($employee_id <= 0) {
            $_SESSION['error'] = "Invalid employee ID.";
            header("Location: ../payroll_employees.php");
            exit;
        }

        $stmt = $conn->prepare("UPDATE employees SET employee_number = ?, first_name = ?, last_name = ?, middle_name = ?, position = ?, department = ?, hire_date = ?, salary = ?, status = ?, user_id = ? WHERE employee_id = ?");
        $stmt->bind_param("ssssssssdii", $employee_number, $first_name, $last_name, $middle_name, $position, $department, $hire_date, $salary, $status, $user_id, $employee_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Employee updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating employee: " . $stmt->error;
        }
        $stmt->close();
    }

    header("Location: ../payroll_employees.php");
    exit;
} else {
    header("Location: ../payroll_employees.php");
    exit;
}
?>
