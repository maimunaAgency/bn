
<?php
// ajax/check_account.php - Check if account number exists
require_once '../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]));
}

// Get account number from query
$accountNumber = isset($_GET['account']) ? sanitizeInput($_GET['account']) : '';

if (empty($accountNumber)) {
    die(json_encode([
        'success' => false,
        'message' => 'অ্যাকাউন্ট নম্বর প্রয়োজন'
    ]));
}

// Get user data
$receiverUser = getUserByAccountNumber($accountNumber);

if (!$receiverUser) {
    die(json_encode([
        'success' => false,
        'message' => 'অ্যাকাউন্ট পাওয়া যায়নি'
    ]));
}

// Check if user is trying to transfer to their own account
$userId = $_SESSION['user_id'];
if ($receiverUser['id'] == $userId) {
    die(json_encode([
        'success' => false,
        'message' => 'আপনি নিজের অ্যাকাউন্টে ট্রান্সফার করতে পারবেন না'
    ]));
}

// Return success with username
echo json_encode([
    'success' => true,
    'username' => $receiverUser['username']
]);