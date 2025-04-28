<?php
// change_password.php - Change user password
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
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($currentPassword)) {
        $errors[] = "বর্তমান পাসওয়ার্ড প্রয়োজন";
    }
    
    if (empty($newPassword)) {
        $errors[] = "নতুন পাসওয়ার্ড প্রয়োজন";
    } elseif (strlen($newPassword) < 6) {
        $errors[] = "পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে";
    }
    
    if ($newPassword != $confirmPassword) {
        $errors[] = "পাসওয়ার্ড মিলছে না";
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        $errors[] = "বর্তমান পাসওয়ার্ড ভুল";
    }
    
    // If no errors, update password
    if (empty($errors)) {
        $db = connectDB();
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        
        // Add notification
        addNotification($userId, 'পাসওয়ার্ড পরিবর্তন', 'আপনার পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে।');
        
        $success = "পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে";
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>পাসওয়ার্ড পরিবর্তন - MZ Income</title>
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
                    <h1 class="h2">পাসওয়ার্ড পরিবর্তন</h1>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h5>পাসওয়ার্ড পরিবর্তন করুন</h5>
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
                                    <div class="text-center mt-3">
                                        <a href="profile.php" class="btn btn-primary">প্রোফাইলে ফিরে যান</a>
                                    </div>
                                <?php else: ?>
                                    <form method="POST" action="" id="passwordForm">
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
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">পাসওয়ার্ড পরিবর্তন করুন</button>
                                            <a href="profile.php" class="btn btn-outline-secondary">বাতিল করুন</a>
                                        </div>
                                    </form>
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
