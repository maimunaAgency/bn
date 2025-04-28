
<?php
// admin/withdrawals.php - Admin withdrawals management page
require_once '../config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('../admin_login.php');
}

// Get admin data
$adminId = $_SESSION['admin_id'];
$adminUsername = $_SESSION['admin_username'];

// Get status filter from query string
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
if (!in_array($statusFilter, ['all', 'pending', 'approved', 'rejected'])) {
    $statusFilter = 'all';
}

// Process withdrawal approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'], $_POST['withdrawal_id'])) {
    $action = sanitizeInput($_POST['action']);
    $withdrawalId = intval($_POST['withdrawal_id']);
    $message = sanitizeInput($_POST['message'] ?? '');
    
    $db = connectDB();
    
    try {
        $db->beginTransaction();
        
        // Get withdrawal details
        $stmt = $db->prepare("
            SELECT w.*, t.id as transaction_id, u.id as user_id, u.wallet_balance, u.username 
            FROM withdrawals w
            JOIN transactions t ON w.transaction_record_id = t.id
            JOIN users u ON w.user_id = u.id
            WHERE w.id = ?
        ");
        $stmt->execute([$withdrawalId]);
        $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$withdrawal) {
            throw new Exception("উইথড্র পাওয়া যায়নি");
        }
        
        if ($action == 'approve') {
            // Update transaction status
            $stmt = $db->prepare("UPDATE transactions SET status = 'approved', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$withdrawal['transaction_id']]);
            
            // Update withdrawal status
            $stmt = $db->prepare("UPDATE withdrawals SET status = 'approved', admin_message = ? WHERE id = ?");
            $stmt->execute([$message, $withdrawalId]);
            
            // Add notification
            addNotification($withdrawal['user_id'], 'উইথড্র অনুমোদিত', 
                           'আপনার ' . formatAmount($withdrawal['amount']) . ' উইথড্র অনুমোদিত হয়েছে। আপনার ' . 
                           ucfirst($withdrawal['payment_method']) . ' অ্যাকাউন্টে (' . $withdrawal['account_number'] . ') শীঘ্রই পাঠানো হবে।');
        } elseif ($action == 'reject') {
            // Update transaction status
            $stmt = $db->prepare("UPDATE transactions SET status = 'rejected', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$withdrawal['transaction_id']]);
            
            // Update withdrawal status
            $stmt = $db->prepare("UPDATE withdrawals SET status = 'rejected', admin_message = ? WHERE id = ?");
            $stmt->execute([$message, $withdrawalId]);
            
            // Refund the amount to user's wallet
            $newBalance = $withdrawal['wallet_balance'] + $withdrawal['amount'];
            $stmt = $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $withdrawal['user_id']]);
            
            // Add notification
            addNotification($withdrawal['user_id'], 'উইথড্র বাতিল', 
                           'আপনার ' . formatAmount($withdrawal['amount']) . ' উইথড্র বাতিল করা হয়েছে। পরিমাণটি আপনার ওয়ালেটে ফেরত দেওয়া হয়েছে। ' . 
                           (!empty($message) ? 'কারণ: ' . $message : ''));
        }
        
        $db->commit();
        
        // Redirect to refresh
        redirect("withdrawals.php?status=$statusFilter&success=1");
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "উইথড্র প্রসেস করতে সমস্যা হয়েছে: " . $e->getMessage();
    }
}

// Get withdrawals with filter
$db = connectDB();

$query = "
    SELECT w.*, t.status, u.username, u.account_number 
    FROM withdrawals w
    JOIN transactions t ON w.transaction_record_id = t.id
    JOIN users u ON w.user_id = u.id
";

if ($statusFilter != 'all') {
    $query .= " WHERE t.status = :status";
}

$query .= " ORDER BY w.created_at DESC";

$stmt = $db->prepare($query);

if ($statusFilter != 'all') {
    $stmt->bindParam(':status', $statusFilter);
}

$stmt->execute();
$withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>উইথড্র ম্যানেজমেন্ট - MZ Income</title>
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
                    <h1 class="h2">উইথড্র ম্যানেজমেন্ট</h1>
                </div>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>সফল!</strong> উইথড্র সফলভাবে প্রসেস করা হয়েছে।
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>ত্রুটি!</strong> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $statusFilter == 'all' ? 'active' : ''; ?>" href="?status=all">সব</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $statusFilter == 'pending' ? 'active' : ''; ?>" href="?status=pending">অপেক্ষমান</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $statusFilter == 'approved' ? 'active' : ''; ?>" href="?status=approved">অনুমোদিত</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $statusFilter == 'rejected' ? 'active' : ''; ?>" href="?status=rejected">বাতিল</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>আইডি</th>
                                        <th>ইউজার</th>
                                        <th>পরিমাণ</th>
                                        <th>মেথড</th>
                                        <th>অ্যাকাউন্ট নম্বর</th>
                                        <th>তারিখ</th>
                                        <th>স্ট্যাটাস</th>
                                        <th>অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($withdrawals) > 0): ?>
                                        <?php foreach ($withdrawals as $withdrawal): ?>
                                            <?php
                                                $statusClass = "";
                                                $statusText = "";
                                                
                                                switch ($withdrawal['status']) {
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
                                                <td><?php echo $withdrawal['id']; ?></td>
                                                <td>
                                                    <?php echo $withdrawal['username']; ?><br>
                                                    <small class="text-muted">#<?php echo $withdrawal['account_number']; ?></small>
                                                </td>
                                                <td><?php echo formatAmount($withdrawal['amount']); ?></td>
                                                <td>
                                                    <img src="../assets/img/payment/<?php echo $withdrawal['payment_method']; ?>.png" alt="<?php echo ucfirst($withdrawal['payment_method']); ?>" class="payment-icon">
                                                    <?php echo ucfirst($withdrawal['payment_method']); ?>
                                                </td>
                                                <td><?php echo $withdrawal['account_number']; ?></td>
                                                <td><?php echo date('d M Y', strtotime($withdrawal['created_at'])); ?></td>
                                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                <td>
                                                    <?php if ($withdrawal['status'] == 'pending'): ?>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-success approve-withdrawal" data-bs-toggle="modal" data-bs-target="#approveWithdrawalModal" data-id="<?php echo $withdrawal['id']; ?>" data-amount="<?php echo formatAmount($withdrawal['amount']); ?>" data-user="<?php echo $withdrawal['username']; ?>" data-method="<?php echo ucfirst($withdrawal['payment_method']); ?>" data-account="<?php echo $withdrawal['account_number']; ?>">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger reject-withdrawal" data-bs-toggle="modal" data-bs-target="#rejectWithdrawalModal" data-id="<?php echo $withdrawal['id']; ?>" data-amount="<?php echo formatAmount($withdrawal['amount']); ?>" data-user="<?php echo $withdrawal['username']; ?>">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">প্রসেস করা হয়েছে</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">কোন উইথড্র পাওয়া যায়নি</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Approve Withdrawal Modal -->
    <div class="modal fade" id="approveWithdrawalModal" tabindex="-1" aria-labelledby="approveWithdrawalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveWithdrawalModalLabel">উইথড্র অনুমোদন করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে আপনি <span id="approveWithdrawalAmount"></span> উইথড্র অনুমোদন করতে চান?</p>
                        
                        <div class="withdrawal-details">
                            <div class="detail-item">
                                <span class="label">ইউজার:</span>
                                <span class="value" id="approveWithdrawalUser"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">পেমেন্ট মেথড:</span>
                                <span class="value" id="approveWithdrawalMethod"></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">অ্যাকাউন্ট নম্বর:</span>
                                <span class="value" id="approveWithdrawalAccount"></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="approve-withdrawal-message" class="form-label">মেসেজ (ঐচ্ছিক)</label>
                            <textarea class="form-control" id="approve-withdrawal-message" name="message" rows="2"></textarea>
                        </div>
                        
                        <input type="hidden" name="withdrawal_id" id="approveWithdrawalId" value="">
                        <input type="hidden" name="action" value="approve">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-success">অনুমোদন করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Reject Withdrawal Modal -->
    <div class="modal fade" id="rejectWithdrawalModal" tabindex="-1" aria-labelledby="rejectWithdrawalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectWithdrawalModalLabel">উইথড্র বাতিল করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে আপনি <span id="rejectWithdrawalAmount"></span> উইথড্র বাতিল করতে চান?</p>
                        <p><strong>ইউজার:</strong> <span id="rejectWithdrawalUser"></span></p>
                        <p class="text-info">বাতিল করলে পরিমাণটি ইউজারের ওয়ালেটে ফেরত দেওয়া হবে।</p>
                        
                        <div class="mb-3">
                            <label for="reject-withdrawal-message" class="form-label">বাতিলের কারণ</label>
                            <textarea class="form-control" id="reject-withdrawal-message" name="message" rows="2" required></textarea>
                        </div>
                        
                        <input type="hidden" name="withdrawal_id" id="rejectWithdrawalId" value="">
                        <input type="hidden" name="action" value="reject">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                        <button type="submit" class="btn btn-danger">বাতিল করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        // Approve withdrawal
        const approveButtons = document.querySelectorAll('.approve-withdrawal');
        approveButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const amount = this.dataset.amount;
                const user = this.dataset.user;
                const method = this.dataset.method;
                const account = this.dataset.account;
                
                document.getElementById('approveWithdrawalId').value = id;
                document.getElementById('approveWithdrawalAmount').textContent = amount;
                document.getElementById('approveWithdrawalUser').textContent = user;
                document.getElementById('approveWithdrawalMethod').textContent = method;
                document.getElementById('approveWithdrawalAccount').textContent = account;
            });
        });
        
        // Reject withdrawal
        const rejectButtons = document.querySelectorAll('.reject-withdrawal');
        rejectButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const amount = this.dataset.amount;
                const user = this.dataset.user;
                
                document.getElementById('rejectWithdrawalId').value = id;
                document.getElementById('rejectWithdrawalAmount').textContent = amount;
                document.getElementById('rejectWithdrawalUser').textContent = user;
            });
        });
    </script>
</body>
</html>