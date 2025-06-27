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

// Check if customer ID is provided
if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit();
}

$customerId = $_GET['id'];

// Get customer data
$stmt = $conn->prepare("
    SELECT * FROM customers 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $customerId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: customers.php");
    exit();
}

$customer = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $company = $_POST['company'];
    $address = $_POST['address'];
    $tax_id = $_POST['tax_id'];
    $credit_limit = $_POST['credit_limit'];
    $payment_terms = $_POST['payment_terms'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("
        UPDATE customers 
        SET name = ?, email = ?, phone = ?, company = ?, address = ?, 
            tax_id = ?, credit_limit = ?, payment_terms = ?, status = ?, notes = ?
        WHERE id = ? AND user_id = ?
    ");

    $stmt->bind_param("ssssssdsssii", $name, $email, $phone, $company, $address, 
                      $tax_id, $credit_limit, $payment_terms, $status, $notes, 
                      $customerId, $userId);

    if ($stmt->execute()) {
        header("Location: customers.php?success=2");
        exit();
    } else {
        $error = "Error updating customer: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - Finance Tracker</title>
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
        <!-- Sidebar -->
        <div class="w-64 bg-slate-800/50 backdrop-blur-lg border-r border-white/10">
            <div class="p-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-lg flex items-center justify-center">
                        <img src="images/logo.png" alt="FinanceTracker Logo" class="h-full w-full object-contain">
                    </div>
                    <span class="text-xl font-bold text-white">FinanceTracker</span>
                </div>
            </div>
            
            <nav class="mt-8 px-4">
                <!-- Dashboard -->
                <div class="mb-6">
                    <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                        Main
                    </h3>
                    <div class="mt-2 space-y-1">
                        <a href="index.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-home w-5 h-5 mr-3"></i>
                            Dashboard
                        </a>
                    </div>
                </div>

                <!-- Customers -->
                <div class="mb-6">
                    <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                        Business
                    </h3>
                    <div class="mt-2 space-y-1">
                        <a href="customers.php" class="flex items-center px-3 py-2 text-sm font-medium text-white bg-white/10 rounded-lg">
                            <i class="fas fa-users w-5 h-5 mr-3"></i>
                            Customers
                        </a>
                        <a href="add_customer.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-user-plus w-5 h-5 mr-3"></i>
                            Add Customer
                        </a>
                        <a href="price_list.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-tags w-5 h-5 mr-3"></i>
                            Price List
                        </a>
                    </div>
                </div>

                <!-- Income -->
                <div class="mb-6">
                    <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                        Income
                    </h3>
                    <div class="mt-2 space-y-1">
                        <a href="income_form.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus w-5 h-5 mr-3"></i>
                            Add Income
                        </a>
                        <a href="view_income.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-list w-5 h-5 mr-3"></i>
                            View Income
                        </a>
                    </div>
                </div>

                <!-- Expenses -->
                <div class="mb-6">
                    <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                        Expenses
                    </h3>
                    <div class="mt-2 space-y-1">
                        <a href="expenses_form.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-plus w-5 h-5 mr-3"></i>
                            Add Expense
                        </a>
                        <a href="view_expenses.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-list w-5 h-5 mr-3"></i>
                            View Expenses
                        </a>
                    </div>
                </div>

                <!-- Reports & Analysis -->
                <div class="mb-6">
                    <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                        Reports & Analysis
                    </h3>
                    <div class="mt-2 space-y-1">
                        <a href="summary.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-chart-pie w-5 h-5 mr-3"></i>
                            Summary
                        </a>
                        <a href="reports.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-chart-bar w-5 h-5 mr-3"></i>
                            Reports
                        </a>
                    </div>
                </div>

                <!-- Settings -->
                <div class="mb-6">
                    <h3 class="px-3 text-xs font-semibold text-white/50 uppercase tracking-wider">
                        Settings
                    </h3>
                    <div class="mt-2 space-y-1">
                        <a href="settings.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-cog w-5 h-5 mr-3"></i>
                            Settings
                        </a>
                        <a href="logout.php" class="flex items-center px-3 py-2 text-sm font-medium text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200">
                            <i class="fas fa-sign-out-alt w-5 h-5 mr-3"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="glass-effect border-b border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <h1 class="text-xl font-bold text-white">Edit Customer</h1>
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

            <!-- Form Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="glass-effect rounded-2xl p-6 shadow-xl">
                    <?php if (isset($error)): ?>
                        <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-400">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="edit_customer.php?id=<?php echo $customerId; ?>" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="space-y-6">
                                <h3 class="text-lg font-medium text-white">Basic Information</h3>
                                
                                <div>
                                    <label for="name" class="block text-sm font-medium text-white/80 mb-2">Customer Name *</label>
                                    <input type="text" name="name" id="name" required
                                           value="<?php echo htmlspecialchars($customer['name']); ?>"
                                           class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                           placeholder="Enter customer name">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-white/80 mb-2">Email</label>
                                    <input type="email" name="email" id="email"
                                           value="<?php echo htmlspecialchars($customer['email']); ?>"
                                           class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                           placeholder="Enter email address">
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-white/80 mb-2">Phone</label>
                                    <input type="tel" name="phone" id="phone"
                                           value="<?php echo htmlspecialchars($customer['phone']); ?>"
                                           class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                           placeholder="Enter phone number">
                                </div>

                                <div>
                                    <label for="company" class="block text-sm font-medium text-white/80 mb-2">Company</label>
                                    <input type="text" name="company" id="company"
                                           value="<?php echo htmlspecialchars($customer['company']); ?>"
                                           class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                           placeholder="Enter company name">
                                </div>
                            </div>

                            <!-- Business Information -->
                            <div class="space-y-6">
                                <h3 class="text-lg font-medium text-white">Business Information</h3>
                                
                                <div>
                                    <label for="tax_id" class="block text-sm font-medium text-white/80 mb-2">Tax ID</label>
                                    <input type="text" name="tax_id" id="tax_id"
                                           value="<?php echo htmlspecialchars($customer['tax_id']); ?>"
                                           class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                           placeholder="Enter tax ID">
                                </div>

                                <div>
                                    <label for="credit_limit" class="block text-sm font-medium text-white/80 mb-2">Credit Limit</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-white/60">
                                            $
                                        </span>
                                        <input type="number" name="credit_limit" id="credit_limit" step="0.01"
                                               value="<?php echo htmlspecialchars($customer['credit_limit']); ?>"
                                               class="w-full pl-8 pr-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <div>
                                    <label for="payment_terms" class="block text-sm font-medium text-white/80 mb-2">Payment Terms</label>
                                    <select name="payment_terms" id="payment_terms"
                                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                        <option value="immediate" <?php echo $customer['payment_terms'] === 'immediate' ? 'selected' : ''; ?>>Immediate</option>
                                        <option value="net15" <?php echo $customer['payment_terms'] === 'net15' ? 'selected' : ''; ?>>Net 15</option>
                                        <option value="net30" <?php echo $customer['payment_terms'] === 'net30' ? 'selected' : ''; ?>>Net 30</option>
                                        <option value="net60" <?php echo $customer['payment_terms'] === 'net60' ? 'selected' : ''; ?>>Net 60</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="status" class="block text-sm font-medium text-white/80 mb-2">Status</label>
                                    <select name="status" id="status"
                                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                        <option value="active" <?php echo $customer['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $customer['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-medium text-white">Additional Information</h3>
                            
                            <div>
                                <label for="address" class="block text-sm font-medium text-white/80 mb-2">Address</label>
                                <textarea name="address" id="address" rows="3"
                                          class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                          placeholder="Enter address"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                            </div>

                            <div>
                                <label for="notes" class="block text-sm font-medium text-white/80 mb-2">Notes</label>
                                <textarea name="notes" id="notes" rows="3"
                                          class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                          placeholder="Enter any additional notes"><?php echo htmlspecialchars($customer['notes']); ?></textarea>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-4">
                            <a href="customers.php" 
                               class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-save mr-2"></i>
                                Update Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 