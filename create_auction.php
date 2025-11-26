<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page">
    <section class="page-header">
        <h1>Create New Auction</h1>
        <p>Fill in the details to start a new auction.</p>
    </section>

    <section class="form-section">
        <!-- DYNAMIC: hata / başarı mesajları -->
        <!--
        <?php if(isset($success)) : ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        -->

        <form method="POST" enctype="multipart/form-data" class="auction-form">
            <label>
                Item Title
                <input type="text" name="title" required>
            </label>

            <label>
                Category
                <select name="category" required>
                    <option value="">Select a category</option>
                    <option value="electronics">Electronics</option>
                    <option value="collectibles">Collectibles</option>
                    <option value="other">Other</option>
                </select>
            </label>

            <label>
                Description
                <textarea name="description" rows="5" required></textarea>
            </label>

            <label>
                Starting Price ($)
                <input type="number" name="starting_price" step="0.01" min="0" required>
            </label>

            <label>
                Start Time
                <input type="datetime-local" name="start_time" required>
            </label>

            <label>
                End Time
                <input type="datetime-local" name="end_time" required>
            </label>

            <label>
                Item Image
                <input type="file" name="image" accept="image/*">
            </label>

            <button type="submit">Create Auction</button>
        </form>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
