<!-- expenses_form.php -->
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
$error = null;

// Get user info
try {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    $error = "Error fetching user info: " . $e->getMessage();
}

// Get all expense categories
try {
    $categories = $conn->query("SELECT * FROM expense_categories ORDER BY name");
    if (!$categories) {
        throw new Exception("Error fetching categories: " . $conn->error);
    }
} catch (Exception $e) {
    $error = "Error fetching expense categories: " . $e->getMessage();
    $categories = null;
}

// Get all payment methods
try {
    $payment_methods = $conn->query("SELECT * FROM payment_methods ORDER BY name");
    if (!$payment_methods) {
        throw new Exception("Error fetching payment methods: " . $conn->error);
    }
} catch (Exception $e) {
    $error = "Error fetching payment methods: " . $e->getMessage();
    $payment_methods = null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    $category_id = $_POST['category_id'];
    $payment_method_id = $_POST['payment_method_id'];
    $expense_date = $_POST['expense_date'];
    $description = $_POST['description'];
    $receipt_number = $_POST['receipt_number'];

    $sql = "INSERT INTO expenses (amount, category_id, payment_method_id, expense_date, description, receipt_number, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("diisssi", $amount, $category_id, $payment_method_id, $expense_date, $description, $receipt_number, $userId);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Expense added successfully!";
        header("Location: view_expenses.php");
        exit();
    } else {
        $error = "Error adding expense: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - Finance Tracker</title>
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
                            <h1 class="text-xl font-bold text-white">Add New Expense</h1>
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
                <?php if (isset($error)): ?>
                    <div class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-400">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="glass-effect rounded-2xl p-6 shadow-xl">
                    <form method="POST" action="" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Amount -->
                            <div>
                                <label for="amount" class="block text-sm font-medium text-white/60 mb-2">Amount (TZS)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-white/40">TSh</span>
                                    <input type="number" name="amount" id="amount" required step="0.01" min="0"
                                           class="w-full pl-12 pr-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                           placeholder="Enter amount">
                                </div>
                            </div>

                            <!-- Date -->
                            <div>
                                <label for="expense_date" class="block text-sm font-medium text-white/60 mb-2">Date</label>
                                <input type="date" name="expense_date" id="expense_date" required
                                       class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>

                            <!-- Category -->
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-white/60 mb-2">Category</label>
                                <select name="category_id" id="category_id" required
                                        class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">Select a category</option>
                                    <?php if ($categories): ?>
                                        <?php while ($category = $categories->fetch_assoc()): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <label for="payment_method_id" class="block text-sm font-medium text-white/60 mb-2">Payment Method</label>
                                <select name="payment_method_id" id="payment_method_id" required
                                        class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">Select payment method</option>
                                    <?php if ($payment_methods): ?>
                                        <?php while ($method = $payment_methods->fetch_assoc()): ?>
                                            <option value="<?php echo $method['id']; ?>">
                                                <?php echo htmlspecialchars($method['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Receipt Number -->
                            <div>
                                <label for="receipt_number" class="block text-sm font-medium text-white/60 mb-2">Receipt Number</label>
                                <input type="text" name="receipt_number" id="receipt_number"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Enter receipt number">
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-white/60 mb-2">Description</label>
                            <textarea name="description" id="description" rows="3"
                                      class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                      placeholder="Enter expense description"></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-2 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-plus mr-2"></i>
                                Add Expense
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set default date to today
        document.getElementById('expense_date').valueAsDate = new Date();
    </script>
</body>
</html>