<?php
session_start();
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

// ---- Kullanıcı kontrolü ----
$userId = $_SESSION['user_id'] ?? 3;
$isLoggedIn = isset($_SESSION['user_id']);

// ---- POST işlemleri (Profil güncelleme) ----
$successMsg = null;
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
    $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // Profil güncelleme API çağrısı
    $updateData = [];
    if ($fullName !== '') $updateData['full_name'] = $fullName;
    if ($email !== '') $updateData['email'] = $email;
    
    if (!empty($updateData)) {
        $response = api_put('/profile', $updateData);
        
        if (isset($response['success']) && $response['success'] === true) {
            $successMsg = 'Profile updated successfully!';
        } else {
            $errorMsg = $response['error'] ?? $response['message'] ?? 'Failed to update profile';
        }
    }
}

// ---- Profil bilgilerini çek ----
$user = null;
$stats = null;
$apiError = null;

$response = api_get('/profile');

if (is_array($response) && isset($response['success']) && $response['success'] === true) {
    $data = $response['data'] ?? [];
    $user = $data['user'] ?? null;
    $stats = $data['stats'] ?? null;
} else {
    $apiError = $response['error'] ?? $response['message'] ?? 'Failed to load profile';
}

// Kullanıcı bilgileri
$username = $user['username'] ?? 'Guest';
$email = $user['email'] ?? '';
$fullName = $user['full_name'] ?? '';
$role = $user['role'] ?? 'buyer';
$avatarPath = $user['avatar_path'] ?? 'assets/img/avatar-placeholder.png';
$createdAt = $user['created_at'] ?? '';

// İstatistikler
$auctionCount = $stats['auctionsCreated'] ?? 0;
$bidCount = $stats['totalBids'] ?? 0;
$wonCount = $stats['auctionsWon'] ?? 0;
?>

<main class="page">

    <div class="home-container">

        <!-- GİRİŞ UYARISI -->
        <?php if (!$isLoggedIn): ?>
            <div class="warning-banner">
                This is a demo view. To see and edit your real profile data, please
                <a href="login.php" class="warning-link">log in to your account</a>.
            </div>
        <?php endif; ?>

        <!-- SAYFA BAŞLIĞI -->
        <section class="page-header">
            <h1>My Profile</h1>
            <p>View and update your account information.</p>
        </section>

        <!-- BAŞARI / HATA MESAJLARI -->
        <?php if ($successMsg): ?>
            <p class="success-message"><?php echo htmlspecialchars($successMsg); ?></p>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <p class="error-message"><?php echo htmlspecialchars($errorMsg); ?></p>
        <?php endif; ?>

        <?php if ($apiError): ?>
            <p class="error-message"><?php echo htmlspecialchars($apiError); ?></p>
        <?php endif; ?>

        <!-- PROFIL LAYOUT -->
        <div class="profile-layout">

            <!-- SOL KISIM: AVATAR + KISA BİLGİ -->
            <aside class="profile-sidebar">
                <div class="avatar-wrapper">
                    <img src="<?php echo htmlspecialchars($avatarPath ?: 'assets/img/avatar-placeholder.png'); ?>" class="avatar-img" alt="Avatar">
                </div>

                <div class="profile-basic-info">
                    <div class="profile-name"><?php echo htmlspecialchars($fullName ?: $username); ?></div>
                    <div class="profile-username">@<?php echo htmlspecialchars($username); ?></div>
                    <div class="profile-role"><?php echo ucfirst(htmlspecialchars($role)); ?></div>
                </div>

                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-label">Auctions</span>
                        <span class="stat-value"><?php echo (int)$auctionCount; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Bids</span>
                        <span class="stat-value"><?php echo (int)$bidCount; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Won</span>
                        <span class="stat-value"><?php echo (int)$wonCount; ?></span>
                    </div>
                </div>

                <?php if ($createdAt): ?>
                    <div class="profile-joined">
                        Member since: <?php echo htmlspecialchars(date('M Y', strtotime($createdAt))); ?>
                    </div>
                <?php endif; ?>
            </aside>

            <!-- SAĞ KISIM: PROFIL FORMU -->
            <section class="profile-main">
                <section class="form-section">
                    <form method="POST" id="profile-form" class="profile-form">
                        <h2>Profile Info</h2>

                        <label>
                            Full Name
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" <?php echo !$isLoggedIn ? 'disabled' : ''; ?>>
                        </label>

                        <label>
                            Email
                            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" <?php echo !$isLoggedIn ? 'disabled' : ''; ?>>
                        </label>

                        <label>
                            Username
                            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
                        </label>

                        <label>
                            Role
                            <input type="text" value="<?php echo ucfirst(htmlspecialchars($role)); ?>" readonly>
                        </label>

                        <?php if ($isLoggedIn): ?>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Save Changes</button>
                            </div>
                        <?php endif; ?>
                    </form>
                </section>
            </section>

        </div>

    </div>

</main>

<?php include 'includes/footer.php'; ?>