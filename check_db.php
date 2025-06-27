<?php
require_once 'connection.php';

// Check if columns exist
$checkColumns = "SHOW COLUMNS FROM users LIKE 'reset_token'";
$result = $conn->query($checkColumns);

if ($result->num_rows == 0) {
    // Add the columns if they don't exist
    $alterTable = "ALTER TABLE users 
                   ADD COLUMN reset_token VARCHAR(64) NULL,
                   ADD COLUMN reset_token_expiry DATETIME NULL";
    
    if ($conn->query($alterTable)) {
        echo "Successfully added reset_token and reset_token_expiry columns to users table.<br>";
    } else {
        echo "Error adding columns: " . $conn->error . "<br>";
    }
} else {
    echo "Reset token columns already exist.<br>";
}

// Show current table structure
$showTable = "DESCRIBE users";
$result = $conn->query($showTable);

echo "<br>Current table structure:<br>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?> 