<?php
// Add this function to your functions.php or another global file
function log_activity($user_id, $action, $details = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}
?>
