<?php
// top_earners.php - Top earners page
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Get period from query string
$period = isset($_GET['period']) ? sanitizeInput($_GET['period']) : 'all';
if (!in_array($period, ['week', 'month', 'all'])) {
    $period = 'all';
}

// Get top earners
$topEarners = getTopEarners($period, 50);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>টপ আর্নারস - MZ Income</title>
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
                    <h1 class="h2">টপ আর্নারস</h1>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $period == 'week' ? 'active' : ''; ?>" href="?period=week">সাপ্তাহিক</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $period == 'month' ? 'active' : ''; ?>" href="?period=month">মাসিক</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $period == 'all' ? 'active' : ''; ?>" href="?period=all">সর্বকালীন</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <?php if (count($topEarners) > 0): ?>
                            <div class="top-earners-table">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>র‍্যাঙ্ক</th>
                                            <th>ইউজার</th>
                                            <th>প্যাকেজ</th>
                                            <th>মোট আর্নিং</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topEarners as $index => $earner): ?>
                                            <?php
                                                $rank = $index + 1;
                                                $rankClass = '';
                                                
                                                if ($rank == 1) {
                                                    $rankClass = 'rank-1';
                                                } elseif ($rank == 2) {
                                                    $rankClass = 'rank-2';
                                                } elseif ($rank == 3) {
                                                    $rankClass = 'rank-3';
                                                }
                                                
                                                // Get user package
                                                $stmt = $db->prepare("SELECT active_package FROM users WHERE id = ?");
                                                $stmt->execute([$earner['id']]);
                                                $package = $stmt->fetchColumn();
                                            ?>
                                            <tr class="<?php echo $earner['id'] == $userId ? 'table-primary' : ''; ?>">
                                                <td>
                                                    <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $rank; ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo isset($earner['profile_picture']) && $earner['profile_picture'] != 'default.jpg' ? PROFILE_PIC_DIR . $earner['profile_picture'] : DEFAULT_PROFILE_PIC; ?>" class="earner-pic rounded-circle" alt="Profile">
                                                        <div class="ms-2">
                                                            <div><?php echo $earner['username']; ?></div>
                                                            <small class="text-muted">#<?php echo $earner['account_number']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($package != 'none'): ?>
                                                        <span class="badge bg-success"><?php echo ucfirst($package); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">বেসিক</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo formatAmount($earner['total_earnings']); ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-4">
                                <div class="mb-3">
                                    <i class="fas fa-trophy fa-3x text-muted"></i>
                                </div>
                                <h5>কোন আর্নার নেই</h5>
                                <p>এখনো কেউ আর্নিং করেনি।</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>আর্নিং সম্পর্কে তথ্য</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-icon">
                                        <i class="fas fa-gift"></i>
                                    </div>
                                    <div class="info-content">
                                        <h5>রেফারেল কমিশন</h5>
                                        <p>আপনার রেফারেল লিঙ্ক শেয়ার করে অন্যদের জয়েন করান এবং কমিশন আয় করুন। বেসিক ১৮%, গোল্ড ৩০%, ডায়মন্ড ৫০%।</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-icon">
                                        <i class="fas fa-sync-alt"></i>
                                    </div>
                                    <div class="info-content">
                                        <h5>প্যাকেজ রিফান্ড</h5>
                                        <p>প্যাকেজ মেয়াদে কমপক্ষে ১টি সফল রেফারেল করলে প্যাকেজ মূল্য ১০০% ফেরত পাবেন।</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-icon">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    <div class="info-content">
                                        <h5>টপ আর্নার হওয়ার টিপস</h5>
                                        <p>ডায়মন্ড প্যাকেজ কিনুন, অনেক বেশি রেফারেল করুন, এবং ডায়মন্ড প্যাকেজ কেনার জন্য উৎসাহিত করুন।</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="info-card">
                                    <div class="info-icon">
                                        <i class="fas fa-hand-holding-usd"></i>
                                    </div>
                                    <div class="info-content">
                                        <h5>উইথড্র সিস্টেম</h5>
                                        <p>যেকোনো সময় আপনার ওয়ালেট ব্যালেন্স থেকে টাকা উইথড্র করুন। ২৪ ঘন্টার মধ্যে প্রসেস করা হয়।</p>
                                    </div>
                                </div>
                            </div>
                        </div>
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