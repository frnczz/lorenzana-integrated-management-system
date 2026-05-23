<?php
session_start();
include "../db_connect.php";

// Check authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse')) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $material_id = intval($_POST['material_id'] ?? 0);
    
    if ($material_id <= 0) {
        $_SESSION['error'] = "Invalid material ID.";
        header("Location: ../inventory_raw_materials.php");
        exit;
    }
    
    // Check if material is used in production batches
    $check_batches = $conn->query("
        SELECT COUNT(*) as count 
        FROM batch_details bd
        INNER JOIN production_batches pb ON bd.batch_id = pb.batch_id
        WHERE bd.material_id = $material_id
    ");
    $batch_count = $check_batches->fetch_assoc()['count'];
    
    if ($batch_count > 0) {
        $_SESSION['error'] = "Cannot delete material. It is used in $batch_count production batch(es).";
        header("Location: ../inventory_raw_materials.php");
        exit;
    }
    
    // Get material name for confirmation message
    $material_result = $conn->query("SELECT material_name FROM raw_materials WHERE material_id = $material_id");
    $material_name = $material_result ? $material_result->fetch_assoc()['material_name'] : 'Unknown';
    
    // Delete material
    $stmt = $conn->prepare("DELETE FROM raw_materials WHERE material_id = ?");
    $stmt->bind_param("i", $material_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Raw material '$material_name' deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting material: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: ../inventory_raw_materials.php");
    exit;
} else {
    header("Location: ../inventory_raw_materials.php");
    exit;
}
?>
