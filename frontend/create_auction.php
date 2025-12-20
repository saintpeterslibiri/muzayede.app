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
    $data = [
        'title'          => $_POST['title'] ?? '',
        'description'    => $_POST['description'] ?? '',
        'category'       => $_POST['category'] ?? '',
        'starting_price' => (float)($_POST['starting_price'] ?? 0),
        'start_time'     => date('Y-m-d H:i:s'),
        'end_time'       => $_POST['end_time'] ?? ''
    ];

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
                    <label>Item Image</label>
                    <input type="file" id="imageInput" name="image" accept="image/*" hidden>
                    <button type="button" class="btn-secondary" id="pickImageBtn" style="width:100%; margin:0;">
                        Choose Image
                    </button>
                    <div id="imageChosenText" style="margin-top:8px; font-size:13px; color:#6b7280; display:none;"></div>
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

<script>
  const pickBtn = document.getElementById('pickImageBtn');
  const input = document.getElementById('imageInput');
  const chosenText = document.getElementById('imageChosenText');

  if (pickBtn && input) {
    pickBtn.addEventListener('click', () => input.click());

    input.addEventListener('change', () => {
      const file = input.files && input.files[0];
      if (!file) {
        chosenText.style.display = 'none';
        chosenText.textContent = '';
        return;
      }

      if (!file.type.startsWith('image/')) {
        alert('Please select an image file.');
        input.value = '';
        chosenText.style.display = 'none';
        chosenText.textContent = '';
        return;
      }

      chosenText.textContent = 'Selected: ' + file.name;
      chosenText.style.display = 'block';
    });
  }
</script>

<?php include 'includes/footer.php'; ?>
