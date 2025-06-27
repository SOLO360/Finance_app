<?php
require_once 'connection.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    $today = date('Y-m-d');
    if ($username && $email && $password && $role) {
        $hashedPassword = md5($password);
        $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password, role, last_password_update, reset_token, reset_token_expiry, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, NOW(), NOW())");
        $stmt->bind_param("ssssss", $username, $email, $phone, $hashedPassword, $role, $today);
        if ($stmt->execute()) {
            $message = '<div style="color: green;">User added successfully!</div>';
        } else {
            $message = '<div style="color: red;">Error: ' . htmlspecialchars($stmt->error) . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div style="color: red;">All required fields must be filled.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen">
<div class="flex h-screen">
    <?php include 'includes/sidebar.php'; ?>
    <div class="flex-1 flex items-center justify-center">
        <div class="bg-white/10 p-8 rounded-2xl shadow-xl w-full max-w-md">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center"><i class="fas fa-user-plus mr-3"></i>Add New User</h2>
            <?php if ($message) echo $message; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-white/80 mb-1" for="username">Username</label>
                    <input type="text" id="username" name="username" class="w-full px-4 py-2 rounded-lg bg-white/20 text-white focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label class="block text-white/80 mb-1" for="email">Email</label>
                    <input type="email" id="email" name="email" class="w-full px-4 py-2 rounded-lg bg-white/20 text-white focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label class="block text-white/80 mb-1" for="phone">Phone (optional)</label>
                    <input type="text" id="phone" name="phone" class="w-full px-4 py-2 rounded-lg bg-white/20 text-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-white/80 mb-1" for="password">Password</label>
                    <input type="password" id="password" name="password" class="w-full px-4 py-2 rounded-lg bg-white/20 text-white focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                </div>
                <div>
                    <label class="block text-white/80 mb-1" for="role">Role</label>
                    <select id="role" name="role" class="w-full px-4 py-2 rounded-lg bg-white/20 text-white focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200">Add User</button>
            </form>
        </div>
    </div>
</div>
</body>
</html> 