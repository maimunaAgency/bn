<?php
// notifications.php - User notifications page
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$userId = $_SESSION['user_id'];

// Mark all notifications as read if requested
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] == '1') {
    $db = connectDB();
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Redirect to remove query parameter
    redirect('notifications.php');
}

// Delete notification if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notificationId = intval($_GET['delete']);
    
    $db = connectDB();
    $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
    
    // Redirect to remove query parameter
    redirect('notifications.php');
}

// Get notifications
$db = connectDB();
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread count
$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadCount = $stmt->fetchColumn();

// Update notifications to mark as read
if ($unreadCount > 0) {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>নোটিফিকেশন - MZ Income</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">নোটিফিকেশন</h1>
                    
                    <?php if (count($notifications) > 0): ?>
                        <div class="btn-toolbar">
                            <div class="btn-group me-2">
                                <a href="?mark_all_read=1" class="btn btn-sm btn-outline-secondary">সব পঠিত হিসেবে চিহ্নিত করুন</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <?php if (count($notifications) > 0): ?>
                            <div class="notifications-list">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                        <div class="notification-icon">
                                            <?php
                                                // Set icon based on notification title
                                                $icon = 'bell';
                                                
                                                if (strpos($notification['title'], 'ডিপোজিট') !== false) {
                                                    $icon = 'wallet';
                                                } elseif (strpos($notification['title'], 'উইথড্র') !== false) {
                                                    $icon = 'money-bill-wave';
                                                } elseif (strpos($notification['title'], 'কমিশন') !== false || strpos($notification['title'], 'আয়') !== false) {
                                                    $icon = 'coins';
                                                } elseif (strpos($notification['title'], 'রেফারেল') !== false) {
                                                    $icon = 'user-plus';
                                                } elseif (strpos($notification['title'], 'প্যাকেজ') !== false) {
                                                    $icon = 'crown';
                                                } elseif (strpos($notification['title'], 'ট্রান্সফার') !== false) {
                                                    $icon = 'exchange-alt';
                                                } elseif (strpos($notification['title'], 'স্বাগতম') !== false) {
                                                    $icon = 'hand-sparkles';
                                                }
                                            ?>
                                            <i class="fas fa-<?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-header">
                                                <h6><?php echo $notification['title']; ?></h6>
                                                <div class="notification-time"><?php echo date('d M Y, h:i A', strtotime($notification['created_at'])); ?></div>
                                            </div>
                                            <div class="notification-message">
                                                <?php echo $notification['message']; ?>
                                            </div>
                                        </div>
                                        <div class="notification-actions">
                                            <a href="?delete=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('আপনি কি এই নোটিফিকেশন মুছতে চান?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-5">
                                <div class="mb-3">
                                    <i class="fas fa-bell-slash fa-3x text-muted"></i>
                                </div>
                                <h5>কোন নোটিফিকেশন নেই</h5>
                                <p>আপনার কোন নতুন নোটিফিকেশন নেই।</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>