<?php
// ajax/admin/search_user.php - Admin search user
require_once '../../config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]));
}

// Get search term
$searchTerm = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';

if (empty($searchTerm)) {
    die(json_encode([
        'success' => false,
        'message' => 'Search term required'
    ]));
}

// Search users
$db = connectDB();
$stmt = $db->prepare("
    SELECT id, username, account_number, mobile, wallet_balance, active_package 
    FROM users 
    WHERE username LIKE ? OR account_number LIKE ? OR mobile LIKE ? OR full_name LIKE ?
    ORDER BY username ASC
    LIMIT 10
");
$searchPattern = "%$searchTerm%";
$stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format users
$formattedUsers = [];
foreach ($users as $user) {
    $formattedUsers[] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'account_number' => $user['account_number'],
        'mobile' => $user['mobile'],
        'wallet_balance' => formatAmount($user['wallet_balance']),
        'active_package' => $user['active_package']
    ];
}

// Return users
echo json_encode([
    'success' => true,
    'users' => $formattedUsers
]);
