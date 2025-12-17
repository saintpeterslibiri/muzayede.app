<?php
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

// API'den tüm açık artırmaları çek
$response = api_get("/auctions");
$auctions = [];

if (isset($response['success']) && $response['success'] === true) {
    // Backend { success:true, data: { auctions: [...] } } dönüyor olabilir
    $auctions = $response['data']['auctions'] ?? $response['data'] ?? [];
}
?>

<main class="page">
    <div class="home-container">
        <section class="page-header">
            <h1>All Auctions</h1>
            <p>Browse and bid on active items.</p>
        </section>

        <section class="auction-grid">
            <?php if (empty($auctions)): ?>
                <p>No active auctions found.</p>
            <?php else: ?>
                <?php foreach ($auctions as $item): ?>
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
                            <a href="auction_detail.php?id=<?php echo $id; ?>" class="btn-secondary">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
