<?php
// view_profile.php - View other user's profile
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('dashboard.php');
}

$profileId = intval($_GET['id']);
$currentUserId = $_SESSION['user_id'];

// Don't allow viewing own profile through this page
if ($profileId == $currentUserId) {
    redirect('profile.php');
}

// Get profile user data
$db = connectDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profileId]);
$profileUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileUser) {
    redirect('dashboard.php');
}

// Get package details if any
$packageDetails = null;
if ($profileUser['active_package'] != 'none') {
    $stmt = $db->prepare("
        SELECT p.name, p.price, p.validity_days, pp.purchase_date, pp.expiry_date 
        FROM packages p
        JOIN package_purchases pp ON p.id = pp.package_id 
        WHERE pp.user_id = ? AND pp.expiry_date >= NOW()
        ORDER BY pp.expiry_date DESC
        LIMIT 1
    ");
    $stmt->execute([$profileId]);
    $packageDetails = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get referrer if any
$referrer = null;
if ($profileUser['referrer_id']) {
    $stmt = $db->prepare("SELECT id, username, account_number FROM users WHERE id = ?");
    $stmt->execute([$profileUser['referrer_id']]);
    $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get top earner rank if any
$stmt = $db->prepare("
    SELECT u.id, SUM(t.amount) as total_earnings
    FROM users u
    JOIN transactions t ON u.id = t.user_id
    WHERE t.type IN ('commission', 'package_refund') 
          AND t.status = 'approved'
    GROUP BY u.id
    ORDER BY total_earnings DESC
");
$stmt->execute();
$earners = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rank = 0;
foreach ($earners as $index => $earner) {
    if ($earner['id'] == $profileId) {
        $rank = $index + 1;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $profileUser['username']; ?> এর প্রোফাইল - MZ Income</title>
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
                    <h1 class="h2">ইউজার প্রোফাইল</h1>
                    <a href="search.php" class="btn btn-outline-secondary">
                        <i class="fas fa-search"></i> সার্চে ফিরুন
                    </a>
                </div>
                
                <div class="row">
                    <div class="col-md-5">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="user-profile-header text-center mb-4">
                                    <img src="<?php echo isset($profileUser['profile_picture']) && $profileUser['profile_picture'] != 'default.jpg' ? PROFILE_PIC_DIR . $profileUser['profile_picture'] : DEFAULT_PROFILE_PIC; ?>" class="public-profile-pic rounded-circle mb-3" alt="Profile Picture">
                                    <h4><?php echo isset($profileUser['full_name']) && !empty($profileUser['full_name']) ? $profileUser['full_name'] : $profileUser['username']; ?></h4>
                                    <p class="mb-1">@<?php echo $profileUser['username']; ?></p>
                                    <p class="text-muted">Account #<?php echo $profileUser['account_number']; ?></p>
                                    <div class="user-badges">
                                        <span class="badge <?php echo $profileUser['active_package'] != 'none' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $profileUser['active_package'] != 'none' ? ucfirst($profileUser['active_package']) . ' সদস্য' : 'বেসিক সদস্য'; ?>
                                        </span>
                                        
                                        <?php if ($rank > 0 && $rank <= 10): ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-trophy"></i> টপ <?php echo $rank; ?> আর্নার
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($profileUser['details'])): ?>
                                    <div class="user-bio mb-4">
                                        <h6>আমার সম্পর্কে</h6>
                                        <p><?php echo $profileUser['details']; ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="user-details mb-4">
                                    <div class="detail-item">
                                        <span class="label"><i class="fas fa-calendar"></i> যোগদান:</span>
                                        <span class="value"><?php echo date('d M Y', strtotime($profileUser['created_at'])); ?></span>
                                    </div>
                                    
                                    <?php if ($referrer): ?>
                                        <div class="detail-item">
                                            <span class="label"><i class="fas fa-user-plus"></i> রেফারার:</span>
                                            <span class="value">
                                                <a href="view_profile.php?id=<?php echo $referrer['id']; ?>"><?php echo $referrer['username']; ?> (#<?php echo $referrer['account_number']; ?>)</a>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($packageDetails): ?>
                                        <div class="detail-item">
                                            <span class="label"><i class="fas fa-crown"></i> প্যাকেজ:</span>
                                            <span class="value">
                                                <?php 
                                                    echo ucfirst($packageDetails['name']) . ' (' . 
                                                    date('d M Y', strtotime($packageDetails['purchase_date'])) . ' - ' . 
                                                    date('d M Y', strtotime($packageDetails['expiry_date'])) . ')';
                                                ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="user-actions">
                                    <div class="row">
                                        <div class="col-6">
                                            <a href="transfer.php?to=<?php echo $profileUser['account_number']; ?>" class="btn btn-primary btn-sm w-100">
                                                <i class="fas fa-exchange-alt"></i> ফান্ড পাঠান
                                            </a>
                                        </div>
                                        <div class="col-6">
                                            <a href="search.php" class="btn btn-outline-secondary btn-sm w-100">
                                                <i class="fas fa-search"></i> অন্য ইউজার খুঁজুন
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <!-- Content can be added here if you want to show more info -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>রেফারেল লিঙ্ক</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <p><i class="fas fa-info-circle"></i> আপনি নিজের রেফারেল লিঙ্ক ব্যবহার করে নতুন ইউজার যোগ করুন এবং কমিশন আয় করুন!</p>
                                </div>
                                
                                <div class="text-center">
                                    <a href="affiliate.php" class="btn btn-primary">
                                        <i class="fas fa-sitemap"></i> আমার রেফারেল লিঙ্ক
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>প্যাকেজ আপগ্রেড করুন</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-success">
                                    <p><i class="fas fa-star"></i> প্যাকেজ কিনে বেশি কমিশন আয় করুন!</p>
                                    <ul class="mb-0">
                                        <li>গোল্ড প্যাকেজ: ৩০% কমিশন</li>
                                        <li>ডায়মন্ড প্যাকেজ: ৫০% কমিশন</li>
                                    </ul>
                                </div>
                                
                                <div class="text-center">
                                    <a href="earning_plans.php" class="btn btn-success">
                                        <i class="fas fa-crown"></i> প্যাকেজ কিনুন
                                    </a>
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