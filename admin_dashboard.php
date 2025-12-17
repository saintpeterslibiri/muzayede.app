<?php
session_start();
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

// ---- Admin kontrolü ----
$userId = $_SESSION['user_id'] ?? 1; // admin for testing
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = true; // TODO: Auth sisteminden kontrol edilecek

// ---- POST işlemleri (Ban user, Delete auction) ----
$successMsg = null;
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ban user
    if (isset($_POST['ban_user_id'])) {
        $banUserId = (int)$_POST['ban_user_id'];
        $response = api_put('/admin/users/' . $banUserId . '/ban', []);
        
        if (isset($response['success']) && $response['success'] === true) {
            $action = $response['data']['action'] ?? 'updated';
            $successMsg = "User {$action} successfully!";
        } else {
            $errorMsg = $response['error'] ?? $response['message'] ?? 'Failed to update user';
        }
    }
    
    // Delete auction
    if (isset($_POST['delete_auction_id'])) {
        $deleteAuctionId = (int)$_POST['delete_auction_id'];
        $response = api_delete('/admin/auctions/' . $deleteAuctionId);
        
        if (isset($response['success']) && $response['success'] === true) {
            $successMsg = 'Auction deleted successfully!';
        } else {
            $errorMsg = $response['error'] ?? $response['message'] ?? 'Failed to delete auction';
        }
    }
}

// ---- İstatistikleri çek ----
$stats = null;
$statsError = null;

$statsResponse = api_get('/admin/stats');

if (is_array($statsResponse) && isset($statsResponse['success']) && $statsResponse['success'] === true) {
    $stats = $statsResponse['data'] ?? null;
} else {
    $statsError = $statsResponse['error'] ?? $statsResponse['message'] ?? 'Failed to load statistics';
}

// ---- Kullanıcıları çek ----
$users = [];
$usersResponse = api_get('/admin/users?limit=20');

if (is_array($usersResponse) && isset($usersResponse['success']) && $usersResponse['success'] === true) {
    $users = $usersResponse['data']['users'] ?? [];
}

// İstatistik değerleri
$totalUsers = $stats['users']['total'] ?? 0;
$activeAuctions = $stats['auctions']['byStatus']['active'] ?? 0;
$endedAuctions = $stats['auctions']['byStatus']['ended'] ?? 0;
$totalBids = $stats['bids']['total'] ?? 0;
?>

<main class="page">

    <div class="home-container">

        <!-- ADMIN KONTROLÜ -->
        <?php if (!$isAdmin): ?>
            <div class="warning-banner">
                ⚠️ You don't have permission to access this page.
                <a href="index.php" class="warning-link">Go Home</a>
            </div>
        <?php endif; ?>

        <!-- SAYFA BAŞLIĞI -->
        <section class="page-header">
            <h1>Admin Dashboard</h1>
            <p>Manage users and auctions.</p>
        </section>

        <!-- BAŞARI / HATA MESAJLARI -->
        <?php if ($successMsg): ?>
            <p class="success-message"><?php echo htmlspecialchars($successMsg); ?></p>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <p class="error-message"><?php echo htmlspecialchars($errorMsg); ?></p>
        <?php endif; ?>

        <?php if ($statsError): ?>
            <p class="error-message"><?php echo htmlspecialchars($statsError); ?></p>
        <?php endif; ?>

        <!-- İSTATİSTİKLER -->
        <section class="admin-stats">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p><?php echo (int)$totalUsers; ?></p>
            </div>
            <div class="stat-card">
                <h3>Active Auctions</h3>
                <p><?php echo (int)$activeAuctions; ?></p>
            </div>
            <div class="stat-card">
                <h3>Ended Auctions</h3>
                <p><?php echo (int)$endedAuctions; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Bids</h3>
                <p><?php echo (int)$totalBids; ?></p>
            </div>
        </section>

        <!-- KULLANICILAR TABLOSU -->
        <section class="admin-tables">
            <h2>Users</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Auctions</th>
                        <th>Bids</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:20px;">
                                No users found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <?php
                                $uid = $user['id'] ?? '';
                                $uname = $user['username'] ?? '';
                                $uemail = $user['email'] ?? '';
                                $urole = $user['role'] ?? '';
                                $ustatus = $user['status'] ?? '';
                                $uAuctionCount = $user['auction_count'] ?? 0;
                                $uBidCount = $user['bid_count'] ?? 0;
                                
                                $statusClass = $ustatus === 'active' ? 'status-active' : 'status-banned';
                            ?>
                            <tr>
                                <td><?php echo (int)$uid; ?></td>
                                <td><?php echo htmlspecialchars($uname); ?></td>
                                <td><?php echo htmlspecialchars($uemail); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($urole)); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst(htmlspecialchars($ustatus)); ?>
                                    </span>
                                </td>
                                <td><?php echo (int)$uAuctionCount; ?></td>
                                <td><?php echo (int)$uBidCount; ?></td>
                                <td>
                                    <?php if ($urole !== 'admin'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="ban_user_id" value="<?php echo (int)$uid; ?>">
                                            <button type="submit" class="btn-small <?php echo $ustatus === 'banned' ? 'btn-success' : 'btn-danger'; ?>">
                                                <?php echo $ustatus === 'banned' ? 'Unban' : 'Ban'; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- SON AKTİVİTELER -->
        <?php if ($stats && isset($stats['recentActivity'])): ?>
            <section class="admin-tables">
                <h2>Recent Auctions</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recentAuctions = $stats['recentActivity']['auctions'] ?? [];
                        foreach ($recentAuctions as $auction): 
                        ?>
                            <tr>
                                <td><?php echo (int)($auction['id'] ?? 0); ?></td>
                                <td>
                                    <a href="auction_detail.php?id=<?php echo (int)($auction['id'] ?? 0); ?>">
                                        <?php echo htmlspecialchars($auction['title'] ?? ''); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($auction['seller'] ?? ''); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($auction['status'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($auction['created_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

    </div>

</main>

<?php include 'includes/footer.php'; ?>