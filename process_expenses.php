<!-- process_expenses.php -->
<?php
include_once('connection.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get form data
$date = $_POST['date'];
$description = $_POST['description'];
$category = $_POST['category'];
$quantity = $_POST['quantity'];
$amount = $_POST['amount'];
$vendor = $_POST['vendor'];
$staff = $_POST['staff'];

// Insert into expenses table
$sql = "INSERT INTO expenses (expense_date, description, category, quantity, amount, vendor, staff)
        VALUES ('$date', '$description', '$category', $quantity, $amount, '$vendor', '$staff')";

if ($conn->query($sql) === TRUE) {
    echo "New expense record added successfully.";
    header("refresh: 3, url=expenses_form.php");
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}


$conn->close();
?>
