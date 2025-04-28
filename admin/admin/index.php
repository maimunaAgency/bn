<?php
// admin/index.php - Admin dashboard main page
require_once '../config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('../admin_login.php');
}

// Get admin data
$adminId = $_SESSION['admin_id'];
$adminUsername = $_SESSION['admin_username'];

// Get site overview statistics
$db = connectDB();

// Total users
$stmt = $db->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$totalUsers = $stmt->fetchColumn();

// Total deposits
$stmt = $db->prepare("
    SELECT SUM(amount) FROM transactions 
    WHERE type = 'deposit' AND status = 'approved'
");
$stmt->execute();
$totalDeposits = $stmt->fetchColumn() ?: 0;

// Total withdrawals
$stmt = $db->prepare("
    SELECT SUM(amount) FROM transactions 
    WHERE type = 'withdraw' AND status = 'approved'
");
$stmt->execute();
$totalWithdrawals = $stmt->fetchColumn() ?: 0;

// Package purchases
$stmt = $db->prepare("
    SELECT COUNT(*) FROM package_purchases
");
$stmt->execute();
$totalPackages = $stmt->fetchColumn();

// Today's transactions
$stmt = $db->prepare("
    SELECT COUNT(*) FROM transactions 
    WHERE DATE(created_at) = CURDATE()
");
$stmt->execute();
$todayTransactions = $stmt->fetchColumn();

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

// Recent users
$stmt = $db->prepare("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent transactions
$stmt = $db->prepare("
    SELECT t.*, u.username, u.account_number 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাডমিন ড্যাশবোর্ড - MZ Income</title>
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
                    <h1 class="h2">ড্যাশবোর্ড</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshStats">
                                <i class="fas fa-sync-alt"></i> রিফ্রেশ
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row stats-cards">
                    <div class="col-md-3">
                        <div class="card mb-4 stats-card">
                            <div class="card-body">
                                <div class="stats-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stats-details">
                                    <h5>মোট ইউজার</h5>
                                    <h3><?php echo $totalUsers; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card mb-4 stats-card">
                            <div class="card-body">
                                <div class="stats-icon">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="stats-details">
                                    <h5>মোট ডিপোজিট</h5>
                                    <h3><?php echo formatAmount($totalDeposits); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card mb-4 stats-card">
                            <div class="card-body">
                                <div class="stats-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="stats-details">
                                    <h5>মোট উইথড্র</h5>
                                    <h3><?php echo formatAmount($totalWithdrawals); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card mb-4 stats-card">
                            <div class="card-body">
                                <div class="stats-icon">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <div class="stats-details">
                                    <h5>প্যাকেজ কেনা</h5>
                                    <h3><?php echo $totalPackages; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Cards -->
                <div class="row action-cards">
                    <div class="col-md-4">
                        <div class="card mb-4 action-card">
                            <div class="card-body">
                                <div class="action-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="action-details">
                                    <h5>আজকের ট্রানজেকশন</h5>
                                    <h3><?php echo $todayTransactions; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card mb-4 action-card pending-deposits">
                            <div class="card-body">
                                <div class="action-icon">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div class="action-details">
                                    <h5>অপেক্ষমান ডিপোজিট</h5>
                                    <h3><?php echo $pendingDeposits; ?></h3>
                                </div>
                                <a href="deposits.php?status=pending" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card mb-4 action-card pending-withdrawals">
                            <div class="card-body">
                                <div class="action-icon">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                                <div class="action-details">
                                    <h5>অপেক্ষমান উইথড্র</h5>
                                    <h3><?php echo $pendingWithdrawals; ?></h3>
                                </div>
                                <a href="withdrawals.php?status=pending" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Recent Users -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>সাম্প্রতিক ইউজারস</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ইউজারনেম</th>
                                                <th>অ্যাকাউন্ট নম্বর</th>
                                                <th>যোগদান</th>
                                                <th>অ্যাকশন</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentUsers as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['username']; ?></td>
                                                    <td><?php echo $user['account_number']; ?></td>
                                                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                                    <td>
                                                        <a href="user_details.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="users.php" class="btn btn-sm btn-outline-primary">সব ইউজার দেখুন</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Transactions -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>সাম্প্রতিক ট্রানজেকশন</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ইউজার</th>
                                                <th>টাইপ</th>
                                                <th>পরিমাণ</th>
                                                <th>স্ট্যাটাস</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentTransactions as $transaction): ?>
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
                                                ?>
                                                <tr>
                                                    <td>
                                                        <?php echo $transaction['username']; ?><br>
                                                        <small class="text-muted">#<?php echo $transaction['account_number']; ?></small>
                                                    </td>
                                                    <td><?php echo $typeName; ?></td>
                                                    <td><?php echo formatAmount($transaction['amount']); ?></td>
                                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="transactions.php" class="btn btn-sm btn-outline-primary">সব ট্রানজেকশন দেখুন</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        // Refresh stats
        document.getElementById('refreshStats').addEventListener('click', function() {
            location.reload();
        });
    </script>
</body>
</html>

