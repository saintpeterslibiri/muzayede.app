<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page">
    <section class="page-header">
        <h1>Admin Dashboard</h1>
        <p>Manage users and auctions.</p>
    </section>

    <section class="admin-stats">
        <!-- DYNAMIC: gerçek sayılar -->
        <div class="stat-card">
            <h3>Total Users</h3>
            <p>42</p>
        </div>
        <div class="stat-card">
            <h3>Active Auctions</h3>
            <p>10</p>
        </div>
        <div class="stat-card">
            <h3>Finished Auctions</h3>
            <p>5</p>
        </div>
    </section>

    <section class="admin-tables">
        <h2>Users</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- DYNAMIC: foreach ($users as $user) -->
                <tr>
                    <td>exampleuser</td>
                    <td>user@example.com</td>
                    <td>user</td>
                    <td>active</td>
                    <td>
                        <button class="btn-small">Ban</button>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2>Auctions</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Seller</th>
                    <th>Status</th>
                    <th>End Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- DYNAMIC: foreach ($allAuctions as $auction) -->
                <tr>
                    <td>Example Item</td>
                    <td>exampleuser</td>
                    <td>active</td>
                    <td>2025-05-01 12:00</td>
                    <td>
                        <button class="btn-small">Close</button>
                        <button class="btn-small">Delete</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
