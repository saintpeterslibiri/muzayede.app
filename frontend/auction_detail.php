<?php
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';
require_once 'includes/api.php';

$isLoggedIn = isLoggedIn();


// ---- ID kontrol ----
$auctionId = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($auctionId === '') {
    echo '<main class="page"><div class="home-container"><p class="error-message">Missing auction id. Open with ?id=1</p></div></main>';
    include 'includes/footer.php';
    exit;
}

// ---- POST işlemleri (Bid / Auto-bid) ----
$successMsg = null;
$errorMsg   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Place Bid
    if (isset($_POST['bid_amount']) && $_POST['bid_amount'] !== '') {
        $amount = (float)$_POST['bid_amount'];

        // Endpoint: /api/auctions/:id/bids
        $r = api_post("/auctions/" . $auctionId . "/bids", ["amount" => $amount]);

        if (isset($r['success']) && $r['success'] === true) {
            $successMsg = $r['message'] ?? "Bid placed successfully.";
        } else {
            $errorMsg = $r['message'] ?? "Failed to place bid.";
        }
    }

    // Enable Auto-bid
    if (isset($_POST['max_auto_bid']) && $_POST['max_auto_bid'] !== '') {
        $max = (float)$_POST['max_auto_bid'];

        // Endpoint: /api/auctions/:id/auto-bid
        $r = api_post("/auctions/" . $auctionId . "/auto-bid", ["max_amount" => $max]);

        if (isset($r['success']) && $r['success'] === true) {
            $successMsg = $r['message'] ?? "Auto-bid enabled.";
        } else {
            $errorMsg = $r['message'] ?? "Failed to enable auto-bid.";
        }
    }
}

// ---- Auction detail çek ----
$detailRes = api_get("/auctions/" . $auctionId);

// Beklenen format: { success:true, data:{ auction:{...} } } veya { success:true, data:{...auction fields...} }
$auction = null;
if (isset($detailRes['success']) && $detailRes['success'] === true) {
    $d = $detailRes['data'] ?? null;
    if (is_array($d)) {
        $auction = $d['auction'] ?? $d;
    }
} else {
    $errorMsg = $detailRes['message'] ?? 'Failed to load auction details.';
}

// ---- Bid history çek ----
$bids = [];
$bidsRes = api_get("/auctions/" . $auctionId . "/bids");
if (isset($bidsRes['success']) && $bidsRes['success'] === true) {
    $bd = $bidsRes['data'] ?? null;
    if (is_array($bd)) {
        $bids = $bd['bids'] ?? [];
    }
}

// ---- Ekrana basılacak alanlar ----
$title       = $auction['title'] ?? 'Auction Detail';
$category    = $auction['category'] ?? '-';
$description = $auction['description'] ?? '';
$starting    = $auction['starting_price'] ?? 0;
$current     = $auction['current_price'] ?? $starting;
$endTime     = $auction['end_time'] ?? '';
$imgPath     = (!empty($auction['image_path'])) ? $auction['image_path'] : 'assets/img/placeholder.png';
if (strpos($imgPath, '/api/') === 0) {
    $imgPath = PUBLIC_API_URL . $imgPath;
}
$sellerUsername =
    $auction['username'] ??
    $auction['seller_username'] ??
    $auction['owner_username'] ??
    $auction['created_by_username'] ??
    ($auction['user']['username'] ?? null) ??
    ($auction['seller']['username'] ?? null) ??
    null;

$sellerId =
    $auction['user_id'] ??
    $auction['seller_id'] ??
    $auction['owner_id'] ??
    ($auction['user']['id'] ?? null) ??
    ($auction['seller']['id'] ?? null) ??
    null;

$sellerProfileHref = null;
if (!empty($sellerId)) {
    $sellerProfileHref = 'seller_profile.php?user_id=' . urlencode((string)$sellerId);
} elseif (!empty($sellerUsername)) {
    $sellerProfileHref = 'seller_profile.php?username=' . urlencode((string)$sellerUsername);
}
?>

<main class="page">

    <div class="home-container">

        <section class="page-header">
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <p>View item details and place your bid.</p>
        </section>

        <?php if ($successMsg): ?>
            <p class="success-message"><?php echo htmlspecialchars($successMsg); ?></p>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <p class="error-message"><?php echo htmlspecialchars($errorMsg); ?></p>
        <?php endif; ?>

        <section class="auction-detail">
            <!-- SOL TARAF: ÜRÜN DETAYI -->
            <div class="auction-main">
                <div class="auction-image">
                    <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="Item Image">
                </div>

                <div class="auction-info">
                    <div class="auction-top">
                        <h2><?php echo htmlspecialchars($title); ?></h2>
                        
                        <div class="auction-badges">
                            <span class="badge">Category: <?php echo htmlspecialchars($category); ?></span>
                        
                            <?php if ($sellerProfileHref && $sellerUsername): ?>
                                <span class="badge">
                                    Seller:
                                    <a class="seller-link" href="<?php echo htmlspecialchars($sellerProfileHref); ?>">
                                        @<?php echo htmlspecialchars((string)$sellerUsername); ?>
                                    </a>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                            
                    <?php if ($description !== ''): ?>
                        <p class="description"><?php echo htmlspecialchars($description); ?></p>
                    <?php endif; ?>
                    
                    <div class="auction-stats">
                        <div class="stat-row">
                            <span class="stat-label">Starting price</span>
                            <span class="stat-value">$<?php echo htmlspecialchars(number_format((float)$starting, 2)); ?></span>
                        </div>
                    
                        <div class="stat-row">
                            <span class="stat-label">Current highest bid</span>
                            <span class="stat-value strong">$<?php echo htmlspecialchars(number_format((float)$current, 2)); ?></span>
                        </div>
                    
                        <div class="stat-row">
                            <span class="stat-label">Ends at</span>
                            <span class="stat-value"><?php echo $endTime ? htmlspecialchars($endTime) : 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SAĞ TARAF: BİD / AUTO-BID / HISTORY -->
            <div class="auction-actions">

                <section class="place-bid">
                    <h3>Place a Bid</h3>

                    <form method="POST" class="bid-form">
                        <input
                            type="number"
                            name="bid_amount"
                            step="0.01"
                            min="<?php echo (float)$current + 0.01; ?>"
                            placeholder="Enter amount"
                            required
                        >
                        <button type="submit" class="btn-primary">Place Bid</button>
                    </form>

                    <p class="message info">You must bid higher than the current price.</p>
                </section>

                <section class="auto-bid">
                    <h3>Set Auto-Bid</h3>
                    <form method="POST" class="bid-form">
                        <input
                            type="number"
                            name="max_auto_bid"
                            step="0.01"
                            min="<?php echo (float)$current + 0.01; ?>"
                            placeholder="Max amount"
                            required
                        >
                        <button type="submit" class="btn-primary">Enable Auto-Bid</button>
                    </form>

                    <p class="message small">
                        The system will automatically bid for you up to this amount.
                    </p>
                </section>

                <section class="bid-history">
                    <h3>Bid History</h3>
                    <ul>
                        <?php if (!$bids || count($bids) === 0): ?>
                            <li>No bids yet.</li>
                        <?php else: ?>
                            <?php foreach ($bids as $bid): ?>
                                <?php
                                    $user = $bid['username'] ?? 'User';
                                    $amt  = $bid['amount'] ?? 0;
                                    $t    = $bid['created_at'] ?? '';
                                ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($user); ?></strong>
                                    bid $<?php echo htmlspecialchars(number_format((float)$amt, 2)); ?>
                                    <?php echo $t ? 'at ' . htmlspecialchars($t) : ''; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>
        </section>
    </div>
</main>

<?php if ($isLoggedIn) : ?>
    <!-- Auto Bid Sidebar -->
    <div id="autoBidSidebar" class="auto-bid-sidebar">
        <div class="auto-bid-sidebar-header">
            <h3>My Auto Bids</h3>
            <button class="auto-bid-toggle" onclick="toggleAutoBidSidebar()">×</button>
        </div>
        <div class="auto-bid-sidebar-content" id="autoBidContent">
            <div class="auto-bid-loading">Loading...</div>
        </div>
    </div>
    <button class="auto-bid-sidebar-toggle-btn" onclick="toggleAutoBidSidebar()" id="autoBidToggleBtn">
        <span>Auto Bids</span>
    </button>

    <script>
    // Load auto bids on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadAutoBids();
        // Refresh every 30 seconds
        setInterval(loadAutoBids, 30000);
    });

    async function loadAutoBids() {
        const content = document.getElementById('autoBidContent');
        if (!content) return;
        
        try {
            const token = '<?php echo isset($_SESSION["auth_token"]) ? $_SESSION["auth_token"] : ""; ?>';
            const response = await fetch(window.CONFIG.API_BASE_URL + '/my/auto-bids', {
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.data.auto_bids.length > 0) {
                let html = '<div class="auto-bid-list">';
                data.data.auto_bids.forEach(function(bid) {
                    const imageUrl = bid.image_path && bid.image_path.startsWith('/api/') 
                        ? '<?php echo getenv("PUBLIC_API_URL") ?: "http://localhost:3000"; ?>' + bid.image_path
                        : (bid.image_path || 'assets/img/placeholder.png');
                    const timeLeft = formatTimeRemaining(bid.end_time);
                    
                    html += `
                        <div class="auto-bid-item">
                            <a href="auction_detail.php?id=${bid.auction_id}">
                                <img src="${imageUrl}" alt="${bid.title}" class="auto-bid-image">
                                <div class="auto-bid-info">
                                    <div class="auto-bid-title">${bid.title}</div>
                                    <div class="auto-bid-price">Max: $${parseFloat(bid.max_amount).toFixed(2)}</div>
                                    <div class="auto-bid-time">${timeLeft}</div>
                                </div>
                            </a>
                        </div>
                    `;
                });
                html += '</div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="auto-bid-empty">No active auto bids</div>';
            }
        } catch (error) {
            console.error('Error loading auto bids:', error);
            content.innerHTML = '<div class="auto-bid-error">Error loading auto bids</div>';
        }
    }

    function toggleAutoBidSidebar() {
        const sidebar = document.getElementById('autoBidSidebar');
        const btn = document.getElementById('autoBidToggleBtn');
        if (sidebar && btn) {
            sidebar.classList.toggle('open');
            btn.classList.toggle('open');
        }
    }

    function formatTimeRemaining(endDateString) {
        const end = new Date(endDateString);
        const now = new Date();
        const diff = end - now;
        
        if (diff <= 0) return 'Ended';
        
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        if (days > 0) return `${days}d ${hours}h`;
        if (hours > 0) return `${hours}h ${minutes}m`;
        return `${minutes}m`;
    }
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
