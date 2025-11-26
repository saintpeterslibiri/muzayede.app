<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page">
    <section class="page-header">
        <h1>Auction Detail</h1>
        <p>View item details and place your bid.</p>
    </section>

    <section class="auction-detail">
        <div class="auction-main">
            <div class="auction-image">
                <!-- DYNAMIC: ürün resmi -->
                <img src="assets/img/placeholder.png" alt="Item Image">
            </div>

            <div class="auction-info">
                <!-- DYNAMIC: ürün bilgileri -->
                <h2>Example Item Title</h2>
                <p class="category">Category: Electronics</p>
                <p class="description">
                    This is a sample description for the item. In the real system, this text will be loaded from the database.
                </p>

                <p>Starting price: $100</p>
                <p>Current highest bid: <strong>$120</strong></p>

                <p class="time-left">
                    Time left:
                    <span id="countdown">02:15:30</span>
                    <!-- DYNAMIC: JS ile geri sayım -->
                </p>
            </div>
        </div>

        <div class="auction-actions">
            <section class="place-bid">
                <h3>Place a Bid</h3>
                <!-- method="POST" ile backend'e gider -->
                <form method="POST">
                    <input type="number" name="bid_amount" placeholder="Enter your bid" step="0.01" min="0" required>
                    <button type="submit">Place Bid</button>
                </form>
                <!-- DYNAMIC: PHP ile hata / başarı mesajları -->
                <p class="message info">You must bid higher than the current price.</p>
            </section>

            <section class="auto-bid">
                <h3>Set Auto-Bid</h3>
                <form method="POST">
                    <input type="number" name="max_auto_bid" placeholder="Max auto-bid amount" step="0.01" min="0" required>
                    <button type="submit">Enable Auto-Bid</button>
                </form>
                <p class="message small">
                    The system will automatically bid for you up to this amount.
                </p>
            </section>

            <section class="bid-history">
                <h3>Bid History</h3>
                <ul>
                    <!-- DYNAMIC: foreach ($bids as $bid) -->
                    <li><strong>User1</strong> bid $120 at 10:15</li>
                    <li><strong>User2</strong> bid $100 at 10:10</li>
                    <li><strong>User3</strong> bid $90 at 10:05</li>
                </ul>
            </section>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
