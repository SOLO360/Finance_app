<?php
session_start();
// Prevent back navigation after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once 'connection.php';

// Set timezone
date_default_timezone_set('UTC');

$message = '';
$messageType = '';
$validToken = false;
$token = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Debug information
    error_log("Reset token received: " . $token);
    error_log("Current server time: " . date('Y-m-d H:i:s'));
    
    // Verify token exists and hasn't expired
    $stmt = $conn->prepare("SELECT id, username, reset_token_expiry FROM users WHERE reset_token = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $message = "Database error. Please try again.";
        $messageType = "error";
    } else {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            error_log("Found user: " . $user['username']);
            error_log("Token expiry: " . $user['reset_token_expiry']);
            
            // Check if token has expired
            $currentTime = new DateTime();
            $expiryTime = new DateTime($user['reset_token_expiry']);
            
            error_log("Current time: " . $currentTime->format('Y-m-d H:i:s'));
            error_log("Expiry time: " . $expiryTime->format('Y-m-d H:i:s'));
            
            if ($currentTime < $expiryTime) {
                error_log("Token is valid");
                $validToken = true;
            } else {
                error_log("Token has expired");
                $message = "Reset link has expired. Please request a new password reset.";
                $messageType = "error";
            }
        } else {
            error_log("Invalid token");
            $message = "Invalid reset link. Please request a new password reset.";
            $messageType = "error";
        }
    }
} else {
    error_log("No token provided in URL");
    $message = "No reset token provided. Please request a new password reset.";
    $messageType = "error";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $messageType = "error";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "error";
    } else {
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update the password and clear the reset token
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            $message = "Database error. Please try again.";
            $messageType = "error";
        } else {
            $stmt->bind_param("ss", $hashedPassword, $token);
            
            if ($stmt->execute()) {
                error_log("Password successfully reset for token: " . $token);
                $message = "Password has been reset successfully. You can now login with your new password.";
                $messageType = "success";
                $validToken = false; // Prevent further attempts
            } else {
                error_log("Error resetting password: " . $stmt->error);
                $message = "Error resetting password. Please try again.";
                $messageType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Finance Tracker</title>
    <link href="output.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <img class="mx-auto h-12 w-auto" src="img/horizontal_logo.png" alt="Finance Tracker">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Reset your password
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Enter your new password below
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <?php if ($message): ?>
                <div class="rounded-md p-4 mb-4 <?php echo $messageType === 'success' ? 'bg-green-50' : 'bg-red-50'; ?>">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <?php if ($messageType === 'success'): ?>
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            <?php else: ?>
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium <?php echo $messageType === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <form class="space-y-6" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            New password
                        </label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" required 
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Enter new password (minimum 8 characters)">
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                            Confirm new password
                        </label>
                        <div class="mt-1">
                            <input id="confirm_password" name="confirm_password" type="password" required 
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Confirm new password">
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Reset password
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">
                            Or
                        </span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="login.php" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Back to login
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 