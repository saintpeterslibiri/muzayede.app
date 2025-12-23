<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
require_once __DIR__ . '/api.php';

$isLoggedIn = isLoggedIn();
$isAdmin = isAdmin();
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$avatar_path = isset($_SESSION['avatar_path']) ? $_SESSION['avatar_path'] : null;

// Avatar varsayılan resmi veya kullanıcı adının ilk harfi
$avatar_url = null;
if ($avatar_path) {
    // Eğer path uploads/ ile başlıyorsa, backend URL'ini kullan
    if (strpos($avatar_path, 'uploads/') === 0) {
        $avatar_url = PUBLIC_API_URL . '/' . $avatar_path;
    } else {
        $avatar_url = $avatar_path;
    }
}
$avatar_initial = strtoupper(substr($username, 0, 1));
?>
<nav class="navbar">
    <div class="logo">
        <a href="index.php">
            <img src="assets/img/logoN.png" alt="AADuction" class="nav-logo">
            
        </a>
    </div>

    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="auctions.php">Auctions</a></li>
        
        <?php if ($isLoggedIn) : ?>
            <li><a href="create_auction.php">Create Auction</a></li>
            <li><a href="my_auctions.php">My Auctions</a></li>
            <li><a href="my_bids.php">My Bids</a></li>
        <?php endif; ?>
    </ul>

    <?php if ($isLoggedIn) : ?>
        <div class="nav-user">
            <a href="profile.php" class="nav-user-link">
                <?php if ($avatar_url) : ?>
                    <img src="<?php echo htmlspecialchars($avatar_url); ?>" 
                         alt="Avatar" 
                         class="nav-avatar">
                <?php else : ?>
                    <div class="nav-avatar nav-avatar-initial">
                        <?php echo htmlspecialchars($avatar_initial); ?>
                    </div>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($username); ?></span>
            </a>
            <?php if ($isAdmin) : ?>
                <a href="admin_dashboard.php" class="nav-admin-link" style="margin-left: 15px; color: #fff; text-decoration: none; padding: 6px 12px; background: #f59e0b; border-radius: 4px; font-size: 13px; font-weight: 600;">
                    Admin
                </a>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <div class="nav-auth-buttons">
            <a href="login.php" class="nav-login-btn">Login</a>
            <a href="register.php" class="nav-register-btn">Register</a>
        </div>
    <?php endif; ?>
</nav>
