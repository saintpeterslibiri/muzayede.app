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
                ⚠️ You must be logged in to view and track your bids.
                <a href="login.php" class="warning-link">Login</a>
            </div>
        <?php endif; ?>

        <!-- SAYFA BAŞLIĞI -->
        <section class="page-header">
            <h1>My Bids</h1>
            <p>Track your active and past bids.</p>
        </section>

        <!-- ACTIVE BIDS -->
        <section class="table-section">
            <h2>Active Bids</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Your Bid</th>
                        <th>Current Highest</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!isset($_SESSION['user_id'])): ?>

                        <tr>
                            <td colspan="4" style="text-align:center; padding:20px; color:#6b7280;">
                                Please log in to see your active bids.
                            </td>
                        </tr>

                    <?php else: ?>

                        <!-- DYNAMIC: Active bids (örnek satırlar) -->
                        <tr>
                            <td><a href="auction_detail.php">Example Item 1</a></td>
                            <td>$100</td>
                            <td>$120</td>
                            <td>Outbid</td>
                        </tr>
                        <tr>
                            <td><a href="auction_detail.php">Example Item 2</a></td>
                            <td>$250</td>
                            <td>$250</td>
                            <td>Leading</td>
                        </tr>

                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- WON / PAST AUCTIONS -->
        <section class="table-section">
            <h2>Won / Past Auctions</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Your Bid</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!isset($_SESSION['user_id'])): ?>

                        <tr>
                            <td colspan="3" style="text-align:center; padding:20px; color:#6b7280;">
                                Please log in to see your past bids and results.
                            </td>
                        </tr>

                    <?php else: ?>

                        <!-- DYNAMIC: Won/Lost (örnek satırlar) -->
                        <tr>
                            <td><a href="auction_detail.php">Old Item</a></td>
                            <td>$90</td>
                            <td>Won</td>
                        </tr>
                        <tr>
                            <td><a href="auction_detail.php">Old Item 2</a></td>
                            <td>$60</td>
                            <td>Lost</td>
                        </tr>

                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </div>

</main>

<?php include 'includes/footer.php'; ?>
