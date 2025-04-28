<?php
/**
 * config.php - Core configuration file for MZ Income platform
 * 
 * This file contains all the essential configuration settings, database connection,
 * session management, and utility functions used throughout the application.
 */

// Enable error reporting during development, disable in production
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost'); 
define('DB_NAME', 'qydipzkd_incomee');
define('DB_USER', 'qydipzkd_incomee');
define('DB_PASS', 'incomee314@');

// Site Configuration
define('SITE_URL', 'http://income.mzgency.xyz/bn/');
define('SITE_NAME', 'MZ Income');
define('ADMIN_EMAIL', 'admin@example.com');

// File and Directory Paths
define('UPLOADS_DIR', 'uploads/');
define('PROFILE_PIC_DIR', UPLOADS_DIR . 'profile_pictures/');
define('DEPOSIT_PROOF_DIR', UPLOADS_DIR . 'deposit_proofs/');
define('DEFAULT_PROFILE_PIC', 'assets/img/default-profile.png');

// Log file path
define('LOG_FILE', dirname(__FILE__) . '/logs/app_errors.log');

// Ensure log directory exists
if (!file_exists(dirname(LOG_FILE))) {
    mkdir(dirname(LOG_FILE), 0755, true);
}

/**
 * Custom error logger
 * 
 * @param string $message Error message
 * @param string $level Error level
 * @return void
 */
function logError($message, $level = 'ERROR') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    error_log($logMessage, 3, LOG_FILE);
}

/**
 * Create database connection with enhanced error handling
 * 
 * @return PDO|false Database connection or false on failure
 */
function connectDB() {
    static $conn = null;
    
    // Return existing connection if available
    if ($conn !== null) {
        return $conn;
    }
    
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        // Set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("SET NAMES utf8mb4");
        return $conn;
    } catch(PDOException $e) {
        // Log error instead of showing it to users in production
        logError("Database connection failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Start session with enhanced security settings
 * 
 * @return void
 */
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
        
        // Start session
        session_start();
        
        // Regenerate session ID periodically to prevent session fixation attacks
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 3600) {
            // Regenerate session ID every hour
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 * 
 * @return bool True if admin is logged in, false otherwise
 */
function isAdminLoggedIn() {
    startSession();
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Redirect to another page
 * 
 * @param string $location URL to redirect to
 * @return void
 */
function redirect($location) {
    header("Location: $location");
    exit;
}

/**
 * Sanitize input to prevent XSS attacks
 * 
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate a random account number
 * 
 * @return string|false Generated account number or false on failure
 */
function generateAccountNumber() {
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database when generating account number");
        return false;
    }
    
    $maxAttempts = 10;
    $attempts = 0;
    
    do {
        $accountNumber = sprintf("%06d", mt_rand(100000, 999999));
        
        // Check if account number already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE account_number = ?");
        $stmt->execute([$accountNumber]);
        $exists = $stmt->fetchColumn();
        
        $attempts++;
        if ($attempts >= $maxAttempts && $exists > 0) {
            logError("Failed to generate unique account number after $maxAttempts attempts");
            return false;
        }
    } while ($exists > 0);
    
    return $accountNumber;
}

/**
 * Generate a referral code for a user
 * 
 * @param string $username User's username
 * @param int $userId User's ID
 * @return string Generated referral code
 */
function generateReferralCode($username, $userId) {
    // Take first 4 characters of username and append user ID
    $prefix = substr(preg_replace('/[^A-Za-z0-9]/', '', $username), 0, 4);
    if (empty($prefix)) {
        $prefix = "USER"; // Fallback if username has no valid characters
    }
    $referralCode = strtoupper($prefix) . $userId;
    return $referralCode;
}

/**
 * Get user data by ID
 * 
 * @param int $userId User ID
 * @return array|false User data array or false on failure
 */
function getUserById($userId) {
    if (empty($userId) || !is_numeric($userId)) {
        return false;
    }
    
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in getUserById");
        return false;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError("Error retrieving user by ID: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user data by account number
 * 
 * @param string $accountNumber User's account number
 * @return array|false User data array or false on failure
 */
function getUserByAccountNumber($accountNumber) {
    if (empty($accountNumber)) {
        return false;
    }
    
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in getUserByAccountNumber");
        return false;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE account_number = ?");
        $stmt->execute([$accountNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError("Error retrieving user by account number: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user data by username
 * 
 * @param string $username User's username
 * @return array|false User data array or false on failure
 */
function getUserByUsername($username) {
    if (empty($username)) {
        return false;
    }
    
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in getUserByUsername");
        return false;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError("Error retrieving user by username: " . $e->getMessage());
        return false;
    }
}

/**
 * Get site settings
 * 
 * @param string|null $key Setting key to retrieve, or null for all settings
 * @return mixed Setting value, array of settings, or null on failure
 */
function getSiteSettings($key = null) {
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in getSiteSettings");
        return null;
    }
    
    try {
        if ($key) {
            $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            return $stmt->fetchColumn();
        } else {
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM site_settings");
            $stmt->execute();
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return $settings;
        }
    } catch (PDOException $e) {
        logError("Error retrieving site settings: " . $e->getMessage());
        return null;
    }
}

/**
 * Add notification for user
 * 
 * @param int $userId User ID
 * @param string $title Notification title
 * @param string $message Notification message
 * @return bool True on success, false on failure
 */
function addNotification($userId, $title, $message) {
    if (empty($userId) || !is_numeric($userId)) {
        return false;
    }
    
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in addNotification");
        return false;
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $title, $message]);
    } catch (PDOException $e) {
        logError("Error adding notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Format amount with currency symbol (BDT/Taka)
 * 
 * @param float $amount Amount to format
 * @return string Formatted amount with currency symbol
 */
function formatAmount($amount) {
    return '৳ ' . number_format($amount, 2);
}

/**
 * Format date and time
 * 
 * @param string $datetime Date and time string
 * @return string Formatted date and time
 */
function formatDateTime($datetime) {
    return date("d M Y, h:i A", strtotime($datetime));
}

/**
 * Get user's commission rate based on active package
 * 
 * @param int $userId User ID
 * @return float Commission rate percentage
 */
function getUserCommissionRate($userId) {
    if (empty($userId) || !is_numeric($userId)) {
        // Return default rate if user ID is invalid
        return (float)getSiteSettings('default_commission_rate') ?: 18.0;
    }
    
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in getUserCommissionRate");
        return (float)getSiteSettings('default_commission_rate') ?: 18.0;
    }
    
    try {
        $stmt = $db->prepare("SELECT active_package FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $activePackage = $stmt->fetchColumn();
        
        if ($activePackage == 'none') {
            // Default commission rate for users without a package
            return (float)getSiteSettings('default_commission_rate') ?: 18.0;
        } else {
            // Get commission rate from package
            $stmt = $db->prepare("SELECT commission_rate FROM packages WHERE name = ?");
            $stmt->execute([$activePackage]);
            $rate = $stmt->fetchColumn();
            return $rate !== false ? (float)$rate : 18.0;
        }
    } catch (PDOException $e) {
        logError("Error retrieving commission rate: " . $e->getMessage());
        return (float)getSiteSettings('default_commission_rate') ?: 18.0;
    }
}

/**
 * Calculate and record commission for a referral package purchase
 * 
 * @param int $referrerId Referrer user ID
 * @param int $referredId Referred user ID
 * @param int $packagePurchaseId Package purchase ID
 * @param float $packagePrice Package price
 * @return bool True on success, false on failure
 */
function processReferralCommission($referrerId, $referredId, $packagePurchaseId, $packagePrice) {
    if (empty($referrerId) || empty($referredId) || empty($packagePurchaseId) || $packagePrice <= 0) {
        logError("Invalid parameters in processReferralCommission");
        return false;
    }
    
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in processReferralCommission");
        return false;
    }
    
    // Get referrer's commission rate
    $commissionRate = getUserCommissionRate($referrerId);
    $commissionAmount = ($packagePrice * $commissionRate) / 100;
    
    // Log commission calculation for debugging
    logError("Processing commission: Referrer=$referrerId, Referred=$referredId, Package=$packagePurchaseId, Price=$packagePrice, Rate=$commissionRate%, Amount=$commissionAmount", "INFO");
    
    try {
        $db->beginTransaction();
        
        // Create transaction record
        $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, status, reference_id, notes) 
                             VALUES (?, 'commission', ?, 'approved', ?, 'Referral commission')");
        $stmt->execute([$referrerId, $commissionAmount, $referredId]);
        $transactionId = $db->lastInsertId();
        
        // Record commission
        $stmt = $db->prepare("INSERT INTO referral_commissions (referrer_id, referred_id, package_purchase_id, 
                             commission_amount, transaction_record_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$referrerId, $referredId, $packagePurchaseId, $commissionAmount, $transactionId]);
        
        // Update referrer's wallet balance
        $stmt = $db->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->execute([$commissionAmount, $referrerId]);
        
        // Add notification
        $referredUser = getUserById($referredId);
        $referredName = $referredUser ? $referredUser['username'] : "user #$referredId";
        
        addNotification($referrerId, 'রেফারেল কমিশন পেয়েছেন', 
                        'আপনি ' . $referredName . ' এর প্যাকেজ কেনার জন্য ' . 
                        formatAmount($commissionAmount) . ' কমিশন পেয়েছেন।');
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        logError("Commission processing error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is eligible for package refund
 * 
 * @param int $userId User ID
 * @param int $packagePurchaseId Package purchase ID
 * @return bool True if eligible, false otherwise
 */
function isEligibleForPackageRefund($userId, $packagePurchaseId) {
    if (empty($userId) || empty($packagePurchaseId)) {
        return false;
    }
    
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in isEligibleForPackageRefund");
        return false;
    }
    
    try {
        // Count how many successful referrals the user has made during the package validity
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM package_purchases pp
            JOIN users u ON pp.user_id = u.id
            WHERE u.referrer_id = ? AND pp.purchase_date >= (
                SELECT purchase_date FROM package_purchases WHERE id = ?
            ) AND pp.purchase_date <= (
                SELECT expiry_date FROM package_purchases WHERE id = ?
            )
        ");
        $stmt->execute([$userId, $packagePurchaseId, $packagePurchaseId]);
        
        $successfulReferrals = $stmt->fetchColumn();
        return $successfulReferrals >= 1;
    } catch (PDOException $e) {
        logError("Error checking package refund eligibility: " . $e->getMessage());
        return false;
    }
}

/**
 * Process package refund if eligible
 * 
 * @param int $userId User ID
 * @param int $packagePurchaseId Package purchase ID
 * @return bool True on success, false on failure
 */
function processPackageRefund($userId, $packagePurchaseId) {
    if (!isEligibleForPackageRefund($userId, $packagePurchaseId)) {
        return false;
    }
    
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in processPackageRefund");
        return false;
    }
    
    try {
        $db->beginTransaction();
        
        // Get package purchase details
        $stmt = $db->prepare("SELECT price_paid FROM package_purchases WHERE id = ?");
        $stmt->execute([$packagePurchaseId]);
        $pricePaid = $stmt->fetchColumn();
        
        if ($pricePaid === false) {
            $db->rollBack();
            logError("Package purchase not found: ID=$packagePurchaseId");
            return false;
        }
        
        // Create transaction record
        $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, status, reference_id, notes) 
                             VALUES (?, 'package_refund', ?, 'approved', ?, 'Package refund for successful referrals')");
        $stmt->execute([$userId, $pricePaid, $packagePurchaseId]);
        $transactionId = $db->lastInsertId();
        
        // Update package purchase record
        $stmt = $db->prepare("UPDATE package_purchases SET is_refunded = 1, refund_transaction_id = ? WHERE id = ?");
        $stmt->execute([$transactionId, $packagePurchaseId]);
        
        // Update user's wallet balance
        $stmt = $db->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->execute([$pricePaid, $userId]);
        
        // Add notification
        addNotification($userId, 'প্যাকেজ রিফান্ড পেয়েছেন', 
                       'সফল রেফারেলের জন্য আপনি আপনার প্যাকেজ মূল্য ' . formatAmount($pricePaid) . ' ফেরত পেয়েছেন।');
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        logError("Package refund error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check for expired packages and process accordingly
 * 
 * @return void
 */
function checkExpiredPackages() {
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in checkExpiredPackages");
        return;
    }
    
    try {
        // Find expired packages that haven't been processed yet
        $stmt = $db->prepare("
            SELECT pp.*, u.id as user_id, u.username
            FROM package_purchases pp
            JOIN users u ON pp.user_id = u.id
            WHERE pp.expiry_date < NOW() 
            AND pp.is_refunded = 0
            AND u.active_package != 'none'
        ");
        $stmt->execute();
        $expiredPackages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($expiredPackages as $package) {
            // Process refund if eligible
            $isRefunded = processPackageRefund($package['user_id'], $package['id']);
            
            // Update user's active package to none regardless of refund status
            $stmt = $db->prepare("UPDATE users SET active_package = 'none', package_expiry_date = NULL, package_purchase_date = NULL WHERE id = ?");
            $stmt->execute([$package['user_id']]);
            
            // Add notification
            $message = 'আপনার ' . ucfirst($package['package_id']) . ' প্যাকেজ মেয়াদ শেষ হয়েছে। ';
            if ($isRefunded) {
                $message .= 'সফল রেফারালের জন্য আপনি প্যাকেজ মূল্য ফেরত পেয়েছেন।';
            } else {
                $message .= 'আপনি কোন সফল রেফারাল না করায় প্যাকেজ মূল্য ফেরত পাননি।';
            }
            
            addNotification($package['user_id'], 'প্যাকেজ মেয়াদ শেষ হয়েছে', $message);
        }
    } catch (Exception $e) {
        logError("Error checking expired packages: " . $e->getMessage());
    }
}

/**
 * Get payment method details
 * 
 * @param string $method Payment method name (bkash, nagad, rocket)
 * @return array|false Payment method details or false on failure
 */
function getPaymentMethod($method) {
    if (empty($method)) {
        return false;
    }
    
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in getPaymentMethod");
        return false;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM payment_methods WHERE name = ? AND is_active = 1");
        $stmt->execute([$method]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError("Error retrieving payment method: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all active payment methods
 * 
 * @return array Active payment methods
 */
function getAllPaymentMethods() {
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in getAllPaymentMethods");
        return [];
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM payment_methods WHERE is_active = 1");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError("Error retrieving payment methods: " . $e->getMessage());
        return [];
    }
}

/**
 * Get top earners
 * 
 * @param string $period Time period (all, week, month)
 * @param int $limit Maximum number of earners to return
 * @return array Top earners
 */
function getTopEarners($period = 'all', $limit = 10) {
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in getTopEarners");
        return [];
    }
    
    try {
        switch ($period) {
            case 'week':
                $dateFilter = "AND t.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $dateFilter = "AND t.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            default:
                $dateFilter = "";
        }
        
        $query = "
            SELECT u.id, u.username, u.account_number, u.profile_picture, 
                   SUM(t.amount) as total_earnings
            FROM users u
            JOIN transactions t ON u.id = t.user_id
            WHERE t.type IN ('commission', 'package_refund') 
                  AND t.status = 'approved'
                  $dateFilter
            GROUP BY u.id
            ORDER BY total_earnings DESC
            LIMIT ?
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError("Error retrieving top earners: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user's referrals
 * 
 * @param int $userId User ID
 * @return array User's referrals
 */
function getUserReferrals($userId) {
    if (empty($userId)) {
        return [];
    }
    
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in getUserReferrals");
        return [];
    }
    
    try {
        $stmt = $db->prepare("
            SELECT u.*, 
                   pp.purchase_date as package_purchase_date,
                   pp.expiry_date as package_expiry_date
            FROM users u
            LEFT JOIN package_purchases pp ON u.id = pp.user_id AND pp.expiry_date >= NOW()
            WHERE u.referrer_id = ?
            ORDER BY u.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError("Error retrieving user referrals: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user's commission total
 * 
 * @param int $userId User ID
 * @param string $period Time period (all, week, month)
 * @return float Total commission amount
 */
function getUserCommissionTotal($userId, $period = 'all') {
    if (empty($userId)) {
        return 0;
    }
    
    $db = connectDB();
    
    if ($db === false) {
        logError("Failed to connect to database in getUserCommissionTotal");
        return 0;
    }
    
    try {
        switch ($period) {
            case 'week':
                $dateFilter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $dateFilter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            default:
                $dateFilter = "";
        }
        
        $stmt = $db->prepare("
            SELECT SUM(amount) as total
            FROM transactions
            WHERE user_id = ? AND type = 'commission' AND status = 'approved'
            $dateFilter
        ");
        $stmt->execute([$userId]);
        $total = $stmt->fetchColumn();
        return $total ?: 0;
    } catch (PDOException $e) {
        logError("Error retrieving user commission total: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check uploaded file and move it to destination
 * 
 * @param string $fileInput File input field name
 * @param string $destinationDir Destination directory
 * @param array $allowedTypes Allowed MIME types
 * @return string|false Filename on success, false on failure
 */
function processUploadedFile($fileInput, $destinationDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg']) {
    // Check if file was uploaded
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] != UPLOAD_ERR_OK) {
        return false;
    }
    
    $file = $_FILES[$fileInput];
    $fileType = $file['type'];
    
    // Validate file type
    if (!in_array($fileType, $allowedTypes)) {
        return false;
    }
    
    // Create directory if it doesn't exist
    if (!file_exists($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }
    
    // Generate unique filename
    $fileName = uniqid() . '_' . basename($file['name']);
    $destination = $destinationDir . $fileName;
    
    // Move file to destination
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $fileName;
    }
    
    return false;
}

/**
 * Initialize the application
 * Check required directories and database connection
 * 
 * @return bool True if initialization successful, false otherwise
 */
function initializeApplication() {
    // Check and create required directories
    $requiredDirs = [
        UPLOADS_DIR,
        PROFILE_PIC_DIR,
        DEPOSIT_PROOF_DIR
    ];
    
    foreach ($requiredDirs as $dir) {
        if (!file_exists($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                logError("Failed to create directory: $dir");
                return false;
            }
        } elseif (!is_writable($dir)) {
            logError("Directory not writable: $dir");
            return false;
        }
    }
    
    // Test database connection
    $db = connectDB();
    if ($db === false) {
        logError("Database connection failed during initialization");
        return false;
    }
    
    return true;
}

// Initialize the application
initializeApplication();