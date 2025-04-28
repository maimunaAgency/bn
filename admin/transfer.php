<?php
// transfer.php - User fund transfer page
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

$errors = [];
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $receiverAccount = sanitizeInput($_POST['receiver_account'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    
    // Validate inputs
    if (empty($receiverAccount)) {
        $errors[] = "প্রাপকের অ্যাকাউন্ট নম্বর প্রয়োজন";
    }
    
    if ($amount <= 0) {
        $errors[] = "অবৈধ পরিমাণ";
    }
    
    if ($amount > $user['wallet_balance']) {
        $errors[] = "আপনার ওয়ালেটে পর্যাপ্ত ব্যালেন্স নেই";
    }
    
    // If no errors, get receiver details
    if (empty($errors)) {
        $receiver = getUserByAccountNumber($receiverAccount);
        
        if (!$receiver) {
            $errors[] = "প্রাপকের অ্যাকাউন্ট পাওয়া যায়নি";
        } elseif ($receiver['id'] == $userId) {
            $errors[] = "আপনি নিজের অ্যাকাউন্টে ট্রান্সফার করতে পারবেন না";
        }
    }
    
    // If no errors, process transfer
    if (empty($errors)) {
        $db = connectDB();
        
        try {
            $db->beginTransaction();
            
            // Create transaction record for sender (deduct)
            $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, status, reference_id, notes) 
                                 VALUES (?, 'transfer_sent', ?, 'approved', ?, 'Transfer to " . $receiver['username'] . " (" . $receiver['account_number'] . ")')");
            $stmt->execute([$userId, $amount, $receiver['id']]);
            $senderTransactionId = $db->lastInsertId();
            
            // Create transaction record for receiver (add)
            $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, status, reference_id, notes) 
                                 VALUES (?, 'transfer_received', ?, 'approved', ?, 'Transfer from " . $user['username'] . " (" . $user['account_number'] . ")')");
            $stmt->execute([$receiver['id'], $amount, $userId]);
            $receiverTransactionId = $db->lastInsertId();
            
            // Create transfer record
            $stmt = $db->prepare("INSERT INTO transfers (sender_id, receiver_id, amount, sent_transaction_id, received_transaction_id) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $receiver['id'], $amount, $senderTransactionId, $receiverTransactionId]);
            
            // Update sender's wallet balance
            $stmt = $db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);
            
            // Update receiver's wallet balance
            $stmt = $db->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt->execute([$amount, $receiver['id']]);
            
            // Add notification for sender
            addNotification($userId, 'ফান্ড ট্রান্সফার সফল', 
                           'আপনি ' . $receiver['username'] . ' (' . $receiver['account_number'] . ') কে ' . formatAmount($amount) . ' সফলভাবে ট্রান্সফার করেছেন।');
            
            // Add notification for receiver
            addNotification($receiver['id'], 'ফান্ড প্রাপ্তি', 
                           $user['username'] . ' (' . $user['account_number'] . ') থেকে ' . formatAmount($amount) . ' প্রাপ্ত হয়েছে।');
            
            $db->commit();
            
            $success = "ফান্ড ট্রান্সফার সফল হয়েছে। " . formatAmount($amount) . " " . $receiver['username'] . " কে ট্রান্সফার করা হয়েছে।";
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "দুঃখিত, ট্রান্সফার প্রসেস করতে সমস্যা হয়েছে। পরে আবার চেষ্টা করুন।";
            error_log("Transfer error: " . $e->getMessage());
        }
    }
}

// Get transfers
$db = connectDB();
$stmt = $db->prepare("
    SELECT t.*, 
           sender.username as sender_username, sender.account_number as sender_account,
           receiver.username as receiver_username, receiver.account_number as receiver_account,
           st.created_at as transfer_date
    FROM transfers t 
    JOIN users sender ON t.sender_id = sender.id 
    JOIN users receiver ON t.receiver_id = receiver.id 
    JOIN transactions st ON t.sent_transaction_id = st.id
    WHERE t.sender_id = ? OR t.receiver_id = ? 
    ORDER BY st.created_at DESC 
    LIMIT 10
");
$stmt->execute([$userId, $userId]);
$transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ফান্ড ট্রান্সফার - MZ Income</title>
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
                    <h1 class="h2">ফান্ড ট্রান্সফার</h1>
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
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>ফান্ড ট্রান্সফার করুন</h5>
                            </div>
                            <div class="card-body">
                                <div class="wallet-balance-info mb-4">
                                    <p>আপনার বর্তমান ওয়ালেট ব্যালেন্স:</p>
                                    <h4><?php echo formatAmount($user['wallet_balance']); ?></h4>
                                </div>
                                
                                <form method="POST" action="" id="transferForm">
                                    <div class="mb-3">
                                        <label for="receiver_account" class="form-label">প্রাপকের অ্যাকাউন্ট নম্বর</label>
                                        <input type="text" class="form-control" id="receiver_account" name="receiver_account" required>
                                        <div id="receiverInfo" class="mt-2"></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">ট্রান্সফার পরিমাণ (টাকা)</label>
                                        <input type="number" class="form-control" id="amount" name="amount" min="10" max="<?php echo $user['wallet_balance']; ?>" step="1" required>
                                    </div>
                                    
                                    <div class="alert alert-info mb-4">
                                        <p><strong>নোট:</strong></p>
                                        <ul>
                                            <li>ন্যূনতম ট্রান্সফার পরিমাণ: ১০ টাকা</li>
                                            <li>সঠিক অ্যাকাউন্ট নম্বর দিতে ভুলবেন না</li>
                                            <li>ট্রান্সফার করার আগে প্রাপকের তথ্য যাচাই করুন</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">ট্রান্সফার করুন</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>ট্রান্সফার হিস্ট্রি</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($transfers) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>তারিখ</th>
                                                    <th>ট্রান্সফার</th>
                                                    <th>পরিমাণ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($transfers as $transfer): ?>
                                                    <?php
                                                        $isSender = $transfer['sender_id'] == $userId;
                                                        $transferType = $isSender ? 'sent' : 'received';
                                                        $otherParty = $isSender ? $transfer['receiver_username'] : $transfer['sender_username'];
                                                        $otherPartyAccount = $isSender ? $transfer['receiver_account'] : $transfer['sender_account'];
                                                        $amountClass = $isSender ? 'text-danger' : 'text-success';
                                                        $amountPrefix = $isSender ? '-' : '+';
                                                        $iconClass = $isSender ? 'fa-arrow-right text-danger' : 'fa-arrow-left text-success';
                                                    ?>
                                                    <tr>
                                                        <td><?php echo date('d M Y', strtotime($transfer['transfer_date'])); ?></td>
                                                        <td>
                                                            <i class="fas <?php echo $iconClass; ?> me-1"></i>
                                                            <?php echo $transferType == 'sent' ? 'প্রেরিত' : 'প্রাপ্ত'; ?><br>
                                                            <small class="text-muted"><?php echo $otherParty; ?> (<?php echo $otherPartyAccount; ?>)</small>
                                                        </td>
                                                        <td class="<?php echo $amountClass; ?>"><?php echo $amountPrefix . formatAmount($transfer['amount']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center">কোন ট্রান্সফার নেই</p>
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
        // Check receiver account info
        document.getElementById('receiver_account').addEventListener('blur', function() {
            const accountNumber = this.value.trim();
            const receiverInfo = document.getElementById('receiverInfo');
            
            if (accountNumber.length > 0) {
                // Send AJAX request to get receiver info
                fetch('ajax/check_account.php?account=' + accountNumber)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        receiverInfo.innerHTML = `
                            <div class="alert alert-success mb-0">
                                <strong>প্রাপক:</strong> ${data.username}
                            </div>
                        `;
                    } else {
                        receiverInfo.innerHTML = `
                            <div class="alert alert-danger mb-0">
                                <strong>ত্রুটি:</strong> ${data.message}
                            </div>
                        `;
                    }
                });
            } else {
                receiverInfo.innerHTML = '';
            }
        });
    </script>
</body>
</html>
