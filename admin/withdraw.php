<?php
// withdraw.php - User withdraw page
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Get active payment methods
$paymentMethods = getAllPaymentMethods();

$errors = [];
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $amount = floatval($_POST['amount'] ?? 0);
    $paymentMethod = sanitizeInput($_POST['payment_method'] ?? '');
    $accountNumber = sanitizeInput($_POST['account_number'] ?? '');
    
    // Validate inputs
    if ($amount <= 0) {
        $errors[] = "অবৈধ পরিমাণ";
    }
    
    if ($amount > $user['wallet_balance']) {
        $errors[] = "আপনার ওয়ালেটে পর্যাপ্ত ব্যালেন্স নেই";
    }
    
    if (!in_array($paymentMethod, ['bkash', 'nagad', 'rocket'])) {
        $errors[] = "অবৈধ পেমেন্ট মেথড";
    }
    
    if (empty($accountNumber)) {
        $errors[] = "অ্যাকাউন্ট নম্বর প্রয়োজন";
    } elseif (!preg_match('/^01[3-9]\d{8}$/', $accountNumber)) {
        $errors[] = "সঠিক অ্যাকাউন্ট নম্বর দিন";
    }
    
    // If no errors, process withdraw
    if (empty($errors)) {
        $db = connectDB();
        
        try {
            $db->beginTransaction();
            
            // Create transaction record
            $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, status, notes) 
                                 VALUES (?, 'withdraw', ?, 'pending', 'Withdraw via " . ucfirst($paymentMethod) . "')");
            $stmt->execute([$userId, $amount]);
            $transactionId = $db->lastInsertId();
            
            // Create withdrawal record
            $stmt = $db->prepare("INSERT INTO withdrawals (user_id, amount, payment_method, account_number, transaction_record_id) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $amount, $paymentMethod, $accountNumber, $transactionId]);
            
            // Update user's wallet balance (reserved but not deducted yet)
            $stmt = $db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);
            
            // Add notification
            addNotification($userId, 'উইথড্র অনুরোধ সফল', 
                           'আপনার ' . formatAmount($amount) . ' উইথড্র অনুরোধ সফলভাবে জমা হয়েছে। অনুমোদনের জন্য অপেক্ষা করুন।');
            
            $db->commit();
            
            $success = "আপনার উইথড্র অনুরোধ সফলভাবে জমা হয়েছে। অনুমোদনের জন্য অপেক্ষা করুন।";
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "দুঃখিত, উইথড্র প্রসেস করতে সমস্যা হয়েছে। পরে আবার চেষ্টা করুন।";
            error_log("Withdraw error: " . $e->getMessage());
        }
    }
}

// Get withdrawals
$db = connectDB();
$stmt = $db->prepare("
    SELECT w.*, t.status, t.updated_at as processed_at, w.admin_message
    FROM withdrawals w 
    JOIN transactions t ON w.transaction_record_id = t.id 
    WHERE w.user_id = ? 
    ORDER BY w.created_at DESC 
    LIMIT 10
");
$stmt->execute([$userId]);
$withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>উইথড্র - MZ Income</title>
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
                    <h1 class="h2">উইথড্র</h1>
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
                
                <div class="row">
                    <div class="col-md-7">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>টাকা উইথড্র করুন</h5>
                            </div>
                            <div class="card-body">
                                <div class="wallet-balance-info mb-4">
                                    <p>আপনার বর্তমান ওয়ালেট ব্যালেন্স:</p>
                                    <h4><?php echo formatAmount($user['wallet_balance']); ?></h4>
                                </div>
                                
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">উইথড্র পরিমাণ (টাকা)</label>
                                        <input type="number" class="form-control" id="amount" name="amount" min="10" max="<?php echo $user['wallet_balance']; ?>" step="1" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">পেমেন্ট মেথড নির্বাচন করুন</label>
                                        <div class="payment-methods">
                                            <?php foreach ($paymentMethods as $method): ?>
                                                <div class="form-check payment-method-item">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="withdraw_<?php echo $method['name']; ?>" value="<?php echo $method['name']; ?>" required>
                                                    <label class="form-check-label" for="withdraw_<?php echo $method['name']; ?>">
                                                        <img src="assets/img/payment/<?php echo $method['name']; ?>.png" alt="<?php echo ucfirst($method['name']); ?>" class="payment-logo">
                                                        <span><?php echo ucfirst($method['name']); ?></span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="account_number" class="form-label">আপনার <?php echo ucfirst($method['name'] ?? ''); ?> নম্বর</label>
                                        <input type="text" class="form-control" id="account_number" name="account_number" placeholder="017xxxxxxxx" required>
                                    </div>
                                    
                                    <div class="alert alert-info mb-4">
                                        <p><strong>নোট:</strong></p>
                                        <ul>
                                            <li>ন্যূনতম উইথড্র পরিমাণ: ৫০ টাকা</li>
                                            <li>উইথড্র প্রসেস হতে ২৪ ঘন্টা সময় লাগতে পারে</li>
                                            <li>সঠিক মোবাইল নম্বর দিতে ভুলবেন না</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">উইথড্র অনুরোধ জমা দিন</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-5">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>উইথড্র হিস্ট্রি</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($withdrawals) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>তারিখ</th>
                                                    <th>পরিমাণ</th>
                                                    <th>মেথড</th>
                                                    <th>স্ট্যাটাস</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($withdrawals as $withdraw): ?>
                                                    <?php
                                                        $statusClass = "";
                                                        $statusText = "";
                                                        
                                                        switch ($withdraw['status']) {
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
                                                    <tr data-bs-toggle="tooltip" title="<?php echo $withdraw['admin_message'] ?: 'কোন মেসেজ নেই'; ?>">
                                                        <td><?php echo date('d M Y', strtotime($withdraw['created_at'])); ?></td>
                                                        <td><?php echo formatAmount($withdraw['amount']); ?></td>
                                                        <td>
                                                            <img src="assets/img/payment/<?php echo $withdraw['payment_method']; ?>.png" alt="<?php echo ucfirst($withdraw['payment_method']); ?>" class="payment-icon">
                                                        </td>
                                                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center">কোন উইথড্র নেই</p>
                                <?php endif; ?>
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
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>