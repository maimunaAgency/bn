<?php
// ajax/mark_notification_read.php - Mark notification as read
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

// Get notification ID
$notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

if ($notificationId <= 0) {
    die(json_encode([
        'success' => false,
        'message' => 'Invalid notification ID'
    ]));
}

// Mark notification as read
$db = connectDB();
$stmt = $db->prepare("
    UPDATE notifications 
    SET is_read = 1 
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$notificationId, $userId]);

// Get unread count
$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadCount = $stmt->fetchColumn();

// Return success
echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount
]);
