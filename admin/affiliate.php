<?php
// affiliate.php - User affiliate page
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Get referrals
$referrals = getUserReferrals($userId);

// Get commission statistics
$totalCommission = getUserCommissionTotal($userId);
$monthlyCommission = getUserCommissionTotal($userId, 'month');
$weeklyCommission = getUserCommissionTotal($userId, 'week');

// Get package purchase conversion rate
$db = connectDB();
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT r.id) as total_referrals,
        COUNT(DISTINCT pp.user_id) as converted_referrals
    FROM users r
    LEFT JOIN package_purchases pp ON pp.user_id = r.id
    WHERE r.referrer_id = ?
");
$stmt->execute([$userId]);
$conversionStats = $stmt->fetch(PDO::FETCH_ASSOC);

$totalReferrals = $conversionStats['total_referrals'] ?: 0;
$convertedReferrals = $conversionStats['converted_referrals'] ?: 0;
$conversionRate = $totalReferrals > 0 ? round(($convertedReferrals / $totalReferrals) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাফিলিয়েট - MZ Income</title>
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
                    <h1 class="h2">অ্যাফিলিয়েট প্রোগ্রাম</h1>
                </div>
                
                <div class="row">
                    <div class="col-md-6 col-lg-7">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>রেফারেল লিঙ্ক</h5>
                            </div>
                            <div class="card-body">
                                <div class="referral-info mb-4">
                                    <p>আপনার রেফারেল লিঙ্ক শেয়ার করুন এবং কমিশন আয় করুন:</p>
                                    
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="referralLink" value="<?php echo SITE_URL . 'register.php?ref=' . $user['referrer_code']; ?>" readonly>
                                        <button class="btn btn-outline-secondary" type="button" id="copyReferralLink">
                                            <i class="fas fa-copy"></i> কপি
                                        </button>
                                    </div>
                                    
                                    <div class="referral-code-display text-center mb-3">
                                        <p class="mb-1">আপনার রেফারেল কোড</p>
                                        <h4 class="code"><?php echo $user['referrer_code']; ?></h4>
                                    </div>
                                    
                                    <div class="social-share">
                                        <p>সোশ্যাল মিডিয়ায় শেয়ার করুন:</p>
                                        <div class="social-buttons">
                                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . 'register.php?ref=' . $user['referrer_code']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fab fa-facebook-f"></i> Facebook
                                            </a>
                                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . 'register.php?ref=' . $user['referrer_code']); ?>&text=<?php echo urlencode('Join MZ Income and earn money!'); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fab fa-twitter"></i> Twitter
                                            </a>
                                            <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('Join MZ Income and earn money! ' . SITE_URL . 'register.php?ref=' . $user['referrer_code']); ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                                <i class="fab fa-whatsapp"></i> WhatsApp
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="commission-info">
                                    <h5>আপনার কমিশন রেট</h5>
                                    <?php if ($user['active_package'] == 'none'): ?>
                                        <div class="commission-rate-display basic">
                                            <div class="rate">18%</div>
                                            <div class="package-name">বেসিক</div>
                                            <a href="earning_plans.php" class="btn btn-sm btn-primary upgrade-btn">আপগ্রেড করুন</a>
                                        </div>
                                    <?php elseif ($user['active_package'] == 'gold'): ?>
                                        <div class="commission-rate-display gold">
                                            <div class="rate">30%</div>
                                            <div class="package-name">গোল্ড প্যাকেজ</div>
                                            <a href="earning_plans.php" class="btn btn-sm btn-primary upgrade-btn">আপগ্রেড করুন</a>
                                        </div>
                                    <?php elseif ($user['active_package'] == 'diamond'): ?>
                                        <div class="commission-rate-display diamond">
                                            <div class="rate">50%</div>
                                            <div class="package-name">ডায়মন্ড প্যাকেজ</div>
                                            <div class="max-badge">সর্বোচ্চ</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>আপনার রেফারেলস</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($referrals) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ইউজার</th>
                                                    <th>যোগদানের তারিখ</th>
                                                    <th>প্যাকেজ</th>
                                                    <th>কমিশন</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($referrals as $referral): ?>
                                                    <?php
                                                        // Get commission earned from this referral
                                                        $stmt = $db->prepare("
                                                            SELECT SUM(commission_amount) as total 
                                                            FROM referral_commissions 
                                                            WHERE referrer_id = ? AND referred_id = ?
                                                        ");
                                                        $stmt->execute([$userId, $referral['id']]);
                                                        $commissionTotal = $stmt->fetchColumn() ?: 0;
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="<?php echo isset($referral['profile_picture']) && $referral['profile_picture'] != 'default.jpg' ? PROFILE_PIC_DIR . $referral['profile_picture'] : DEFAULT_PROFILE_PIC; ?>" class="referral-pic rounded-circle" alt="Profile">
                                                                <div class="ms-2">
                                                                    <div><?php echo $referral['username']; ?></div>
                                                                    <small class="text-muted">#<?php echo $referral['account_number']; ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?php echo date('d M Y', strtotime($referral['created_at'])); ?></td>
                                                        <td>
                                                            <?php if ($referral['active_package'] != 'none'): ?>
                                                                <span class="badge bg-success"><?php echo ucfirst($referral['active_package']); ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">কোন প্যাকেজ নেই</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo formatAmount($commissionTotal); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-users fa-3x text-muted"></i>
                                        </div>
                                        <h5>কোন রেফারেল নেই</h5>
                                        <p>আপনার রেফারেল লিঙ্ক শেয়ার করুন এবং আয় করা শুরু করুন!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-5">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>কমিশন সারসংক্ষেপ</h5>
                            </div>
                            <div class="card-body">
                                <div class="stats-card mb-4">
                                    <div class="stats-item">
                                        <div class="icon-container">
                                            <i class="fas fa-coins"></i>
                                        </div>
                                        <div class="stats-content">
                                            <h4><?php echo formatAmount($totalCommission); ?></h4>
                                            <p>মোট কমিশন</p>
                                        </div>
                                    </div>
                                    <div class="stats-item">
                                        <div class="icon-container">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <div class="stats-content">
                                            <h4><?php echo $totalReferrals; ?></h4>
                                            <p>রেফারেলস</p>
                                        </div>
                                    </div>
                                    <div class="stats-item">
                                        <div class="icon-container">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                        <div class="stats-content">
                                            <h4><?php echo $conversionRate; ?>%</h4>
                                            <p>কনভার্সন রেট</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="commission-periods mb-4">
                                    <div class="period-item">
                                        <div class="period-label">
                                            <i class="fas fa-calendar-day"></i>
                                            <span>সাপ্তাহিক</span>
                                        </div>
                                        <div class="period-value"><?php echo formatAmount($weeklyCommission); ?></div>
                                    </div>
                                    <div class="period-item">
                                        <div class="period-label">
                                            <i class="fas fa-calendar-week"></i>
                                            <span>মাসিক</span>
                                        </div>
                                        <div class="period-value"><?php echo formatAmount($monthlyCommission); ?></div>
                                    </div>
                                </div>
                                
                                <div class="conversion-progress">
                                    <div class="progress-label">
                                        <span>প্যাকেজ কনভার্সন</span>
                                        <span><?php echo $convertedReferrals; ?>/<?php echo $totalReferrals; ?></span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $conversionRate; ?>%" aria-valuenow="<?php echo $conversionRate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted">কতজন রেফারেল প্যাকেজ কিনেছে</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>মার্কেটিং টিপস</h5>
                            </div>
                            <div class="card-body">
                                <div class="marketing-tips">
                                    <div class="tip-item">
                                        <div class="tip-icon">
                                            <i class="fas fa-bullseye"></i>
                                        </div>
                                        <div class="tip-content">
                                            <h5>সঠিক অডিয়েন্স খুঁজুন</h5>
                                            <p>আয় করতে আগ্রহী এবং অনলাইন কাজে আগ্রহী ব্যক্তিদের টার্গেট করুন।</p>
                                        </div>
                                    </div>
                                    
                                    <div class="tip-item">
                                        <div class="tip-icon">
                                            <i class="fas fa-share-alt"></i>
                                        </div>
                                        <div class="tip-content">
                                            <h5>সোশ্যাল মিডিয়া ব্যবহার করুন</h5>
                                            <p>Facebook, WhatsApp গ্রুপ, Telegram চ্যানেলে আপনার রেফারেল লিঙ্ক শেয়ার করুন।</p>
                                        </div>
                                    </div>
                                    
                                    <div class="tip-item">
                                        <div class="tip-icon">
                                            <i class="fas fa-comments"></i>
                                        </div>
                                        <div class="tip-content">
                                            <h5>আপনার সাফল্যের গল্প বলুন</h5>
                                            <p>আপনি কিভাবে MZ Income থেকে আয় করেছেন সেই অভিজ্ঞতা শেয়ার করুন।</p>
                                        </div>
                                    </div>
                                    
                                    <div class="tip-item">
                                        <div class="tip-icon">
                                            <i class="fas fa-question-circle"></i>
                                        </div>
                                        <div class="tip-content">
                                            <h5>সবার প্রশ্নের উত্তর দিন</h5>
                                            <p>আগ্রহী ব্যক্তিদের প্রশ্নের উত্তর দিয়ে তাদের সাইন আপ করতে সাহায্য করুন।</p>
                                        </div>
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
    <script>
        // Copy referral link to clipboard
        document.getElementById('copyReferralLink').addEventListener('click', function() {
            const referralLinkInput = document.getElementById('referralLink');
            referralLinkInput.select();
            document.execCommand('copy');
            
            this.innerHTML = '<i class="fas fa-check"></i> কপি হয়েছে';
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-copy"></i> কপি';
            }, 2000);
        });
    </script>
</body>
</html>
