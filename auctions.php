<?php
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

// Filtre değerlerini GET'ten al
$q        = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$sort     = isset($_GET['sort']) ? trim($_GET['sort']) : 'ending_soon';
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// API query string oluştur
$params = [];
if ($q !== '')        $params['q'] = $q;
if ($category !== '') $params['category'] = $category;
if ($sort !== '')     $params['sort'] = $sort;
$params['page']  = $page;
$params['limit'] = 9;

$queryString = http_build_query($params);

// API çağrısı
$response = api_get('/auctions?' . $queryString);

// Backend sendSuccess formatına göre parse
$auctions = [];
$pagination = null;
$apiError = null;

if (is_array($response) && isset($response['success']) && $response['success'] === true) {
    $data = $response['data'] ?? [];

    $auctions = $data['auctions'] ?? [];
    $pagination = $data['pagination'] ?? null;
} else {
    $apiError = $response['message'] ?? 'API error';
}

?>

<main class="page">

    <!-- BEYAZ KART BAŞLANGIÇ -->
    <div class="home-container">

        <!-- SAYFA BAŞLIĞI -->
        <section class="page-header">
            <h1>All Auctions</h1>
            <p>Browse all active auctions and place your bids in real-time.</p>
        </section>

        <!-- FİLTRE FORMU -->
        <section class="filters">
            <form class="filter-form" method="GET">
                <input type="text" name="q" placeholder="Search items..." value="<?php echo htmlspecialchars($q); ?>">

                <select name="category">
                    <option value="" <?php echo $category === '' ? 'selected' : ''; ?>>All Categories</option>
                    <option value="electronics" <?php echo $category === 'electronics' ? 'selected' : ''; ?>>Electronics</option>
                    <option value="collectibles" <?php echo $category === 'collectibles' ? 'selected' : ''; ?>>Collectibles</option>
                    <option value="other" <?php echo $category === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>

                <select name="sort">
                    <option value="ending_soon" <?php echo $sort === 'ending_soon' ? 'selected' : ''; ?>>Ending Soon</option>
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                    <option value="highest_bid" <?php echo $sort === 'highest_bid' ? 'selected' : ''; ?>>Highest Bid</option>
                </select>

                <button type="submit">Filter</button>
            </form>
        </section>


        <!-- AUCTION LİSTESİ -->
        <section class="auction-list">

            <?php if (!$auctions || count($auctions) === 0): ?>
                <p class="no-auctions">No auctions found.</p>
            <?php else: ?>
                <?php foreach ($auctions as $a): ?>
                    <?php
                        $id       = $a['id'] ?? '';
                        $title    = $a['title'] ?? 'Untitled';
                        $price    = $a['current_price'] ?? ($a['starting_price'] ?? 0);
                        $endTime  = $a['end_time'] ?? '';
                        $img      = $a['image_path'] ?? 'assets/img/placeholder.png';
                    ?>
                    <article class="auction-card">
                        <img src="<?php echo htmlspecialchars($img ?: 'assets/img/placeholder.png'); ?>" alt="">
                        <h2><?php echo htmlspecialchars($title); ?></h2>
                        <p>Current bid: $<?php echo htmlspecialchars(number_format((float)$price, 2)); ?></p>
                        <p class="time-left">
                            <?php echo $endTime ? 'Ends at: ' . htmlspecialchars($endTime) : 'Ends at: N/A'; ?>
                        </p>
                        <a href="auction_detail.php?id=<?php echo urlencode((string)$id); ?>" class="btn-small">View Details</a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>

        </section>

        <!-- PAGINATION (KART İÇİNDE) -->
        <?php
        // Pagination backend'den gelirse onu bas
        if (is_array($pagination)) {
            $totalPages  = (int)($pagination['totalPages'] ?? 1);
            $currentPage = (int)($pagination['page'] ?? $page);

            if ($totalPages > 1) {
                echo '<nav class="pagination">';
                for ($p = 1; $p <= $totalPages; $p++) {
                    $qs = $_GET;
                    $qs['page'] = $p;
                    $link = '?' . http_build_query($qs);
                    $active = ($p === $currentPage) ? 'active' : '';
                    echo '<a href="' . htmlspecialchars($link) . '" class="page-link ' . $active . '">' . $p . '</a>';
                }
                echo '</nav>';
            }
        }
        ?>

    </div>
    <!-- BEYAZ KART BİTİŞ -->

</main>

<?php include 'includes/footer.php'; ?>
