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
        INSERT INTO customers (user_id, name, email, phone, company, address, tax_id, credit_limit, payment_terms, status, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("issssssdsss", $userId, $name, $email, $phone, $company, $address, $tax_id, $credit_limit, $payment_terms, $status, $notes);

    if ($stmt->execute()) {
        header("Location: customers.php?success=1");
        exit();
    } else {
        $error = "Error adding customer: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer - Finance Tracker</title>
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
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="glass-effect border-b border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <h1 class="text-xl font-bold text-white">Add Customer</h1>
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

                    <form action="add_customer.php" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="space-y-6">
                                <h3 class="text-lg font-medium text-white">Basic Information</h3>
                                
                                <div>
                                    <label for="name" class="block text-sm font-medium text-white/80 mb-2">Customer Name *</label>
                                    <input type="text" name="name" id="name" required
                                           class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                           placeholder="Enter customer name">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-white/80 mb-2">Email</label>
                                    <input type="email" name="email" id="email"
                                           class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                           placeholder="Enter email address">
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-white/80 mb-2">Phone</label>
                                    <input type="tel" name="phone" id="phone"
                                           class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                           placeholder="Enter phone number">
                                </div>

                                <div>
                                    <label for="company" class="block text-sm font-medium text-white/80 mb-2">Company</label>
                                    <input type="text" name="company" id="company"
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
                                               class="w-full pl-8 pr-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <div>
                                    <label for="payment_terms" class="block text-sm font-medium text-white/80 mb-2">Payment Terms</label>
                                    <select name="payment_terms" id="payment_terms"
                                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                        <option value="immediate">Immediate</option>
                                        <option value="net15">Net 15</option>
                                        <option value="net30">Net 30</option>
                                        <option value="net60">Net 60</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="status" class="block text-sm font-medium text-white/80 mb-2">Status</label>
                                    <select name="status" id="status"
                                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
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
                                          placeholder="Enter address"></textarea>
                            </div>

                            <div>
                                <label for="notes" class="block text-sm font-medium text-white/80 mb-2">Notes</label>
                                <textarea name="notes" id="notes" rows="3"
                                          class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                          placeholder="Enter any additional notes"></textarea>
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
                                Save Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 