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

if (!$isLoggedIn) {
    $error = "You must be logged in to complete an order.";
}
if (!$auctionId) {
    $error = $error ?: "Missing auction_id.";
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
    <?php else: ?>

      <div class="card">
        <h2>Order Summary</h2>

        <p><strong>Auction ID:</strong> <?php echo htmlspecialchars($auctionId); ?></p>
        <p><strong>Winning Amount:</strong>
          $<?php echo number_format((float)($amount ?? 0), 2); ?>
        </p>

        <hr>

        <!-- ŞİMDİLİK FORM: Sonra backend'e POST atacağız -->
        <form method="post" action="order_complete.php?auction_id=<?php echo urlencode($auctionId); ?>">
          <h3>Shipping Info</h3>

          <label>Full Name</label>
          <input type="text" name="full_name" required>

          <label>Address</label>
          <input type="text" name="address" required>

          <label>City</label>
          <input type="text" name="city" required>

          <label>Phone</label>
          <input type="text" name="phone" required>

          <hr>

          <h3>Payment</h3>
          <label>Card Number</label>
          <input type="text" name="card_number" placeholder="1234 5678 9012 3456" required>

          <label>Expiry</label>
          <input type="text" name="expiry" placeholder="MM/YY" required>

          <label>CVC</label>
          <input type="text" name="cvc" placeholder="123" required>

          <button type="submit" class="btn btn-primary">
            Place Order
          </button>
        </form>

      </div>

    <?php endif; ?>

  </div>
</main>

<?php include 'includes/footer.php'; ?>
