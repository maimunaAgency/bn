<?php
// includes/sidebar.php - User sidebar
$currentPage = basename($_SERVER['PHP_SELF']);

// Get user data if logged in
$user = null;
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $user = getUserById($userId);
}
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <?php if ($user): ?>
        <div class="sidebar-user-info text-center mb-3">
            <img src="<?php echo isset($user['profile_picture']) && $user['profile_picture'] != 'default.jpg' ? PROFILE_PIC_DIR . $user['profile_picture'] : DEFAULT_PROFILE_PIC; ?>" class="sidebar-profile-pic rounded-circle" alt="Profile">
            <h6 class="mb-0 mt-2"><?php echo isset($user['full_name']) && !empty($user['full_name']) ? $user['full_name'] : $user['username']; ?></h6>
            <small class="text-muted">#<?php echo $user['account_number']; ?></small>
            <p class="wallet-balance mt-1 mb-0">
                <?php if ($user['hide_balance']): ?>
                    <span>*** *** ***</span>
                <?php else: ?>
                    <span><?php echo formatAmount($user['wallet_balance']); ?></span>
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home"></i>
                    ড্যাশবোর্ড
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'wallet.php' ? 'active' : ''; ?>" href="wallet.php">
                    <i class="fas fa-wallet"></i>
                    ওয়ালেট
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'transfer.php' ? 'active' : ''; ?>" href="transfer.php">
                    <i class="fas fa-exchange-alt"></i>
                    ফান্ড ট্রান্সফার
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'deposit.php' ? 'active' : ''; ?>" href="deposit.php">
                    <i class="fas fa-coins"></i>
                    ডিপোজিট
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'withdraw.php' ? 'active' : ''; ?>" href="withdraw.php">
                    <i class="fas fa-money-bill-wave"></i>
                    উইথড্র
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'earning_plans.php' ? 'active' : ''; ?>" href="earning_plans.php">
                    <i class="fas fa-crown"></i>
                    আর্নিং প্ল্যান
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'affiliate.php' ? 'active' : ''; ?>" href="affiliate.php">
                    <i class="fas fa-sitemap"></i>
                    অ্যাফিলিয়েট
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'top_earners.php' ? 'active' : ''; ?>" href="top_earners.php">
                    <i class="fas fa-trophy"></i>
                    টপ আর্নারস
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                    <i class="fas fa-user"></i>
                    প্রোফাইল
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                    <i class="fas fa-bell"></i>
                    নোটিফিকেশন
                    <?php if ($unreadNotificationsCount > 0): ?>
                        <span class="badge rounded-pill bg-danger"><?php echo $unreadNotificationsCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>অন্যান্য</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    লগআউট
                </a>
            </li>
        </ul>
    </div>
</nav>
