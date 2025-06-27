<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";  // Add your MySQL password
$dbname = "finance_tracker";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add this function to your functions.php or another global file
function log_activity($user_id, $action, $details = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}


?>