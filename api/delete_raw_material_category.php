<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'warehouse')) {
    header("Location: ../login.php");
    exit;
}

include "../includes/functions.php";
include "../db_connect.php";

$category = $_POST['category'] ?? null;

if (!$category) {
    setMessage('error', 'Category name is required.');
    header("Location: ../inventory_raw_materials.php");
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Move all materials in this category to Uncategorized
    $stmt = $conn->prepare("UPDATE raw_materials SET category = 'Uncategorized' WHERE category = ?");
    $stmt->bind_param("s", $category);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update materials: " . $conn->error);
    }
    
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    setMessage('success', 'Category "' . htmlspecialchars($category) . '" has been deleted. All materials moved to Uncategorized.');
    header("Location: ../inventory_raw_materials.php");
    exit;
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    setMessage('error', 'Error deleting category: ' . $e->getMessage());
    header("Location: ../inventory_raw_materials.php");
    exit;
}
?>
