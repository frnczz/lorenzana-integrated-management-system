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
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $attendance_date = $_POST['attendance_date'] ?? date('Y-m-d');
    $time_in = !empty($_POST['time_in']) ? $_POST['time_in'] : null;
    $time_out = !empty($_POST['time_out']) ? $_POST['time_out'] : null;
    $status = $_POST['status'] ?? 'Present';
    $remarks = trim($_POST['remarks'] ?? '');

    // Validate required fields
    if ($employee_id <= 0) {
        $_SESSION['error'] = "Employee is required.";
        header("Location: ../payroll_attendance.php");
        exit;
    }

    // Calculate hours worked if both time in and time out are provided
    $hours_worked = 0;
    if ($time_in && $time_out) {
        $time1 = strtotime($time_in);
        $time2 = strtotime($time_out);
        $hours_worked = ($time2 - $time1) / 3600;
        if ($hours_worked < 0) $hours_worked = 0;
    }

    // Check if attendance already exists for this date
    $check = $conn->prepare("SELECT attendance_id FROM attendance WHERE employee_id = ? AND attendance_date = ?");
    $check->bind_param("is", $employee_id, $attendance_date);
    $check->execute();
    $existing = $check->get_result();
    
    if ($existing->num_rows > 0) {
        // Update existing
        $row = $existing->fetch_assoc();
        $stmt = $conn->prepare("UPDATE attendance SET time_in = ?, time_out = ?, hours_worked = ?, status = ?, remarks = ? WHERE attendance_id = ?");
        $stmt->bind_param("ssdssi", $time_in, $time_out, $hours_worked, $status, $remarks, $row['attendance_id']);
    } else {
        // Insert new with auto-generated reference
        $attendance_ref = generateReferenceId($conn, 'ATT');
        if (!$attendance_ref) {
            $_SESSION['error'] = "Could not generate attendance reference. Please try again.";
            header("Location: ../payroll_attendance.php");
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO attendance (attendance_ref, employee_id, attendance_date, time_in, time_out, hours_worked, status, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssdss", $attendance_ref, $employee_id, $attendance_date, $time_in, $time_out, $hours_worked, $status, $remarks);
    }
    $check->close();
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Attendance recorded successfully!";
    } else {
        $_SESSION['error'] = "Error recording attendance: " . $stmt->error;
    }
    $stmt->close();

    header("Location: ../payroll_attendance.php");
    exit;
} else {
    header("Location: ../payroll_attendance.php");
    exit;
}
?>
