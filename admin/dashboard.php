<?php
// dashboard.php - User dashboard main page
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Get site notice
$siteNotice = getSiteSettings('site_notice');

// Get unread notifications count
$db = connectDB();
$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadNotificationsCount = $stmt->fetchColumn();

// Check for expired packages
checkExpiredPackages();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ড্যাশবোর্ড - MZ Income</title>
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
                <?php if ($siteNotice): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <strong>নোটিশ:</strong> <?php echo $siteNotice; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-12 col-lg-8">
                        <div class="card mb-4">
                            <div class="card-body wallet-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">ওয়ালেট ব্যালেন্স</h5>
                                    <button id="toggleBalance" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas <?php echo $user['hide_balance'] ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                                    </button>
                                </div>
                                
                                <div class="wallet-balance" id="walletBalance">
                                    <?php if ($user['hide_balance']): ?>
                                        <h2>*** *** ***</h2>
                                    <?php else: ?>
                                        <h2><?php echo formatAmount($user['wallet_balance']); ?></h2>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="package-info">
                                    <?php if ($user['active_package'] != 'none'): ?>
                                        <span class="badge bg-success"><?php echo ucfirst($user['active_package']); ?> প্যাকেজ</span>
                                        <?php
                                            // Calculate days remaining
                                            $expiryDate = new DateTime($user['package_expiry_date']);
                                            $currentDate = new DateTime();
                                            $interval = $currentDate->diff($expiryDate);
                                            $daysRemaining = $interval->days;
                                        ?>
                                        <span class="text-muted ms-2"><small><?php echo $daysRemaining; ?> দিন বাকি</small></span>
                                    <?php else: ?>
                                        <a href="earning_plans.php" class="btn btn-sm btn-outline-primary">প্যাকেজ কিনুন</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mb-4 text-center quick-action-card">
                                    <div class="card-body">
                                        <div class="icon-container mb-3">
                                            <i class="fas fa-exchange-alt"></i>
                                        </div>
                                        <h5 class="card-title">ফান্ড ট্রান্সফার</h5>
                                        <a href="transfer.php" class="stretched-link"></a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card mb-4 text-center quick-action-card">
                                    <div class="card-body">
                                        <div class="icon-container mb-3">
                                            <i class="fas fa-wallet"></i>
                                        </div>
                                        <h5 class="card-title">ডিপোজিট</h5>
                                        <a href="deposit.php" class="stretched-link"></a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card mb-4 text-center quick-action-card">
                                    <div class="card-body">
                                        <div class="icon-container mb-3">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <h5 class="card-title">উইথড্র</h5>
                                        <a href="withdraw.php" class="stretched-link"></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>সাম্প্রতিক লেনদেন</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>তারিখ</th>
                                                <th>বিবরণ</th>
                                                <th>পরিমাণ</th>
                                                <th>স্ট্যাটাস</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $stmt = $db->prepare("
                                                SELECT * FROM transactions 
                                                WHERE user_id = ? 
                                                ORDER BY created_at DESC 
                                                LIMIT 5
                                            ");
                                            $stmt->execute([$userId]);
                                            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            if (count($transactions) > 0) {
                                                foreach ($transactions as $transaction) {
                                                    $typeName = "";
                                                    switch ($transaction['type']) {
                                                        case 'deposit':
                                                            $typeName = "ডিপোজিট";
                                                            break;
                                                        case 'withdraw':
                                                            $typeName = "উইথড্র";
                                                            break;
                                                        case 'transfer_sent':
                                                            $typeName = "ট্রান্সফার প্রেরিত";
                                                            break;
                                                        case 'transfer_received':
                                                            $typeName = "ট্রান্সফার প্রাপ্ত";
                                                            break;
                                                        case 'commission':
                                                            $typeName = "কমিশন";
                                                            break;
                                                        case 'package_purchase':
                                                            $typeName = "প্যাকেজ কেনা";
                                                            break;
                                                        case 'package_refund':
                                                            $typeName = "প্যাকেজ ফেরত";
                                                            break;
                                                    }
                                                    
                                                    $statusClass = "";
                                                    $statusText = "";
                                                    
                                                    switch ($transaction['status']) {
                                                        case 'pending':
                                                            $statusClass = "bg-warning";
                                                            $statusText = "অপেক্ষমান";
                                                            break;
                                                        case 'approved':
                                                            $statusClass = "bg-success";
                                                            $statusText = "অনুমোদিত";
                                                            break;
                                                        case 'rejected':
                                                            $statusClass = "bg-danger";
                                                            $statusText = "বাতিল";
                                                            break;
                                                    }
                                                    
                                                    $amount = $transaction['amount'];
                                                    $amountClass = in_array($transaction['type'], ['withdraw', 'transfer_sent', 'package_purchase']) ? 'text-danger' : 'text-success';
                                                    $amountPrefix = in_array($transaction['type'], ['withdraw', 'transfer_sent', 'package_purchase']) ? '-' : '+';
                                                    
                                                    echo '<tr>';
                                                    echo '<td>' . date('d M Y, h:i A', strtotime($transaction['created_at'])) . '</td>';
                                                    echo '<td>' . $typeName . '</td>';
                                                    echo '<td class="' . $amountClass . '">' . $amountPrefix . formatAmount($amount) . '</td>';
                                                    echo '<td><span class="badge ' . $statusClass . '">' . $statusText . '</span></td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="4" class="text-center">কোন লেনদেন নেই</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>আপনার প্রোফাইল</h5>
                            </div>
                            <div class="card-body profile-summary">
                                <div class="text-center mb-3">
                                    <img src="<?php echo isset($user['profile_picture']) && $user['profile_picture'] != 'default.jpg' ? PROFILE_PIC_DIR . $user['profile_picture'] : DEFAULT_PROFILE_PIC; ?>" class="profile-pic rounded-circle" alt="Profile Picture">
                                    <h5 class="mt-3"><?php echo isset($user['full_name']) && !empty($user['full_name']) ? $user['full_name'] : $user['username']; ?></h5>
                                    <p class="text-muted">Account #<?php echo $user['account_number']; ?></p>
                                </div>
                                
                                <div class="profile-info">
                                    <div class="info-item">
                                        <span class="label"><i class="fas fa-user me-2"></i> ইউজারনেম:</span>
                                        <span class="value"><?php echo $user['username']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label"><i class="fas fa-phone me-2"></i> মোবাইল:</span>
                                        <span class="value"><?php echo $user['mobile']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label"><i class="fas fa-calendar me-2"></i> যোগদান:</span>
                                        <span class="value"><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="profile.php" class="btn btn-outline-primary btn-sm">প্রোফাইল দেখুন</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>রেফারেল লিঙ্ক</h5>
                                <button id="copyReferLink" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="referralLink" value="<?php echo SITE_URL . 'register.php?ref=' . $user['referrer_code']; ?>" readonly>
                                </div>
                                
                                <div class="text-center">
                                    <p class="mb-1">আপনার রেফারেল কোড</p>
                                    <h4><?php echo $user['referrer_code']; ?></h4>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="affiliate.php" class="btn btn-primary btn-sm">অ্যাফিলিয়েট বিবরণ</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>টপ আর্নারস</h5>
                            </div>
                            <div class="card-body">
                                <div class="top-earners-list">
                                    <?php
                                    $topEarners = getTopEarners('month', 5);
                                    
                                    if (count($topEarners) > 0) {
                                        foreach ($topEarners as $index => $earner) {
                                            $position = $index + 1;
                                            echo '<div class="earner-item">';
                                            echo '<div class="position">' . $position . '</div>';
                                            echo '<div class="user-info">';
                                            echo '<img src="' . (isset($earner['profile_picture']) && $earner['profile_picture'] != 'default.jpg' ? PROFILE_PIC_DIR . $earner['profile_picture'] : DEFAULT_PROFILE_PIC) . '" class="earner-pic rounded-circle" alt="Profile">';
                                            echo '<div>';
                                            echo '<h6>' . $earner['username'] . '</h6>';
                                            echo '<small class="text-muted">Account #' . $earner['account_number'] . '</small>';
                                            echo '</div>';
                                            echo '</div>';
                                            echo '<div class="amount">' . formatAmount($earner['total_earnings']) . '</div>';
                                            echo '</div>';
                                        }
                                    } else {
                                        echo '<p class="text-center">কোন আর্নার নেই</p>';
                                    }
                                    ?>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="top_earners.php" class="btn btn-outline-primary btn-sm">সকল আর্নার দেখুন</a>
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
        // Toggle wallet balance visibility
        document.getElementById('toggleBalance').addEventListener('click', function() {
            const walletBalance = document.getElementById('walletBalance');
            const icon = this.querySelector('i');
            
            // Send AJAX request to update user preference
            fetch('ajax/update_balance_visibility.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=toggle'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.hidden) {
                        walletBalance.innerHTML = '<h2>*** *** ***</h2>';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    } else {
                        walletBalance.innerHTML = '<h2>' + data.balance + '</h2>';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    }
                }
            });
        });
        
        // Copy referral link
        document.getElementById('copyReferLink').addEventListener('click', function() {
            const referralLinkInput = document.getElementById('referralLink');
            referralLinkInput.select();
            document.execCommand('copy');
            
            this.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-copy"></i>';
            }, 2000);
        });
    </script>
</body>
</html>
