<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page">

    <!-- BÜYÜK CONTAINER BAŞLANGIÇ -->
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
            <p>Here are some example auctions. Later this section will be filled dynamically from the database.</p>

            <div class="auction-list">
                <article class="auction-card">
                    <img src="assets/img/placeholder.png" alt="Example item">
                    <h3>Example Laptop</h3>
                    <p>Current bid: $450</p>
                    <p class="time-left">Time left: 03:25:10</p>
                    <a href="auction_detail.php" class="btn-small">View Details</a>
                </article>

                <article class="auction-card">
                    <img src="assets/img/placeholder.png" alt="Example item">
                    <h3>Vintage Watch</h3>
                    <p>Current bid: $180</p>
                    <p class="time-left">Time left: 01:12:45</p>
                    <a href="auction_detail.php" class="btn-small">View Details</a>
                </article>

                <article class="auction-card">
                    <img src="assets/img/placeholder.png" alt="Example item">
                    <h3>Gaming Headset</h3>
                    <p>Current bid: $75</p>
                    <p class="time-left">Time left: 05:02:30</p>
                    <a href="auction_detail.php" class="btn-small">View Details</a>
                </article>
            </div>
        </section>

    </div>
    <!-- BÜYÜK CONTAINER BİTİŞ -->

</main>

<!-- BOŞ ARAMA UYARISI İÇİN JS -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.search-form');
    if (!form) return;

    const searchInput = form.querySelector('input[name="q"]');
    if (!searchInput) return;

    const defaultPlaceholder = searchInput.getAttribute('placeholder') || '';

    form.addEventListener('submit', function (e) {
        if (searchInput.value.trim() === '') {
            e.preventDefault(); // formu göndermeyi engelle
            searchInput.value = '';
            searchInput.placeholder = 'Please enter a search term';
            searchInput.classList.add('input-warning');
            searchInput.focus();
        }
    });

    // Kullanıcı yazmaya başlayınca uyarıyı temizle
    searchInput.addEventListener('input', function () {
        if (searchInput.classList.contains('input-warning')) {
            searchInput.classList.remove('input-warning');
            searchInput.placeholder = defaultPlaceholder;
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
