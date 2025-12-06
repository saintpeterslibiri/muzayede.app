<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page">

    <!-- BEYAZ KART BAŞLANGIÇ -->
    <div class="home-container">

        <!-- SAYFA BAŞLIĞI -->
        <section class="page-header">
            <h1>All Auctions</h1>
            <p>Browse all active auctions and place your bids in real-time.</p>
        </section>

        <!-- FİLTRE FORMU -->
        <section class="filters">
            <form class="filter-form" method="GET">
                <input type="text" name="q" placeholder="Search items...">

                <select name="category">
                    <option value="">All Categories</option>
                    <option value="electronics">Electronics</option>
                    <option value="collectibles">Collectibles</option>
                    <option value="other">Other</option>
                </select>

                <select name="sort">
                    <option value="ending_soon">Ending Soon</option>
                    <option value="newest">Newest</option>
                    <option value="highest_bid">Highest Bid</option>
                </select>

                <button type="submit">Filter</button>
            </form>
        </section>

        <!-- AUCTION LİSTESİ -->
        <section class="auction-list">
            <article class="auction-card">
                <img src="assets/img/placeholder.png" alt="">
                <h2>Example Phone</h2>
                <p>Current bid: $320</p>
                <p class="time-left">Time left: 02:15:30</p>
                <a href="auction_detail.php" class="btn-small">View Details</a>
            </article>

            <article class="auction-card">
                <img src="assets/img/placeholder.png" alt="">
                <h2>Gaming Console</h2>
                <p>Current bid: $500</p>
                <p class="time-left">Time left: 00:45:10</p>
                <a href="auction_detail.php" class="btn-small">View Details</a>
            </article>

            <article class="auction-card">
                <img src="assets/img/placeholder.png" alt="">
                <h2>Collectible Figure</h2>
                <p>Current bid: $90</p>
                <p class="time-left">Time left: 04:12:05</p>
                <a href="auction_detail.php" class="btn-small">View Details</a>
            </article>
        </section>

        <!-- PAGINATION (KART İÇİNDE) -->
        <nav class="pagination">
            <a href="?page=1" class="page-link active">1</a>
            <a href="?page=2" class="page-link">2</a>
            <a href="?page=3" class="page-link">3</a>
            <span class="page-ellipsis">...</span>
            <a href="?page=10" class="page-link">10</a>
        </nav>

    </div>
    <!-- BEYAZ KART BİTİŞ -->

</main>

<?php include 'includes/footer.php'; ?>
