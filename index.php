<?php
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

// ---- Featured auctions çek ----
$response = api_get('/auctions?limit=3&sort=ending_soon');

$featuredAuctions = [];
if (is_array($response) && isset($response['success']) && $response['success'] === true) {
    $featuredAuctions = $response['data']['auctions'] ?? [];
}
?>

<main class="page">

    <div class="home-container">

        <!-- HERO -->
        <section class="hero">
            <div class="hero-content">
                <h1>Online Auction Platform</h1>
                <p>Bid on unique items, compete in real-time, and win your favorite products.</p>
                <div class="hero-actions">
                    <a href="auctions.php" class="btn-primary">View Auctions</a>
                    <a href="register.php" class="btn-secondary">Get Started</a>
                </div>
            </div>
        </section>

        <!-- ARAMA / FİLTRELEME BÖLÜMÜ -->
        <section class="page-section search-section">
            <h2>Search Auctions</h2>
            <p>Find items by name, category or sort by time and price.</p>

            <form class="search-form" action="auctions.php" method="GET">
                <input type="text" name="q" placeholder="Search items...">

                <select name="category">
                    <option value="">All Categories</option>
                    <option value="electronics">Electronics</option>
                    <option value="collectibles">Collectibles</option>
                    <option value="fashion">Fashion</option>
                    <option value="home">Home</option>
                    <option value="sports">Sports</option>
                    <option value="other">Other</option>
                </select>

                <select name="sort">
                    <option value="ending_soon">Ending Soon</option>
                    <option value="newest">Newest</option>
                    <option value="highest_bid">Highest Bid</option>
                </select>

                <button type="submit">Search</button>
            </form>
        </section>

        <!-- FEATURED AUCTIONS -->
        <section class="page-section">
            <h2>Featured Auctions</h2>
            <p>Hot items ending soon - don't miss out!</p>

            <div class="auction-list">
                <?php if (empty($featuredAuctions)): ?>
                    <p>No active auctions at the moment.</p>
                <?php else: ?>
                    <?php foreach ($featuredAuctions as $auction): ?>
                        <?php
                            $id = $auction['id'] ?? '';
                            $title = $auction['title'] ?? 'Untitled';
                            $price = $auction['current_price'] ?? 0;
                            $endTime = $auction['end_time'] ?? '';
                            $img = $auction['image_path'] ?? 'assets/img/placeholder.png';
                        ?>
                        <article class="auction-card">
                            <img src="<?php echo htmlspecialchars($img ?: 'assets/img/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($title); ?>">
                            <h3><?php echo htmlspecialchars($title); ?></h3>
                            <p>Current bid: $<?php echo number_format((float)$price, 2); ?></p>
                            <p class="time-left">
                                <?php echo $endTime ? 'Ends: ' . htmlspecialchars($endTime) : ''; ?>
                            </p>
                            <a href="auction_detail.php?id=<?php echo urlencode($id); ?>" class="btn-small">View Details</a>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

    </div>

</main>

<?php include 'includes/footer.php'; ?>