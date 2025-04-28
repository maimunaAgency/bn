<?php
// includes/footer.php - User footer
?>
<footer class="footer mt-auto py-3 bg-light">
    <div class="container text-center">
        <span class="text-muted">&copy; <?php echo date('Y'); ?> MZ Income. সর্বসত্ব সংরক্ষিত।</span>
    </div>
</footer>


<?php
// admin/includes/header.php - Admin header
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">MZ Income Admin</a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarAdminMenu" aria-controls="sidebarAdminMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="navbar-nav">
        <div class="nav-item text-nowrap d-none d-md-block">
            <span class="nav-link px-3">
                <i class="fas fa-user-shield"></i> <?php echo $_SESSION['admin_username']; ?>
            </span>
        </div>
        <div class="nav-item text-nowrap">
            <a class="nav-link px-3" href="../admin_logout.php">লগআউট</a>
        </div>
    </div>
</header>
