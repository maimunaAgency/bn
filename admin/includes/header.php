<?php
// includes/header.php - User header
$currentPage = basename($_SERVER['PHP_SELF']);

// Get unread notifications count
$unreadNotificationsCount = 0;
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $db = connectDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadNotificationsCount = $stmt->fetchColumn();
}
?>
<header class="navbar navbar-dark sticky-top bg-primary flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="dashboard.php">MZ Income</a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    
    <form class="w-100" action="search.php" method="GET">
        <input class="form-control form-control-dark w-100" type="text" name="q" placeholder="ইউজার সার্চ করুন" aria-label="Search">
    </form>
    
    <div class="navbar-nav">
        <div class="nav-item text-nowrap d-none d-md-block">
            <a class="nav-link px-3" href="notifications.php">
                <i class="fas fa-bell"></i>
                <?php if ($unreadNotificationsCount > 0): ?>
                    <span class="badge rounded-pill bg-danger"><?php echo $unreadNotificationsCount; ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="nav-item text-nowrap">
            <a class="nav-link px-3" href="logout.php">লগআউট</a>
        </div>
    </div>
</header>
