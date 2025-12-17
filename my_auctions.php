<?php
session_start();
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

// ---- Kullanıcı kontrolü ----
// TODO: Auth sistemi gelince burası güncellenecek
// Şimdilik test için seller1 (id: 2) kullanıyoruz
$userId = $_SESSION['user_id'] ?? 2;
$isLoggedIn = isset($_SESSION['user_id']);

// ---- Filtre değerlerini al ----
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// ---- API çağrısı ----
$params = [];
if ($status !== '') $params['status'] = $status;
$params['page'] = $page;
$params['limit'] = 10;

$queryString = http_build_query($params);

// /api/my/auctions endpoint'ini çağır
$response = api_get('/my/auctions?' . $queryString);

// Response'u parse et
$auctions = [];
$pagination = null;
$apiError = null;

if (is_array($response) && isset($response['success']) && $response['success'] === true) {
    $data = $response['data'] ?? [];
    $auctions = $data['auctions'] ?? [];
    $pagination = $data['pagination'] ?? null;
} else {
    $apiError = $response['error'] ?? $response['message'] ?? 'Failed to load auctions';
}
?>

<main class="page">

    <div class="home-container">

        <!-- GİRİŞ YAPMAMIŞ KULLANICI İÇİN UYARI -->
        <?php if (!$isLoggedIn): ?>
            <div class="warning-banner">
                ⚠️ You must be logged in to manage your auctions.
                <a href="login.php" class="warning-link">Login</a>
            </div>
        <?php endif; ?>

        <!-- SAYFA BAŞLIĞI -->
        <section class="page-header">
            <h1>My Auctions</h1>
            <p>Manage the auctions you have created.</p>
            <a href="create_auction.php" class="btn-primary">Create New Auction</a>
        </section>

        <!-- FİLTRE -->
        <section class="filters">
            <form class="filter-form" method="GET">
                <select name="status">
                    <option value="" <?php echo $status === '' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="ended" <?php echo $status === 'ended' ? 'selected' : ''; ?>>Ended</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit">Filter</button>
            </form>
        </section>

        <!-- API HATASI -->
        <?php if ($apiError): ?>
            <p class="error-message"><?php echo htmlspecialchars($apiError); ?></p>
        <?php endif; ?>

        <!-- TABLO BÖLÜMÜ -->
        <section class="table-section">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Starting Price</th>
                        <th>Current Bid</th>
                        <th>Bids</th>
                        <th>Status</th>
                        <th>End Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>

                    <?php if (!$isLoggedIn): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:20px; color:#6b7280;">
                                Please log in to see your auctions.
                            </td>
                        </tr>

                    <?php elseif (empty($auctions)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:20px; color:#6b7280;">
                                You haven't created any auctions yet.
                                <a href="create_auction.php">Create your first auction</a>
                            </td>
                        </tr>

                    <?php else: ?>
                        <?php foreach ($auctions as $auction): ?>
                            <?php
                                $id = $auction['id'] ?? '';
                                $title = $auction['title'] ?? 'Untitled';
                                $startingPrice = $auction['starting_price'] ?? 0;
                                $currentPrice = $auction['current_price'] ?? $startingPrice;
                                $bidCount = $auction['bid_count'] ?? 0;
                                $auctionStatus = $auction['status'] ?? 'unknown';
                                $endTime = $auction['end_time'] ?? '';
                                
                                // Status badge class
                                $statusClass = '';
                                switch ($auctionStatus) {
                                    case 'active': $statusClass = 'status-active'; break;
                                    case 'pending': $statusClass = 'status-pending'; break;
                                    case 'ended': $statusClass = 'status-ended'; break;
                                    case 'cancelled': $statusClass = 'status-cancelled'; break;
                                }
                            ?>
                            <tr>
                                <td>
                                    <a href="auction_detail.php?id=<?php echo urlencode($id); ?>">
                                        <?php echo htmlspecialchars($title); ?>
                                    </a>
                                </td>
                                <td>$<?php echo number_format((float)$startingPrice, 2); ?></td>
                                <td>$<?php echo number_format((float)$currentPrice, 2); ?></td>
                                <td><?php echo (int)$bidCount; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($auctionStatus); ?>
                                    </span>
                                </td>
                                <td><?php echo $endTime ? htmlspecialchars($endTime) : 'N/A'; ?></td>
                                <td>
                                    <a href="auction_detail.php?id=<?php echo urlencode($id); ?>" class="btn-small">View</a>
                                    <?php if ($auctionStatus === 'pending'): ?>
                                        <a href="edit_auction.php?id=<?php echo urlencode($id); ?>" class="btn-small">Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </tbody>
            </table>
        </section>

        <!-- PAGINATION -->
        <?php if (is_array($pagination) && ($pagination['totalPages'] ?? 1) > 1): ?>
            <nav class="pagination">
                <?php
                $totalPages = (int)($pagination['totalPages'] ?? 1);
                $currentPage = (int)($pagination['currentPage'] ?? $page);
                
                for ($p = 1; $p <= $totalPages; $p++) {
                    $qs = $_GET;
                    $qs['page'] = $p;
                    $link = '?' . http_build_query($qs);
                    $active = ($p === $currentPage) ? 'active' : '';
                    echo '<a href="' . htmlspecialchars($link) . '" class="page-link ' . $active . '">' . $p . '</a>';
                }
                ?>
            </nav>
        <?php endif; ?>

    </div>

</main>

<?php include 'includes/footer.php'; ?>