<?php
session_start();
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

// ---- Kullanıcı kontrolü ----
// TODO: Auth sistemi gelince güncellenecek
// Şimdilik test için buyer1 (id: 3) kullanıyoruz
$userId = $_SESSION['user_id'] ?? 3;
$isLoggedIn = isset($_SESSION['user_id']);

// ---- API çağrısı ----
$response = api_get('/my/bids');

// Response'u parse et
$activeBids = [];
$wonBids = [];
$lostBids = [];
$stats = null;
$apiError = null;

if (is_array($response) && isset($response['success']) && $response['success'] === true) {
    $data = $response['data'] ?? [];
    $activeBids = $data['active'] ?? [];
    $wonBids = $data['won'] ?? [];
    $lostBids = $data['lost'] ?? [];
    $stats = $data['stats'] ?? null;
} else {
    $apiError = $response['error'] ?? $response['message'] ?? 'Failed to load bids';
}
?>

<main class="page">

    <div class="home-container">

        <!-- GİRİŞ YAPMAMIŞ KULLANICI İÇİN UYARI -->
        <?php if (!$isLoggedIn): ?>
            <div class="warning-banner">
                ⚠️ You must be logged in to view and track your bids.
                <a href="login.php" class="warning-link">Login</a>
            </div>
        <?php endif; ?>

        <!-- SAYFA BAŞLIĞI -->
        <section class="page-header">
            <h1>My Bids</h1>
            <p>Track your active and past bids.</p>
        </section>

        <!-- API HATASI -->
        <?php if ($apiError): ?>
            <p class="error-message"><?php echo htmlspecialchars($apiError); ?></p>
        <?php endif; ?>

        <!-- İSTATİSTİKLER -->
        <?php if ($stats && $isLoggedIn): ?>
            <section class="stats-section">
                <div class="stat-card">
                    <h3>Total Bids</h3>
                    <p><?php echo (int)($stats['totalBidsPlaced'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Active</h3>
                    <p><?php echo (int)($stats['activeBids'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Leading</h3>
                    <p><?php echo (int)($stats['leading'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Outbid</h3>
                    <p><?php echo (int)($stats['outbid'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Won</h3>
                    <p><?php echo (int)($stats['won'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Lost</h3>
                    <p><?php echo (int)($stats['lost'] ?? 0); ?></p>
                </div>
            </section>
        <?php endif; ?>

        <!-- ACTIVE BIDS -->
        <section class="table-section">
            <h2>Active Bids</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Your Bid</th>
                        <th>Current Highest</th>
                        <th>Time Left</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$isLoggedIn): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:20px; color:#6b7280;">
                                Please log in to see your active bids.
                            </td>
                        </tr>

                    <?php elseif (empty($activeBids)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:20px; color:#6b7280;">
                                You don't have any active bids.
                                <a href="auctions.php">Browse auctions</a>
                            </td>
                        </tr>

                    <?php else: ?>
                        <?php foreach ($activeBids as $bid): ?>
                            <?php
                                $auctionId = $bid['auction_id'] ?? '';
                                $title = $bid['title'] ?? 'Untitled';
                                $myBid = $bid['my_highest_bid'] ?? 0;
                                $currentPrice = $bid['current_price'] ?? 0;
                                $endTime = $bid['end_time'] ?? '';
                                $bidStatus = $bid['bid_status'] ?? 'unknown';
                                
                                // Status class
                                $statusClass = $bidStatus === 'leading' ? 'status-active' : 'status-outbid';
                            ?>
                            <tr>
                                <td>
                                    <a href="auction_detail.php?id=<?php echo urlencode($auctionId); ?>">
                                        <?php echo htmlspecialchars($title); ?>
                                    </a>
                                </td>
                                <td>$<?php echo number_format((float)$myBid, 2); ?></td>
                                <td>$<?php echo number_format((float)$currentPrice, 2); ?></td>
                                <td><?php echo $endTime ? htmlspecialchars($endTime) : 'N/A'; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($bidStatus); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- WON AUCTIONS -->
        <section class="table-section">
            <h2>Won Auctions</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Winning Bid</th>
                        <th>Result</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$isLoggedIn): ?>
                        <tr>
                            <td colspan="3" style="text-align:center; padding:20px; color:#6b7280;">
                                Please log in to see your won auctions.
                            </td>
                        </tr>

                    <?php elseif (empty($wonBids)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding:20px; color:#6b7280;">
                                You haven't won any auctions yet.
                            </td>
                        </tr>

                    <?php else: ?>
                        <?php foreach ($wonBids as $bid): ?>
                            <?php
                                $auctionId = $bid['auction_id'] ?? '';
                                $title = $bid['title'] ?? 'Untitled';
                                $myBid = $bid['my_highest_bid'] ?? 0;

                                $completeUrl = 'order_complete.php?auction_id=' . urlencode($auctionId)
                                             . '&amount=' . urlencode((string)$myBid);
                            ?>
                            <tr>
                                <td>
                                    <a href="auction_detail.php?id=<?php echo urlencode($auctionId); ?>">
                                        <?php echo htmlspecialchars($title); ?>
                                    </a>
                                </td>
                                <td>$<?php echo number_format((float)$myBid, 2); ?></td>
                                <td>
                                    <span class="status-badge status-won">Won</span>
                                </td>
                                <td>
                                    <a href="<?php echo $completeUrl; ?>" class="btn btn-primary btn-sm">
                                        Complete Order
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- LOST AUCTIONS -->
        <section class="table-section">
            <h2>Lost Auctions</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Your Bid</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$isLoggedIn): ?>
                        <tr>
                            <td colspan="3" style="text-align:center; padding:20px; color:#6b7280;">
                                Please log in to see your past bids.
                            </td>
                        </tr>

                    <?php elseif (empty($lostBids)): ?>
                        <tr>
                            <td colspan="3" style="text-align:center; padding:20px; color:#6b7280;">
                                No lost auctions.
                            </td>
                        </tr>

                    <?php else: ?>
                        <?php foreach ($lostBids as $bid): ?>
                            <?php
                                $auctionId = $bid['auction_id'] ?? '';
                                $title = $bid['title'] ?? 'Untitled';
                                $myBid = $bid['my_highest_bid'] ?? 0;
                            ?>
                            <tr>
                                <td>
                                    <a href="auction_detail.php?id=<?php echo urlencode($auctionId); ?>">
                                        <?php echo htmlspecialchars($title); ?>
                                    </a>
                                </td>
                                <td>$<?php echo number_format((float)$myBid, 2); ?></td>
                                <td>
                                    <span class="status-badge status-lost">Lost</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </div>

</main>

<?php include 'includes/footer.php'; ?>