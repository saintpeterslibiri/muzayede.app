<?php
session_start();
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

// ---- Kullanıcı kontrolü ----
$userId = $_SESSION['user_id'] ?? 2; // seller1 for testing
$isLoggedIn = isset($_SESSION['user_id']);

// ---- POST işlemleri ----
$successMsg = null;
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $startingPrice = isset($_POST['starting_price']) ? (float)$_POST['starting_price'] : 0;
    $startTime = isset($_POST['start_time']) ? trim($_POST['start_time']) : '';
    $endTime = isset($_POST['end_time']) ? trim($_POST['end_time']) : '';
    
    // Validasyon
    if ($title === '' || $category === '' || $startingPrice <= 0 || $startTime === '' || $endTime === '') {
        $errorMsg = 'Please fill in all required fields.';
    } else {
        // API'ye gönder
        $auctionData = [
            'title' => $title,
            'category' => $category,
            'description' => $description,
            'starting_price' => $startingPrice,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
        
        $response = api_post('/auctions', $auctionData);
        
        if (isset($response['success']) && $response['success'] === true) {
            $newAuction = $response['data']['auction'] ?? null;
            $successMsg = 'Auction created successfully!';
            
            // Yeni oluşturulan açık artırmaya yönlendir
            if ($newAuction && isset($newAuction['id'])) {
                header('Location: auction_detail.php?id=' . $newAuction['id'] . '&created=1');
                exit;
            }
        } else {
            $errorMsg = $response['error'] ?? $response['message'] ?? 'Failed to create auction';
        }
    }
}

// Form değerleri (hata durumunda korumak için)
$formTitle = $_POST['title'] ?? '';
$formCategory = $_POST['category'] ?? '';
$formDescription = $_POST['description'] ?? '';
$formStartingPrice = $_POST['starting_price'] ?? '';
$formStartTime = $_POST['start_time'] ?? '';
$formEndTime = $_POST['end_time'] ?? '';
?>

<main class="page">

    <div class="home-container">

        <!-- GİRİŞ UYARISI -->
        <?php if (!$isLoggedIn): ?>
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

        <!-- BAŞARI / HATA MESAJLARI -->
        <?php if ($successMsg): ?>
            <p class="success-message"><?php echo htmlspecialchars($successMsg); ?></p>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <p class="error-message"><?php echo htmlspecialchars($errorMsg); ?></p>
        <?php endif; ?>

        <!-- FORM BÖLÜMÜ -->
        <section class="form-section">
            <form method="POST" enctype="multipart/form-data" class="auction-form">

                <label>
                    Item Title *
                    <input type="text" name="title" 
                        value="<?php echo htmlspecialchars($formTitle); ?>"
                        <?php if (!$isLoggedIn) echo "disabled"; ?> 
                        required>
                </label>

                <label>
                    Category *
                    <select name="category" 
                        <?php if (!$isLoggedIn) echo "disabled"; ?> 
                        required>
                        <option value="">Select a category</option>
                        <option value="electronics" <?php echo $formCategory === 'electronics' ? 'selected' : ''; ?>>Electronics</option>
                        <option value="collectibles" <?php echo $formCategory === 'collectibles' ? 'selected' : ''; ?>>Collectibles</option>
                        <option value="fashion" <?php echo $formCategory === 'fashion' ? 'selected' : ''; ?>>Fashion</option>
                        <option value="home" <?php echo $formCategory === 'home' ? 'selected' : ''; ?>>Home</option>
                        <option value="sports" <?php echo $formCategory === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="other" <?php echo $formCategory === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </label>

                <label class="full-width">
                    Description
                    <textarea name="description" rows="4" 
                        <?php if (!$isLoggedIn) echo "disabled"; ?>
                    ><?php echo htmlspecialchars($formDescription); ?></textarea>
                </label>

                <label>
                    Starting Price ($) *
                    <input type="number" name="starting_price" step="0.01" min="0.01"
                        value="<?php echo htmlspecialchars($formStartingPrice); ?>"
                        <?php if (!$isLoggedIn) echo "disabled"; ?>
                        required>
                </label>

                <label>
                    Start Time *
                    <input type="datetime-local" name="start_time"
                        value="<?php echo htmlspecialchars($formStartTime); ?>"
                        <?php if (!$isLoggedIn) echo "disabled"; ?>
                        required>
                </label>

                <label>
                    End Time *
                    <input type="datetime-local" name="end_time"
                        value="<?php echo htmlspecialchars($formEndTime); ?>"
                        <?php if (!$isLoggedIn) echo "disabled"; ?>
                        required>
                </label>

                <label class="full-width">
                    Item Image
                    <input type="file" name="image" accept="image/*"
                        <?php if (!$isLoggedIn) echo "disabled"; ?>>
                    <small>Image upload coming soon. For now, a placeholder will be used.</small>
                </label>

                <div class="form-actions">
                    <button type="submit" class="btn-primary"
                        <?php if (!$isLoggedIn) echo "disabled"; ?>>
                        Create Auction
                    </button>
                </div>

            </form>
        </section>

    </div>

</main>

<?php include 'includes/footer.php'; ?>