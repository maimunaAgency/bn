<?php
// admin/includes/sidebar.php - Admin sidebar
$currentPage = basename($_SERVER['PHP_SELF']);

// Get pending counts
$db = connectDB();

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
?>
<nav id="sidebarAdminMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    ড্যাশবোর্ড
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i>
                    ইউজারস
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'deposits.php' ? 'active' : ''; ?>" href="deposits.php">
                    <i class="fas fa-coins"></i>
                    ডিপোজিট
                    <?php if ($pendingDeposits > 0): ?>
                        <span class="badge rounded-pill bg-warning text-dark"><?php echo $pendingDeposits; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'withdrawals.php' ? 'active' : ''; ?>" href="withdrawals.php">
                    <i class="fas fa-money-bill-wave"></i>
                    উইথড্র
                    <?php if ($pendingWithdrawals > 0): ?>
                        <span class="badge rounded-pill bg-warning text-dark"><?php echo $pendingWithdrawals; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'transactions.php' ? 'active' : ''; ?>" href="transactions.php">
                    <i class="fas fa-exchange-alt"></i>
                    ট্রানজেকশন
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i>
                    সেটিংস
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>অন্যান্য</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    সাইট দেখুন
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../admin_logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    লগআউট
                </a>
            </li>
        </ul>
    </div>
</nav>