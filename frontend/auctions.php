<?php
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

$response = api_get("/auctions");
$auctions = [];

if (isset($response['success']) && $response['success'] === true) {
    $auctions = $response['data']['auctions'] ?? $response['data'] ?? [];
}

$categories = [];
foreach ($auctions as $it) {
    $cat =
        ($it['category'] ?? null) ??
        ($it['category_name'] ?? null) ??
        ($it['categoryTitle'] ?? null) ??
        (($it['category']['name'] ?? null) ?? null);

    if (is_string($cat)) {
        $catTrim = trim($cat);
        if ($catTrim !== '') $categories[$catTrim] = true;
    }
}
$categoryList = array_keys($categories);
sort($categoryList);

$prefSearch = isset($_GET['search']) ? (string)$_GET['search'] : '';
$prefCategory = isset($_GET['category']) ? (string)$_GET['category'] : '';
$prefMin = isset($_GET['min']) ? (string)$_GET['min'] : '';
$prefMax = isset($_GET['max']) ? (string)$_GET['max'] : '';
$prefSort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'default';
?>

<main class="page">
    <div class="home-container">

        <section class="page-header">
            <h1>All Auctions</h1>
            <p>Browse and bid on active items.</p>
        </section>

        <section class="search-section">
            <h2>Search & Filter</h2>
            <p>Filter auctions instantly on this page.</p>

            <form class="search-form" id="auctionFilterForm" onsubmit="return false;">
                <input type="text" id="q" placeholder="Search title" value="<?php echo htmlspecialchars($prefSearch); ?>">

                <select id="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categoryList as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($prefCategory === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="number" id="minPrice" placeholder="Min price" min="0" step="0.01" value="<?php echo htmlspecialchars($prefMin); ?>">
                <input type="number" id="maxPrice" placeholder="Max price" min="0" step="0.01" value="<?php echo htmlspecialchars($prefMax); ?>">

                <select id="sortBy">
                    <option value="default" <?php echo ($prefSort === 'default') ? 'selected' : ''; ?>>Sort</option>
                    <option value="priceAsc" <?php echo ($prefSort === 'priceAsc') ? 'selected' : ''; ?>>Price ↑</option>
                    <option value="priceDesc" <?php echo ($prefSort === 'priceDesc') ? 'selected' : ''; ?>>Price ↓</option>
                    <option value="titleAsc" <?php echo ($prefSort === 'titleAsc') ? 'selected' : ''; ?>>Title A–Z</option>
                    <option value="titleDesc" <?php echo ($prefSort === 'titleDesc') ? 'selected' : ''; ?>>Title Z–A</option>
                </select>

                <button class="btn-primary" type="button" id="searchBtn">Ara</button>
                <button class="btn-secondary" type="button" id="resetBtn">Temizle</button>
            </form>

            <p id="filterInfo" class="section-subtitle"></p>
        </section>

        <section class="auction-grid" id="auctionGrid">
            <?php if (empty($auctions)): ?>
                <p class="empty-state">No active auctions found.</p>
            <?php else: ?>
                <?php foreach ($auctions as $item): ?>
                    <?php
                        $id      = $item['id'] ?? 0;
                        $title   = $item['title'] ?? 'No Title';
                        $price   = $item['current_price'] ?? $item['starting_price'] ?? 0;
                        $endTime = $item['end_time'] ?? '';
                        $image   = (!empty($item['image_path'])) ? $item['image_path'] : 'assets/img/placeholder.png';

                        $cat =
                            ($item['category'] ?? null) ??
                            ($item['category_name'] ?? null) ??
                            ($item['categoryTitle'] ?? null) ??
                            (($item['category']['name'] ?? null) ?? null);

                        $sellerUsername =
                            ($item['username'] ?? null) ??
                            ($item['seller_username'] ?? null) ??
                            ($item['owner_username'] ?? null) ??
                            ($item['created_by_username'] ?? null) ??
                            (($item['user']['username'] ?? null) ?? null) ??
                            (($item['seller']['username'] ?? null) ?? null);

                        $sellerId =
                            ($item['user_id'] ?? null) ??
                            ($item['seller_id'] ?? null) ??
                            ($item['owner_id'] ?? null) ??
                            (($item['user']['id'] ?? null) ?? null) ??
                            (($item['seller']['id'] ?? null) ?? null);

                        $sellerHref = '';
                        if (!empty($sellerId)) {
                            $sellerHref = 'seller_profile.php?user_id=' . urlencode((string)$sellerId);
                        } elseif (!empty($sellerUsername)) {
                            $sellerHref = 'seller_profile.php?username=' . urlencode((string)$sellerUsername);
                        }
                    ?>
                    <div class="auction-card"
                         data-title="<?php echo htmlspecialchars(mb_strtolower((string)$title)); ?>"
                         data-price="<?php echo htmlspecialchars((string)$price); ?>"
                         data-category="<?php echo htmlspecialchars(mb_strtolower((string)($cat ?? ''))); ?>">

                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($image); ?>" alt="Item">
                        </div>

                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($title); ?></h3>

                            <?php if ($sellerHref !== '' && !empty($sellerUsername)): ?>
                                <p class="time">
                                    Seller:
                                    <a class="view-all" href="<?php echo htmlspecialchars($sellerHref); ?>">
                                        @<?php echo htmlspecialchars((string)$sellerUsername); ?>
                                    </a>
                                </p>
                            <?php endif; ?>

                            <p class="price">Current Bid: $<?php echo number_format((float)$price, 2); ?></p>
                            <p class="time">Ends: <?php echo htmlspecialchars($endTime); ?></p>
                            <a href="auction_detail.php?id=<?php echo $id; ?>" class="btn-secondary">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <script>
            const q = document.getElementById("q");
            const category = document.getElementById("category");
            const minPrice = document.getElementById("minPrice");
            const maxPrice = document.getElementById("maxPrice");
            const sortBy = document.getElementById("sortBy");
            const searchBtn = document.getElementById("searchBtn");
            const resetBtn = document.getElementById("resetBtn");
            const grid = document.getElementById("auctionGrid");
            const info = document.getElementById("filterInfo");

            function n(v){
                const x = parseFloat(v);
                return Number.isFinite(x) ? x : null;
            }

            function applyFilters(){
                const query = (q.value || "").trim().toLowerCase();
                const cat = (category.value || "").trim().toLowerCase();
                const min = n(minPrice.value);
                const max = n(maxPrice.value);

                const cards = Array.from(grid.querySelectorAll(".auction-card"));
                let visible = 0;

                cards.forEach(card => {
                    const t = (card.dataset.title || "");
                    const p = n(card.dataset.price) ?? 0;
                    const c = (card.dataset.category || "");

                    const okTitle = !query || t.includes(query);
                    const okCat = !cat || c === cat;
                    const okMin = (min === null) || p >= min;
                    const okMax = (max === null) || p <= max;

                    const show = okTitle && okCat && okMin && okMax;
                    card.style.display = show ? "" : "none";
                    if (show) visible++;
                });

                const visibleCards = cards.filter(c => c.style.display !== "none");
                const mode = sortBy.value;

                const sorted = [...visibleCards].sort((a,b)=>{
                    const pa = n(a.dataset.price) ?? 0;
                    const pb = n(b.dataset.price) ?? 0;
                    const ta = (a.querySelector("h3")?.textContent || "").toLowerCase();
                    const tb = (b.querySelector("h3")?.textContent || "").toLowerCase();

                    if (mode === "priceAsc") return pa - pb;
                    if (mode === "priceDesc") return pb - pa;
                    if (mode === "titleAsc") return ta.localeCompare(tb);
                    if (mode === "titleDesc") return tb.localeCompare(ta);
                    return 0;
                });

                sorted.forEach(el => grid.appendChild(el));

                if (info) info.textContent = visible === 0 ? "No results found." : (visible + " item(s) shown.");
            }

            function resetFilters(){
                q.value = "";
                category.value = "";
                minPrice.value = "";
                maxPrice.value = "";
                sortBy.value = "default";
                Array.from(grid.querySelectorAll(".auction-card")).forEach(card => card.style.display = "");
                if (info) info.textContent = "";
                const url = new URL(window.location.href);
                ["search","category","min","max","sort"].forEach(k => url.searchParams.delete(k));
                window.history.replaceState({}, "", url.toString());
            }

            function syncUrl(){
                const url = new URL(window.location.href);
                const s = (q.value || "").trim();
                const c = (category.value || "").trim();
                const min = (minPrice.value || "").trim();
                const max = (maxPrice.value || "").trim();
                const sort = (sortBy.value || "default").trim();

                if (s) url.searchParams.set("search", s); else url.searchParams.delete("search");
                if (c) url.searchParams.set("category", c); else url.searchParams.delete("category");
                if (min) url.searchParams.set("min", min); else url.searchParams.delete("min");
                if (max) url.searchParams.set("max", max); else url.searchParams.delete("max");
                if (sort && sort !== "default") url.searchParams.set("sort", sort); else url.searchParams.delete("sort");

                window.history.replaceState({}, "", url.toString());
            }

            searchBtn.onclick = function(){
                syncUrl();
                applyFilters();
            };

            resetBtn.onclick = function(){
                resetFilters();
            };

            q.addEventListener("keydown", function(e){
                if (e.key === "Enter"){
                    e.preventDefault();
                    searchBtn.click();
                }
            });

            applyFilters();
        </script>

    </div>
</main>

<?php include 'includes/footer.php'; ?>
