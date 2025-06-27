<?php
require_once 'connection.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create price_list table
$sql = "CREATE TABLE IF NOT EXISTS price_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('DESIGN', 'PRINTING', 'CONSULTATION') NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "<div style='color: green;'>Table price_list created successfully</div>";
} else {
    echo "<div style='color: red;'>Error creating table: " . $conn->error . "</div>";
    exit();
}

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'price_list'");
if ($table_check->num_rows == 0) {
    echo "<div style='color: red;'>Table was not created successfully</div>";
    exit();
}

// Check if table has the correct structure
$columns = $conn->query("SHOW COLUMNS FROM price_list");
$has_category = false;
while($column = $columns->fetch_assoc()) {
    if ($column['Field'] === 'category') {
        $has_category = true;
        break;
    }
}

if (!$has_category) {
    echo "<div style='color: red;'>Table structure is incorrect - missing 'category' column</div>";
    exit();
}

// Insert sample data
$sample_data = [
    ['DESIGN', 'Logo Design', 'Professional logo design service', 150.00],
    ['DESIGN', 'Business Card Design', 'Custom business card design', 50.00],
    ['PRINTING', 'Business Cards (100)', '100 business cards printing', 30.00],
    ['PRINTING', 'Brochures (100)', '100 brochures printing', 150.00],
    ['CONSULTATION', 'Brand Strategy', 'Brand strategy consultation', 200.00],
    ['CONSULTATION', 'Marketing Consultation', 'Marketing strategy consultation', 150.00]
];

$stmt = $conn->prepare("INSERT INTO price_list (category, item_name, description, price) VALUES (?, ?, ?, ?)");

if (!$stmt) {
    echo "<div style='color: red;'>Error preparing statement: " . $conn->error . "</div>";
    exit();
}

$success_count = 0;
foreach ($sample_data as $data) {
    $stmt->bind_param("sssd", $data[0], $data[1], $data[2], $data[3]);
    if ($stmt->execute()) {
        $success_count++;
    } else {
        echo "<div style='color: red;'>Error inserting data: " . $stmt->error . "</div>";
    }
}

echo "<div style='color: green;'>Successfully inserted $success_count sample records</div>";

// Verify data was inserted
$count = $conn->query("SELECT COUNT(*) as count FROM price_list")->fetch_assoc()['count'];
echo "<div style='color: green;'>Total records in price_list table: $count</div>";

// Add a link to go back to price list
echo "<div style='margin-top: 20px;'><a href='price_list.php' style='color: blue; text-decoration: underline;'>Go back to Price List</a></div>";

$conn->close();
?> 