<?php
include_once('connection.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);

    if ($stmt->execute()) {
        echo "User registered successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<form method="POST">
    <input class="form-control" type="text" name="username" placeholder="Username" required>
    <input class="form-control" type="password" name="password" placeholder="Password" required>
    <select class="form-control" name="role">
        <option value="admin">Admin</option>
        <option value="staff">Staff</option>
    </select>
    <button class="btn my-2"type="submit">Register</button>
</form>
