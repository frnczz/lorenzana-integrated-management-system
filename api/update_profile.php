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
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;

    // Validate required fields
    if (empty($full_name)) {
        $_SESSION['error'] = "Full name is required.";
        header("Location: ../profile.php");
        exit;
    }

    // Validate email format if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email address format.";
        header("Location: ../profile.php");
        exit;
    }

    // Validate birth date if provided
    if (!empty($birth_date) && strtotime($birth_date) > time()) {
        $_SESSION['error'] = "Birth date cannot be in the future.";
        header("Location: ../profile.php");
        exit;
    }

    // Update user profile - build query based on available columns
    // First, check which columns exist in the database
    $column_check = $conn->query("SHOW COLUMNS FROM users");
    $existing_columns = [];
    if ($column_check) {
        while ($row = $column_check->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }
    }
    
    // Build SET clause and parameters
    $set_clauses = ["full_name = ?", "email = ?"];
    $bind_params = [$full_name, $email];
    $types = "ss";
    
    // Add optional fields if they exist in the database
    if (in_array('phone_number', $existing_columns)) {
        $set_clauses[] = "phone_number = ?";
        $bind_params[] = $phone_number ? $phone_number : null;
        $types .= "s";
    }
    
    if (in_array('address', $existing_columns)) {
        $set_clauses[] = "address = ?";
        $bind_params[] = $address ? $address : null;
        $types .= "s";
    }
    
    if (in_array('birth_date', $existing_columns)) {
        $set_clauses[] = "birth_date = ?";
        $bind_params[] = $birth_date;
        $types .= "s";
    }

    if (in_array('vehicle_type', $existing_columns) && in_array($_SESSION['role'] ?? '', ['delivery', 'driver'])) {
        $vehicle_type = trim($_POST['vehicle_type'] ?? '') ?: null;
        $set_clauses[] = "vehicle_type = ?";
        $bind_params[] = $vehicle_type;
        $types .= "s";
    }
    
    // Add user_id for WHERE clause
    $bind_params[] = $user_id;
    $types .= "i";
    
    // Build final query
    $sql = "UPDATE users SET " . implode(", ", $set_clauses) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Bind parameters
        $stmt->bind_param($types, ...$bind_params);
        
        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['full_name'] = $full_name;
            if (!empty($email)) {
                $_SESSION['email'] = $email;
            }
            
            $_SESSION['success'] = "Profile updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Error preparing update statement: " . $conn->error;
    }

    header("Location: ../profile.php");
    exit;
} else {
    header("Location: ../profile.php");
    exit;
}
?>
