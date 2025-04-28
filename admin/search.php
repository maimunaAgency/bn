<?php
// search.php - User search page
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$userId = $_SESSION['user_id'];

$searchResults = [];
$searchTerm = '';

// Process search
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $searchTerm = sanitizeInput($_GET['q']);
    
    $db = connectDB();
    
    // Search by account number or username
    $stmt = $db->prepare("
        SELECT * FROM users 
        WHERE (account_number LIKE ? OR username LIKE ?) 
        AND id != ?
        LIMIT 20
    ");
    $stmt->execute(["%$searchTerm%", "%$searchTerm%", $userId]);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>সার্চ - MZ Income</title>
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
                    <h1 class="h2">ইউজার সার্চ</h1>
                </div>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="" method="GET" class="search-form">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" name="q" placeholder="অ্যাকাউন্ট নম্বর বা ইউজারনেম দিয়ে সার্চ করুন" value="<?php echo $searchTerm; ?>" required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> সার্চ
                                </button>
                            </div>
                        </form>
                        
                        <?php if (!empty($searchTerm)): ?>
                            <div class="search-results mt-4">
                                <h5>সার্চ রেজাল্ট: "<?php echo $searchTerm; ?>"</h5>
                                
                                <?php if (count($searchResults) > 0): ?>
                                    <div class="row">
                                        <?php foreach ($searchResults as $user): ?>
                                            <div class="col-md-6 col-lg-4">
                                                <div class="user-card">
                                                    <div class="user-card-header">
                                                        <img src="<?php echo isset($user['profile_picture']) && $user['profile_picture'] != 'default.jpg' ? PROFILE_PIC_DIR . $user['profile_picture'] : DEFAULT_PROFILE_PIC; ?>" class="user-pic rounded-circle" alt="Profile">
                                                        <div class="user-info">
                                                            <h5><?php echo isset($user['full_name']) && !empty($user['full_name']) ? $user['full_name'] : $user['username']; ?></h5>
                                                            <p class="text-muted">@<?php echo $user['username']; ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="user-card-body">
                                                        <div class="user-details">
                                                            <div class="detail-item">
                                                                <span class="label"><i class="fas fa-id-card"></i> অ্যাকাউন্ট:</span>
                                                                <span class="value">#<?php echo $user['account_number']; ?></span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <span class="label"><i class="fas fa-crown"></i> প্যাকেজ:</span>
                                                                <span class="value">
                                                                    <?php if ($user['active_package'] != 'none'): ?>
                                                                        <span class="badge bg-success"><?php echo ucfirst($user['active_package']); ?></span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-secondary">বেসিক</span>
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <span class="label"><i class="fas fa-calendar"></i> যোগদান:</span>
                                                                <span class="value"><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if (!empty($user['details'])): ?>
                                                            <div class="user-bio">
                                                                <p><?php echo strlen($user['details']) > 100 ? substr($user['details'], 0, 100) . '...' : $user['details']; ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="user-actions mt-3">
                                                            <a href="view_profile.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-user"></i> প্রোফাইল দেখুন
                                                            </a>
                                                            <a href="transfer.php?to=<?php echo $user['account_number']; ?>" class="btn btn-sm btn-outline-success">
                                                                <i class="fas fa-exchange-alt"></i> ফান্ড পাঠান
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-search fa-3x text-muted"></i>
                                        </div>
                                        <h5>কোন ইউজার পাওয়া যায়নি</h5>
                                        <p>দুঃখিত, আপনার সার্চ কোয়েরি মেলে এমন কোন ইউজার পাওয়া যায়নি।</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-5">
                                <div class="mb-3">
                                    <i class="fas fa-users fa-3x text-muted"></i>
                                </div>
                                <h5>ইউজার খুঁজুন</h5>
                                <p>অ্যাকাউন্ট নম্বর বা ইউজারনেম দিয়ে সার্চ করুন।</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>