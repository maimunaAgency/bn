<?php
// earning_plans.php - User earning plans page
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Get packages
$db = connectDB();
$stmt = $db->prepare("SELECT * FROM packages ORDER BY price ASC");
$stmt->execute();
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $packageId = intval($_POST['package_id'] ?? 0);
    
    // Validate inputs
    if ($packageId <= 0) {
        $errors[] = "অবৈধ প্যাকেজ";
    }
    
    // Get package details
    $stmt = $db->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        $errors[] = "প্যাকেজ পাওয়া যায়নি";
    }
    
    // Check if user already has an active package
    if ($user['active_package'] != 'none') {
        $errors[] = "আপনার ইতিমধ্যে একটি সক্রিয় প্যাকেজ আছে";
    }
    
    // Check wallet balance
    if ($package['price'] > $user['wallet_balance']) {
        $errors[] = "আপনার ওয়ালেটে পর্যাপ্ত ব্যালেন্স নেই";
    }
    
    // If no errors, process package purchase
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Create transaction record
            $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, status, notes) 
                                 VALUES (?, 'package_purchase', ?, 'approved', 'Purchase of " . ucfirst($package['name']) . " package')");
            $stmt->execute([$userId, $package['price']]);
            $transactionId = $db->lastInsertId();
            
            // Calculate expiry date
            $purchaseDate = new DateTime();
            $expiryDate = clone $purchaseDate;
            $expiryDate->add(new DateInterval('P' . $package['validity_days'] . 'D'));
            
            // Create package purchase record
            $stmt = $db->prepare("INSERT INTO package_purchases (user_id, package_id, price_paid, purchase_date, expiry_date, transaction_record_id) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userId, 
                $packageId, 
                $package['price'], 
                $purchaseDate->format('Y-m-d H:i:s'), 
                $expiryDate->format('Y-m-d H:i:s'), 
                $transactionId
            ]);
            $packagePurchaseId = $db->lastInsertId();
            
            // Update user's wallet balance
            $stmt = $db->prepare("UPDATE users SET wallet_balance = wallet_balance - ?, active_package = ?, package_purchase_date = ?, package_expiry_date = ? WHERE id = ?");
            $stmt->execute([
                $package['price'], 
                $package['name'], 
                $purchaseDate->format('Y-m-d H:i:s'), 
                $expiryDate->format('Y-m-d H:i:s'), 
                $userId
            ]);
            
            // Add notification
            addNotification($userId, 'প্যাকেজ কেনা সফল', 
                           'আপনি ' . ucfirst($package['name']) . ' প্যাকেজ সফলভাবে কিনেছেন। মেয়াদ: ' . 
                           date('d M Y', $purchaseDate->getTimestamp()) . ' থেকে ' . 
                           date('d M Y', $expiryDate->getTimestamp()) . ' পর্যন্ত।');
            
            // Process referral commission if user has a referrer
            if ($user['referrer_id']) {
                processReferralCommission($user['referrer_id'], $userId, $packagePurchaseId, $package['price']);
            }
            
            $db->commit();
            
            $success = "প্যাকেজ কেনা সফল হয়েছে! আপনি এখন " . ucfirst($package['name']) . " সদস্য।";
            
            // Refresh user data
            $user = getUserById($userId);
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "দুঃখিত, প্যাকেজ কেনা প্রসেস করতে সমস্যা হয়েছে। পরে আবার চেষ্টা করুন।";
            error_log("Package purchase error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>আর্নিং প্ল্যান - MZ Income</title>
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
                    <h1 class="h2">আর্নিং প্ল্যান</h1>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($user['active_package'] != 'none'): ?>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-check-circle me-2"></i> আপনার বর্তমান প্যাকেজ: <?php echo ucfirst($user['active_package']); ?></h5>
                        <?php
                            // Calculate days remaining
                            $expiryDate = new DateTime($user['package_expiry_date']);
                            $currentDate = new DateTime();
                            $interval = $currentDate->diff($expiryDate);
                            $daysRemaining = $interval->days;
                        ?>
                        <p>মেয়াদ: <?php echo date('d M Y', strtotime($user['package_purchase_date'])); ?> থেকে <?php echo date('d M Y', strtotime($user['package_expiry_date'])); ?> (<?php echo $daysRemaining; ?> দিন বাকি)</p>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <?php foreach ($packages as $package): ?>
                        <div class="col-md-6">
                            <div class="card mb-4 package-card <?php echo $package['name']; ?>-package">
                                <div class="card-header">
                                    <h3 class="package-title"><?php echo ucfirst($package['name']); ?> প্যাকেজ</h3>
                                    <div class="package-price">
                                        <span class="price"><?php echo formatAmount($package['price']); ?></span>
                                        <span class="validity"><?php echo $package['validity_days']; ?> দিন</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <ul class="package-features">
                                        <li>
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <strong><?php echo $package['commission_rate']; ?>%</strong> কমিশন রেফারেল থেকে
                                        </li>
                                        <li>
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            প্যাকেজের মেয়াদে কমপক্ষে ১টি সফল রেফারেল করলে প্যাকেজ মূল্য ফেরত
                                        </li>
                                        <li>
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            অসীম রেফারেল করার সুযোগ
                                        </li>
                                        <li>
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            ২৪/৭ সাপোর্ট
                                        </li>
                                    </ul>
                                    
                                    <div class="buy-package-section">
                                        <?php if ($user['active_package'] != 'none'): ?>
                                            <button class="btn btn-outline-secondary w-100" disabled>ইতিমধ্যে একটি প্যাকেজ আছে</button>
                                        <?php elseif ($package['price'] > $user['wallet_balance']): ?>
                                            <a href="deposit.php" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-wallet me-1"></i> ব্যালেন্স রিচার্জ করুন
                                            </a>
                                        <?php else: ?>
                                            <form method="POST" action="" onsubmit="return confirm('আপনি কি নিশ্চিত যে আপনি এই প্যাকেজটি কিনতে চান?');">
                                                <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-shopping-cart me-1"></i> এখনই কিনুন
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>প্যাকেজ ও রেফারেল সিস্টেম বিবরণ</h5>
                    </div>
                    <div class="card-body">
                        <div class="package-description">
                            <h5>প্যাকেজ কিনলে কি কি সুবিধা পাবেন?</h5>
                            <ul>
                                <li><strong>গোল্ড প্যাকেজ (৳১,৯৯৯):</strong> 
                                    <ul>
                                        <li>৪০ দিন মেয়াদ</li>
                                        <li>৩০% কমিশন রেফারেল থেকে</li>
                                    </ul>
                                </li>
                                <li><strong>ডায়মন্ড প্যাকেজ (৳২,৯৯৯):</strong> 
                                    <ul>
                                        <li>৬০ দিন মেয়াদ</li>
                                        <li>৫০% কমিশন রেফারেল থেকে</li>
                                    </ul>
                                </li>
                            </ul>
                            
                            <h5>রেফারেল সিস্টেম কিভাবে কাজ করে?</h5>
                            <ul>
                                <li>যদি আপনি কোন প্যাকেজ না কিনে রেফার করেন তবে পাবেন ১৮% কমিশন</li>
                                <li>যদি আপনি গোল্ড প্যাকেজ কিনে রেফার করেন তবে পাবেন ৩০% কমিশন</li>
                                <li>যদি আপনি ডায়মন্ড প্যাকেজ কিনে রেফার করেন তবে পাবেন ৫০% কমিশন</li>
                            </ul>
                            
                            <h5>প্যাকেজ মূল্য ফেরত কিভাবে পাবেন?</h5>
                            <ul>
                                <li>যদি আপনি প্যাকেজের মেয়াদে কমপক্ষে ১টি সফল রেফারেল করেন (যিনি কোন প্যাকেজ কিনেছেন), তবে প্যাকেজের মেয়াদ শেষে আপনি আপনার প্যাকেজের মূল্য ১০০% ফেরত পাবেন</li>
                                <li>যদি আপনি কোন সফল রেফারেল না করেন, তবে প্যাকেজের মূল্য ফেরত পাবেন না</li>
                            </ul>
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