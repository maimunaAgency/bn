
<?php
// admin/deposits.php - Admin deposits management page
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

// Process deposit approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'], $_POST['deposit_id'])) {
    $action = sanitizeInput($_POST['action']);
    $depositId = intval($_POST['deposit_id']);
    $message = sanitizeInput($_POST['message'] ?? '');
    
    $db = connectDB();
    
    try {
        $db->beginTransaction();
        
        // Get deposit details
        $stmt = $db->prepare("
            SELECT d.*, t.id as transaction_id, u.id as user_id, u.wallet_balance, u.username 
            FROM deposits d
            JOIN transactions t ON d.transaction_record_id = t.id
            JOIN users u ON d.user_id = u.id
            WHERE d.id = ?
        ");
        $stmt->execute([$depositId]);
        $deposit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$deposit) {
            throw new Exception("ডিপোজিট পাওয়া যায়নি");
        }
        
        if ($action == 'approve') {
            // Update transaction status
            $stmt = $db->prepare("UPDATE transactions SET status = 'approved', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$deposit['transaction_id']]);
            
            // Update deposit status
            $stmt = $db->prepare("UPDATE deposits SET status = 'approved', admin_message = ? WHERE id = ?");
            $stmt->execute([$message, $depositId]);
            
            // Calculate deposit amount with bonus if any
            $depositAmount = $deposit['amount'];
            $discountRate = getSiteSettings('deposit_discount');
            
            if ($discountRate > 0) {
                $bonusAmount = ($depositAmount * $discountRate) / 100;
                $depositAmount += $bonusAmount;
            }
            
            // Update user's wallet balance
            $newBalance = $deposit['wallet_balance'] + $depositAmount;
            $stmt = $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $deposit['user_id']]);
            
            // Add notification
            addNotification($deposit['user_id'], 'ডিপোজিট অনুমোদিত', 
                           'আপনার ' . formatAmount($deposit['amount']) . ' ডিপোজিট অনুমোদিত হয়েছে। ' . 
                           ($discountRate > 0 ? $discountRate . '% বোনাস সহ মোট ' . formatAmount($depositAmount) . ' আপনার ওয়ালেটে যোগ করা হয়েছে।' : ''));
        } elseif ($action == 'reject') {
            // Update transaction status
            $stmt = $db->prepare("UPDATE transactions SET status = 'rejected', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$deposit['transaction_id']]);
            
            // Update deposit status
            $stmt = $db->prepare("UPDATE deposits SET status = 'rejected', admin_message = ? WHERE id = ?");
            $stmt->execute([$message, $depositId]);
            
            // Add notification
            addNotification($deposit['user_id'], 'ডিপোজিট বাতিল', 
                           'আপনার ' . formatAmount($deposit['amount']) . ' ডিপোজিট বাতিল করা হয়েছে। ' . 
                           (!empty($message) ? 'কারণ: ' . $message : ''));
        }
        
        $db->commit();
        
        // Redirect to refresh
        redirect("deposits.php?status=$statusFilter&success=1");
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "ডিপোজিট প্রসেস করতে সমস্যা হয়েছে: " . $e->getMessage();
    }
}

// Get deposits with filter
$db = connectDB();

$query = "
    SELECT d.*, t.status, u.username, u.account_number 
    FROM deposits d
    JOIN transactions t ON d.transaction_record_id = t.id
    JOIN users u ON d.user_id = u.id
";

if ($statusFilter != 'all') {
    $query .= " WHERE t.status = :status";
}

$query .= " ORDER BY d.created_at DESC";

$stmt = $db->prepare($query);

if ($statusFilter != 'all') {
    $stmt->bindParam(':status', $statusFilter);
}

$stmt->execute();
$deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ডিপোজিট ম্যানেজমেন্ট - MZ Income</title>
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
                    <h1 class="h2">ডিপোজিট ম্যানেজমেন্ট</h1>
                </div>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>সফল!</strong> ডিপোজিট সফলভাবে প্রসেস করা হয়েছে।
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
                                        <th>ট্রানজেকশন আইডি</th>
                                        <th>প্রুফ</th>
                                        <th>তারিখ</th>
                                        <th>স্ট্যাটাস</th>
                                        <th>অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($deposits) > 0): ?>
                                        <?php foreach ($deposits as $deposit): ?>
                                            <?php
                                                $statusClass = "";
                                                $statusText = "";
                                                
                                                switch ($deposit['status']) {
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
                                                <td><?php echo $deposit['id']; ?></td>
                                                <td>
                                                    <?php echo $deposit['username']; ?><br>
                                                    <small class="text-muted">#<?php echo $deposit['account_number']; ?></small>
                                                </td>
                                                <td><?php echo formatAmount($deposit['amount']); ?></td>
                                                <td>
                                                    <img src="../assets/img/payment/<?php echo $deposit['payment_method']; ?>.png" alt="<?php echo ucfirst($deposit['payment_method']); ?>" class="payment-icon">
                                                    <?php echo ucfirst($deposit['payment_method']); ?>
                                                </td>
                                                <td><?php echo $deposit['transaction_id']; ?></td>
                                                <td>
                                                    <?php if ($deposit['screenshot']): ?>
                                                        <a href="#" class="btn btn-sm btn-info view-screenshot" data-bs-toggle="modal" data-bs-target="#screenshotModal" data-src="../<?php echo DEPOSIT_PROOF_DIR . $deposit['screenshot']; ?>">
                                                            <i class="fas fa-image"></i> দেখুন
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">নেই</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($deposit['created_at'])); ?></td>
                                                <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                <td>
                                                    <?php if ($deposit['status'] == 'pending'): ?>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-success approve-deposit" data-bs-toggle="modal" data-bs-target="#approveModal" data-id="<?php echo $deposit['id']; ?>" data-amount="<?php echo formatAmount($deposit['amount']); ?>" data-user="<?php echo $deposit['username']; ?>">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger reject-deposit" data-bs-toggle="modal" data-bs-target="#rejectModal" data-id="<?php echo $deposit['id']; ?>" data-amount="<?php echo formatAmount($deposit['amount']); ?>" data-user="<?php echo $deposit['username']; ?>">
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
                                            <td colspan="9" class="text-center">কোন ডিপোজিট পাওয়া যায়নি</td>
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
    
    <!-- Screenshot Modal -->
    <div class="modal fade" id="screenshotModal" tabindex="-1" aria-labelledby="screenshotModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="screenshotModalLabel">পেমেন্ট স্ক্রিনশট</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" id="screenshotImage" class="img-fluid" alt="Payment Screenshot">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveModalLabel">ডিপোজিট অনুমোদন করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে আপনি <span id="approveAmount"></span> ডিপোজিট অনুমোদন করতে চান?</p>
                        <p><strong>ইউজার:</strong> <span id="approveUser"></span></p>
                        
                        <div class="mb-3">
                            <label for="approve-message" class="form-label">মেসেজ (ঐচ্ছিক)</label>
                            <textarea class="form-control" id="approve-message" name="message" rows="2"></textarea>
                        </div>
                        
                        <input type="hidden" name="deposit_id" id="approveDepositId" value="">
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
    
    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel">ডিপোজিট বাতিল করুন</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>আপনি কি নিশ্চিত যে আপনি <span id="rejectAmount"></span> ডিপোজিট বাতিল করতে চান?</p>
                        <p><strong>ইউজার:</strong> <span id="rejectUser"></span></p>
                        
                        <div class="mb-3">
                            <label for="reject-message" class="form-label">বাতিলের কারণ</label>
                            <textarea class="form-control" id="reject-message" name="message" rows="2" required></textarea>
                        </div>
                        
                        <input type="hidden" name="deposit_id" id="rejectDepositId" value="">
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
        // View screenshot
        const viewScreenshotButtons = document.querySelectorAll('.view-screenshot');
        viewScreenshotButtons.forEach(button => {
            button.addEventListener('click', function() {
                const src = this.dataset.src;
                document.getElementById('screenshotImage').src = src;
            });
        });
        
        // Approve deposit
        const approveButtons = document.querySelectorAll('.approve-deposit');
        approveButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const amount = this.dataset.amount;
                const user = this.dataset.user;
                
                document.getElementById('approveDepositId').value = id;
                document.getElementById('approveAmount').textContent = amount;
                document.getElementById('approveUser').textContent = user;
            });
        });
        
        // Reject deposit
        const rejectButtons = document.querySelectorAll('.reject-deposit');
        rejectButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const amount = this.dataset.amount;
                const user = this.dataset.user;
                
                document.getElementById('rejectDepositId').value = id;
                document.getElementById('rejectAmount').textContent = amount;
                document.getElementById('rejectUser').textContent = user;
            });
        });
    </script>
</body>
</html>