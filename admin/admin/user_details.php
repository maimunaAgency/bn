<?php
// admin/user_details.php - Admin user details page
require_once '../config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('../admin_login.php');
}

// Get admin data
$adminId = $_SESSION['admin_id'];
$adminUsername = $_SESSION['admin_username'];

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('users.php');
}

$userId = intval($_GET['id']);

// Get user data
$db = connectDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirect('users.php');
}

// Get user's transactions
$stmt = $db->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's referrals
$referrals = getUserReferrals($userId);

// Get user's referrer if any
$referrer = null;
if ($user['referrer_id']) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user['referrer_id']]);
    $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get commission statistics
$totalCommission = getUserCommissionTotal($userId);
$monthlyCommission = getUserCommissionTotal($userId, 'month');
$weeklyCommission = getUserCommissionTotal($userId, 'week');

// Process user action if any
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = sanitizeInput($_POST['action']);
    $message = sanitizeInput($_POST['message'] ?? '');
    
    try {
        if ($action == 'send_message') {
            // Add notification
            addNotification($userId, 'অ্যাডমিন থেকে মেসেজ', $message);
            $success = "মেসেজ সফলভাবে পাঠানো হয়েছে";
        } elseif ($action == 'add_balance') {
            $amount = floatval($_POST['amount']);
            
            if ($amount <= 0) {
                throw new Exception("অবৈধ পরিমাণ");
            }
            
            $db->beginTransaction();
            
            // Update user's wallet balance
            $newBalance = $user['wallet_balance'] + $amount;
            $stmt = $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $userId]);
            
            // Add transaction record
            $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, status, notes) 
                                 VALUES (?, 'deposit', ?, 'approved', 'Added by admin: " . $message . "')");
            $stmt->execute([$userId, $amount]);
            
            // Add notification
            addNotification($userId, 'ব্যালেন্স যোগ করা হয়েছে', 
                           'অ্যাডমিন আপনার ওয়ালেটে ' . formatAmount($amount) . ' যোগ করেছেন। ' . 
                           (!empty($message) ? 'মেসেজ: ' . $message : ''));
            
            $db->commit();
            $success = "ব্যালেন্স সফলভাবে যোগ করা হয়েছে";
            
            // Refresh user data
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($action == 'deduct_balance') {
            $amount = floatval($_POST['amount']);
            
            if ($amount <= 0) {
                throw new Exception("অবৈধ পরিমাণ");
            }
            
            if ($amount > $user['wallet_balance']) {
                throw new Exception("ইউজারের ওয়ালেটে পর্যাপ্ত ব্যালেন্স নেই");
            }
            
            $db->beginTransaction();
            
            // Update user's wallet balance
            $newBalance = $user['wallet_balance'] - $amount;
            $stmt = $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $userId]);
            
            // Add transaction record
            $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, status, notes) 
                                 VALUES (?, 'withdraw', ?, 'approved', 'Deducted by admin: " . $message . "')");
            $stmt->execute([$userId, $amount]);
            
            // Add notification
            addNotification($userId, 'ব্যালেন্স কাটা হয়েছে', 
                           'অ্যাডমিন আপনার ওয়ালেট থেকে ' . formatAmount($amount) . ' কেটে নিয়েছেন। ' . 
                           (!empty($message) ? 'কারণ: ' . $message : ''));
            
            $db->commit();
            $success = "ব্যালেন্স সফলভাবে কাটা হয়েছে";
            
            // Refresh user data
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        $error = "ত্রুটি: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ইউজার বিবরণ - MZ Income</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">ইউজার বিবরণ</h1>
                    <a href="users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> ইউজার লিস্টে ফিরুন
                    </a>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>সফল!</strong> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>ত্রুটি!</strong> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>ইউজার তথ্য</h5>
                            </div>
                            <div class="card-body">
                                <div class="user-profile-header text-center mb-4">
                                    <img src="<?php echo isset($user['profile_picture']) && $user['profile_picture'] != 'default.jpg' ? '../' . PROFILE_PIC_DIR . $user['profile_picture'] : '../' . DEFAULT_PROFILE_PIC; ?>" class="user-profile-pic rounded-circle mb-3" alt="Profile Picture">
                                    <h4><?php echo isset($user['full_name']) && !empty($user['full_name']) ? $user['full_name'] : $user['username']; ?></h4>
                                    <p class="mb-1">@<?php echo $user['username']; ?></p>
                                    <p class="text-muted">Account #<?php echo $user['account_number']; ?></p>
                                    <span class="badge <?php echo $user['active_package'] != 'none' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $user['active_package'] != 'none' ? ucfirst($user['active_package']) . ' সদস্য' : 'বেসিক সদস্য'; ?>
                                    </span>
                                </div>
                                
                                <div class="user-details">
                                    <div class="detail-item">
                                        <span class="label"><i class="fas fa-wallet"></i> ওয়ালেট ব্যালেন্স:</span>
                                        <span class="value"><?php echo formatAmount($user['wallet_balance']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><i class="fas fa-phone"></i> মোবাইল:</span>
                                        <span class="value"><?php echo $user['mobile']; ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><i class="fas fa-sitemap"></i> রেফারেল কোড:</span>
                                        <span class="value"><?php echo $user['referrer_code']; ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><i class="fas fa-user-plus"></i> রেফারার:</span>
                                        <span class="value">
                                            <?php if ($referrer): ?>
                                                <a href="user_details.php?id=<?php echo $referrer['id']; ?>"><?php echo $referrer['username']; ?> (#<?php echo $referrer['account_number']; ?>)</a>
                                            <?php else: ?>
                                                কেউ নেই
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><i class="fas fa-calendar"></i> যোগদান:</span>
                                        <span class="value"><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                                    </div>
                                    <?php if ($user['active_package'] != 'none'): ?>
                                        <div class="detail-item">
                                            <span class="label"><i class="fas fa-crown"></i> প্যাকেজ মেয়াদ:</span>
                                            <span class="value">
                                                <?php
                                                    // Calculate days remaining
                                                    $expiryDate = new DateTime($user['package_expiry_date']);
                                                    $currentDate = new DateTime();
                                                    $interval = $currentDate->diff($expiryDate);
                                                    $daysRemaining = $interval->days;
                                                    
                                                    echo date('d M Y', strtotime($user['package_expiry_date'])) . ' (' . $daysRemaining . ' দিন বাকি)';
                                                ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($user['details'])): ?>
                                    <div class="user-bio mt-4">
                                        <h6>বিবরণ</h6>
                                        <p><?php echo $user['details']; ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="user-actions mt-4">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <button type="button" class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
                                                <i class="fas fa-envelope"></i> মেসেজ
                                            </button>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <button type="button" class="btn btn-success btn-sm w-100" data-bs-toggle="modal" data-bs-target="#addBalanceModal">
                                                <i class="fas fa-plus"></i> ব্যালেন্স যোগ
                                            </button>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <button type="button" class="btn btn-danger btn-sm w-100" data-bs-toggle="modal" data-bs-target="#deductBalanceModal">
                                                <i class="fas fa-minus"></i> ব্যালেন্স কাটুন
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>কমিশন সারসংক্ষেপ</h5>
                            </div>
                            <div class="card-body">
                                <div class="commission-summary">
                                    <div class="summary-item">
                                        <div class="summary-icon">
                                            <i class="fas fa-coins"></i>
                                        </div>
                                        <div class="summary-content">
                                            <h4><?php echo formatAmount($totalCommission); ?></h4>
                                            <p>মোট কমিশন</p>
                                        </div>
                                    </div>
                                    <div class="summary-item">
                                        <div class="summary-icon">
                                            <i class="fas fa-calendar-week"></i>
                                        </div>
                                        <div class="summary-content">
                                            <h4><?php echo formatAmount($monthlyCommission); ?></h4>
                                            <p>মাসিক কমিশন</p>
                                        </div>
                                    </div>
                                    <div class="summary-item">
                                        <div class="summary-icon">
                                            <i class="fas fa-calendar-day"></i>
                                        </div>
                                        <div class="summary-content">
                                            <h4><?php echo formatAmount($weeklyCommission); ?></h4>
                                            <p>সাপ্তাহিক কমিশন</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>ট্রানজেকশন হিস্ট্রি</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>আইডি</th>
                                                <th>টাইপ</th>
                                                <th>পরিমাণ</th>
                                                <th>স্ট্যাটাস</th>
                                                <th>নোট</th>
                                                <th>তারিখ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($transactions) > 0): ?>
                                                <?php foreach ($transactions as $transaction): ?>
                                                    <?php
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
                                                        
                                                        $amountClass = in_array($transaction['type'], ['withdraw', 'transfer_sent', 'package_purchase']) ? 'text-danger' : 'text-success';
                                                        $amountPrefix = in_array($transaction['type'], ['withdraw', 'transfer_sent', 'package_purchase']) ? '-' : '+';
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $transaction['id']; ?></td>
                                                        <td><?php echo $typeName; ?></td>
                                                        <td class="<?php echo $amountClass; ?>"><?php echo $amountPrefix . formatAmount($transaction['amount']); ?></td>
                                                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                        <td><?php echo $transaction['notes'] ?: '-'; ?></td>
                                                        <td><?php echo date('d M Y, h:i A', strtotime($transaction['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">কোন ট্রানজেকশন নেই</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>রেফারেলস</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($referrals) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ইউজার</th>
                                                    <th>যোগদান</th>
                                                    <th>প্যাকেজ</th>
                                                    <th>ব্যালেন্স</th>
                                                    <th>অ্যাকশন</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($referrals as $referral): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="<?php echo isset($referral['profile_picture']) && $referral['profile_picture'] != 'default.jpg' ? '../' . PROFILE_PIC_DIR . $referral['profile_picture'] : '../' . DEFAULT_PROFILE_PIC; ?>" class="referral-pic rounded-circle" alt="Profile">
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
                                                                <span class="badge bg-secondary">বেসিক</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo formatAmount($referral['wallet_balance']); ?></td>
                                                        <td>
                                                            <a href="user_details.php?id=<?php echo $referral['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i> বিবরণ
                                                            </a>
                                                        </td>
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
                                        <p>এই ইউজার এখনো কাউকে রেফার করেনি।</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Send Message Modal -->
    <div class="modal fade" id="sendMessageModal" tabindex="-1" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sendMessageModalLabel">মেসেজ পাঠান</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>ইউজার <strong><?php echo $user['username']; ?></strong> কে মেসেজ পাঠান</p>
                        
                        <div class="mb-3">
                            <label for="message-text" class="form-label">মেসেজ</label>
                            <textarea class="form-control" id="message-text" name="message" rows="3" required></textarea>
                        </div>
                        
                        <input type="hidden" name="action" value="send_message">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-primary">পাঠান</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add Balance Modal -->
    <div class="modal fade" id="addBalanceModal" tabindex="-1" aria-labelledby="addBalanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBalanceModalLabel">ব্যালেন্স যোগ করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>ইউজার <strong><?php echo $user['username']; ?></strong> এর ওয়ালেটে ব্যালেন্স যোগ করুন</p>
                        
                        <div class="mb-3">
                            <label for="add-amount" class="form-label">পরিমাণ</label>
                            <input type="number" class="form-control" id="add-amount" name="amount" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="add-balance-message" class="form-label">কারণ (ঐচ্ছিক)</label>
                            <textarea class="form-control" id="add-balance-message" name="message" rows="2"></textarea>
                        </div>
                        
                        <input type="hidden" name="action" value="add_balance">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-success">যোগ করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Deduct Balance Modal -->
    <div class="modal fade" id="deductBalanceModal" tabindex="-1" aria-labelledby="deductBalanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deductBalanceModalLabel">ব্যালেন্স কাটুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>ইউজার <strong><?php echo $user['username']; ?></strong> এর ওয়ালেট থেকে ব্যালেন্স কাটুন</p>
                        <p>বর্তমান ব্যালেন্স: <strong><?php echo formatAmount($user['wallet_balance']); ?></strong></p>
                        
                        <div class="mb-3">
                            <label for="deduct-amount" class="form-label">পরিমাণ</label>
                            <input type="number" class="form-control" id="deduct-amount" name="amount" min="1" max="<?php echo $user['wallet_balance']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deduct-balance-message" class="form-label">কারণ</label>
                            <textarea class="form-control" id="deduct-balance-message" name="message" rows="2" required></textarea>
                        </div>
                        
                        <input type="hidden" name="action" value="deduct_balance">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-danger">কাটুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
