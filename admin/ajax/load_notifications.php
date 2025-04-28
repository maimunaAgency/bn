<?php
// ajax/load_notifications.php - Load notifications
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

// Get notifications
$db = connectDB();
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread count
$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadCount = $stmt->fetchColumn();

// Format notifications
$formattedNotifications = [];
foreach ($notifications as $notification) {
    $formattedNotifications[] = [
        'id' => $notification['id'],
        'title' => $notification['title'],
        'message' => $notification['message'],
        'is_read' => (bool)$notification['is_read'],
        'created_at' => date('d M Y, h:i A', strtotime($notification['created_at']))
    ];
}

// Return notifications
echo json_encode([
    'success' => true,
    'notifications' => $formattedNotifications,
    'unread_count' => $unreadCount
]);