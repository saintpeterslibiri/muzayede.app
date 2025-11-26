<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page">
    <section class="page-header">
        <h1>My Auctions</h1>
        <p>Manage the auctions you have created.</p>
        <a href="create_auction.php" class="btn-primary">Create New Auction</a>
    </section>

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
                <!-- DYNAMIC: foreach ($myAuctions as $auction) -->
                <tr>
                    <td>Example Item 1</td>
                    <td>$120</td>
                    <td>Active</td>
                    <td>2025-05-01 12:00</td>
                    <td>
                        <a href="auction_detail.php" class="btn-small">View</a>
                        <!-- İleride Edit/Close gibi butonlar eklenebilir -->
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
            </tbody>
        </table>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
