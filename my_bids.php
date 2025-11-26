<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page">
    <section class="page-header">
        <h1>My Bids</h1>
        <p>Track your active and past bids.</p>
    </section>

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
                <!-- DYNAMIC: Active bids -->
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
            </tbody>
        </table>
    </section>

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
                <!-- DYNAMIC: Won/Lost -->
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
            </tbody>
        </table>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
