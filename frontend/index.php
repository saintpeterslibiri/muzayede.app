<?php
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

// API'den öne çıkan (featured) veya son eklenen açık artırmaları çek
$response = api_get("/auctions");
$featuredAuctions = [];

if (isset($response['success']) && $response['success'] === true) {
    // İlk 3 tanesini alalım (örnek)
    $all = $response['data']['auctions'] ?? $response['data'] ?? [];
    $featuredAuctions = array_slice($all, 0, 3);
}
?>

<main>
    <!-- Hero Section -->
    <section class="hero">
        <div class="home-container">
            <h1>Discover Unique Items & Bid Today</h1>
            <p>The most exciting online auction platform for collectors and enthusiasts.</p>
            <div class="hero-buttons">
                <a href="auctions.php" class="btn-primary">Browse Auctions</a>
                <a href="create_auction.php" class="btn-secondary">Start Selling</a>
            </div>
        </div>
    </section>

    <!-- Featured Auctions -->
    <section class="featured-auctions">
        <div class="home-container">
            <div class="section-header">
                <h2>Featured Auctions</h2>
                <a href="auctions.php" class="view-all">View All</a>
            </div>

            <div class="auction-grid">
                <?php if (empty($featuredAuctions)): ?>
                    <p>No active auctions at the moment.</p>
                <?php else: ?>
                    <?php foreach ($featuredAuctions as $item): ?>
                        <?php
                            $id      = $item['id'] ?? 0;
                            $title   = $item['title'] ?? 'No Title';
                            $price   = $item['current_price'] ?? $item['starting_price'] ?? 0;
                            $endTime = $item['end_time'] ?? '';
                            $image   = (!empty($item['image_path'])) ? $item['image_path'] : 'assets/img/placeholder.png';
                        ?>
                        <div class="auction-card">
                            <div class="card-image">
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="Item">
                            </div>
                            <div class="card-content">
                                <h3><?php echo htmlspecialchars($title); ?></h3>
                                <p class="price">Current Bid: $<?php echo number_format((float)$price, 2); ?></p>
                                <p class="time">Ends: <?php echo htmlspecialchars($endTime); ?></p>
                                <a href="auction_detail.php?id=<?php echo $id; ?>" class="btn-secondary">Bid Now</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <div class="home-container">
            <h2>How It Works</h2>
            <div class="steps-grid">
                <div class="step">
                    <div class="step-icon">1</div>
                    <h3>Create Account</h3>
                    <p>Sign up for free and verify your profile to start bidding.</p>
                </div>
                <div class="step">
                    <div class="step-icon">2</div>
                    <h3>Find Items</h3>
                    <p>Browse through hundreds of unique items across various categories.</p>
                </div>
                <div class="step">
                    <div class="step-icon">3</div>
                    <h3>Place Bids</h3>
                    <p>Enter your bid or set up auto-bidding to win your favorite items.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
