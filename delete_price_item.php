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

// Check if item_id is provided
if (!isset($_POST['item_id']) || !is_numeric($_POST['item_id'])) {
    $_SESSION['error'] = "Invalid item ID.";
    header("Location: price_list.php");
    exit();
}

$item_id = (int)$_POST['item_id'];

try {
    // Delete the price item
    $stmt = $conn->prepare("DELETE FROM price_list WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Price item deleted successfully.";
    } else {
        throw new Exception("Error deleting price item: " . $stmt->error);
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: price_list.php");
exit(); 