<?php
// login.php - User login form and processing
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
                                <input type="text" class="form-control" id="account_number" name="account_number" value="<?php echo isset($_POST['account_number']) ? $_POST['account_number'] : ''; ?>" required>
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

