<?php
// wallet.php - User wallet page
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Get transactions
$db = connectDB();
$stmt = $db->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ওয়ালেট - MZ Income</title>
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
                    <h1 class="h2">ওয়ালেট</h1>
                </div>
                
                <div class="row">
                    <div class="col-12 col-lg-8">
                        <div class="card mb-4">
                            <div class="card-body wallet-card-large">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">ওয়ালেট ব্যালেন্স</h5>
                                    <button id="toggleBalance" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas <?php echo $user['hide_balance'] ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                                    </button>
                                </div>
                                
                                <div class="wallet-balance-large" id="walletBalance">
                                    <?php if ($user['hide_balance']): ?>
                                        <h1>*** *** ***</h1>
                                    <?php else: ?>
                                        <h1><?php echo formatAmount($user['wallet_balance']); ?></h1>
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
                                <div class="card mb-4 text-center wallet-action-card">
                                    <div class="card-body">
                                        <div class="icon-container mb-3">
                                            <i class="fas fa-exchange-alt"></i>
                                        </div>
                                        <h5 class="card-title">ফান্ড ট্রান্সফার</h5>
                                        <a href="transfer.php" class="btn btn-outline-primary btn-sm">ট্রান্সফার</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card mb-4 text-center wallet-action-card">
                                    <div class="card-body">
                                        <div class="icon-container mb-3">
                                            <i class="fas fa-wallet"></i>
                                        </div>
                                        <h5 class="card-title">ডিপোজিট</h5>
                                        <a href="deposit.php" class="btn btn-outline-primary btn-sm">ডিপোজিট</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card mb-4 text-center wallet-action-card">
                                    <div class="card-body">
                                        <div class="icon-container mb-3">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <h5 class="card-title">উইথড্র</h5>
                                        <a href="withdraw.php" class="btn btn-outline-primary btn-sm">উইথড্র</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>ইনকাম সারসংক্ষেপ</h5>
                            </div>
                            <div class="card-body">
                                <div class="income-summary">
                                    <div class="summary-item">
                                        <div class="summary-label">
                                            <i class="fas fa-calendar-day text-primary"></i>
                                            <span>আজকের ইনকাম</span>
                                        </div>
                                        <div class="summary-value">
                                            <?php
                                            $stmt = $db->prepare("
                                                SELECT SUM(amount) FROM transactions 
                                                WHERE user_id = ? AND type IN ('commission', 'package_refund') 
                                                AND status = 'approved'
                                                AND DATE(created_at) = CURDATE()
                                            ");
                                            $stmt->execute([$userId]);
                                            $todayIncome = $stmt->fetchColumn() ?: 0;
                                            echo formatAmount($todayIncome);
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="summary-item">
                                        <div class="summary-label">
                                            <i class="fas fa-calendar-week text-success"></i>
                                            <span>সাপ্তাহিক ইনকাম</span>
                                        </div>
                                        <div class="summary-value">
                                            <?php
                                            $stmt = $db->prepare("
                                                SELECT SUM(amount) FROM transactions 
                                                WHERE user_id = ? AND type IN ('commission', 'package_refund') 
                                                AND status = 'approved'
                                                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
                                            ");
                                            $stmt->execute([$userId]);
                                            $weeklyIncome = $stmt->fetchColumn() ?: 0;
                                            echo formatAmount($weeklyIncome);
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="summary-item">
                                        <div class="summary-label">
                                            <i class="fas fa-calendar-alt text-warning"></i>
                                            <span>মাসিক ইনকাম</span>
                                        </div>
                                        <div class="summary-value">
                                            <?php
                                            $stmt = $db->prepare("
                                                SELECT SUM(amount) FROM transactions 
                                                WHERE user_id = ? AND type IN ('commission', 'package_refund') 
                                                AND status = 'approved'
                                                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                                            ");
                                            $stmt->execute([$userId]);
                                            $monthlyIncome = $stmt->fetchColumn() ?: 0;
                                            echo formatAmount($monthlyIncome);
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="summary-item">
                                        <div class="summary-label">
                                            <i class="fas fa-coins text-danger"></i>
                                            <span>মোট ইনকাম</span>
                                        </div>
                                        <div class="summary-value">
                                            <?php
                                            $stmt = $db->prepare("
                                                SELECT SUM(amount) FROM transactions 
                                                WHERE user_id = ? AND type IN ('commission', 'package_refund') 
                                                AND status = 'approved'
                                            ");
                                            $stmt->execute([$userId]);
                                            $totalIncome = $stmt->fetchColumn() ?: 0;
                                            echo formatAmount($totalIncome);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>লেনদেন হিস্ট্রি</h5>
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
                                        <th>নোট</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
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
                                            echo '<td>' . ($transaction['notes'] ?: '-') . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center">কোন লেনদেন নেই</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
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
                        walletBalance.innerHTML = '<h1>*** *** ***</h1>';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    } else {
                        walletBalance.innerHTML = '<h1>' + data.balance + '</h1>';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    }
                }
            });
        });
    </script>
</body>
</html>