<?php
session_start();
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

/* ---- Login kontrol ---- */
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn) {
    // login olmayan kullanıcı bu sayfayı göremez
    header("Location: login.php");
    exit;
}

/* ---- API çağrısı ----
   Backend’de örnek endpoint: GET /my/orders
   (Yoksa, şimdilik response boş dönecek şekilde ayarlayabilirsin)
*/
$response = api_get('/my/orders');

/* ---- Parse ---- */
$orders = [];
$apiError = null;

if (is_array($response) && ($response['success'] ?? false) === true) {
    $orders = $response['data'] ?? [];
} else {
    $apiError = $response['error'] ?? $response['message'] ?? 'Failed to load orders';
}
?>

<main class="page">
  <div class="home-container">

    <section class="page-header">
      <h1>My Orders</h1>
      <p>Track your orders and delivery status.</p>
    </section>

    <?php if ($apiError): ?>
      <p class="error-message"><?php echo htmlspecialchars($apiError); ?></p>
    <?php endif; ?>

    <section class="table-section">
      <h2>Orders</h2>

      <table class="data-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Auction</th>
            <th>Total</th>
            <th>Status</th>
            <th>Created</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody>
          <?php if (empty($orders)): ?>
            <tr>
              <td colspan="6" style="text-align:center; padding:20px; color:#6b7280;">
                You don't have any orders yet.
              </td>
            </tr>
          <?php else: ?>

            <?php foreach ($orders as $o): ?>
              <?php
                $orderId   = $o['order_id'] ?? '';
                $auctionId = $o['auction_id'] ?? '';
                $title     = $o['title'] ?? 'Untitled';
                $total     = $o['total_amount'] ?? 0;
                $status    = $o['status'] ?? 'pending'; // pending / paid / shipped / delivered / cancelled
                $createdAt = $o['created_at'] ?? '';

                // status badge class map
                $statusClass = 'status-outbid';
                if ($status === 'paid') $statusClass = 'status-active';
                if ($status === 'shipped') $statusClass = 'status-active';
                if ($status === 'delivered') $statusClass = 'status-won';
                if ($status === 'cancelled') $statusClass = 'status-lost';
              ?>

              <tr>
                <td>#<?php echo htmlspecialchars((string)$orderId); ?></td>

                <td>
                  <a href="auction_detail.php?id=<?php echo urlencode($auctionId); ?>">
                    <?php echo htmlspecialchars($title); ?>
                  </a>
                </td>

                <td>$<?php echo number_format((float)$total, 2); ?></td>

                <td>
                  <span class="status-badge <?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars(ucfirst($status)); ?>
                  </span>
                </td>

                <td><?php echo $createdAt ? htmlspecialchars($createdAt) : '—'; ?></td>

                <td>
                  <a class="btn-small" href="order_detail.php?id=<?php echo urlencode((string)$orderId); ?>">
                    View
                  </a>
                </td>
              </tr>

            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

  </div>
</main>

<?php include 'includes/footer.php'; ?>
