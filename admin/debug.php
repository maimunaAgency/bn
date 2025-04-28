<?php
/**
 * debug.php - MZ Income Registration Debug Tool
 * 
 * This file helps diagnose and fix issues with the registration system.
 * It tests database connections, form validation, user creation, and related functionality.
 * 
 * IMPORTANT: Remove this file from production after debugging is complete!
 */

// Set error reporting to maximum for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to control what's shown
ob_start();

// Define the debug mode - set to true to enable tests
$debugMode = true;

// Define a simple authentication to protect this debug page
$debugPassword = "mzincome_debug_2023";

// CSS styles for debug output
echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MZ Income Debug Tool</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body { padding: 20px; font-family: monospace; }
        .debug-section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .debug-title { font-weight: bold; font-size: 18px; margin-bottom: 10px; }
        .debug-pass { color: green; }
        .debug-fail { color: red; }
        .debug-warning { color: orange; }
        .debug-info { color: blue; }
        .debug-form { margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; }
        .fixed-issues { background-color: #d4edda; }
        pre { background-color: #f8f9fa; padding: 10px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>MZ Income Debug Tool</h1>
        <p class="text-danger">⚠️ WARNING: This tool provides sensitive debugging information. Remove from production server after use!</p>
';

// Authentication check
if (isset($_POST['debug_password']) && $_POST['debug_password'] === $debugPassword) {
    $_SESSION['debug_authenticated'] = true;
}

if (!isset($_SESSION['debug_authenticated'])) {
    // Show login form
    echo '
    <div class="debug-form">
        <h2>Debug Authentication</h2>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="debug_password" class="form-label">Debug Password</label>
                <input type="password" class="form-control" id="debug_password" name="debug_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Access Debug Tool</button>
        </form>
    </div>
    ';
    
    // End the script here
    echo '</div></body></html>';
    ob_end_flush();
    exit;
}

// Include configuration file
if (file_exists('config.php')) {
    require_once 'config.php';
    echo '<div class="debug-section"><div class="debug-title">Configuration File</div>';
    echo '<p class="debug-pass">✓ Config file found and included</p>';
    echo '</div>';
} else {
    echo '<div class="debug-section"><div class="debug-title">Configuration File</div>';
    echo '<p class="debug-fail">✗ Config file not found! Please make sure config.php exists in the root directory.</p>';
    echo '<p>Common fixes:</p>';
    echo '<ul>';
    echo '<li>Check if config.php exists in the root directory</li>';
    echo '<li>Make sure file permissions allow reading the file</li>';
    echo '</ul>';
    echo '</div>';
    
    // Cannot continue without config file
    echo '</div></body></html>';
    ob_end_flush();
    exit;
}

// Test database connection
echo '<div class="debug-section"><div class="debug-title">Database Connection</div>';
try {
    $db = connectDB();
    if ($db) {
        echo '<p class="debug-pass">✓ Database connection successful</p>';
        
        // Check DB schema
        try {
            // Check users table
            $stmt = $db->prepare("SHOW TABLES LIKE 'users'");
            $stmt->execute();
            $userTableExists = $stmt->rowCount() > 0;
            
            if ($userTableExists) {
                echo '<p class="debug-pass">✓ Users table exists</p>';
                
                // Check users table structure
                $stmt = $db->prepare("DESCRIBE users");
                $stmt->execute();
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Check if necessary columns exist
                $requiredColumns = [
                    'id', 'account_number', 'username', 'password', 'mobile', 
                    'referrer_code', 'referrer_id', 'wallet_balance', 'active_package'
                ];
                
                $missingColumns = array_diff($requiredColumns, $columns);
                
                if (empty($missingColumns)) {
                    echo '<p class="debug-pass">✓ Users table structure appears correct</p>';
                } else {
                    echo '<p class="debug-fail">✗ Users table is missing required columns: ' . implode(', ', $missingColumns) . '</p>';
                }
            } else {
                echo '<p class="debug-fail">✗ Users table does not exist!</p>';
                echo '<pre>Common fix: Run the database setup SQL to create the required tables</pre>';
            }
        } catch (PDOException $e) {
            echo '<p class="debug-fail">✗ Error checking database schema: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    } else {
        echo '<p class="debug-fail">✗ Database connection failed</p>';
    }
} catch (PDOException $e) {
    echo '<p class="debug-fail">✗ Database connection error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Common fixes:</p>';
    echo '<ul>';
    echo '<li>Check if database server is running</li>';
    echo '<li>Verify database name, username and password in config.php</li>';
    echo '<li>Make sure database user has necessary permissions</li>';
    echo '</ul>';
}
echo '</div>';

// Test registration form submission
echo '<div class="debug-section"><div class="debug-title">Registration Form Validation</div>';

// Create a simulated form submission
$testUsername = "test_user_" . time();
$testPassword = "password123";
$testMobile = "01712345678";
$testReferCode = "";

echo "<p class=\"debug-info\">Testing registration with username: $testUsername, mobile: $testMobile</p>";

// Function to simulate registration
function testRegistration($username, $password, $mobile, $referCode = '') {
    $errors = [];
    
    // Basic validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if (empty($mobile)) {
        $errors[] = "Mobile number is required";
    } elseif (!preg_match('/^01[3-9]\d{8}$/', $mobile)) {
        $errors[] = "Invalid mobile number format";
    }
    
    // Check if username already exists
    try {
        $db = connectDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Username already exists";
        }
        
        // Check if mobile already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE mobile = ?");
        $stmt->execute([$mobile]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Mobile number already exists";
        }
        
        // Validate referral code if provided
        $referrerId = null;
        if (!empty($referCode)) {
            $stmt = $db->prepare("SELECT id FROM users WHERE id = ? OR id = (SELECT id FROM users WHERE account_number = ?)");
            $stmt->execute([substr($referCode, 4), $referCode]);
            $referrerId = $stmt->fetchColumn();
            
            if (!$referrerId) {
                $errors[] = "Invalid referral code";
            }
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
    
    return [
        'errors' => $errors,
        'referrerId' => $referrerId
    ];
}

// Test the registration validation
$testResult = testRegistration($testUsername, $testPassword, $testMobile, $testReferCode);

if (empty($testResult['errors'])) {
    echo '<p class="debug-pass">✓ Registration validation passed for test user</p>';
    
    // Now test account number generation
    echo '<div class="debug-title">Account Number Generation</div>';
    
    try {
        $accountNumber = generateAccountNumber();
        if ($accountNumber && strlen($accountNumber) == 6 && is_numeric($accountNumber)) {
            echo '<p class="debug-pass">✓ Account number generated successfully: ' . $accountNumber . '</p>';
        } else {
            echo '<p class="debug-fail">✗ Account number generation failed or invalid format</p>';
        }
    } catch (Exception $e) {
        echo '<p class="debug-fail">✗ Error in account number generation: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    
    // Test referral code generation
    echo '<div class="debug-title">Referral Code Generation</div>';
    
    try {
        $referralCode = generateReferralCode($testUsername, 1000);
        if ($referralCode) {
            echo '<p class="debug-pass">✓ Referral code generated successfully: ' . $referralCode . '</p>';
        } else {
            echo '<p class="debug-fail">✗ Referral code generation failed</p>';
        }
    } catch (Exception $e) {
        echo '<p class="debug-fail">✗ Error in referral code generation: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    
    // Test actual user creation (optional, commented out to prevent actual database changes)
    echo '<div class="debug-title">User Creation (Simulation)</div>';
    echo '<p class="debug-info">Note: Actual user creation is simulated to prevent database changes</p>';
    
    // Simulate the user creation process
    echo '<pre>
// Database code to create user (not actually executed):
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
addNotification($userId, \'স্বাগতম!\', \'MZ Income এ আপনাকে স্বাগতম! আপনার অ্যাকাউন্ট নম্বর: \' . $accountNumber);
            
// Add notification for referrer if exists
if ($referrerId) {
    addNotification($referrerId, \'নতুন রেফারেল!\', \'আপনার রেফারেলে \' . $username . \' যোগ দিয়েছেন।\');
}
            
$db->commit();
</pre>';
    
    // Test session handling
    echo '<div class="debug-title">Session Handling</div>';
    
    if (function_exists('startSession')) {
        echo '<p class="debug-pass">✓ Session function exists</p>';
        
        // Check session status
        if (session_status() == PHP_SESSION_NONE) {
            startSession();
            echo '<p class="debug-pass">✓ Session started successfully</p>';
        } else {
            echo '<p class="debug-pass">✓ Session already active</p>';
        }
    } else {
        echo '<p class="debug-fail">✗ Session function not found</p>';
    }
    
} else {
    echo '<p class="debug-fail">✗ Registration validation failed with errors:</p>';
    echo '<ul>';
    foreach ($testResult['errors'] as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
}
echo '</div>';

// Test form submission
echo '<div class="debug-section"><div class="debug-title">Registration Form Submission Test</div>';
echo '<p>To test the actual registration process manually, use this form:</p>';

echo '<form method="POST" action="register.php" target="_blank" class="debug-form">';
echo '<div class="mb-3">';
echo '<label for="username" class="form-label">Username</label>';
echo '<input type="text" class="form-control" id="username" name="username" value="' . $testUsername . '">';
echo '</div>';
echo '<div class="mb-3">';
echo '<label for="password" class="form-label">Password</label>';
echo '<input type="password" class="form-control" id="password" name="password" value="' . $testPassword . '">';
echo '</div>';
echo '<div class="mb-3">';
echo '<label for="confirm_password" class="form-label">Confirm Password</label>';
echo '<input type="password" class="form-control" id="confirm_password" name="confirm_password" value="' . $testPassword . '">';
echo '</div>';
echo '<div class="mb-3">';
echo '<label for="mobile" class="form-label">Mobile Number</label>';
echo '<input type="text" class="form-control" id="mobile" name="mobile" value="' . $testMobile . '">';
echo '</div>';
echo '<div class="mb-3">';
echo '<label for="refer_code" class="form-label">Referral Code (optional)</label>';
echo '<input type="text" class="form-control" id="refer_code" name="refer_code" value="">';
echo '</div>';
echo '<button type="submit" class="btn btn-primary">Test Registration</button>';
echo '</form>';
echo '</div>';

// Common issues and fixes
echo '<div class="debug-section fixed-issues"><div class="debug-title">Common Issues and Fixes</div>';

echo '<h4>Issue: Registration Not Working</h4>';
echo '<ol>';
echo '<li><strong>Database Connection</strong>: Ensure the database connection parameters are correct in config.php</li>';
echo '<li><strong>Table Structure</strong>: Verify all required tables and columns exist in the database</li>';
echo '<li><strong>File Permissions</strong>: Make sure PHP has write access to the necessary directories</li>';
echo '<li><strong>Session Configuration</strong>: Check PHP session settings in php.ini or .htaccess</li>';
echo '</ol>';

echo '<h4>Issue: User Created But Cannot Login</h4>';
echo '<ol>';
echo '<li><strong>Password Hashing</strong>: Ensure the password_hash() function is using PASSWORD_DEFAULT</li>';
echo '<li><strong>Session Configuration</strong>: Verify session.save_path is writable</li>';
echo '<li><strong>Session Variables</strong>: Check if session variables are properly set during login</li>';
echo '</ol>';

echo '<h4>Issue: Referral System Not Working</h4>';
echo '<ol>';
echo '<li><strong>Referral Code Format</strong>: Ensure the referral code format is consistent</li>';
echo '<li><strong>Referrer ID Storage</strong>: Check if referrer_id is properly stored in the users table</li>';
echo '<li><strong>Commission Calculation</strong>: Verify the commission calculation logic</li>';
echo '</ol>';

echo '<h4>Modified register.php Code:</h4>';
echo '<pre>
// Correct version of the registration process
if ($_SERVER[\'REQUEST_METHOD\'] == \'POST\') {
    // Get form data
    $username = sanitizeInput($_POST[\'username\'] ?? \'\');
    $password = $_POST[\'password\'] ?? \'\';
    $confirmPassword = $_POST[\'confirm_password\'] ?? \'\';
    $mobile = sanitizeInput($_POST[\'mobile\'] ?? \'\');
    $referCode = sanitizeInput($_POST[\'refer_code\'] ?? \'\');
    
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
    } elseif (!preg_match(\'/^01[3-9]\\d{8}$/\', $mobile)) {
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
            addNotification($userId, \'স্বাগতম!\', \'MZ Income এ আপনাকে স্বাগতম! আপনার অ্যাকাউন্ট নম্বর: \' . $accountNumber);
            
            // Add notification for referrer if exists
            if ($referrerId) {
                addNotification($referrerId, \'নতুন রেফারেল!\', \'আপনার রেফারেলে \' . $username . \' যোগ দিয়েছেন।\');
            }
            
            $db->commit();
            
            $success = "রেজিস্ট্রেশন সফল! আপনার অ্যাকাউন্ট নম্বর: " . $accountNumber;
            
            // Automatic login
            startSession();
            $_SESSION[\'user_id\'] = $userId;
            $_SESSION[\'account_number\'] = $accountNumber;
            $_SESSION[\'username\'] = $username;
            
            // Redirect to dashboard
            redirect(\'dashboard.php\');
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "দুঃখিত, কিছু সমস্যা হয়েছে। পরে আবার চেষ্টা করুন।";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
</pre>';

echo '</div>';

// Fix command information
echo '<div class="debug-section"><div class="debug-title">Commands to Fix Common Issues</div>';

echo '<h4>Fix Database Connection</h4>';
echo '<pre>
// In config.php
define(\'DB_HOST\', \'localhost\'); 
define(\'DB_NAME\', \'qydipzkd_incomee\');
define(\'DB_USER\', \'qydipzkd_incomee\');
define(\'DB_PASS\', \'incomee314@\');

// Test connection
function testDatabaseConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connection successful!";
        return true;
    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        return false;
    }
}
</pre>';

echo '<h4>Fix File Permissions</h4>';
echo '<pre>
// Run these commands on your server
chmod 755 /path/to/root
chmod 755 /path/to/root/includes
chmod 755 /path/to/root/assets
chmod 755 /path/to/root/ajax
chmod 755 /path/to/root/admin
chmod 755 /path/to/root/uploads
chmod 777 /path/to/root/uploads/profile_pictures
chmod 777 /path/to/root/uploads/deposit_proofs
</pre>';

echo '<h4>Fix Session Issues</h4>';
echo '<pre>
// Make sure startSession() is correct in config.php
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        // Set the session cookie parameters
        session_set_cookie_params([
            \'lifetime\' => 86400,
            \'path\' => \'/\',
            \'domain\' => \'\',
            \'secure\' => false,
            \'httponly\' => true,
            \'samesite\' => \'Lax\'
        ]);
        
        session_start();
    }
}

// Test session functionality
function testSession() {
    startSession();
    $_SESSION[\'test\'] = \'Session works!\';
    echo $_SESSION[\'test\'];
}
</pre>';

echo '</div>';

// Manual Testing Instructions
echo '<div class="debug-section"><div class="debug-title">Manual Testing Instructions</div>';

echo '<h4>Test Registration Process</h4>';
echo '<ol>';
echo '<li>Go to <a href="register.php" target="_blank">register.php</a></li>';
echo '<li>Fill in the form with test user details</li>';
echo '<li>Submit the form and observe any errors</li>';
echo '<li>If successful, you should be redirected to the dashboard</li>';
echo '</ol>';

echo '<h4>Test Login Process</h4>';
echo '<ol>';
echo '<li>Go to <a href="login.php" target="_blank">login.php</a></li>';
echo '<li>Enter account number and password of a registered user</li>';
echo '<li>Submit the form and observe any errors</li>';
echo '<li>If successful, you should be redirected to the dashboard</li>';
echo '</ol>';

echo '<h4>Test Referral System</h4>';
echo '<ol>';
echo '<li>Register a new user with a valid referral code</li>';
echo '<li>Login as the referrer</li>';
echo '<li>Check if the new user appears in the referral list</li>';
echo '</ol>';

echo '</div>';

// End of debug output
echo '</div></body></html>';
ob_end_flush();
?>