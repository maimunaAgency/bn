
<?php
// admin/settings.php - Admin site settings page
require_once '../config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('../admin_login.php');
}

// Get admin data
$adminId = $_SESSION['admin_id'];
$adminUsername = $_SESSION['admin_username'];

// Process settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $siteName = sanitizeInput($_POST['site_name'] ?? 'MZ Income');
    $defaultCommissionRate = floatval($_POST['default_commission_rate'] ?? 18);
    $depositDiscount = floatval($_POST['deposit_discount'] ?? 0);
    $siteNotice = sanitizeInput($_POST['site_notice'] ?? '');
    
    $db = connectDB();
    
    try {
        $db->beginTransaction();
        
        // Update site name
        $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'site_name'");
        $stmt->execute([$siteName]);
        
        // Update default commission rate
        $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'default_commission_rate'");
        $stmt->execute([$defaultCommissionRate]);
        
        // Update deposit discount
        $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'deposit_discount'");
        $stmt->execute([$depositDiscount]);
        
        // Update site notice
        $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'site_notice'");
        $stmt->execute([$siteNotice]);
        
        $db->commit();
        $success = "সেটিংস সফলভাবে আপডেট করা হয়েছে";
    } catch (Exception $e) {
        $db->rollBack();
        $error = "সেটিংস আপডেট করতে সমস্যা হয়েছে: " . $e->getMessage();
    }
}

// Process payment method update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_payment_methods'])) {
    $bkashNumber = sanitizeInput($_POST['bkash_number'] ?? '');
    $nagadNumber = sanitizeInput($_POST['nagad_number'] ?? '');
    $rocketNumber = sanitizeInput($_POST['rocket_number'] ?? '');
    
    // Validate numbers
    if (empty($bkashNumber) || empty($nagadNumber) || empty($rocketNumber)) {
        $error = "সব পেমেন্ট মেথড নম্বর দিতে হবে";
    } elseif (!preg_match('/^01[3-9]\d{8}$/', $bkashNumber) || 
              !preg_match('/^01[3-9]\d{8}$/', $nagadNumber) || 
              !preg_match('/^01[3-9]\d{8}$/', $rocketNumber)) {
        $error = "সঠিক মোবাইল নম্বর দিন";
    } else {
        $db = connectDB();
        
        try {
            $db->beginTransaction();
            
            // Update bKash number
            $stmt = $db->prepare("UPDATE payment_methods SET account_number = ? WHERE name = 'bkash'");
            $stmt->execute([$bkashNumber]);
            
            // Update Nagad number
            $stmt = $db->prepare("UPDATE payment_methods SET account_number = ? WHERE name = 'nagad'");
            $stmt->execute([$nagadNumber]);
            
            // Update Rocket number
            $stmt = $db->prepare("UPDATE payment_methods SET account_number = ? WHERE name = 'rocket'");
            $stmt->execute([$rocketNumber]);
            
            $db->commit();
            $success = "পেমেন্ট মেথড সফলভাবে আপডেট করা হয়েছে";
        } catch (Exception $e) {
            $db->rollBack();
            $error = "পেমেন্ট মেথড আপডেট করতে সমস্যা হয়েছে: " . $e->getMessage();
        }
    }
}

// Process package update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_packages'])) {
    $goldPrice = floatval($_POST['gold_price'] ?? 1999);
    $goldValidity = intval($_POST['gold_validity'] ?? 40);
    $goldCommission = floatval($_POST['gold_commission'] ?? 30);
    
    $diamondPrice = floatval($_POST['diamond_price'] ?? 2999);
    $diamondValidity = intval($_POST['diamond_validity'] ?? 60);
    $diamondCommission = floatval($_POST['diamond_commission'] ?? 50);
    
    // Validate inputs
    if ($goldPrice <= 0 || $diamondPrice <= 0 || 
        $goldValidity <= 0 || $diamondValidity <= 0 || 
        $goldCommission <= 0 || $diamondCommission <= 0) {
        $error = "সব ইনপুট পজিটিভ হতে হবে";
    } else {
        $db = connectDB();
        
        try {
            $db->beginTransaction();
            
            // Update gold package
            $stmt = $db->prepare("UPDATE packages SET price = ?, validity_days = ?, commission_rate = ? WHERE name = 'gold'");
            $stmt->execute([$goldPrice, $goldValidity, $goldCommission]);
            
            // Update diamond package
            $stmt = $db->prepare("UPDATE packages SET price = ?, validity_days = ?, commission_rate = ? WHERE name = 'diamond'");
            $stmt->execute([$diamondPrice, $diamondValidity, $diamondCommission]);
            
            $db->commit();
            $success = "প্যাকেজ সেটিংস সফলভাবে আপডেট করা হয়েছে";
        } catch (Exception $e) {
            $db->rollBack();
            $error = "প্যাকেজ সেটিংস আপডেট করতে সমস্যা হয়েছে: " . $e->getMessage();
        }
    }
}

// Process admin password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "সব ফিল্ড পূরণ করুন";
    } elseif ($newPassword != $confirmPassword) {
        $error = "নতুন পাসওয়ার্ড মিলেনি";
    } elseif (strlen($newPassword) < 6) {
        $error = "পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে";
    } else {
        $db = connectDB();
        
        // Get admin record
        $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin || !password_verify($currentPassword, $admin['password'])) {
            $error = "বর্তমান পাসওয়ার্ড ভুল";
        } else {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $adminId]);
            
            $success = "পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে";
        }
    }
}

// Get current settings
$db = connectDB();
$settings = getSiteSettings();

// Get payment methods
$stmt = $db->prepare("SELECT * FROM payment_methods");
$stmt->execute();
$paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get packages
$stmt = $db->prepare("SELECT * FROM packages");
$stmt->execute();
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>সাইট সেটিংস - MZ Income</title>
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
                    <h1 class="h2">সাইট সেটিংস</h1>
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
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>বেসিক সেটিংস</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="site_name" class="form-label">সাইটের নাম</label>
                                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo $settings['site_name'] ?? 'MZ Income'; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="default_commission_rate" class="form-label">ডিফল্ট কমিশন রেট (%)</label>
                                        <input type="number" class="form-control" id="default_commission_rate" name="default_commission_rate" value="<?php echo $settings['default_commission_rate'] ?? 18; ?>" min="0" max="100" step="0.01" required>
                                        <div class="form-text">প্যাকেজ ছাড়া রেফারেল কমিশন রেট</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="deposit_discount" class="form-label">ডিপোজিট বোনাস (%)</label>
                                        <input type="number" class="form-control" id="deposit_discount" name="deposit_discount" value="<?php echo $settings['deposit_discount'] ?? 0; ?>" min="0" max="100" step="0.01" required>
                                        <div class="form-text">ডিপোজিটের উপর কত শতাংশ বোনাস দিতে চান (0 = বোনাস নেই)</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="site_notice" class="form-label">সাইট নোটিশ</label>
                                        <textarea class="form-control" id="site_notice" name="site_notice" rows="3"><?php echo $settings['site_notice'] ?? ''; ?></textarea>
                                        <div class="form-text">ইউজার ড্যাশবোর্ডে দেখানো হবে</div>
                                    </div>
                                    
                                    <button type="submit" name="update_settings" class="btn btn-primary">সেটিংস আপডেট করুন</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>পেমেন্ট মেথড সেটিংস</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <?php foreach ($paymentMethods as $method): ?>
                                        <div class="mb-3">
                                            <label for="<?php echo $method['name']; ?>_number" class="form-label">
                                                <img src="../assets/img/payment/<?php echo $method['name']; ?>.png" alt="<?php echo ucfirst($method['name']); ?>" class="payment-icon">
                                                <?php echo ucfirst($method['name']); ?> নম্বর
                                            </label>
                                            <input type="text" class="form-control" id="<?php echo $method['name']; ?>_number" name="<?php echo $method['name']; ?>_number" value="<?php echo $method['account_number']; ?>" required>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <button type="submit" name="update_payment_methods" class="btn btn-primary">পেমেন্ট মেথড আপডেট করুন</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>প্যাকেজ সেটিংস</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <?php 
                                        $goldPackage = null;
                                        $diamondPackage = null;
                                        
                                        foreach ($packages as $package) {
                                            if ($package['name'] == 'gold') {
                                                $goldPackage = $package;
                                            } elseif ($package['name'] == 'diamond') {
                                                $diamondPackage = $package;
                                            }
                                        }
                                    ?>
                                    
                                    <div class="package-settings-section mb-4">
                                        <h6>গোল্ড প্যাকেজ</h6>
                                        
                                        <div class="mb-3">
                                            <label for="gold_price" class="form-label">মূল্য (টাকা)</label>
                                            <input type="number" class="form-control" id="gold_price" name="gold_price" value="<?php echo $goldPackage['price']; ?>" min="1" step="1" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="gold_validity" class="form-label">মেয়াদ (দিন)</label>
                                            <input type="number" class="form-control" id="gold_validity" name="gold_validity" value="<?php echo $goldPackage['validity_days']; ?>" min="1" step="1" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="gold_commission" class="form-label">কমিশন রেট (%)</label>
                                            <input type="number" class="form-control" id="gold_commission" name="gold_commission" value="<?php echo $goldPackage['commission_rate']; ?>" min="1" max="100" step="0.01" required>
                                        </div>
                                    </div>
                                    
                                    <div class="package-settings-section mb-4">
                                        <h6>ডায়মন্ড প্যাকেজ</h6>
                                        
                                        <div class="mb-3">
                                            <label for="diamond_price" class="form-label">মূল্য (টাকা)</label>
                                            <input type="number" class="form-control" id="diamond_price" name="diamond_price" value="<?php echo $diamondPackage['price']; ?>" min="1" step="1" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="diamond_validity" class="form-label">মেয়াদ (দিন)</label>
                                            <input type="number" class="form-control" id="diamond_validity" name="diamond_validity" value="<?php echo $diamondPackage['validity_days']; ?>" min="1" step="1" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="diamond_commission" class="form-label">কমিশন রেট (%)</label>
                                            <input type="number" class="form-control" id="diamond_commission" name="diamond_commission" value="<?php echo $diamondPackage['commission_rate']; ?>" min="1" max="100" step="0.01" required>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="update_packages" class="btn btn-primary">প্যাকেজ আপডেট করুন</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>পাসওয়ার্ড পরিবর্তন করুন</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST" id="passwordForm">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">বর্তমান পাসওয়ার্ড</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">নতুন পাসওয়ার্ড</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">নতুন পাসওয়ার্ড নিশ্চিত করুন</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <div id="passwordMatch" class="form-text"></div>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-warning">পাসওয়ার্ড পরিবর্তন করুন</button>
                                </form>
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
        // Password match validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('passwordMatch');
        const passwordForm = document.getElementById('passwordForm');
        
        function validatePassword() {
            if (confirmPassword.value === newPassword.value) {
                passwordMatch.textContent = 'পাসওয়ার্ড মিলেছে';
                passwordMatch.classList.remove('text-danger');
                passwordMatch.classList.add('text-success');
                return true;
            } else {
                passwordMatch.textContent = 'পাসওয়ার্ড মিলেনি';
                passwordMatch.classList.remove('text-success');
                passwordMatch.classList.add('text-danger');
                return false;
            }
        }
        
        confirmPassword.addEventListener('input', validatePassword);
        newPassword.addEventListener('input', validatePassword);
        
        passwordForm.addEventListener('submit', function(event) {
            if (!validatePassword()) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>