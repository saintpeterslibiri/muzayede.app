<?php 
session_start();
require_once 'includes/api.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$token = getTokenFromSession();
$error = '';
$success = '';
$user = null;
$stats = null;

// Profil bilgilerini getir
$profileResponse = getUserProfile($token);
if ($profileResponse['success'] && isset($profileResponse['data']['data'])) {
    $user = $profileResponse['data']['data']['user'];
    $stats = $profileResponse['data']['data']['stats'];
    
    // Session'ı güncelle
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['avatar_path'] = $user['avatar_path'];
} else {
    $error = 'Failed to load profile data';
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Profil güncelleme (update_profile flag'i varsa)
    if (isset($_POST['update_profile']) && $_POST['update_profile'] === '1' && 
        isset($_POST['full_name']) && isset($_POST['email'])) {
        
        $profileUpdated = false;
        $avatarUpdated = false;
        
        // Avatar yükleme (eğer dosya seçildiyse)
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $tempFile = $_FILES['avatar']['tmp_name'];
            
            // Dosya boyutu kontrolü
            if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                $error = 'File size exceeds 2MB limit';
            } else {
                $uploadResponse = uploadAvatar($token, $tempFile);
                
                if ($uploadResponse['success']) {
                    $avatarUpdated = true;
                } else {
                    $errorMsg = $uploadResponse['data']['message'] ?? 'Failed to upload avatar';
                    if (isset($uploadResponse['http_code'])) {
                        $errorMsg .= ' (HTTP ' . $uploadResponse['http_code'] . ')';
                    }
                    $error = $errorMsg;
                }
            }
        } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_OK && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Dosya yükleme hatası (dosya seçilmediyse hata gösterme)
            switch ($_FILES['avatar']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = 'File size exceeds limit';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = 'File was only partially uploaded';
                    break;
                default:
                    $error = 'File upload error occurred';
            }
        }
        
        // Profil bilgilerini güncelle
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        
        if (empty($full_name) || empty($email)) {
            $error = 'Full name and email are required';
        } else {
            $updateResponse = updateUserProfile($token, $full_name, $email);
            
            if ($updateResponse['success']) {
                $profileUpdated = true;
            } else {
                $error = $updateResponse['data']['message'] ?? 'Failed to update profile';
            }
        }
        
        // Başarı mesajı
        if ($profileUpdated || $avatarUpdated) {
            $successMessages = [];
            if ($profileUpdated) {
                $successMessages[] = 'Profile updated successfully';
            }
            if ($avatarUpdated) {
                $successMessages[] = 'Avatar updated successfully';
            }
            $success = implode('. ', $successMessages);
            
            // Profil bilgilerini yeniden yükle
            $profileResponse = getUserProfile($token);
            if ($profileResponse['success']) {
                $user = $profileResponse['data']['data']['user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['avatar_path'] = $user['avatar_path'];
            }
        }
    }
    
    // Şifre değiştirme (ayrı form)
    if (isset($_POST['change_password']) && $_POST['change_password'] === '1') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_new_password'] ?? '';
        
        if (empty($current_password)) {
            $error = 'Current password is required';
        } elseif (empty($new_password)) {
            $error = 'New password is required';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long';
        } else {
            $passwordResponse = changePassword($token, $current_password, $new_password);
            
            if ($passwordResponse['success']) {
                $success = 'Password changed successfully';
            } else {
                $error = $passwordResponse['data']['message'] ?? 'Failed to change password';
            }
        }
    }
}

include 'includes/header.php'; 
include 'includes/navbar.php'; 
?>

<main class="page">

    <div class="home-container">
        <!-- SAYFA BAŞLIĞI -->
        <section class="page-header">
            <h1>My Profile</h1>
            <p>View and update your account information.</p>
        </section>

        <?php if ($error) : ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof showErrorToast === 'function') {
                        showErrorToast('<?php echo addslashes($error); ?>');
                    }
                });
            </script>
        <?php endif; ?>

        <?php if ($success) : ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof showSuccessToast === 'function') {
                        showSuccessToast('<?php echo addslashes($success); ?>');
                    }
                });
            </script>
        <?php endif; ?>

        <?php if ($user) : ?>
            <!-- PROFIL LAYOUT: SOL (avatar kartı) + SAĞ (form) -->
            <div class="profile-layout">

                <!-- SOL KISIM: AVATAR + KISA BILGI -->
                <aside class="profile-sidebar">
                    <div class="avatar-wrapper">
                        <?php if ($user['avatar_path']) : ?>
                            <?php 
                            // Avatar path'i düzelt (backend'den gelen path'i kullan)
                            $avatarUrl = $user['avatar_path'];
                            // Eğer path uploads/ ile başlıyorsa, backend URL'ini kullan
                            if (strpos($avatarUrl, 'uploads/') === 0) {
                                $avatarUrl = PUBLIC_API_URL . '/' . $avatarUrl;
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" class="avatar-img" alt="Avatar">
                        <?php else : ?>
                            <div class="avatar-img" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; font-size: 48px;">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="profile-basic-info">
                        <div class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                    </div>

                    <div class="avatar-upload">
                        <label class="avatar-upload-label">
                            Change Photo
                            <input type="file" name="avatar" id="avatar-input" form="profile-form" accept="image/*" style="display: none;">
                        </label>
                        <div class="avatar-hint">
                            JPG, PNG • Max 2MB
                        </div>
                        <script>
                            document.getElementById('avatar-input').addEventListener('change', function() {
                                if (this.files.length > 0) {
                                    document.querySelector('.avatar-hint').textContent = 'Selected: ' + this.files[0].name;
                                }
                            });
                        </script>
                    </div>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-label">Auctions</span>
                            <span class="stat-value"><?php echo htmlspecialchars($stats['auctions'] ?? 0); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Bids</span>
                            <span class="stat-value"><?php echo htmlspecialchars($stats['bids'] ?? 0); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Won</span>
                            <span class="stat-value"><?php echo htmlspecialchars($stats['won'] ?? 0); ?></span>
                        </div>
                    </div>
                </aside>

                <!-- SAĞ KISIM: PROFIL FORMU -->
                <section class="profile-main">

                    <section class="form-section">

                        <form method="POST" id="profile-form" class="profile-form" enctype="multipart/form-data">
                            <input type="hidden" name="update_profile" value="1">
                            <h2>Profile Info</h2>

                            <label>
                                Full Name
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </label>

                            <label>
                                Email
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </label>

                            <label>
                                Username
                                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly style="background-color: #f3f4f6; cursor: not-allowed;">
                            </label>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Save Changes</button>
                            </div>
                        </form>

                        <form method="POST" id="password-form" class="profile-form">
                            <input type="hidden" name="change_password" value="1">
                            <h2>Change Password</h2>

                            <label>
                                Current Password
                                <input type="password" name="current_password" required>
                            </label>

                            <label>
                                New Password
                                <input type="password" name="new_password" required>
                            </label>

                            <label>
                                Confirm New Password
                                <input type="password" name="confirm_new_password" required>
                            </label>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Change Password</button>
                            </div>
                        </form>

                        <!-- Logout Section -->
                        <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #e5e7eb;">
                            <h2>Account Actions</h2>
                            <p style="color: #6b7280; margin-bottom: 15px;">Sign out from your account</p>
                            <a href="logout.php" class="btn-primary" style="background-color: #ef4444; display: inline-block; text-decoration: none;">
                                Logout
                            </a>
                        </div>
                    </section>
                </section>
            </div><!-- .profile-layout -->
        <?php else : ?>
            <div class="warning-banner">
                Failed to load profile data. Please try again later.
            </div>
        <?php endif; ?>

    </div><!-- .home-container -->

</main>

<?php include 'includes/footer.php'; ?>
