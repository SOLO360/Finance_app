<?php
session_start();
require_once 'connection.php';

// Set timezone
date_default_timezone_set('UTC');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    
    if (!empty($username)) {
        // Check if username exists in database
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE username = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            $message = "Database error. Please try again.";
            $messageType = "error";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                // Generate a unique token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                error_log("Generated token: " . $token);
                error_log("Expiry time: " . $expiry);
                error_log("Current server time: " . date('Y-m-d H:i:s'));
                
                // Store token in database
                $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE username = ?");
                if (!$stmt) {
                    error_log("Prepare failed: " . $conn->error);
                    $message = "Database error. Please try again.";
                    $messageType = "error";
                } else {
                    $stmt->bind_param("sss", $token, $expiry, $username);
                    
                    if ($stmt->execute()) {
                        // Verify the token was stored
                        $verifyStmt = $conn->prepare("SELECT reset_token, reset_token_expiry FROM users WHERE username = ?");
                        $verifyStmt->bind_param("s", $username);
                        $verifyStmt->execute();
                        $verifyResult = $verifyStmt->get_result();
                        $storedData = $verifyResult->fetch_assoc();
                        
                        error_log("Stored token: " . $storedData['reset_token']);
                        error_log("Stored expiry: " . $storedData['reset_token_expiry']);
                        
                        // Create reset link
                        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/Finance_app/reset_password.php?token=" . $token;
                        
                        // For development, show the reset link and debug info
                        $debugInfo = "<br><br>Debug Information:<br>";
                        $debugInfo .= "Generated Token: " . $token . "<br>";
                        $debugInfo .= "Current Server Time: " . date('Y-m-d H:i:s') . "<br>";
                        $debugInfo .= "Expiry Time: " . $expiry . "<br>";
                        $debugInfo .= "Stored Token: " . $storedData['reset_token'] . "<br>";
                        $debugInfo .= "Stored Expiry: " . $storedData['reset_token_expiry'] . "<br>";
                        
                        $message = "Password reset link: <a href='" . $resetLink . "' class='text-blue-600 hover:underline'>Click here to reset your password</a>" . $debugInfo;
                        $messageType = "success";
                    } else {
                        error_log("Error storing token: " . $stmt->error);
                        $message = "Error generating reset link. Please try again.";
                        $messageType = "error";
                    }
                }
            } else {
                error_log("No user found with username: " . $username);
                $message = "No account found with that username.";
                $messageType = "error";
            }
        }
    } else {
        $message = "Please enter your username.";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Finance Tracker</title>
    <link href="output.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <img class="mx-auto h-12 w-auto" src="img/horizontal_logo.png" alt="Finance Tracker">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Forgot your password?
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Enter your username and we'll generate a password reset link.
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
                                <?php echo $messageType === 'success' ? $message : htmlspecialchars($message); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="forgot_password.php" method="POST">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">
                        Username
                    </label>
                    <div class="mt-1">
                        <input id="username" name="username" type="text" required 
                            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Enter your username">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Generate Reset Link
                    </button>
                </div>
            </form>

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