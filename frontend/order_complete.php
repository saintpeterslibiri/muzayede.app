<?php
session_start();
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;

// Parametreler
$auctionId = $_GET['auction_id'] ?? null;
$amount = $_GET['amount'] ?? null;

$error = null;
$orderCreated = false;

if (!$isLoggedIn) {
    $error = "You must be logged in to complete an order.";
}
if (!$auctionId) {
    $error = $error ?: "Missing auction_id.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    // Here you would typically call the backend API to create the order
    // For now, we'll just simulate a successful order creation
    $orderCreated = true;
}

// İstersen burada API'den auction detayını çekebiliriz:
// $auctionRes = api_get('/auctions/' . urlencode($auctionId));
// (Backend endpoint'ine göre düzenleriz)

?>

<main class="page">
  <div class="home-container">

    <section class="page-header">
      <h1>Complete Order</h1>
      <p>Finish your purchase for the auction you won.</p>
    </section>

    <?php if ($error): ?>
      <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
      <p><a href="my_bids.php">Back to My Bids</a></p>
    <?php elseif ($orderCreated): ?>
      <div class="card order-card" style="text-align: center; padding: 50px;">
        <div style="color: var(--green); font-size: 48px; margin-bottom: 20px;">✓</div>
        <h2 class="section-title" style="border: none;">Order Created Successfully!</h2>
        <p>Thank you for your purchase. Your order has been placed.</p>
        <div style="margin-top: 30px;">
            <a href="my_bids.php" class="btn-primary">Back to My Bids</a>
            <a href="auctions.php" class="btn-secondary">Browse More Auctions</a>
        </div>
      </div>
    <?php else: ?>

      <div class="card order-card">
        <h2 class="section-title">Order Summary</h2>

        <div class="order-details">
          <p><strong>Auction ID:</strong> #<?php echo htmlspecialchars($auctionId); ?></p>
          <p><strong>Winning Amount:</strong>
            <span class="price-tag">$<?php echo number_format((float)($amount ?? 0), 2); ?></span>
          </p>
        </div>

        <hr class="divider">

        <!-- ŞİMDİLİK FORM: Sonra backend'e POST atacağız -->
        <form method="post" action="order_complete.php?auction_id=<?php echo urlencode($auctionId); ?>&amount=<?php echo urlencode($amount); ?>" class="order-form">
          <h3 class="subsection-title">Shipping Info</h3>

          <div class="form-row">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" name="full_name" required class="form-input">
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="text" name="phone" required class="form-input">
            </div>
          </div>

          <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" required class="form-input">
          </div>

          <div class="form-group">
            <label>City</label>
            <input type="text" name="city" required class="form-input">
          </div>

          <hr class="divider">

          <h3 class="subsection-title">Payment</h3>
          
          <div class="form-group">
            <label>Card Number</label>
            <input type="text" name="card_number" placeholder="1234 5678 9012 3456" required class="form-input">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Expiry</label>
              <input type="text" name="expiry" placeholder="MM/YY" required class="form-input">
            </div>
            <div class="form-group">
              <label>CVC</label>
              <input type="text" name="cvc" placeholder="123" required class="form-input">
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-primary btn-block">
              Place Order
            </button>
          </div>
        </form>

      </div>

    <?php endif; ?>

  </div>
</main>

<?php include 'includes/footer.php'; ?>
