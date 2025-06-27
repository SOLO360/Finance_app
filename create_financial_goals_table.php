<?php
require_once 'connection.php';

// Create financial_goals table
$sql = "CREATE TABLE IF NOT EXISTS financial_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    target_amount DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) DEFAULT 0.00,
    progress INT DEFAULT 0,
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table financial_goals created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}  

// Insert some sample goals for testing
$userId = 1; // Replace with your actual user ID
$sampleGoals = [
    [
        'name' => 'Emergency Fund',
        'target_amount' => 10000.00,
        'current_amount' => 7500.00,
        'progress' => 75,
        'deadline' => '2024-12-31'
    ],
    [
        'name' => 'Vacation Fund',
        'target_amount' => 5000.00,
        'current_amount' => 2250.00,
        'progress' => 45,
        'deadline' => '2024-08-31'
    ],
    [
        'name' => 'New Car',
        'target_amount' => 20000.00,
        'current_amount' => 6000.00,
        'progress' => 30,
        'deadline' => '2025-06-30'
    ]
];

$stmt = $conn->prepare("INSERT INTO financial_goals (user_id, name, target_amount, current_amount, progress, deadline) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($sampleGoals as $goal) {
    $stmt->bind_param("isddss", 
        $userId,
        $goal['name'],
        $goal['target_amount'],
        $goal['current_amount'],
        $goal['progress'],
        $goal['deadline']
    );
    $stmt->execute();
}

echo "<br>Sample goals inserted successfully";

$conn->close();
?> 