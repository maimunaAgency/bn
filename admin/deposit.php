<?php
// deposit.php - User deposit page
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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['step'])) {
    $step = $_POST['step'];
    
    if ($step == '1') {
        // Step 1: Get amount and payment method
        $amount = floatval($_POST['amount'] ?? 0);
        $paymentMethod = sanitizeInput($_POST['payment_method'] ?? '');
        
        // Validate inputs
        if ($amount <= 0) {
            $errors[] = "অবৈধ পরিমাণ";
        }
        
        if (!in_array($paymentMethod, ['bkash', 'nagad', 'rocket'])) {
            $errors[] = "অবৈধ পেমেন্ট মেথড";
        }
        
        // If no errors, proceed to step 2
        if (empty($errors)) {
            $selectedMethod = getPaymentMethod($paymentMethod);
            
            if (!$selectedMethod) {
                $errors[] = "পেমেন্ট মেথড পাওয়া যায়নি";
            } else {
                // Check if there's a deposit discount
                $discountRate = getSiteSettings('deposit_discount');
                $finalAmount = $amount;
                $discountAmount = 0;
                
                if ($discountRate > 0) {
                    $discountAmount = ($amount * $discountRate) / 100;
                    $finalAmount = $amount + $discountAmount;
                }
                
                // Store data in session for step 2
                $_SESSION['deposit_data'] = [
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'account_number' => $selectedMethod['account_number'],
                    'discount_rate' => $discountRate,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount
                ];
                
                // Proceed to step 2
                $step = '2';
            }
        }
    } elseif ($step == '2') {
        // Step 2: Get transaction ID and process deposit
        $transactionId = sanitizeInput($_POST['transaction_id'] ?? '');
        
        // Validate transaction ID
        if (empty($transactionId)) {
            $errors[] = "ট্রানজেকশন আইডি প্রয়োজন";
        }
        
        // Check if deposit data exists in session
        if (!isset($_SESSION['deposit_data'])) {
            $errors[] = "ডিপোজিট ডাটা পাওয়া যায়নি। আবার চেষ্টা করুন।";
        }
        
        // If no errors, process deposit
        if (empty($errors)) {
            $depositData = $_SESSION['deposit_data'];
            $amount = $depositData['amount'];
            $paymentMethod = $depositData['payment_method'];
            
            $db = connectDB();
            
            try {
                $db->beginTransaction();
                
                // Create transaction record
                $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, status, notes) 
                                     VALUES (?, 'deposit', ?, 'pending', 'Deposit via " . ucfirst($paymentMethod) . "')");
                $stmt->execute([$userId, $amount]);
                $transactionId = $db->lastInsertId();
                
                // Process uploaded screenshot if exists
                $screenshotFile = null;
                if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] == UPLOAD_ERR_OK) {
                    $screenshotFile = processUploadedFile('payment_screenshot', DEPOSIT_PROOF_DIR);
                    
                    if (!$screenshotFile) {
                        throw new Exception("স্ক্রিনশট আপলোড করতে সমস্যা হয়েছে");
                    }
                }
                
                // Create deposit record
                $stmt = $db->prepare("INSERT INTO deposits (user_id, amount, payment_method, transaction_id, screenshot, transaction_record_id) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $userId, 
                    $amount, 
                    $paymentMethod, 
                    $_POST['transaction_id'],
                    $screenshotFile,
                    $transactionId
                ]);
                
                // Add notification
                addNotification($userId, 'ডিপোজিট অনুরোধ সফল', 
                               'আপনার ' . formatAmount($amount) . ' ডিপোজিট অনুরোধ সফলভাবে জমা হয়েছে। অনুমোদনের জন্য অপেক্ষা করুন।');
                
                $db->commit();
                
                // Clear deposit data from session
                unset($_SESSION['deposit_data']);
                
                $success = "আপনার ডিপোজিট অনুরোধ সফলভাবে জমা হয়েছে। অনুমোদনের জন্য অপেক্ষা করুন।";
                
                // Reset step
                $step = '1';
                
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = "দুঃখিত, ডিপোজিট প্রসেস করতে সমস্যা হয়েছে। পরে আবার চেষ্টা করুন।";
                error_log("Deposit error: " . $e->getMessage());
            }
        }
    }
} else {
    // Default step
    $step = '1';
    
    // Clear any existing deposit data
    if (isset($_SESSION['deposit_data'])) {
        unset($_SESSION['deposit_data']);
    }
}

// Get pending deposits
$db = connectDB();
$stmt = $db->prepare("
    SELECT d.*, t.status 
    FROM deposits d 
    JOIN transactions t ON d.transaction_record_id = t.id 
    WHERE d.user_id = ? 
    ORDER BY d.created_at DESC 
    LIMIT 10
");
$stmt->execute([$userId]);
$pendingDeposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ডিপোজিট - MZ Income</title>
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
                    <h1 class="h2">ডিপোজিট</h1>
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
                                <h5><?php echo $step == '1' ? 'ডিপোজিট করুন' : 'পেমেন্ট কনফার্ম করুন'; ?></h5>
                            </div>
                            <div class="card-body">
                                <?php if ($step == '1'): ?>
                                    <!-- Step 1: Amount and payment method selection -->
                                    <form method="POST" action="">
                                        <input type="hidden" name="step" value="1">
                                        
                                        <div class="mb-3">
                                            <label for="amount" class="form-label">পরিমাণ (টাকা)</label>
                                            <input type="number" class="form-control" id="amount" name="amount" min="10" step="1" required>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">পেমেন্ট মেথড নির্বাচন করুন</label>
                                            <div class="payment-methods">
                                                <?php foreach ($paymentMethods as $method): ?>
                                                    <div class="form-check payment-method-item">
                                                        <input class="form-check-input" type="radio" name="payment_method" id="<?php echo $method['name']; ?>" value="<?php echo $method['name']; ?>" required>
                                                        <label class="form-check-label" for="<?php echo $method['name']; ?>">
                                                            <img src="assets/img/payment/<?php echo $method['name']; ?>.png" alt="<?php echo ucfirst($method['name']); ?>" class="payment-logo">
                                                            <span><?php echo ucfirst($method['name']); ?></span>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        
                                        <?php 
                                            $discountRate = getSiteSettings('deposit_discount');
                                            if ($discountRate > 0): 
                                        ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-gift me-2"></i> ডিপোজিটের উপর <?php echo $discountRate; ?>% বোনাস পাবেন!
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">পরবর্তী ধাপ</button>
                                        </div>
                                    </form>
                                <?php elseif ($step == '2'): ?>
                                    <!-- Step 2: Payment confirmation -->
                                    <div class="payment-confirmation mb-4">
                                        <div class="selected-payment-method text-center mb-4">
                                            <img src="assets/img/payment/<?php echo $_SESSION['deposit_data']['payment_method']; ?>.png" alt="<?php echo ucfirst($_SESSION['deposit_data']['payment_method']); ?>" class="payment-logo-large mb-3">
                                            <h4><?php echo ucfirst($_SESSION['deposit_data']['payment_method']); ?></h4>
                                        </div>
                                        
                                        <div class="payment-details">
                                            <div class="payment-detail-item">
                                                <span class="label">আপনার পরিমাণ:</span>
                                                <span class="value"><?php echo formatAmount($_SESSION['deposit_data']['amount']); ?></span>
                                            </div>
                                            
                                            <?php if ($_SESSION['deposit_data']['discount_rate'] > 0): ?>
                                                <div class="payment-detail-item">
                                                    <span class="label">বোনাস (<?php echo $_SESSION['deposit_data']['discount_rate']; ?>%):</span>
                                                    <span class="value text-success">+<?php echo formatAmount($_SESSION['deposit_data']['discount_amount']); ?></span>
                                                </div>
                                                
                                                <div class="payment-detail-item total">
                                                    <span class="label">মোট পাবেন:</span>
                                                    <span class="value"><?php echo formatAmount($_SESSION['deposit_data']['final_amount']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="payment-detail-item">
                                                <span class="label">পেমেন্ট একাউন্ট:</span>
                                                <div class="account-number-container">
                                                    <span id="accountNumber"><?php echo $_SESSION['deposit_data']['account_number']; ?></span>
                                                    <button id="copyAccountNumber" type="button" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="payment-instructions alert alert-primary mt-3">
                                            <p><strong>পেমেন্ট নির্দেশনা:</strong></p>
                                            <ol>
                                                <li>উপরের <?php echo ucfirst($_SESSION['deposit_data']['payment_method']); ?> নম্বরে <?php echo formatAmount($_SESSION['deposit_data']['amount']); ?> টাকা সেন্ড মানি করুন।</li>
                                                <li>আপনার ট্রানজেকশন আইডি নোট করুন।</li>
                                                <li>নিচের ফর্মে ট্রানজেকশন আইডি লিখুন এবং পেমেন্ট স্ক্রিনশট (ঐচ্ছিক) আপলোড করুন।</li>
                                                <li>ডিপোজিট কমপ্লিট বাটনে ক্লিক করুন।</li>
                                            </ol>
                                        </div>
                                    </div>
                                    
                                    <form method="POST" action="" enctype="multipart/form-data">
                                        <input type="hidden" name="step" value="2">
                                        
                                        <div class="mb-3">
                                            <label for="transaction_id" class="form-label">ট্রানজেকশন আইডি</label>
                                            <input type="text" class="form-control" id="transaction_id" name="transaction_id" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="payment_screenshot" class="form-label">পেমেন্ট স্ক্রিনশট (ঐচ্ছিক)</label>
                                            <input type="file" class="form-control" id="payment_screenshot" name="payment_screenshot">
                                            <div class="form-text">JPG, PNG ফরম্যাট (সর্বোচ্চ 2MB)</div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between">
                                            <a href="deposit.php" class="btn btn-outline-secondary">বাতিল করুন</a>
                                            <button type="submit" class="btn btn-primary">ডিপোজিট কমপ্লিট করুন</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-5">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>ডিপোজিট হিস্ট্রি</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($pendingDeposits) > 0): ?>
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
                                                <?php foreach ($pendingDeposits as $deposit): ?>
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
                                                        <td><?php echo date('d M Y', strtotime($deposit['created_at'])); ?></td>
                                                        <td><?php echo formatAmount($deposit['amount']); ?></td>
                                                        <td>
                                                            <img src="assets/img/payment/<?php echo $deposit['payment_method']; ?>.png" alt="<?php echo ucfirst($deposit['payment_method']); ?>" class="payment-icon">
                                                        </td>
                                                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center">কোন ডিপোজিট নেই</p>
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
        // Copy account number to clipboard
        document.addEventListener('DOMContentLoaded', function() {
            const copyAccountNumberBtn = document.getElementById('copyAccountNumber');
            
            if (copyAccountNumberBtn) {
                copyAccountNumberBtn.addEventListener('click', function() {
                    const accountNumber = document.getElementById('accountNumber').textContent;
                    navigator.clipboard.writeText(accountNumber).then(function() {
                        copyAccountNumberBtn.innerHTML = '<i class="fas fa-check"></i>';
                        setTimeout(function() {
                            copyAccountNumberBtn.innerHTML = '<i class="fas fa-copy"></i>';
                        }, 2000);
                    });
                });
            }
        });
    </script>
</body>
</html>

