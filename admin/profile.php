<?php
// profile.php - User profile page
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
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $details = sanitizeInput($_POST['details'] ?? '');
    $mobile = sanitizeInput($_POST['mobile'] ?? '');
    
    // Validate inputs
    if (strlen($details) > 150) {
        $errors[] = "প্রোফাইল ডিটেইলস ১৫০ শব্দের বেশি হওয়া যাবে না";
    }
    
    if (empty($mobile)) {
        $errors[] = "মোবাইল নম্বর প্রয়োজন";
    } elseif (!preg_match('/^01[3-9]\d{8}$/', $mobile)) {
        $errors[] = "সঠিক মোবাইল নম্বর দিন";
    }
    
    // Check if mobile belongs to another user
    if ($mobile != $user['mobile']) {
        $db = connectDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE mobile = ? AND id != ?");
        $stmt->execute([$mobile, $userId]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "মোবাইল নম্বর ইতিমধ্যে ব্যবহৃত হয়েছে";
        }
    }
    
    // Process profile picture upload if exists
    $profilePicture = $user['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $uploadedProfilePic = processUploadedFile('profile_picture', PROFILE_PIC_DIR, $allowedTypes);
        
        if (!$uploadedProfilePic) {
            $errors[] = "প্রোফাইল ছবি আপলোড করতে সমস্যা হয়েছে। JPG বা PNG ফরম্যাট ব্যবহার করুন (সর্বোচ্চ 2MB)";
        } else {
            $profilePicture = $uploadedProfilePic;
        }
    }
    
    // If no errors, update user profile
    if (empty($errors)) {
        $db = connectDB();
        
        try {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, details = ?, mobile = ?, profile_picture = ? WHERE id = ?");
            $stmt->execute([$fullName, $details, $mobile, $profilePicture, $userId]);
            
            $success = "প্রোফাইল সফলভাবে আপডেট করা হয়েছে";
            
            // Refresh user data
            $user = getUserById($userId);
            
        } catch (Exception $e) {
            $errors[] = "দুঃখিত, প্রোফাইল আপডেট করতে সমস্যা হয়েছে";
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>প্রোফাইল - MZ Income</title>
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
                    <h1 class="h2">প্রোফাইল</h1>
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
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>প্রোফাইল তথ্য</h5>
                            </div>
                            <div class="card-body profile-card">
                                <div class="text-center mb-4">
                                    <img src="<?php echo isset($user['profile_picture']) && $user['profile_picture'] != 'default.jpg' ? PROFILE_PIC_DIR . $user['profile_picture'] : DEFAULT_PROFILE_PIC; ?>" class="profile-picture-large rounded-circle mb-3" alt="Profile Picture">
                                    <h4><?php echo isset($user['full_name']) && !empty($user['full_name']) ? $user['full_name'] : $user['username']; ?></h4>
                                    <p class="text-muted">
                                        <span class="badge bg-primary"><?php echo $user['active_package'] != 'none' ? ucfirst($user['active_package']) . ' সদস্য' : 'বেসিক সদস্য'; ?></span>
                                    </p>
                                </div>
                                
                                <div class="profile-info">
                                    <div class="info-item">
                                        <span class="label"><i class="fas fa-id-card me-2"></i> অ্যাকাউন্ট নম্বর:</span>
                                        <span class="value"><?php echo $user['account_number']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label"><i class="fas fa-user me-2"></i> ইউজারনেম:</span>
                                        <span class="value"><?php echo $user['username']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label"><i class="fas fa-phone me-2"></i> মোবাইল:</span>
                                        <span class="value"><?php echo $user['mobile']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label"><i class="fas fa-sitemap me-2"></i> রেফারেল কোড:</span>
                                        <span class="value"><?php echo $user['referrer_code']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label"><i class="fas fa-user-plus me-2"></i> রেফারার:</span>
                                        <span class="value">
                                            <?php
                                                if ($user['referrer_id']) {
                                                    $referrer = getUserById($user['referrer_id']);
                                                    echo $referrer ? $referrer['username'] . ' (#' . $referrer['account_number'] . ')' : 'অজানা';
                                                } else {
                                                    echo 'কেউ নেই';
                                                }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label"><i class="fas fa-calendar me-2"></i> যোগদান:</span>
                                        <span class="value"><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                                    </div>
                                    <?php if ($user['active_package'] != 'none'): ?>
                                        <div class="info-item">
                                            <span class="label"><i class="fas fa-crown me-2"></i> প্যাকেজ মেয়াদ:</span>
                                            <span class="value">
                                                <?php
                                                    // Calculate days remaining
                                                    $expiryDate = new DateTime($user['package_expiry_date']);
                                                    $currentDate = new DateTime();
                                                    $interval = $currentDate->diff($expiryDate);
                                                    $daysRemaining = $interval->days;
                                                    
                                                    echo date('d M Y', strtotime($user['package_expiry_date'])) . ' (' . $daysRemaining . ' দিন বাকি)';
                                                ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($user['details'])): ?>
                                    <div class="profile-details mt-4">
                                        <h6>আমার সম্পর্কে</h6>
                                        <p><?php echo $user['details']; ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>প্রোফাইল এডিট করুন</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="profile_picture" class="form-label">প্রোফাইল ছবি</label>
                                        <input type="file" class="form-control" id="profile_picture" name="profile_picture">
                                        <div class="form-text">JPG বা PNG ফরম্যাট (সর্বোচ্চ 2MB)</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">পুরো নাম</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo isset($user['full_name']) ? $user['full_name'] : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="mobile" class="form-label">মোবাইল নম্বর</label>
                                        <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo $user['mobile']; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="details" class="form-label">আমার সম্পর্কে (সর্বোচ্চ ১৫০ শব্দ)</label>
                                        <textarea class="form-control" id="details" name="details" rows="3" maxlength="150"><?php echo isset($user['details']) ? $user['details'] : ''; ?></textarea>
                                        <div class="form-text">
                                            <span id="charCount">0</span>/150 শব্দ
                                        </div>
                                    </div>
                                    
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary">প্রোফাইল আপডেট করুন</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>অ্যাকাউন্ট সেটিংস</h5>
                            </div>
                            <div class="card-body">
                                <div class="settings-section">
                                    <h6>পাসওয়ার্ড পরিবর্তন করুন</h6>
                                    <form method="POST" action="change_password.php" id="passwordForm">
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
                                        
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-warning">পাসওয়ার্ড পরিবর্তন করুন</button>
                                        </div>
                                    </form>
                                </div>
                                
                                <hr>
                                
                                <div class="settings-section">
                                    <h6>ওয়ালেট সেটিংস</h6>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="toggleWalletBalance" <?php echo $user['hide_balance'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="toggleWalletBalance">ব্যালেন্স লুকান</label>
                                    </div>
                                </div>
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
        // Character counter for details
        const detailsTextarea = document.getElementById('details');
        const charCount = document.getElementById('charCount');
        
        function updateCharCount() {
            charCount.textContent = detailsTextarea.value.length;
        }
        
        detailsTextarea.addEventListener('input', updateCharCount);
        updateCharCount(); // Initial count
        
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
        
        // Toggle wallet balance visibility
        document.getElementById('toggleWalletBalance').addEventListener('change', function() {
            // Send AJAX request to update user preference
            fetch('ajax/update_balance_visibility.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=set&hide=' + (this.checked ? '1' : '0')
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success notification can be added here
                }
            });
        });
    </script>
</body>
</html>

