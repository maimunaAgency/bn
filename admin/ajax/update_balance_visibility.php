<?php
// ajax/update_balance_visibility.php - Update user's wallet balance visibility
require_once '../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]));
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Process toggle action
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = connectDB();
    
    if (isset($_POST['action']) && $_POST['action'] == 'toggle') {
        // Toggle hide_balance value
        $newHideBalance = $user['hide_balance'] ? 0 : 1;
        
        $stmt = $db->prepare("UPDATE users SET hide_balance = ? WHERE id = ?");
        $stmt->execute([$newHideBalance, $userId]);
        
        echo json_encode([
            'success' => true,
            'hidden' => (bool)$newHideBalance,
            'balance' => formatAmount($user['wallet_balance'])
        ]);
    } elseif (isset($_POST['action']) && $_POST['action'] == 'set' && isset($_POST['hide'])) {
        // Set hide_balance to specified value
        $hideBalance = $_POST['hide'] == '1' ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE users SET hide_balance = ? WHERE id = ?");
        $stmt->execute([$hideBalance, $userId]);
        
        echo json_encode([
            'success' => true,
            'hidden' => (bool)$hideBalance,
            'balance' => formatAmount($user['wallet_balance'])
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}