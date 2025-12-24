<?php
include 'includes/header.php';
include 'includes/navbar.php';
require_once 'includes/api_client.php';

// İlk yüklemede sadece ilk sayfayı yükle (limit=12)
$response = api_get("/auctions?page=1&limit=12");
$auctions = [];
$pagination = null;

if (isset($response['success']) && $response['success'] === true) {
    $auctions = $response['data']['auctions'] ?? $response['data'] ?? [];
    $pagination = $response['data']['pagination'] ?? null;
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
                        if (strpos($image, '/api/') === 0) {
                            $image = PUBLIC_API_URL . $image;
                        }

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

        <!-- Loading indicator -->
        <div id="loadingIndicator" style="text-align: center; padding: 20px; display: none;">
            <p>Loading more auctions...</p>
        </div>

        <!-- End of list message -->
        <div id="endOfList" style="text-align: center; padding: 20px; display: none;">
            <p>No more auctions to load.</p>
        </div>

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
            const loadingIndicator = document.getElementById("loadingIndicator");
            const endOfList = document.getElementById("endOfList");

            // Infinite scroll state
            let currentPage = <?php echo $pagination ? $pagination['currentPage'] : 1; ?>;
            let totalPages = <?php echo $pagination ? $pagination['totalPages'] : 1; ?>;
            let isLoading = false;
            let hasMorePages = <?php echo $pagination ? ($pagination['hasNextPage'] ? 'true' : 'false') : 'false'; ?>;
            let currentFilters = {
                search: '<?php echo htmlspecialchars($prefSearch, ENT_QUOTES); ?>',
                category: '<?php echo htmlspecialchars($prefCategory, ENT_QUOTES); ?>',
                min: '<?php echo htmlspecialchars($prefMin, ENT_QUOTES); ?>',
                max: '<?php echo htmlspecialchars($prefMax, ENT_QUOTES); ?>',
                sort: '<?php echo htmlspecialchars($prefSort, ENT_QUOTES); ?>'
            };

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

            // Load more auctions via API
            async function loadMoreAuctions() {
                if (isLoading || !hasMorePages) return;

                isLoading = true;
                loadingIndicator.style.display = 'block';
                endOfList.style.display = 'none';

                try {
                    const nextPage = currentPage + 1;
                    const params = new URLSearchParams({
                        page: nextPage,
                        limit: '12'
                    });

                    // Add filters
                    if (currentFilters.search) params.append('q', currentFilters.search);
                    if (currentFilters.category) params.append('category', currentFilters.category);
                    if (currentFilters.min) params.append('min', currentFilters.min);
                    if (currentFilters.max) params.append('max', currentFilters.max);
                    if (currentFilters.sort && currentFilters.sort !== 'default') {
                        // Map frontend sort values to backend sort values
                        const sortMap = {
                            'priceAsc': 'lowest_bid',
                            'priceDesc': 'highest_bid',
                            'titleAsc': 'newest', // Backend doesn't have title sort, use newest
                            'titleDesc': 'newest'
                        };
                        const backendSort = sortMap[currentFilters.sort] || currentFilters.sort;
                        params.append('sort', backendSort);
                    }

                    const response = await fetch('<?php echo PUBLIC_API_URL; ?>/api/auctions?' + params.toString());
                    const data = await response.json();

                    if (data.success && data.data && data.data.auctions) {
                        const newAuctions = data.data.auctions;
                        const pagination = data.data.pagination;

                        // Add new auction cards to grid
                        newAuctions.forEach(item => {
                            const card = createAuctionCard(item);
                            grid.appendChild(card);
                        });

                        // Update state
                        currentPage = pagination.currentPage;
                        totalPages = pagination.totalPages;
                        hasMorePages = pagination.hasNextPage;

                        // Apply filters to new cards
                        applyFilters();

                        if (!hasMorePages) {
                            endOfList.style.display = 'block';
                        }
                    }
                } catch (error) {
                    console.error('Error loading more auctions:', error);
                } finally {
                    isLoading = false;
                    loadingIndicator.style.display = 'none';
                }
            }

            // Create auction card element
            function createAuctionCard(item) {
                const card = document.createElement('div');
                card.className = 'auction-card';
                card.dataset.title = (item.title || '').toLowerCase();
                card.dataset.price = item.current_price || item.starting_price || 0;
                card.dataset.category = (item.category || '').toLowerCase();

                let image = item.image_path || 'assets/img/placeholder.png';
                if (image.startsWith('/api/')) {
                    image = '<?php echo PUBLIC_API_URL; ?>' + image;
                }

                const sellerUsername = item.seller_username || item.username || '';
                const sellerId = item.seller_id || item.user_id || '';
                let sellerHref = '';
                if (sellerId) {
                    sellerHref = `seller_profile.php?user_id=${encodeURIComponent(sellerId)}`;
                } else if (sellerUsername) {
                    sellerHref = `seller_profile.php?username=${encodeURIComponent(sellerUsername)}`;
                }

                card.innerHTML = `
                    <div class="card-image">
                        <img src="${escapeHtml(image)}" alt="Item">
                    </div>
                    <div class="card-content">
                        <h3>${escapeHtml(item.title || 'No Title')}</h3>
                        ${sellerHref && sellerUsername ? `
                            <p class="time">
                                Seller:
                                <a class="view-all" href="${escapeHtml(sellerHref)}">
                                    @${escapeHtml(sellerUsername)}
                                </a>
                            </p>
                        ` : ''}
                        <p class="price">Current Bid: $${formatNumber(item.current_price || item.starting_price || 0)}</p>
                        <p class="time">Ends: ${escapeHtml(item.end_time || '')}</p>
                        <a href="auction_detail.php?id=${item.id}" class="btn-secondary">View Details</a>
                    </div>
                `;

                return card;
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function formatNumber(num) {
                return parseFloat(num).toFixed(2);
            }

            // Scroll event listener for infinite scroll
            let scrollTimeout;
            window.addEventListener('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(function() {
                    // Check if user scrolled near bottom (within 200px)
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const windowHeight = window.innerHeight;
                    const documentHeight = document.documentElement.scrollHeight;

                    if (documentHeight - (scrollTop + windowHeight) < 200) {
                        loadMoreAuctions();
                    }
                }, 100);
            });

            // Reload auctions from API with new filters
            async function reloadAuctions() {
                isLoading = true;
                loadingIndicator.style.display = 'block';
                endOfList.style.display = 'none';

                // Clear existing cards
                grid.innerHTML = '';

                try {
                    const params = new URLSearchParams({
                        page: '1',
                        limit: '12'
                    });

                    // Add filters
                    if (currentFilters.search) params.append('q', currentFilters.search);
                    if (currentFilters.category) params.append('category', currentFilters.category);
                    if (currentFilters.min) params.append('min', currentFilters.min);
                    if (currentFilters.max) params.append('max', currentFilters.max);
                    if (currentFilters.sort && currentFilters.sort !== 'default') {
                        const sortMap = {
                            'priceAsc': 'lowest_bid',
                            'priceDesc': 'highest_bid',
                            'titleAsc': 'newest',
                            'titleDesc': 'newest'
                        };
                        const backendSort = sortMap[currentFilters.sort] || currentFilters.sort;
                        params.append('sort', backendSort);
                    }

                    const response = await fetch('<?php echo PUBLIC_API_URL; ?>/api/auctions?' + params.toString());
                    const data = await response.json();

                    if (data.success && data.data && data.data.auctions) {
                        const auctions = data.data.auctions;
                        const pagination = data.data.pagination;

                        if (auctions.length === 0) {
                            grid.innerHTML = '<p class="empty-state">No active auctions found.</p>';
                        } else {
                            auctions.forEach(item => {
                                const card = createAuctionCard(item);
                                grid.appendChild(card);
                            });
                        }

                        // Update state
                        currentPage = pagination.currentPage;
                        totalPages = pagination.totalPages;
                        hasMorePages = pagination.hasNextPage;

                        if (!hasMorePages && auctions.length > 0) {
                            endOfList.style.display = 'block';
                        }
                    }
                } catch (error) {
                    console.error('Error reloading auctions:', error);
                    grid.innerHTML = '<p class="empty-state">Error loading auctions. Please try again.</p>';
                } finally {
                    isLoading = false;
                    loadingIndicator.style.display = 'none';
                }
            }

            // Update filters when search button is clicked
            searchBtn.onclick = function(){
                // Reset pagination when filters change
                currentPage = 1;
                hasMorePages = true;
                endOfList.style.display = 'none';
                
                // Update current filters
                currentFilters = {
                    search: (q.value || "").trim(),
                    category: (category.value || "").trim(),
                    min: (minPrice.value || "").trim(),
                    max: (maxPrice.value || "").trim(),
                    sort: (sortBy.value || "default").trim()
                };

                syncUrl();
                reloadAuctions();
            };

            resetBtn.onclick = function(){
                // Reset form values
                q.value = "";
                category.value = "";
                minPrice.value = "";
                maxPrice.value = "";
                sortBy.value = "default";
                
                // Reset pagination and filters
                currentPage = 1;
                hasMorePages = true;
                endOfList.style.display = 'none';
                currentFilters = {
                    search: '',
                    category: '',
                    min: '',
                    max: '',
                    sort: 'default'
                };

                // Update URL
                const url = new URL(window.location.href);
                ["search","category","min","max","sort"].forEach(k => url.searchParams.delete(k));
                window.history.replaceState({}, "", url.toString());

                // Reload auctions
                reloadAuctions();
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
