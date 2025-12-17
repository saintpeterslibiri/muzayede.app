<?php
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';


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
                    <h2><?php echo htmlspecialchars($title); ?></h2>
                    <p class="category">Category: <?php echo htmlspecialchars($category); ?></p>

                    <?php if ($description !== ''): ?>
                        <p class="description"><?php echo htmlspecialchars($description); ?></p>
                    <?php endif; ?>

                    <p>Starting price: $<?php echo htmlspecialchars(number_format((float)$starting, 2)); ?></p>
                    <p>Current highest bid: <strong>$<?php echo htmlspecialchars(number_format((float)$current, 2)); ?></strong></p>

                    <p class="time-left">
                        Ends at:
                        <span><?php echo $endTime ? htmlspecialchars($endTime) : 'N/A'; ?></span>
                    </p>
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

<?php include 'includes/footer.php'; ?>
