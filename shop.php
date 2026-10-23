<?php
require_once 'config.php';

$pageTitle = 'Shop poultry products';
$pageDescription = 'Shop fresh eggs, healthy chicks, poultry feed and farm supplies. Browse available products and arrange local delivery.';
$getString = static function (string $key, string $default = ''): string {
    $value = $_GET[$key] ?? $default;
    return is_scalar($value) ? trim((string) $value) : $default;
};
$hasFilterValue = static function (string $key): bool {
    if (!array_key_exists($key, $_GET)) {
        return false;
    }
    return !is_scalar($_GET[$key]) || trim((string) $_GET[$key]) !== '';
};
$search = $getString('q');
$search = substr($search, 0, 120);
$category = substr($getString('category'), 0, 80);
$availability = $getString('availability', 'in_stock');
$sort = $getString('sort', 'newest');
$allowedSorts = [
    'newest' => 'p.created_at DESC, p.id DESC',
    'price_asc' => 'p.price ASC, p.id DESC',
    'price_desc' => 'p.price DESC, p.id DESC',
    'name_asc' => 'p.name ASC, p.id ASC',
];
if (!isset($allowedSorts[$sort])) {
    $sort = 'newest';
}
if (!in_array($availability, ['all', 'in_stock', 'out_of_stock'], true)) {
    $availability = 'in_stock';
}
$perPage = filter_var($_GET['per_page'] ?? 12, FILTER_VALIDATE_INT);
if (!in_array($perPage, [12, 24, 48], true)) {
    $perPage = 12;
}
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = $page && $page > 0 ? $page : 1;

$parsePrice = static function (mixed $value): ?string {
    if (!is_scalar($value) || trim((string) $value) === '') {
        return null;
    }
    $value = trim((string) $value);
    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $value)) {
        return null;
    }
    return number_format((float) $value, 2, '.', '');
};
$minPrice = $parsePrice($_GET['min_price'] ?? null);
$maxPrice = $parsePrice($_GET['max_price'] ?? null);
$filterErrors = [];
if ($hasFilterValue('min_price') && $minPrice === null) {
    $filterErrors[] = 'Enter a valid minimum price.';
}
if ($hasFilterValue('max_price') && $maxPrice === null) {
    $filterErrors[] = 'Enter a valid maximum price.';
}
if ($minPrice !== null && $maxPrice !== null && (float) $minPrice > (float) $maxPrice) {
    [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
}

$categories = [];
$products = [];
$totalProducts = 0;
$catalogError = false;
$filterError = '';
$baseParams = [];
$where = [];
$pageCount = 0;
$firstResult = 0;
$lastResult = 0;
$availableStockSql = "(CASE WHEN p.batch_tracking = 1
    THEN (SELECT COALESCE(SUM(pb.available_units), 0) FROM product_batches pb WHERE pb.product_id = p.id AND pb.status = 'open')
    ELSE p.stock END)";

try {
    $pdo = db();
    $categories = $pdo->query(
        'SELECT c.id, c.name, c.slug, COUNT(p.id) AS product_count
         FROM categories c
         LEFT JOIN products p ON p.category_id = c.id
         GROUP BY c.id, c.name, c.slug
         ORDER BY c.name'
    )->fetchAll();

    if ($category !== '') {
        $categoryExists = false;
        foreach ($categories as $item) {
            if (hash_equals($item['name'], $category)) {
                $category = $item['name'];
                $categoryExists = true;
                break;
            }
        }
        if ($categoryExists) {
            $where[] = 'c.name = ?';
            $baseParams[] = $category;
        } else {
            $filterErrors[] = 'That category was not found. Showing all categories instead.';
            $category = '';
        }
    }

    if ($search !== '') {
        $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
        $baseParams[] = '%' . $search . '%';
        $baseParams[] = '%' . $search . '%';
    }
    if ($minPrice !== null) {
        $where[] = 'p.price >= ?';
        $baseParams[] = $minPrice;
    }
    if ($maxPrice !== null) {
        $where[] = 'p.price <= ?';
        $baseParams[] = $maxPrice;
    }
    if ($availability === 'in_stock') {
        $where[] = $availableStockSql . ' > 0';
    } elseif ($availability === 'out_of_stock') {
        $where[] = $availableStockSql . ' <= 0';
    }

    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $countStatement = $pdo->prepare(
        'SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id' . $whereSql
    );
    $countStatement->execute($baseParams);
    $totalProducts = (int) $countStatement->fetchColumn();
    $pageCount = (int) ceil($totalProducts / $perPage);
    if ($pageCount === 0) {
        $page = 1;
    } elseif ($page > $pageCount) {
        $page = $pageCount;
    }
    $offset = ($page - 1) * $perPage;
    $firstResult = $totalProducts > 0 ? $offset + 1 : 0;
    $lastResult = min($offset + $perPage, $totalProducts);

    $productStatement = $pdo->prepare(
        'SELECT p.*, c.name AS category_name, ' . $availableStockSql . ' AS available_stock,
                (SELECT MIN(pb.hatch_date) FROM product_batches pb WHERE pb.product_id = p.id AND pb.status = \'open\' AND pb.available_units > 0 AND pb.hatch_date > CURRENT_DATE()) AS next_batch_date
         FROM products p
         JOIN categories c ON c.id = p.category_id' . $whereSql .
         ' ORDER BY ' . $allowedSorts[$sort] . ' LIMIT ? OFFSET ?'
    );
    foreach ($baseParams as $index => $value) {
        $productStatement->bindValue($index + 1, $value);
    }
    $productStatement->bindValue(count($baseParams) + 1, $perPage, PDO::PARAM_INT);
    $productStatement->bindValue(count($baseParams) + 2, $offset, PDO::PARAM_INT);
    $productStatement->execute();
    $products = $productStatement->fetchAll();

    $priceBounds = $pdo->query('SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM products')->fetch();
} catch (Throwable $exception) {
    error_log('Shop catalogue could not be loaded: ' . $exception->getMessage());
    $catalogError = true;
    $priceBounds = ['min_price' => 0, 'max_price' => 0];
}

$filterError = implode(' ', $filterErrors);
$defaultMinPrice = $priceBounds['min_price'] ?? 0;
$defaultMaxPrice = $priceBounds['max_price'] ?? 0;
$queryParams = [
    'q' => $search,
    'category' => $category,
    'availability' => $availability,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'sort' => $sort,
    'per_page' => $perPage,
];
$queryParams = array_filter($queryParams, static fn ($value): bool => $value !== '' && $value !== null);
$rangeMinimum = (int) floor((float) $defaultMinPrice);
$rangeMaximum = max($rangeMinimum + 1, (int) ceil((float) $defaultMaxPrice));
$pageUrl = static function (int $targetPage) use ($queryParams): string {
    return 'shop.php?' . http_build_query($queryParams + ['page' => $targetPage]);
};

include 'includes/header.php';
?>
<section class="shop-banner">
    <div class="container shop-banner-content">
        <div class="eyebrow">Our farm stand</div>
        <h1>Shop</h1>
        <p>Quality poultry products, fresh from our farm to your table.</p>
        <nav class="shop-breadcrumb" aria-label="Breadcrumb"><a href="index.php">Home</a><span aria-hidden="true">›</span><span aria-current="page">Shop</span></nav>
    </div>
    <div class="shop-banner-seal" aria-hidden="true"><strong>Fresh</strong><strong>Healthy</strong><strong>Reliable</strong></div>
</section>

<section class="shop-page">
    <div class="container">
        <?php if ($filterError !== ''): ?><div class="notice" role="status"><?= e($filterError) ?></div><?php endif; ?>
        <?php if ($catalogError): ?>
            <div class="notice" role="alert">The product catalogue is temporarily unavailable. Please try again shortly.</div>
        <?php else: ?>
            <form class="shop-layout" action="shop.php" method="get">
                <aside class="shop-sidebar">
                    <div class="shop-filter-block">
                        <h2>Categories</h2>
                        <nav class="shop-category-list" aria-label="Filter by category">
                            <a class="<?= $category === '' ? 'active' : '' ?>" href="<?= e('shop.php' . ($search !== '' ? '?q=' . urlencode($search) : '')) ?>">
                                <span>All products</span><span class="shop-filter-count"><?= array_sum(array_column($categories, 'product_count')) ?></span>
                            </a>
                            <?php foreach ($categories as $item): ?>
                                <a class="<?= $category === $item['name'] ? 'active' : '' ?>" href="<?= e('shop.php?' . http_build_query(array_filter(['category' => $item['name'], 'q' => $search], static fn ($value): bool => $value !== ''))) ?>">
                                    <span><?= e($item['name']) ?></span><span class="shop-filter-count"><?= (int) $item['product_count'] ?></span>
                                </a>
                            <?php endforeach; ?>
                        </nav>
                    </div>

                    <div class="shop-filter-block">
                        <h2>Price range <small>(KSh)</small></h2>
                        <div class="shop-price-fields">
                            <label>Min <input type="number" name="min_price" min="0" step="1" value="<?= e($minPrice ?? '') ?>" placeholder="<?= e((string) $defaultMinPrice) ?>"></label>
                            <span aria-hidden="true">–</span>
                            <label>Max <input type="number" name="max_price" min="0" step="1" value="<?= e($maxPrice ?? '') ?>" placeholder="<?= e((string) $defaultMaxPrice) ?>"></label>
                        </div>
                        <div class="shop-price-sliders" data-price-range>
                            <label class="shop-sr-only" for="shop-min-range">Minimum price</label>
                            <input id="shop-min-range" type="range" min="<?= $rangeMinimum ?>" max="<?= $rangeMaximum ?>" value="<?= e($minPrice ?? (string) $rangeMinimum) ?>" data-price-min>
                            <label class="shop-sr-only" for="shop-max-range">Maximum price</label>
                            <input id="shop-max-range" type="range" min="<?= $rangeMinimum ?>" max="<?= $rangeMaximum ?>" value="<?= e($maxPrice ?? (string) $rangeMaximum) ?>" data-price-max>
                        </div>
                    </div>

                    <div class="shop-filter-block">
                        <h2>Availability</h2>
                        <label class="shop-select-label" for="shop-availability">Show products</label>
                        <select id="shop-availability" name="availability">
                            <option value="in_stock" <?= $availability === 'in_stock' ? 'selected' : '' ?>>In stock</option>
                            <option value="out_of_stock" <?= $availability === 'out_of_stock' ? 'selected' : '' ?>>Out of stock</option>
                            <option value="all" <?= $availability === 'all' ? 'selected' : '' ?>>All products</option>
                        </select>
                    </div>
                    <input type="hidden" name="category" value="<?= e($category) ?>">
                    <div class="shop-filter-actions">
                        <button class="btn btn-primary" type="submit">Apply filters</button>
                        <a class="shop-reset-link" href="shop.php">Reset filters</a>
                    </div>
                </aside>

                <div class="shop-results">
                    <div class="shop-search-bar">
                        <label class="shop-search-field">
                            <span class="shop-sr-only">Search products</span>
                            <span aria-hidden="true">⌕</span>
                            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search poultry products..." maxlength="120">
                        </label>
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                    <div class="shop-results-toolbar">
                        <p class="shop-result-count">Showing <strong><?= $firstResult ?>–<?= $lastResult ?></strong> of <strong><?= $totalProducts ?></strong> products</p>
                        <div class="shop-toolbar-controls">
                            <label>Sort by
                                <select name="sort" aria-label="Sort products" data-auto-submit>
                                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
                                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
                                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
                                    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A–Z</option>
                                </select>
                            </label>
                            <label class="shop-per-page">Show
                                <select name="per_page" aria-label="Products per page" data-auto-submit>
                                    <?php foreach ([12, 24, 48] as $amount): ?>
                                        <option value="<?= $amount ?>" <?= $perPage === $amount ? 'selected' : '' ?>><?= $amount ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <div class="shop-view-toggle" aria-label="Product layout">
                                <button type="button" class="is-active" data-shop-view="grid" aria-label="Grid view" aria-pressed="true">▦</button>
                                <button type="button" data-shop-view="list" aria-label="List view" aria-pressed="false">☷</button>
                            </div>
                        </div>
                    </div>

                    <?php if ($products): ?>
                        <div class="product-grid shop-product-grid" data-shop-grid>
                            <?php foreach ($products as $product): ?>
                                <?php $stock = (int) $product['available_stock']; ?>
                                <article class="product-card shop-product-card">
                                    <a class="shop-product-image-link" href="product.php?id=<?= (int) $product['id'] ?>">
                                        <div class="product-image">
                                            <img src="<?= e(product_image_src($product['image'])) ?>" alt="<?= e($product['name']) ?>" width="700" height="500" loading="lazy">
                                            <span class="shop-stock-badge <?= $stock > 0 ? 'available' : 'unavailable' ?>">
                                                <?php if ((int) $product['batch_tracking'] && $product['next_batch_date']): ?>
                                                    Next batch <?= e(date('j M', strtotime($product['next_batch_date']))) ?>
                                                <?php else: ?>
                                                    <?= e(stock_message($stock)) ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </a>
                                    <div class="product-info">
                                        <span class="shop-product-category"><?= e($product['category_name']) ?></span>
                                        <h2><a href="product.php?id=<?= (int) $product['id'] ?>"><?= e($product['name']) ?></a></h2>
                                        <p class="shop-product-description"><?= e($product['description']) ?></p>
                                        <div class="shop-product-price"><strong><?= money($product['price']) ?></strong><span>/ <?= e($product['unit']) ?></span></div>
                                        <div class="shop-product-stock <?= $stock <= LOW_STOCK_THRESHOLD ? 'is-low' : '' ?>">
                                            <span aria-hidden="true"><?= $stock > LOW_STOCK_THRESHOLD ? '✓' : '!' ?></span>
                                            <?= e(stock_message($stock)) ?>
                                        </div>
                                        <?php $productBatch = $product['batch_tracking'] && $product['next_batch_date'] ? ['hatch_date' => $product['next_batch_date']] : null; ?>
                                        <?php include __DIR__ . '/includes/quality-badges.php'; ?>
                                        <?php if ($productBatch): ?>
                                            <p class="batch-release-note">Pre-order for <?= e(date('j F Y', strtotime($productBatch['hatch_date']))) ?></p>
                                        <?php endif; ?>
                                        <?php if ($stock > 0): ?>
                                            <div class="shop-product-actions">
                                                <div class="shop-quantity-control" data-quantity-control>
                                                    <button type="button" data-quantity-change="-1" aria-label="Decrease quantity for <?= e($product['name']) ?>">−</button>
                                                    <input class="shop-quantity" type="number" min="1" max="<?= $stock ?>" value="1" inputmode="numeric" aria-label="Quantity for <?= e($product['name']) ?>">
                                                    <button type="button" data-quantity-change="1" aria-label="Increase quantity for <?= e($product['name']) ?>">+</button>
                                                </div>
                                                <button class="btn btn-primary shop-add-cart" type="button" data-add-cart="<?= (int) $product['id'] ?>" data-csrf="<?= e(csrf_token()) ?>">Add to cart</button>
                                            </div>
                                            <span class="shop-cart-feedback" data-cart-feedback aria-live="polite"></span>
                                        <?php else: ?>
                                            <button class="btn btn-outline shop-add-cart" type="button" disabled>Currently unavailable</button>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($pageCount > 1): ?>
                            <nav class="shop-pagination" aria-label="Product pages">
                                <?php if ($page > 1): ?><a href="<?= e($pageUrl($page - 1)) ?>" aria-label="Previous page">‹ Previous</a><?php endif; ?>
                                <?php for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++): ?>
                                    <?php if ($pageNumber === 1 || $pageNumber === $pageCount || abs($pageNumber - $page) <= 1): ?>
                                        <a href="<?= e($pageUrl($pageNumber)) ?>" class="<?= $pageNumber === $page ? 'active' : '' ?>" <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                                    <?php elseif (abs($pageNumber - $page) === 2): ?>
                                        <span aria-hidden="true">…</span>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <?php if ($page < $pageCount): ?><a href="<?= e($pageUrl($page + 1)) ?>" aria-label="Next page">Next ›</a><?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state shop-empty-state">
                            <div class="empty-icon" aria-hidden="true">⌕</div>
                            <h2>No products found</h2>
                            <p>Try a different search or adjust your filters to see more of the farm catalogue.</p>
                            <a class="btn btn-primary" href="shop.php">Reset filters</a>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="shop-trust-band" aria-label="Our service">
    <div class="container shop-trust-grid">
        <div><span aria-hidden="true">⌁</span><strong>Free delivery</strong><small>On qualifying orders over KSh 5,000</small></div>
        <div><span aria-hidden="true">✓</span><strong>Secure payments</strong><small>M-Pesa and bank options</small></div>
        <div><span aria-hidden="true">♡</span><strong>Quality products</strong><small>Selected for your home and flock</small></div>
        <div><span aria-hidden="true">☎</span><strong>Here to help</strong><small>Support from our farm team</small></div>
        <div><span aria-hidden="true">★</span><strong>Trusted farm</strong><small>Local service across Nairobi &amp; Kiambu</small></div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
