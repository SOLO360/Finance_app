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
$success_message = '';
$error_message = '';

// Get user info
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check if item ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: price_list.php");
    exit();
}

$item_id = (int)$_GET['id'];

// Fetch item details
try {
    $stmt = $conn->prepare("SELECT * FROM price_list WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if (!$item) {
        throw new Exception("Price item not found.");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: price_list.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $category = $_POST['category'] ?? '';
        $item_name = trim($_POST['item_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);

        if (empty($category) || empty($item_name) || $price <= 0) {
            throw new Exception("Please fill in all required fields with valid values.");
        }

        // Update price item
        $stmt = $conn->prepare("UPDATE price_list SET category = ?, item_name = ?, description = ?, price = ? WHERE id = ?");
        $stmt->bind_param("sssdi", $category, $item_name, $description, $price, $item_id);
        
        if ($stmt->execute()) {
            $success_message = "Price item updated successfully!";
            // Refresh item data
            $stmt = $conn->prepare("SELECT * FROM price_list WHERE id = ?");
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();
        } else {
            throw new Exception("Error updating price item: " . $stmt->error);
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Price Item - Finance Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
                            <h1 class="text-xl font-bold text-white">Edit Price Item</h1>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <div class="h-8 w-8 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <span class="text-white font-medium">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <?php if ($success_message): ?>
                    <div class="mb-4 p-4 bg-green-500/20 border border-green-500/50 rounded-lg text-green-400">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-400">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Edit Price Item Form -->
                <div class="glass-effect rounded-2xl p-6 shadow-xl">
                    <form method="POST" action="" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Category -->
                            <div>
                                <label for="category" class="block text-sm font-medium text-white/80 mb-2">Category *</label>
                                <select name="category" id="category" required
                                        class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">Select Category</option>
                                    <option value="DESIGN" <?php echo $item['category'] === 'DESIGN' ? 'selected' : ''; ?>>Design Services</option>
                                    <option value="PRINTING" <?php echo $item['category'] === 'PRINTING' ? 'selected' : ''; ?>>Printing Services</option>
                                    <option value="CONSULTATION" <?php echo $item['category'] === 'CONSULTATION' ? 'selected' : ''; ?>>Consultation</option>
                                </select>
                            </div>

                            <!-- Item Name -->
                            <div>
                                <label for="item_name" class="block text-sm font-medium text-white/80 mb-2">Item Name *</label>
                                <input type="text" name="item_name" id="item_name" required
                                       value="<?php echo htmlspecialchars($item['item_name']); ?>"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Enter item name">
                            </div>

                            <!-- Price -->
                            <div>
                                <label for="price" class="block text-sm font-medium text-white/80 mb-2">Price *</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-white/40">$</span>
                                    <input type="number" name="price" id="price" required step="0.01" min="0"
                                           value="<?php echo htmlspecialchars($item['price']); ?>"
                                           class="w-full pl-8 pr-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                           placeholder="0.00">
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-white/80 mb-2">Description</label>
                                <textarea name="description" id="description" rows="3"
                                          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                          placeholder="Enter item description"><?php echo htmlspecialchars($item['description']); ?></textarea>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4">
                            <a href="price_list.php" 
                               class="px-6 py-2 bg-white/5 border border-white/10 rounded-lg text-white hover:bg-white/10 transition-colors duration-200">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                                Update Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 