<?php
session_start();
// Prevent back navigation after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Check if customer ID is provided
if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit();
}

$customerId = $_GET['id'];

// Delete customer
$stmt = $conn->prepare("
    DELETE FROM customers 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $customerId, $userId);

if ($stmt->execute()) {
    header("Location: customers.php?success=3");
} else {
    header("Location: customers.php?error=1");
}
exit(); 