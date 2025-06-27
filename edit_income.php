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

// Get user info
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: view_income.php");
    exit();
}

$income_id = $_GET['id'];

// Fetch income record
$stmt = $conn->prepare("SELECT * FROM income WHERE id = ?");
$stmt->bind_param("i", $income_id);
$stmt->execute();
$income = $stmt->get_result()->fetch_assoc();

// If record doesn't exist, redirect
if (!$income) {
    header("Location: view_income.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $source = $_POST['source'];
    $income_date = $_POST['income_date'];

    $stmt = $conn->prepare("UPDATE income SET description = ?, amount = ?, category = ?, source = ?, income_date = ? WHERE id = ?");
    $stmt->bind_param("sdsssi", $description, $amount, $category, $source, $income_date, $income_id);
    
    if ($stmt->execute()) {
        header("Location: view_income.php");
        exit();
    } else {
        $error = "Error updating record: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Income - Finance Tracker</title>
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
                            <h1 class="text-xl font-bold text-white">Edit Income</h1>
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
                <div class="glass-effect rounded-2xl p-6 shadow-xl">
                    <?php if (isset($error)): ?>
                        <div class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-400">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-white/80 mb-2">Description</label>
                                <input type="text" id="description" name="description" required
                                       value="<?php echo htmlspecialchars($income['description']); ?>"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>

                            <!-- Amount -->
                            <div>
                                <label for="amount" class="block text-sm font-medium text-white/80 mb-2">Amount</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-white/40">$</span>
                                    <input type="number" id="amount" name="amount" step="0.01" required
                                           value="<?php echo htmlspecialchars($income['amount']); ?>"
                                           class="w-full pl-8 pr-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                            </div>

                            <!-- Category -->
                            <div>
                                <label for="category" class="block text-sm font-medium text-white/80 mb-2">Category</label>
                                <select id="category" name="category" required
                                        class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="DESIGN" <?php echo $income['category'] == 'DESIGN' ? 'selected' : ''; ?>>DESIGN</option>
                                    <option value="PRINTING" <?php echo $income['category'] == 'PRINTING' ? 'selected' : ''; ?>>PRINTING</option>
                                    <option value="CONSULTATION" <?php echo $income['category'] == 'CONSULTATION' ? 'selected' : ''; ?>>CONSULTATION</option>
                                </select>
                            </div>

                            <!-- Source -->
                            <div>
                                <label for="source" class="block text-sm font-medium text-white/80 mb-2">Source</label>
                                <input type="text" id="source" name="source" required
                                       value="<?php echo htmlspecialchars($income['source']); ?>"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>

                            <!-- Date -->
                            <div>
                                <label for="income_date" class="block text-sm font-medium text-white/80 mb-2">Date</label>
                                <input type="date" id="income_date" name="income_date" required
                                       value="<?php echo htmlspecialchars($income['income_date']); ?>"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end space-x-4">
                            <a href="view_income.php" 
                               class="px-6 py-2 bg-white/5 border border-white/10 rounded-lg text-white hover:bg-white/10 transition-colors duration-200">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                                Update Income
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
