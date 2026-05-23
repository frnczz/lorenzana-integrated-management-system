<?php
$host = "localhost";      // Usually localhost
$user = "root";           // Your DB username
$password = "";           // Your DB password
$database = "lorinims_db";   // The database you just created

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
