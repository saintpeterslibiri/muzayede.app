<?php
session_start();
?>

<?php
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

// API'den kullanıcının kendi açık artırmalarını çek
$response = api_get("/my/auctions");
$myAuctions = [];

if (isset($response['success']) && $response['success'] === true) {
    $myAuctions = $response['data']['auctions'] ?? $response['data'] ?? [];
}
?>

<main class="page">
    <div class="home-container">
        <!-- GİRİŞ YAPMAMIŞ KULLANICI İÇİN UYARI -->
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="warning-banner">
                ⚠️ You must be logged in to manage your auctions.
                <a href="login.php" class="warning-link">Login</a>
            </div>
        <?php endif; ?>

        <!-- SAYFA BAŞLIĞI -->
        <section class="page-header">
            <h1>My Auctions</h1>
            <p>Manage the items you have listed for sale.</p>
        </section>

        <!-- TABLO BÖLÜMÜ -->
        <section class="data-table-section">
            <?php if (empty($myAuctions)): ?>
                <div class="empty-state">
                    <p>You haven't created any auctions yet.</p>
                    <a href="create_auction.php" class="btn-primary">Create Your First Auction</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Starting Price</th>
                            <th>Current Bid</th>
                            <th>End Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myAuctions as $item): ?>
                            <?php
                                $id      = $item['id'] ?? 0;
                                $title   = $item['title'] ?? 'No Title';
                                $start   = $item['starting_price'] ?? 0;
                                $current = $item['current_price'] ?? $start;
                                $end     = $item['end_time'] ?? '';
                                $status  = $item['status'] ?? 'Active';
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($title); ?></strong></td>
                                <td>$<?php echo number_format((float)$start, 2); ?></td>
                                <td>$<?php echo number_format((float)$current, 2); ?></td>
                                <td><?php echo htmlspecialchars($end); ?></td>
                                <td><span class="status-badge <?php echo strtolower($status); ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                <td>
                                    <a href="auction_detail.php?id=<?php echo $id; ?>" class="btn-small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
