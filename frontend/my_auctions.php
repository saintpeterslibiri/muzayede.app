<?php
session_start();
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page">

    <div class="home-container">

        <!-- GİRİŞ YAPMAMIŞ KULLANICI İÇİN UYARI -->
        <?php if (!isset($_SESSION['user_id'])): ?>
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

        <!-- TABLO BÖLÜMÜ -->
        <section class="table-section">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Current Bid</th>
                        <th>Status</th>
                        <th>End Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>

                    <?php if (!isset($_SESSION['user_id'])): ?>

                        <!-- GİRİŞ YOKSA TABLO İÇİ MESAJ -->
                        <tr>
                            <td colspan="5" style="text-align:center; padding:20px; color:#6b7280;">
                                Please log in to see your auctions.
                            </td>
                        </tr>

                    <?php else: ?>

                        <!-- DYNAMIC: foreach ($myAuctions as $auction) { ... } -->
                        <!-- Şimdilik örnek satırlar: -->
                        <tr>
                            <td>Example Item 1</td>
                            <td>$120</td>
                            <td>Active</td>
                            <td>2025-05-01 12:00</td>
                            <td>
                                <a href="auction_detail.php" class="btn-small">View</a>
                                <!-- Sonra Edit / Close vs. eklenebilir -->
                            </td>
                        </tr>

                        <tr>
                            <td>Example Item 2</td>
                            <td>$300</td>
                            <td>Ended</td>
                            <td>2025-04-20 18:30</td>
                            <td>
                                <a href="auction_detail.php" class="btn-small">View</a>
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>
        </section>
    </div>

</main>

<?php include 'includes/footer.php'; ?>
