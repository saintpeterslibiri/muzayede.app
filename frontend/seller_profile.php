<?php
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

$userId = isset($_GET['user_id']) ? trim((string)$_GET['user_id']) : '';
$usernameParam = isset($_GET['username']) ? trim((string)$_GET['username']) : '';

$response = api_get("/auctions");
$allAuctions = [];

if (isset($response['success']) && $response['success'] === true) {
    $allAuctions = $response['data']['auctions'] ?? $response['data'] ?? [];
}

function pick_seller_username($item) {
    return
        ($item['username'] ?? null) ??
        ($item['seller_username'] ?? null) ??
        ($item['owner_username'] ?? null) ??
        ($item['created_by_username'] ?? null) ??
        ($item['user']['username'] ?? null) ??
        ($item['seller']['username'] ?? null) ??
        null;
}

function pick_seller_id($item) {
    return
        ($item['user_id'] ?? null) ??
        ($item['seller_id'] ?? null) ??
        ($item['owner_id'] ?? null) ??
        ($item['user']['id'] ?? null) ??
        ($item['seller']['id'] ?? null) ??
        null;
}

function is_active_auction($item) {
    $status = $item['status'] ?? null;
    if (is_string($status)) {
        $s = strtolower(trim($status));
        if ($s === 'ended' || $s === 'closed' || $s === 'finished') return false;
    }
    return true;
}

$filtered = [];

foreach ($allAuctions as $a) {
    $sid = pick_seller_id($a);
    $sun = pick_seller_username($a);

    $matchById = ($userId !== '' && $sid !== null && (string)$sid === (string)$userId);
    $matchByUsername = ($usernameParam !== '' && $sun !== null && strtolower((string)$sun) === strtolower($usernameParam));

    if ($matchById || $matchByUsername) {
        $filtered[] = $a;
    }
}

$profileUsername = $usernameParam;
$profileUserId = $userId;

if ($profileUsername === '' || $profileUserId === '') {
    foreach ($filtered as $a) {
        if ($profileUsername === '') {
            $u = pick_seller_username($a);
            if (!empty($u)) $profileUsername = (string)$u;
        }
        if ($profileUserId === '') {
            $i = pick_seller_id($a);
            if (!empty($i)) $profileUserId = (string)$i;
        }
        if ($profileUsername !== '' && $profileUserId !== '') break;
    }
}

$totalAuctions = count($filtered);
$activeCount = 0;
$endedCount = 0;

foreach ($filtered as $a) {
    if (is_active_auction($a)) $activeCount++;
    else $endedCount++;
}

$displayName = $profileUsername !== '' ? '@' . $profileUsername : 'Seller';
$initial = 'U';
if ($profileUsername !== '') $initial = strtoupper(mb_substr($profileUsername, 0, 1));
?>

<main class="page">
    <div class="home-container">

        <section class="page-header">
            <h1>Seller Profile</h1>
            <p>Profile details and auctions by this seller.</p>
        </section>

        <section class="profile-sidebar" style="flex-direction:row;align-items:center;justify-content:space-between;gap:18px;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div class="avatar-wrapper" style="width:92px;height:92px;">
                    <div class="nav-avatar-initial" style="width:100%;height:100%;border-radius:999px;">
                        <?php echo htmlspecialchars($initial); ?>
                    </div>
                </div>

                <div class="profile-basic-info" style="text-align:left;">
                    <div class="profile-name" style="font-size:20px;"><?php echo htmlspecialchars($displayName); ?></div>
                    <?php if ($profileUserId !== ''): ?>
                        <div class="profile-username">User ID: <?php echo htmlspecialchars($profileUserId); ?></div>
                    <?php else: ?>
                        <div class="profile-username">Public seller page</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-stats" style="margin-top:0;max-width:360px;">
                <div class="stat-item">
                    <span class="stat-label">Total</span>
                    <span class="stat-value"><?php echo (int)$totalAuctions; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Active</span>
                    <span class="stat-value"><?php echo (int)$activeCount; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Ended</span>
                    <span class="stat-value"><?php echo (int)$endedCount; ?></span>
                </div>
            </div>
        </section>

        <div style="display:flex;justify-content:flex-end;margin-top:10px;">
            <a class="btn-secondary" href="auctions.php" style="margin:0;">Back to Auctions</a>
        </div>

        <section style="margin-top:18px;">
            <h2 style="margin:0 0 12px;">Auctions by <?php echo htmlspecialchars($displayName); ?></h2>

            <?php if (empty($filtered)): ?>
                <p class="empty-state">No auctions found for this seller.</p>
            <?php else: ?>
                <section class="auction-grid">
                    <?php foreach ($filtered as $item): ?>
                        <?php
                            $id      = $item['id'] ?? 0;
                            $title   = $item['title'] ?? 'No Title';
                            $price   = $item['current_price'] ?? $item['starting_price'] ?? 0;
                            $endTime = $item['end_time'] ?? '';
                            $image   = (!empty($item['image_path'])) ? $item['image_path'] : 'assets/img/placeholder.png';
                            if (strpos($image, '/api/') === 0) {
                                $image = PUBLIC_API_URL . $image;
                            }
                        ?>
                        <div class="auction-card">
                            <div class="card-image">
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="Item">
                            </div>
                            <div class="card-content">
                                <h3><?php echo htmlspecialchars($title); ?></h3>
                                <p class="price">Current Bid: $<?php echo number_format((float)$price, 2); ?></p>
                                <p class="time">Ends: <?php echo htmlspecialchars($endTime); ?></p>
                                <a href="auction_detail.php?id=<?php echo (int)$id; ?>" class="btn-secondary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php include 'includes/footer.php'; ?>
