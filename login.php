<?php
session_start();
include_once('connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit();
        } else {
            header("Location: login.php?error=wrong_password");
            exit();
        }
    } else {
        header("Location: login.php?error=user_not_found");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="output.css" rel="stylesheet">
    <title>Login</title>
    <style>
    body {
      position: relative;
      height: 100vh;
    }

    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: url('img/background.jpg'); /* Ensure the image file exists */
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      opacity: 0.5;
      z-index: -1; /* Keep the background behind */
    }
  </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
  
  <!-- Modal for Error -->
  <div id="error-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-sm">
      <h3 class="text-xl font-bold text-red-500 mb-4">Error</h3>
      <p id="error-message" class="text-gray-700 mb-4"></p>
      <div class="flex justify-end">
        <button onclick="closeModal()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Close</button>
      </div>
    </div>
  </div>

  <!-- Login Form -->
  <div class="bg-white bg-opacity-80 p-8 rounded-lg shadow-lg w-full max-w-sm">
    <h2 class="text-2xl font-bold text-center mb-6">Login</h2>
    
    <form action="login.php" method="POST">
      <!-- Username Field -->
      <div class="mb-4">
        <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
        <input type="text" id="username" name="username" placeholder="Enter your username"
          class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
      </div>

      <!-- Password Field -->
      <div class="mb-6">
        <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password"
          class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
      </div>

      <!-- Remember Me -->
      <div class="flex items-center justify-between mb-4">
        <label class="inline-flex items-center">
          <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600">
          <span class="ml-2 text-gray-700 text-sm">Remember Me</span>
        </label>
        <a href="forgot_password.php" class="text-sm text-blue-500 hover:underline">Forgot Password?</a>
      </div>

      <!-- Submit Button -->
      <div class="text-center">
        <button type="submit"
          class="w-full bg-blue-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
          Sign In
        </button>
      </div>
    </form>
  </div>

  <!-- JavaScript -->
  <script>
    window.onload = function () {
      const urlParams = new URLSearchParams(window.location.search);
      const error = urlParams.get('error');
      
      if (error) {
        showModal(error);
      }
    };

    function showModal(error) {
      const modal = document.getElementById('error-modal');
      const errorMessage = document.getElementById('error-message');
      
      // Customize error message based on the error type
      if (error === 'wrong_password') {
        errorMessage.textContent = 'The password or username you entered is incorrect. Please try again.';
      } else if (error === 'user_not_found') {
        errorMessage.textContent = 'User not found. Please check your username.';
      }
      
      modal.classList.remove('hidden');
    }

    function closeModal() {
      document.getElementById('error-modal').classList.add('hidden');
    }
  </script>
</body>
</html>
