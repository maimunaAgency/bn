<?php
// admin/users.php - Admin user management page
require_once '../config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('../admin_login.php');
}

// Get admin data
$adminId = $_SESSION['admin_id'];
$adminUsername = $_SESSION['admin_username'];

// Search user functionality
$searchTerm = '';
$users = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = sanitizeInput($_GET['search']);
    
    $db = connectDB();
    $stmt = $db->prepare("
        SELECT * FROM users 
        WHERE username LIKE ? OR account_number LIKE ? OR mobile LIKE ? OR full_name LIKE ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $searchPattern = "%$searchTerm%";
    $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Get recent users if no search
    $db = connectDB();
    $stmt = $db->prepare("
        SELECT * FROM users 
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Process user action if any
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $action = sanitizeInput($_POST['action']);
    $userId = intval($_POST['user_id']);
    $message = sanitizeInput($_POST['message'] ?? '');
    
    $db = connectDB();
    
    // Get user
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = "ইউজার পাওয়া যায়নি";
    } else {
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
            }
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $error = "ত্রুটি: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ইউজার ম্যানেজমেন্ট - MZ Income</title>
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
                    <h1 class="h2">ইউজার ম্যানেজমেন্ট</h1>
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
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3 mb-4">
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="search" placeholder="ইউজারনেম, অ্যাকাউন্ট নম্বর বা মোবাইল নম্বর দিয়ে সার্চ করুন" value="<?php echo $searchTerm; ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> সার্চ
                                </button>
                                <?php if (!empty($searchTerm)): ?>
                                    <a href="users.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> রিসেট
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>আইডি</th>
                                        <th>ইউজারনেম</th>
                                        <th>অ্যাকাউন্ট নম্বর</th>
                                        <th>মোবাইল</th>
                                        <th>ব্যালেন্স</th>
                                        <th>প্যাকেজ</th>
                                        <th>যোগদান</th>
                                        <th>অ্যাকশন</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($users) > 0): ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo $user['username']; ?></td>
                                                <td><?php echo $user['account_number']; ?></td>
                                                <td><?php echo $user['mobile']; ?></td>
                                                <td><?php echo formatAmount($user['wallet_balance']); ?></td>
                                                <td>
                                                    <?php if ($user['active_package'] != 'none'): ?>
                                                        <span class="badge bg-success"><?php echo ucfirst($user['active_package']); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">বেসিক</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="user_details.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-primary send-message" data-bs-toggle="modal" data-bs-target="#sendMessageModal" data-id="<?php echo $user['id']; ?>" data-username="<?php echo $user['username']; ?>">
                                                            <i class="fas fa-envelope"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-success add-balance" data-bs-toggle="modal" data-bs-target="#addBalanceModal" data-id="<?php echo $user['id']; ?>" data-username="<?php echo $user['username']; ?>">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger deduct-balance" data-bs-toggle="modal" data-bs-target="#deductBalanceModal" data-id="<?php echo $user['id']; ?>" data-username="<?php echo $user['username']; ?>" data-balance="<?php echo $user['wallet_balance']; ?>">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">কোন ইউজার পাওয়া যায়নি</td>
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
                        <p>ইউজার <strong id="messageUsername"></strong> কে মেসেজ পাঠান</p>
                        
                        <div class="mb-3">
                            <label for="message-text" class="form-label">মেসেজ</label>
                            <textarea class="form-control" id="message-text" name="message" rows="3" required></textarea>
                        </div>
                        
                        <input type="hidden" name="user_id" id="messageUserId" value="">
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
                        <p>ইউজার <strong id="addBalanceUsername"></strong> এর ওয়ালেটে ব্যালেন্স যোগ করুন</p>
                        
                        <div class="mb-3">
                            <label for="add-amount" class="form-label">পরিমাণ</label>
                            <input type="number" class="form-control" id="add-amount" name="amount" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="add-balance-message" class="form-label">কারণ (ঐচ্ছিক)</label>
                            <textarea class="form-control" id="add-balance-message" name="message" rows="2"></textarea>
                        </div>
                        
                        <input type="hidden" name="user_id" id="addBalanceUserId" value="">
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
                        <p>ইউজার <strong id="deductBalanceUsername"></strong> এর ওয়ালেট থেকে ব্যালেন্স কাটুন</p>
                        <p>বর্তমান ব্যালেন্স: <strong id="currentBalance"></strong></p>
                        
                        <div class="mb-3">
                            <label for="deduct-amount" class="form-label">পরিমাণ</label>
                            <input type="number" class="form-control" id="deduct-amount" name="amount" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deduct-balance-message" class="form-label">কারণ</label>
                            <textarea class="form-control" id="deduct-balance-message" name="message" rows="2" required></textarea>
                        </div>
                        
                        <input type="hidden" name="user_id" id="deductBalanceUserId" value="">
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
    <script>
        // Send message
        const sendMessageButtons = document.querySelectorAll('.send-message');
        sendMessageButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.id;
                const username = this.dataset.username;
                
                document.getElementById('messageUserId').value = userId;
                document.getElementById('messageUsername').textContent = username;
            });
        });
        
        // Add balance
        const addBalanceButtons = document.querySelectorAll('.add-balance');
        addBalanceButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.id;
                const username = this.dataset.username;
                
                document.getElementById('addBalanceUserId').value = userId;
                document.getElementById('addBalanceUsername').textContent = username;
            });
        });
        
        // Deduct balance
        const deductBalanceButtons = document.querySelectorAll('.deduct-balance');
        deductBalanceButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.id;
                const username = this.dataset.username;
                const balance = this.dataset.balance;
                
                document.getElementById('deductBalanceUserId').value = userId;
                document.getElementById('deductBalanceUsername').textContent = username;
                document.getElementById('currentBalance').textContent = '৳ ' + parseFloat(balance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('deduct-amount').max = balance;
            });
        });
    </script>
</body>
</html>
