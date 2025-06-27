<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$result = null;
$error_message = null;
$table_exists = false;

// Get user info
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check if price_list table exists
$table_check = $conn->query("SHOW TABLES LIKE 'price_list'");
$table_exists = $table_check->num_rows > 0;

// Fetch price list items with error handling
if ($table_exists) {
    try {
        $sql = "SELECT * FROM price_list ORDER BY category, item_name";
        $result = $conn->query($sql);
        
        if (!$result) {
            throw new Exception("Error fetching price list: " . $conn->error);
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Display success message if set
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Display error message if set
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price List - Finance Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen">
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="glass-effect border-b border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <h1 class="text-xl font-bold text-white">Price List</h1>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <div class="h-8 w-8 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <span class="text-white font-medium">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <?php if ($table_exists): ?>
                            <a href="add_price_item.php" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-plus mr-2"></i>
                                Add Item
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <?php if (isset($success_message)): ?>
                    <div class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg text-green-400">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-400">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!$table_exists): ?>
                    <div class="glass-effect rounded-2xl p-6 shadow-xl mb-8">
                        <div class="text-center">
                            <h2 class="text-xl font-bold text-white mb-4">Price List Table Not Found</h2>
                            <p class="text-white/70 mb-6">The price list table needs to be created before you can manage price items.</p>
                            <a href="create_price_list_table.php" 
                               class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 inline-block">
                                <i class="fas fa-database mr-2"></i>
                                Create Price List Table
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Categories -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <!-- Design Category -->
                        <div class="glass-effect rounded-2xl p-6 shadow-xl">
                            <h2 class="text-xl font-bold text-white mb-4">Design Services</h2>
                            <div class="space-y-4">
                                <?php
                                if ($result && $result->num_rows > 0) {
                                    $result->data_seek(0);
                                    while($row = $result->fetch_assoc()) {
                                        if ($row['category'] === 'DESIGN') {
                                            echo "<div class='flex justify-between items-center'>
                                                    <span class='text-white/80'>". htmlspecialchars($row['item_name']) ."</span>
                                                    <span class='text-green-400 font-bold'>$". number_format($row['price'], 2) ."</span>
                                                </div>";
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Printing Category -->
                        <div class="glass-effect rounded-2xl p-6 shadow-xl">
                            <h2 class="text-xl font-bold text-white mb-4">Printing Services</h2>
                            <div class="space-y-4">
                                <?php
                                if ($result && $result->num_rows > 0) {
                                    $result->data_seek(0);
                                    while($row = $result->fetch_assoc()) {
                                        if ($row['category'] === 'PRINTING') {
                                            echo "<div class='flex justify-between items-center'>
                                                    <span class='text-white/80'>". htmlspecialchars($row['item_name']) ."</span>
                                                    <span class='text-green-400 font-bold'>$". number_format($row['price'], 2) ."</span>
                                                </div>";
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Consultation Category -->
                        <div class="glass-effect rounded-2xl p-6 shadow-xl">
                            <h2 class="text-xl font-bold text-white mb-4">Consultation</h2>
                            <div class="space-y-4">
                                <?php
                                if ($result && $result->num_rows > 0) {
                                    $result->data_seek(0);
                                    while($row = $result->fetch_assoc()) {
                                        if ($row['category'] === 'CONSULTATION') {
                                            echo "<div class='flex justify-between items-center'>
                                                    <span class='text-white/80'>". htmlspecialchars($row['item_name']) ."</span>
                                                    <span class='text-green-400 font-bold'>$". number_format($row['price'], 2) ."</span>
                                                </div>";
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Full Price List Table -->
                    <div class="glass-effect rounded-2xl p-6 shadow-xl">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-white/60 text-sm">
                                        <th class="pb-4">Category</th>
                                        <th class="pb-4">Item Name</th>
                                        <th class="pb-4">Description</th>
                                        <th class="pb-4">Price</th>
                                        <th class="pb-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-white">
                                    <?php
                                    if ($result && $result->num_rows > 0) {
                                        $result->data_seek(0);
                                        while($row = $result->fetch_assoc()) {
                                            echo "<tr class='border-t border-white/10 hover:bg-white/5 transition-colors duration-200'>
                                                    <td class='py-4'>
                                                        <span class='px-3 py-1 rounded-full text-sm bg-green-500/20 text-green-400'>
                                                            ". htmlspecialchars($row['category']) ."
                                                        </span>
                                                    </td>
                                                    <td class='py-4 font-medium'>". htmlspecialchars($row['item_name']) ."</td>
                                                    <td class='py-4 text-white/70'>". htmlspecialchars($row['description']) ."</td>
                                                    <td class='py-4 font-bold text-green-400'>$". number_format($row['price'], 2) ."</td>
                                                    <td class='py-4'>
                                                        <div class='flex items-center space-x-3'>
                                                            <a href='edit_price_item.php?id=". htmlspecialchars($row['id']) ."' class='text-blue-400 hover:text-blue-300'>
                                                                <i class='fas fa-edit'></i>
                                                            </a>
                                                            <form method='POST' action='delete_price_item.php' class='inline-block'>
                                                                <input type='hidden' name='item_id' value='". htmlspecialchars($row['id']) ."'>
                                                                <button type='submit' class='text-red-400 hover:text-red-300' onclick=\"return confirm('Are you sure you want to delete this item?');\">
                                                                    <i class='fas fa-trash'></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='py-4 text-center text-white/60'>No price items found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
