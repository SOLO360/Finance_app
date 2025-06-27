<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connection.php';

// Check if connection is successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create customers table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    total_purchases DECIMAL(15,2) DEFAULT 0.00,
    loyalty_points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Customers table checked/created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Check if loyalty_points column exists
$result = $conn->query("SHOW COLUMNS FROM customers LIKE 'loyalty_points'");
if ($result->num_rows == 0) {
    // Add loyalty_points column if it doesn't exist
    $sql = "ALTER TABLE customers ADD COLUMN loyalty_points INT DEFAULT 0";
    if ($conn->query($sql) === TRUE) {
        echo "Added loyalty_points column successfully<br>";
    } else {
        echo "Error adding loyalty_points column: " . $conn->error . "<br>";
    }
}

// Check if total_purchases column exists
$result = $conn->query("SHOW COLUMNS FROM customers LIKE 'total_purchases'");
if ($result->num_rows == 0) {
    // Add total_purchases column if it doesn't exist
    $sql = "ALTER TABLE customers ADD COLUMN total_purchases DECIMAL(15,2) DEFAULT 0.00";
    if ($conn->query($sql) === TRUE) {
        echo "Added total_purchases column successfully<br>";
    } else {
        echo "Error adding total_purchases column: " . $conn->error . "<br>";
    }
}

// Check if the table exists and show its structure
$result = $conn->query("SHOW TABLES LIKE 'customers'");
if ($result->num_rows > 0) {
    echo "Customers table exists<br>";
    
    // Show table structure
    $result = $conn->query("DESCRIBE customers");
    echo "<br>Table structure:<br>";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
} else {
    echo "Customers table does not exist<br>";
}

// Modify income table to include customer_id
$sql = "ALTER TABLE income 
        ADD COLUMN IF NOT EXISTS customer_id INT,
        ADD FOREIGN KEY IF NOT EXISTS (customer_id) REFERENCES customers(id) ON DELETE SET NULL";

if ($conn->query($sql) === TRUE) {
    echo "<div style='color: green;'>Income table modified successfully</div>";
} else {
    echo "<div style='color: red;'>Error modifying income table: " . $conn->error . "</div>";
}

$conn->close();
?> 