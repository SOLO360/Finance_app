<?php
require_once 'connection.php';

// Define tier thresholds in TZS
define('DIAMOND_THRESHOLD', 10000000);  // 10M TZS
define('GOLD_THRESHOLD', 5000000);      // 5M TZS
define('SILVER_THRESHOLD', 1000000);    // 1M TZS
define('BRONZE_THRESHOLD', 1000000);    // 1M TZS

function formatCurrency($amount) {
    return number_format($amount, 0, '.', ',') . ' TZS';
}

function calculatePoints($amount, $tier) {
    // Convert amount to points (1 point per 1000 TZS)
    $base_points = floor($amount / 1000);
    
    // Apply tier multiplier
    switch ($tier) {
        case 'Diamond':
            $multiplier = 3;
            break;
        case 'Gold':
            $multiplier = 2;
            break;
        case 'Silver':
            $multiplier = 1.5;
            break;
        default: // Bronze
            $multiplier = 1;
    }
    
    return round($base_points * $multiplier);
}

function getLoyaltyTier($total_purchases) {
    if ($total_purchases >= DIAMOND_THRESHOLD) {
        return ['name' => 'Diamond', 'color' => 'purple', 'min' => 10000000, 'max' => PHP_FLOAT_MAX];
    }
    if ($total_purchases >= GOLD_THRESHOLD) {
        return ['name' => 'Gold', 'color' => 'yellow', 'min' => 5000000, 'max' => 9999999];
    }
    if ($total_purchases >= SILVER_THRESHOLD) {
        return ['name' => 'Silver', 'color' => 'gray', 'min' => 1000000, 'max' => 4999999];
    }
    return ['name' => 'Bronze', 'color' => 'amber', 'min' => 0, 'max' => 999999];
}

function updateCustomerPoints($customer_id, $amount) {
    global $conn;
    
    // Get customer's current total purchases
    $stmt = $conn->prepare("SELECT total_purchases FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    
    // Calculate new total purchases
    $new_total = $customer['total_purchases'] + $amount;
    
    // Get customer's tier
    $tier = getLoyaltyTier($new_total);
    
    // Calculate points for this purchase
    $points_earned = calculatePoints($amount, $tier['name']);
    
    // Check if this is first purchase
    $stmt = $conn->prepare("SELECT COUNT(*) as purchase_count FROM income WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $purchase_count = $result->fetch_assoc()['purchase_count'];
    
    if ($purchase_count == 0) {
        $points_earned += 100; // First purchase bonus
    }
    
    // Update customer's points and total purchases
    $stmt = $conn->prepare("UPDATE customers SET 
                           loyalty_points = loyalty_points + ?,
                           total_purchases = ?
                           WHERE id = ?");
    $stmt->bind_param("idi", $points_earned, $new_total, $customer_id);
    $stmt->execute();
    
    return $points_earned;
}

function addReferralBonus($customer_id) {
    global $conn;
    
    $referral_bonus = 200;
    
    $stmt = $conn->prepare("UPDATE customers SET 
                           loyalty_points = loyalty_points + ?
                           WHERE id = ?");
    $stmt->bind_param("ii", $referral_bonus, $customer_id);
    $stmt->execute();
    
    return $referral_bonus;
}

function addBirthdayBonus($customer_id) {
    global $conn;
    
    $birthday_bonus = 50;
    
    $stmt = $conn->prepare("UPDATE customers SET 
                           loyalty_points = loyalty_points + ?
                           WHERE id = ?");
    $stmt->bind_param("ii", $birthday_bonus, $customer_id);
    $stmt->execute();
    
    return $birthday_bonus;
}
?> 