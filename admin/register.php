<?php
// Fixed register.php - Corrected registration process
require_once 'config.php';

$errors = [];
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $mobile = sanitizeInput($_POST['mobile'] ?? '');
    $referCode = sanitizeInput($_POST['refer_code'] ?? '');
    
    // Validate inputs
    if (empty($username)) {
        $errors[] = "ইউজারনেম প্রয়োজন";
    } elseif (strlen($username) < 4) {
        $errors[] = "ইউজারনেম কমপক্ষে ৪ অক্ষর হতে হবে";
    }
    
    if (empty($password)) {
        $errors[] = "পাসওয়ার্ড প্রয়োজন";
    } elseif (strlen($password) < 6) {
        $errors[] = "পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "পাসওয়ার্ড মিলছে না";
    }
    
    if (empty($mobile)) {
        $errors[] = "মোবাইল নম্বর প্রয়োজন";
    } elseif (!preg_match('/^01[3-9]\d{8}$/', $mobile)) {
        $errors[] = "সঠিক মোবাইল নম্বর দিন";
    }
    
    // Check if username already exists
    $db = connectDB();
    if ($db === false) {
        $errors[] = "ডাটাবেস কানেকশন ত্রুটি";
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "ইউজারনেম ইতিমধ্যে ব্যবহৃত হয়েছে";
        }
        
        // Check if mobile already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE mobile = ?");
        $stmt->execute([$mobile]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "মোবাইল নম্বর ইতিমধ্যে ব্যবহৃত হয়েছে";
        }
        
        // Validate referral code if provided
        $referrerId = null;
        if (!empty($referCode)) {
            // Fix: Modified query to properly check referral code
            $stmt = $db->prepare("SELECT id FROM users WHERE referrer_code = ? OR account_number = ?");
            $stmt->execute([$referCode, $referCode]);
            $referrerId = $stmt->fetchColumn();
            
            if (!$referrerId) {
                $errors[] = "অবৈধ রেফার কোড";
            }
        }
    }
    
    // If no errors, register the user
    if (empty($errors) && $db !== false) {
        try {
            $db->beginTransaction();
            
            // Generate unique account number
            $accountNumber = generateAccountNumber();
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $db->prepare("INSERT INTO users (account_number, username, password, mobile, referrer_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$accountNumber, $username, $hashedPassword, $mobile, $referrerId]);
            $userId = $db->lastInsertId();
            
            // Generate and save referral code
            $referralCode = generateReferralCode($username, $userId);
            $stmt = $db->prepare("UPDATE users SET referrer_code = ? WHERE id = ?");
            $stmt->execute([$referralCode, $userId]);
            
            // Add notification for new user
            addNotification($userId, 'স্বাগতম!', 'MZ Income এ আপনাকে স্বাগতম! আপনার অ্যাকাউন্ট নম্বর: ' . $accountNumber);
            
            // Add notification for referrer if exists
            if ($referrerId) {
                addNotification($referrerId, 'নতুন রেফারেল!', 'আপনার রেফারেলে ' . $username . ' যোগ দিয়েছেন।');
            }
            
            $db->commit();
            
            $success = "রেজিস্ট্রেশন সফল! আপনার অ্যাকাউন্ট নম্বর: " . $accountNumber;
            
            // Automatic login
            startSession();
            $_SESSION['user_id'] = $userId;
            $_SESSION['account_number'] = $accountNumber;
            $_SESSION['username'] = $username;
            
            // Redirect to dashboard
            redirect('dashboard.php');
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "দুঃখিত, কিছু সমস্যা হয়েছে। পরে আবার চেষ্টা করুন।";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>রেজিস্ট্রেশন - MZ Income</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card auth-card">
                    <div class="card-header text-center">
                        <h2 class="logo">MZ Income</h2>
                        <h4>রেজিস্ট্রেশন করুন</h4>
                    </div>
                    <div class="card-body">
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
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">ইউজারনেম</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">পাসওয়ার্ড</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">পাসওয়ার্ড নিশ্চিত করুন</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="mobile" class="form-label">মোবাইল নম্বর</label>
                                <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="refer_code" class="form-label">রেফার কোড (ঐচ্ছিক)</label>
                                <input type="text" class="form-control" id="refer_code" name="refer_code" value="<?php echo isset($_POST['refer_code']) ? htmlspecialchars($_POST['refer_code']) : (isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : ''); ?>">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">রেজিস্ট্রেশন করুন</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p>ইতিমধ্যে একাউন্ট আছে? <a href="login.php">লগইন করুন</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>


<?php
// Fixed login.php - Corrected login process
require_once 'config.php';

$errors = [];
$success = '';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $accountNumber = sanitizeInput($_POST['account_number'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($accountNumber)) {
        $errors[] = "অ্যাকাউন্ট নম্বর প্রয়োজন";
    }
    
    if (empty($password)) {
        $errors[] = "পাসওয়ার্ড প্রয়োজন";
    }
    
    // If no errors, attempt to login
    if (empty($errors)) {
        $db = connectDB();
        
        if ($db === false) {
            $errors[] = "ডাটাবেস কানেকশন ত্রুটি";
        } else {
            $stmt = $db->prepare("SELECT id, account_number, username, password FROM users WHERE account_number = ?");
            $stmt->execute([$accountNumber]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                startSession();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['account_number'] = $user['account_number'];
                $_SESSION['username'] = $user['username'];
                
                // Redirect to dashboard
                redirect('dashboard.php');
            } else {
                $errors[] = "অবৈধ অ্যাকাউন্ট নম্বর বা পাসওয়ার্ড";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>লগইন - MZ Income</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card auth-card">
                    <div class="card-header text-center">
                        <h2 class="logo">MZ Income</h2>
                        <h4>লগইন করুন</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="account_number" class="form-label">অ্যাকাউন্ট নম্বর</label>
                                <input type="text" class="form-control" id="account_number" name="account_number" value="<?php echo isset($_POST['account_number']) ? htmlspecialchars($_POST['account_number']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">পাসওয়ার্ড</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">লগইন করুন</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p>একাউন্ট নেই? <a href="register.php">রেজিস্ট্রেশন করুন</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>


<?php
// Fixed config.php - Enhanced database connection and session handling
// Database Configuration
define('DB_HOST', 'localhost'); 
define('DB_NAME', 'qydipzkd_incomee');
define('DB_USER', 'qydipzkd_incomee');
define('DB_PASS', 'incomee314@');

// Other constants
define('SITE_URL', 'http://income.mzgency.xyz/bn/');
define('UPLOADS_DIR', 'uploads/');
define('PROFILE_PIC_DIR', UPLOADS_DIR . 'profile_pictures/');
define('DEPOSIT_PROOF_DIR', UPLOADS_DIR . 'deposit_proofs/');
define('DEFAULT_PROFILE_PIC', 'assets/img/default-profile.png');

// Create database connection
function connectDB() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        // Set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("SET NAMES utf8mb4");
        return $conn;
    } catch(PDOException $e) {
        // Log error instead of showing it to users in production
        error_log("Connection failed: " . $e->getMessage());
        return false;
    }
}

// Start session with improved security
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        // Set the session cookie parameters
        session_set_cookie_params([
            'lifetime' => 86400, // 24 hours
            'path' => '/',
            'domain' => '',
            'secure' => false, // Set to true if using HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        session_start();
    }
}

// Check if user is logged in
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    startSession();
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect to another page
function redirect($location) {
    header("Location: $location");
    exit;
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generate a random account number
function generateAccountNumber() {
    $db = connectDB();
    
    if ($db === false) {
        error_log("Could not connect to database while generating account number");
        return false;
    }
    
    do {
        $accountNumber = sprintf("%06d", mt_rand(100000, 999999));
        
        // Check if account number already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE account_number = ?");
        $stmt->execute([$accountNumber]);
        $exists = $stmt->fetchColumn();
    } while ($exists > 0);
    
    return $accountNumber;
}

// Generate a referral code for a user
function generateReferralCode($username, $userId) {
    // Take first 4 characters of username and append user ID
    $prefix = substr(preg_replace('/[^A-Za-z0-9]/', '', $username), 0, 4);
    $referralCode = strtoupper($prefix) . $userId;
    return $referralCode;
}

// Fixed getUserById function
function getUserById($userId) {
    $db = connectDB();
    
    if ($db === false) {
        error_log("Could not connect to database in getUserById");
        return false;
    }
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fixed getUserByAccountNumber function
function getUserByAccountNumber($accountNumber) {
    $db = connectDB();
    
    if ($db === false) {
        error_log("Could not connect to database in getUserByAccountNumber");
        return false;
    }
    
    $stmt = $db->prepare("SELECT * FROM users WHERE account_number = ?");
    $stmt->execute([$accountNumber]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Additional functions remain the same as in your original config.php
// Add notification for user with improved error handling
function addNotification($userId, $title, $message) {
    $db = connectDB();
    
    if ($db === false) {
        error_log("Could not connect to database in addNotification");
        return false;
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $title, $message]);
    } catch (PDOException $e) {
        error_log("Error adding notification: " . $e->getMessage());
        return false;
    }
}

// The rest of your config.php functions...