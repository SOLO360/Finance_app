<?php
require_once 'connection.php';

// Drop the table if it exists
$sql = "DROP TABLE IF EXISTS price_list";

if ($conn->query($sql) === TRUE) {
    echo "<div style='color: green;'>Table price_list dropped successfully</div>";
} else {
    echo "<div style='color: red;'>Error dropping table: " . $conn->error . "</div>";
}

// Add a link to create the table
echo "<div style='margin-top: 20px;'><a href='create_price_list_table.php' style='color: blue; text-decoration: underline;'>Create Price List Table</a></div>";

$conn->close();
?> 