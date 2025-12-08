<?php
session_start();
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<main class="page">

    <div class="home-container">

        <!-- LOGIN UYARISI (Giriş yapılmamışsa görünür) -->
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="warning-banner">
                You must be logged in to create an auction.
                <a href="login.php" class="warning-link">Login here</a>
            </div>
        <?php endif; ?>

        <!-- SAYFA BAŞLIĞI -->
        <section class="page-header">
            <h1>Create New Auction</h1>
            <p>Fill in the details to start a new auction.</p>
        </section>

        <!-- FORM BÖLÜMÜ -->
        <section class="form-section">

            <!-- Successful message (optional) -->
            <!--
            <?php if(isset($success)) : ?>
                <p class="success-message"><?php echo $success; ?></p>
            <?php endif; ?>
            -->

            <form method="POST" enctype="multipart/form-data" class="auction-form">

                <label>
                    Item Title
                    <input type="text" name="title" 
                        <?php if (!isset($_SESSION['user_id'])) echo "disabled"; ?> 
                        required>
                </label>

                <label>
                    Category
                    <select name="category" 
                        <?php if (!isset($_SESSION['user_id'])) echo "disabled"; ?> 
                        required>
                        <option value="">Select a category</option>
                        <option value="electronics">Electronics</option>
                        <option value="collectibles">Collectibles</option>
                        <option value="other">Other</option>
                    </select>
                </label>

                <label class="full-width">
                    Description
                    <textarea name="description" rows="4" 
                        <?php if (!isset($_SESSION['user_id'])) echo "disabled"; ?> 
                        required></textarea>
                </label>

                <label>
                    Starting Price ($)
                    <input type="number" name="starting_price" step="0.01" min="0"
                        <?php if (!isset($_SESSION['user_id'])) echo "disabled"; ?>
                        required>
                </label>

                <label>
                    Start Time
                    <input type="datetime-local" name="start_time"
                        <?php if (!isset($_SESSION['user_id'])) echo "disabled"; ?>
                        required>
                </label>

                <label>
                    End Time
                    <input type="datetime-local" name="end_time"
                        <?php if (!isset($_SESSION['user_id'])) echo "disabled"; ?>
                        required>
                </label>

                <label class="full-width">
                    Item Image
                    <input type="file" name="image" accept="image/*"
                        <?php if (!isset($_SESSION['user_id'])) echo "disabled"; ?>>
                </label>

                <div class="form-actions">
                    <button type="submit" class="btn-primary"
                        <?php if (!isset($_SESSION['user_id'])) echo "disabled"; ?>>
                        Create Auction
                    </button>
                </div>

            </form>

        </section>
    </div>

</main>

<?php include 'includes/footer.php'; ?>
