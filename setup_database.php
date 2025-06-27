<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connection.php';

// Check if connection is successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h2>Database Setup</h2>";

// Read the SQL file
$sql = file_get_contents('setup_database.sql');

// Split the SQL file into individual queries
$queries = array_filter(array_map('trim', explode(';', $sql)));

// Execute each query
foreach ($queries as $query) {
    if (!empty($query)) {
        if ($conn->query($query)) {
            echo "Successfully executed: " . substr($query, 0, 50) . "...<br>";
        } else {
            echo "Error executing query: " . $conn->error . "<br>";
            echo "Query: " . $query . "<br><br>";
        }
    }
}

echo "<br>Database setup completed. <a href='index.php'>Go to dashboard</a>";
?> 