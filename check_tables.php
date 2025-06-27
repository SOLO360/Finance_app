<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connection.php';

// Check if connection is successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h2>Database Connection Status</h2>";
echo "Connected successfully to database<br><br>";

// Check required tables
$required_tables = ['users', 'transactions', 'categories'];

foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    echo "<h3>Checking table: $table</h3>";
    
    if ($result->num_rows > 0) {
        echo "Table '$table' exists<br>";
        
        // Show table structure
        $columns = $conn->query("SHOW COLUMNS FROM $table");
        echo "<pre>Table structure:\n";
        while ($column = $columns->fetch_assoc()) {
            print_r($column);
        }
        echo "</pre>";
    } else {
        echo "Table '$table' does not exist!<br>";
    }
    echo "<br>";
}

// Check if categories table has data
$result = $conn->query("SELECT COUNT(*) as count FROM categories");
$count = $result->fetch_assoc()['count'];
echo "<h3>Categories Data</h3>";
echo "Number of categories: $count<br>";

if ($count == 0) {
    echo "Warning: No categories found. You need to add categories for the application to work properly.<br>";
}

// Check if there are any users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$count = $result->fetch_assoc()['count'];
echo "<h3>Users Data</h3>";
echo "Number of users: $count<br>";

// Check if there are any transactions
$result = $conn->query("SELECT COUNT(*) as count FROM transactions");
$count = $result->fetch_assoc()['count'];
echo "<h3>Transactions Data</h3>";
echo "Number of transactions: $count<br>";
?> 