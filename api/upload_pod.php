<?php
session_start();
include "../db_connect.php";

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'delivery' && $_SESSION['role'] != 'driver')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pod_file'])) {
    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    
    if ($assignment_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid assignment ID']);
        exit;
    }
    
    // Verify assignment belongs to this driver
    $verify_stmt = $conn->prepare("SELECT assignment_id FROM delivery_assignments WHERE assignment_id = ? AND driver_id = ?");
    $verify_stmt->bind_param("ii", $assignment_id, $_SESSION['user_id']);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $verify_stmt->close();
    
    if ($verify_result->num_rows == 0) {
        echo json_encode(['success' => false, 'error' => 'Assignment not found or unauthorized']);
        exit;
    }
    
    $file = $_FILES['pod_file'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Validate file
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.']);
        exit;
    }
    
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'error' => 'File size too large. Maximum 5MB allowed.']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = '../uploads/pod/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'POD_' . $assignment_id . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update database
        $update_stmt = $conn->prepare("UPDATE delivery_assignments SET proof_of_delivery = ? WHERE assignment_id = ?");
        $relative_path = 'uploads/pod/' . $filename;
        $update_stmt->bind_param("si", $relative_path, $assignment_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'file_path' => $relative_path]);
        } else {
            unlink($filepath); // Delete file if DB update fails
            echo json_encode(['success' => false, 'error' => 'Database update failed: ' . $update_stmt->error]);
        }
        $update_stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'File upload failed']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
