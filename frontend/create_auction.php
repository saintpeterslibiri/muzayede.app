<?php
session_start();
?>

<?php
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

$successMsg = null;
$errorMsg   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form verilerini al
    $data = [
        'title'          => $_POST['title'] ?? '',
        'description'    => $_POST['description'] ?? '',
        'category'       => $_POST['category'] ?? '',
        'starting_price' => (float)($_POST['starting_price'] ?? 0),
        'start_time'     => date('Y-m-d H:i:s'), // Hemen başlasın
        'end_time'       => $_POST['end_time'] ?? ''
    ];

    // API'ye gönder
    $response = api_post("/auctions", $data);

    if (isset($response['success']) && $response['success'] === true) {
        $successMsg = "Auction created successfully!";
    } else {
        $errorMsg = $response['message'] ?? $response['error'] ?? "Failed to create auction.";
    }
}
?>

<main class="page">
    <div class="home-container">
        <section class="page-header">
            <h1>Create New Auction</h1>
            <p>Fill in the details to list your item.</p>
        </section>

        <?php if ($successMsg): ?>
            <p class="success-message"><?php echo htmlspecialchars($successMsg); ?></p>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <p class="error-message"><?php echo htmlspecialchars($errorMsg); ?></p>
        <?php endif; ?>

        <section class="form-section">
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="title">Item Title</label>
                    <input type="text" id="title" name="title" required placeholder="e.g. Vintage Watch">
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="electronics">Electronics</option>
                        <option value="collectibles">Collectibles</option>
                        <option value="fashion">Fashion</option>
                        <option value="home">Home</option>
                        <option value="sports">Sports</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="starting_price">Starting Price ($)</label>
                    <input type="number" id="starting_price" name="starting_price" step="0.01" required placeholder="0.00">
                </div>

                <div class="form-group">
                    <label for="end_time">End Date & Time</label>
                    <input type="datetime-local" id="end_time" name="end_time" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" placeholder="Describe your item..."></textarea>
                </div>

                <button type="submit" class="btn-primary">Create Auction</button>
            </form>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
