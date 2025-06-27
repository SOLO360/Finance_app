<?php
include_once('connection.php');

// Get the expense ID from the URL
$id = $_GET['id'];

// Delete the expense record
$sql = "DELETE FROM expenses WHERE id = $id";
if ($conn->query($sql) === TRUE) {
    header('Location: view_expenses.php');
} else {
    echo "Error deleting record: " . $conn->error;
}
// Get current user ID from session
$user_id = $_SESSION['user_id'];  // Ensure the session has user ID stored
$action = "Deleted Expense";
$details = "Deleted expense ID: $expense_id";

// Log the action
log_activity($user_id, $action, $details);

$conn->close();
?>
