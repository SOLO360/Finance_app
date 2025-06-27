<?php
session_start();
// Prevent back navigation after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
require_once 'connection.php';
require_once 'includes/loyalty_functions.php';

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

// Get all customers
$customers = $conn->query("SELECT id, name, email FROM customers ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $source = $_POST['source'];
    $income_date = $_POST['income_date'];
    $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
    
    // If new customer is being added
    if (isset($_POST['new_customer']) && $_POST['new_customer'] == '1') {
        $customer_name = $_POST['customer_name'];
        $customer_email = $_POST['customer_email'];
        $customer_phone = $_POST['customer_phone'];
        $customer_address = $_POST['customer_address'];
        
        // Insert new customer
        $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $customer_name, $customer_email, $customer_phone, $customer_address);
        $stmt->execute();
        $customer_id = $conn->insert_id;
    }
    
    // Insert income record
    $stmt = $conn->prepare("INSERT INTO income (user_id, amount, description, category, source, income_date, customer_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idssssi", $userId, $amount, $description, $category, $source, $income_date, $customer_id);
    
    if ($stmt->execute()) {
        // Calculate and award points if customer is selected
        if ($customer_id) {
            $points_earned = updateCustomerPoints($customer_id, $amount);
            $_SESSION['success_message'] = "Income recorded successfully! Customer earned " . $points_earned . " loyalty points.";
        } else {
            $_SESSION['success_message'] = "Income recorded successfully!";
        }
        header("Location: view_income.php");
        exit();
    } else {
        $error = "Error recording income: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Income - Finance Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="glass-effect border-b border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <h1 class="text-xl font-bold text-white">Add Income</h1>
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
                        <!-- Customer Selection -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-white">Customer Information</h3>
                                <button type="button" onclick="toggleNewCustomer()" class="text-blue-400 hover:text-blue-300 text-sm">
                                    <i class="fas fa-plus mr-1"></i> Add New Customer
                                </button>
                            </div>
                            
                            <!-- Existing Customer Selection -->
                            <div id="existingCustomerSection">
                                <label class="block text-white/70 mb-2">Select Customer</label>
                                <select name="customer_id" class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">No Customer</option>
                                    <?php while ($customer = $customers->fetch_assoc()): ?>
                                        <option value="<?php echo $customer['id']; ?>">
                                            <?php echo htmlspecialchars($customer['name']); ?> 
                                            <?php if ($customer['email']): ?>
                                                (<?php echo htmlspecialchars($customer['email']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- New Customer Form -->
                            <div id="newCustomerSection" class="hidden space-y-4">
                                <input type="hidden" name="new_customer" value="1">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-white/70 mb-2">Customer Name</label>
                                        <input type="text" name="customer_name" class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-white/70 mb-2">Email</label>
                                        <input type="email" name="customer_email" class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-white/70 mb-2">Phone</label>
                                        <input type="tel" name="customer_phone" class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-white/70 mb-2">Address</label>
                                        <input type="text" name="customer_address" class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Income Details -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-white">Income Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-white/70 mb-2">Amount</label>
                                    <input type="number" name="amount" step="0.01" required
                                           class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-white/70 mb-2">Date</label>
                                    <input type="date" name="income_date" required
                                           class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-white/70 mb-2">Category</label>
                                    <select name="category" required
                                            class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="SALES">Sales</option>
                                        <option value="SERVICES">Services</option>
                                        <option value="INVESTMENTS">Investments</option>
                                        <option value="OTHER">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-white/70 mb-2">Source</label>
                                    <input type="text" name="source" required
                                           class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>
                            <div>
                                <label class="block text-white/70 mb-2">Description</label>
                                <textarea name="description" required
                                          class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <a href="view_income.php" 
                               class="px-6 py-2 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-colors duration-200">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-colors duration-200">
                                Add Income
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleNewCustomer() {
            const existingSection = document.getElementById('existingCustomerSection');
            const newSection = document.getElementById('newCustomerSection');
            
            if (newSection.classList.contains('hidden')) {
                existingSection.classList.add('hidden');
                newSection.classList.remove('hidden');
            } else {
                existingSection.classList.remove('hidden');
                newSection.classList.add('hidden');
            }
        }
    </script>
</body>
</html>