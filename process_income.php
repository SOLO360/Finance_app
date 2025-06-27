<?php
include_once('connection.php');

// Get form data and sanitize
$date = $_POST['date'];
$description = $conn->real_escape_string($_POST['description']);
$category = $conn->real_escape_string($_POST['category']);
$amount = $_POST['amount'];
$client = $conn->real_escape_string($_POST['client']);
$staff = $conn->real_escape_string($_POST['staff']);

// Prepare and bind
$stmt = $conn->prepare("INSERT INTO income (income_date, description, category, amount, client, staff) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssdds", $date, $description, $category, $amount, $client, $staff);

// Execute the statement
if ($stmt->execute()) {
    echo "New income record added successfully.";
    header("refresh: 3, url=income_form.php");
} else {
    echo "Error: " . $stmt->error;
}

// Get current user ID from session
$user_id = $_SESSION['user_id'];
$action = "Added Income";
$details = "Income: $description, Amount: $amount";

// Log the action
log_activity($user_id, $action, $details);

$stmt->close();
$conn->close();
?>
