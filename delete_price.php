<?php
include_once('connection.php'); // Include your database connection file

// Check if the form is submitted and ID is provided
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['price_id'])) {
    $price_id = $_POST['price_id'];
    
    // Delete the item from the database
    $delete_query = "DELETE FROM price_list WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $price_id);
    
    if ($delete_stmt->execute()) {
        header("Location: price_list.php"); // Redirect to the price list after deletion
        exit();
    } else {
        echo "Error deleting item: " . $conn->error;
    }
} else {
    header("Location: price_list.php"); // Redirect back to the price list if accessed directly
    exit();
}
?>
