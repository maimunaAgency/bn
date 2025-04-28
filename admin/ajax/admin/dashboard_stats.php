<?php
// ajax/admin/dashboard_stats.php - Admin dashboard statistics
require_once '../../config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]));
}

// Get statistics
$db = connectDB();

// Total users
$stmt = $db->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$totalUsers = $stmt->fetchColumn();

// Total deposits
$stmt = $db->prepare("
    SELECT SUM(amount) FROM transactions 
    WHERE type = 'deposit' AND status = 'approved'
");
$stmt->execute();
$totalDeposits = $stmt->fetchColumn() ?: 0;

// Total withdrawals
$stmt = $db->prepare("
    SELECT SUM(amount) FROM transactions 
    WHERE type = 'withdraw' AND status = 'approved'
");
$stmt->execute();
$totalWithdrawals = $stmt->fetchColumn() ?: 0;

// Package purchases
$stmt = $db->prepare("
    SELECT COUNT(*) FROM package_purchases
");
$stmt->execute();
$totalPackages = $stmt->fetchColumn();

// Today's transactions
$stmt = $db->prepare("
    SELECT COUNT(*) FROM transactions 
    WHERE DATE(created_at) = CURDATE()
");
$stmt->execute();
$todayTransactions = $stmt->fetchColumn();

// Pending deposits
$stmt = $db->prepare("
    SELECT COUNT(*) FROM transactions 
    WHERE type = 'deposit' AND status = 'pending'
");
$stmt->execute();
$pendingDeposits = $stmt->fetchColumn();

// Pending withdrawals
$stmt = $db->prepare("
    SELECT COUNT(*) FROM transactions 
    WHERE type = 'withdraw' AND status = 'pending'
");
$stmt->execute();
$pendingWithdrawals = $stmt->fetchColumn();

// Return statistics
echo json_encode([
    'success' => true,
    'stats' => [
        'total_users' => $totalUsers,
        'total_deposits' => formatAmount($totalDeposits),
        'total_withdrawals' => formatAmount($totalWithdrawals),
        'total_packages' => $totalPackages,
        'today_transactions' => $todayTransactions,
        'pending_deposits' => $pendingDeposits,
        'pending_withdrawals' => $pendingWithdrawals
    ]
]);