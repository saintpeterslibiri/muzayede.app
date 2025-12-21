<?php
session_start();
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

$response = api_get("/my/bids");
$myBids = [];

if (isset($response['success']) && $response['success'] === true) {
    $data = $response['data'] ?? [];
    $activeBids = $data['active'] ?? [];
    $wonBids = $data['won'] ?? [];
    $lostBids = $data['lost'] ?? [];
    $myBids = array_merge($activeBids, $wonBids, $lostBids);
}
?>

<main class="page">
    <div class="home-container">
        <section class="page-header">
            <h1>My Bids</h1>
            <p>Track the items you are currently bidding on.</p>
        </section>

        <section class="data-table-section">
            <?php if (empty($myBids)): ?>
                <div class="empty-state">
                    <p>You haven't placed any bids yet.</p>
                    <a href="auctions.php" class="btn-primary">Browse Auctions</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Your Bid</th>
                            <th>Current Highest</th>
                            <th>End Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myBids as $bid): ?>
                            <?php
                                $id      = $bid['auction_id'] ?? 0;
                                $title   = $bid['title'] ?? 'No Title';
                                $myAmt   = $bid['my_highest_bid'] ?? 0;
                                $current = $bid['current_price'] ?? 0;
                                $end     = $bid['end_time'] ?? '';
                                $bidStatus = $bid['bid_status'] ?? '';
                            ?>
                            <tr>
                                <td>
                                    <div class="table-item">
                                        <img
                                            src="assets/img/placeholder.png"
                                            alt="Item"
                                            class="table-thumb"
                                        >
                                        <strong class="table-title">
                                            <?php echo htmlspecialchars($title); ?>
                                        </strong>
                                    </div>
                                </td>

                                <td>$<?php echo number_format((float)$myAmt, 2); ?></td>
                                <td>$<?php echo number_format((float)$current, 2); ?></td>
                                <td><?php echo htmlspecialchars($end); ?></td>

                                <td>
                                    <?php if ($bidStatus === 'leading'): ?>
                                        <span class="status-badge active">Highest Bidder</span>
                                    <?php elseif ($bidStatus === 'outbid'): ?>
                                        <span class="status-badge outbid">Outbid</span>
                                    <?php elseif ($bidStatus === 'won'): ?>
                                        <span class="status-badge won">Won</span>
                                    <?php elseif ($bidStatus === 'lost'): ?>
                                        <span class="status-badge lost">Lost</span>
                                    <?php else: ?>
                                        <span class="status-badge">Active</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="auction_detail.php?id=<?php echo $id; ?>" class="btn-small">
                                        View Item
                                    </a>
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
