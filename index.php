<?php
require_once 'config.php';
<<<<<<< HEAD

$pageTitle = 'Fresh poultry products, raised with care';
$catalogError = false;
$categories = [];
$featured = [];
$categoryDetails = [
    'broilers' => [
        'title' => 'Live broilers',
        'description' => 'Healthy birds, raised with care.',
        'image' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?auto=format&fit=crop&w=600&q=80',
    ],
    'layers' => [
        'title' => 'Layers & pullets',
        'description' => 'A strong start for your laying flock.',
        'image' => 'https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?auto=format&fit=crop&w=600&q=80',
    ],
    'day-old-chicks' => [
        'title' => 'Day-old chicks',
        'description' => 'Lively chicks for a confident start.',
        'image' => 'https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?auto=format&fit=crop&w=600&q=80',
    ],
    'fresh-eggs' => [
        'title' => 'Fresh eggs',
        'description' => 'Farm-fresh eggs for your table.',
        'image' => 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=600&q=80',
    ],
    'feed' => [
        'title' => 'Poultry feed',
        'description' => 'Balanced nutrition for every stage.',
        'image' => 'https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=600&q=80',
    ],
    'poultry-equipment' => [
        'title' => 'Equipment & supplies',
        'description' => 'Practical essentials for your flock.',
        'image' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=600&q=80',
    ],
];

try {
    $pdo = db();
    $availableStockSql = "(CASE WHEN p.batch_tracking = 1
        THEN (SELECT COALESCE(SUM(pb.available_units), 0) FROM product_batches pb WHERE pb.product_id = p.id AND pb.status = 'open')
        ELSE p.stock END)";
    $categorySlugs = array_keys($categoryDetails);
    $placeholders = implode(',', array_fill(0, count($categorySlugs), '?'));
    $statement = $pdo->prepare("SELECT id, name, slug FROM categories WHERE slug IN ($placeholders)");
    $statement->execute($categorySlugs);
    foreach ($statement->fetchAll() as $category) {
        $categories[$category['slug']] = $category;
    }

    $featured = $pdo->query(
        'SELECT p.*, c.name AS category_name, ' . $availableStockSql . ' AS available_stock,
                (SELECT MIN(pb.hatch_date) FROM product_batches pb WHERE pb.product_id = p.id AND pb.status = \'open\' AND pb.available_units > 0 AND pb.hatch_date > CURRENT_DATE()) AS next_batch_date
         FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.featured = 1 AND ' . $availableStockSql . ' > 0
         ORDER BY p.id DESC
         LIMIT 6'
    )->fetchAll();

    if (!$featured) {
        $featured = $pdo->query(
            'SELECT p.*, c.name AS category_name, ' . $availableStockSql . ' AS available_stock,
                    (SELECT MIN(pb.hatch_date) FROM product_batches pb WHERE pb.product_id = p.id AND pb.status = \'open\' AND pb.available_units > 0 AND pb.hatch_date > CURRENT_DATE()) AS next_batch_date
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE ' . $availableStockSql . ' > 0
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT 6'
        )->fetchAll();
    }
} catch (Throwable $exception) {
    error_log('Homepage catalog could not be loaded: ' . $exception->getMessage());
    $catalogError = true;
}

include 'includes/header.php';
?>
<section class="hero home-hero">
    <div class="container hero-content">
        <div class="eyebrow">Welcome to Green Garden Farm</div>
        <h1>Fresh poultry products, straight from our farm.</h1>
        <p>Healthy birds, fresh eggs and trusted farm essentials, thoughtfully raised and ready for delivery across our local community.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="shop.php">Shop products <span aria-hidden="true">→</span></a>
            <a class="btn btn-light" href="contact.php">Contact our farm</a>
        </div>
        <div class="hero-promises" aria-label="Our promises">
            <span><strong>Fresh</strong> from the farm</span>
            <span><strong>Healthy</strong> poultry</span>
            <span><strong>Careful</strong> delivery</span>
        </div>
    </div>
    <div class="hero-quality" aria-label="Farm-fresh quality">
        <span aria-hidden="true">✦</span>
        <strong>Farm fresh</strong>
        <small>Quality you can trust</small>
    </div>
</section>

<section class="section home-categories">
    <div class="container">
        <div class="section-heading">
            <div>
                <div class="eyebrow">Find what you need</div>
                <h2>Shop by category</h2>
                <p>Everything for your table and your flock, all in one place.</p>
            </div>
            <a class="text-link" href="shop.php">Browse all products <span aria-hidden="true">→</span></a>
        </div>
        <div class="home-category-grid">
            <?php foreach ($categoryDetails as $slug => $details): ?>
                <?php if (isset($categories[$slug])): ?>
                    <a class="home-category-card" href="shop.php?category=<?= urlencode($categories[$slug]['name']) ?>" style="--category-image: url('<?= e($details['image']) ?>')">
                        <span class="home-category-arrow" aria-hidden="true">↗</span>
                        <span class="home-category-copy">
                            <strong><?= e($details['title']) ?></strong>
                            <small><?= e($details['description']) ?></small>
                            <span class="home-category-link">Shop now <span aria-hidden="true">→</span></span>
                        </span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="home-benefits" aria-label="Why shop with us">
    <div class="container home-benefit-grid">
        <div class="home-benefit"><span aria-hidden="true">♡</span><div><strong>Healthy, well-cared-for birds</strong><small>Care comes first at every stage.</small></div></div>
        <div class="home-benefit"><span aria-hidden="true">✓</span><div><strong>Quality you can count on</strong><small>Thoughtful products, prepared with care.</small></div></div>
        <div class="home-benefit"><span aria-hidden="true">⌁</span><div><strong>Convenient local delivery</strong><small>We make getting farm essentials simple.</small></div></div>
        <div class="home-benefit"><span aria-hidden="true">☎</span><div><strong>Helpful, personal service</strong><small>Our farm is here when you need us.</small></div></div>
    </div>
</section>

<section class="section home-featured">
    <div class="container">
        <div class="section-heading">
            <div>
                <div class="eyebrow">Selected from our farm</div>
                <h2>Featured products</h2>
                <p>Popular picks for your kitchen, home and growing flock.</p>
            </div>
            <a class="text-link" href="shop.php">View all products <span aria-hidden="true">→</span></a>
        </div>

        <?php if ($catalogError): ?>
            <div class="notice" role="status">Our product catalogue is temporarily unavailable. Please try again shortly or contact our farm for assistance.</div>
        <?php elseif ($featured): ?>
            <div class="product-grid home-product-grid">
                <?php foreach ($featured as $product): ?>
                    <?php $stock = (int) $product['available_stock']; ?>
                    <article class="product-card home-product-card">
                        <a href="product.php?id=<?= (int) $product['id'] ?>" class="home-product-image-link">
                            <div class="product-image">
                                <img src="<?= e(product_image_src($product['image'])) ?>" alt="<?= e($product['name']) ?>" width="900" height="620" loading="lazy">
                                <span class="badge"><?= e($product['category_name']) ?></span>
                            </div>
                        </a>
                        <div class="product-info">
                            <div class="rating" aria-label="<?= e((string) $product['rating']) ?> out of 5 stars">★★★★★ <span><?= e((string) $product['rating']) ?></span></div>
                            <h3><a href="product.php?id=<?= (int) $product['id'] ?>"><?= e($product['name']) ?></a></h3>
                            <p><?= e($product['description']) ?></p>
                            <div class="home-stock <?= $stock <= LOW_STOCK_THRESHOLD ? 'is-low' : '' ?>">
                                <span aria-hidden="true"><?= $stock <= LOW_STOCK_THRESHOLD ? '!' : '✓' ?></span>
                                <?= e(stock_message($stock)) ?>
                            </div>
                            <?php $batch = $product['batch_tracking'] && $product['next_batch_date'] ? ['hatch_date' => $product['next_batch_date']] : null; ?>
                            <?php include __DIR__ . '/includes/quality-badges.php'; ?>
                            <?php if ($batch): ?><p class="batch-release-note">Next batch <?= e(date('j F Y', strtotime($batch['hatch_date']))) ?></p><?php endif; ?>
                            <div class="home-product-purchase">
                                <span class="price"><?= money($product['price']) ?></span>
                                <label class="home-quantity-label">
                                    <span>Qty</span>
                                    <input class="home-quantity" type="number" min="1" max="<?= $stock ?>" value="1" inputmode="numeric" aria-label="Quantity for <?= e($product['name']) ?>">
                                </label>
                            </div>
                            <button class="btn btn-primary home-add-cart" type="button" data-add-cart="<?= (int) $product['id'] ?>" data-csrf="<?= e(csrf_token()) ?>">
                                Add to cart
                            </button>
                            <span class="home-cart-feedback" aria-live="polite"></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>New farm favourites are on the way</h3>
                <p>Explore our full catalogue to find something for your flock.</p>
                <a class="btn btn-primary" href="shop.php">Browse products</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="home-process">
    <div class="container home-process-layout">
        <div>
            <div class="eyebrow">From our farm to your home</div>
            <h2>Good care at every step.</h2>
            <p>From choosing the right products to getting your order home, we make the experience clear, thoughtful and personal.</p>
            <a class="btn btn-primary" href="about.php">Get to know our farm</a>
        </div>
        <ol class="home-process-steps">
            <li><span>01</span><div><strong>Choose with confidence</strong><p>Browse farm products and find the right fit for your home or flock.</p></div></li>
            <li><span>02</span><div><strong>We prepare your order</strong><p>Our team gets your selected products ready with care.</p></div></li>
            <li><span>03</span><div><strong>Stay in the loop</strong><p>Track your order and contact us if you need a hand.</p></div></li>
        </ol>
    </div>
</section>

<section class="section home-testimonials">
    <div class="container">
        <div class="section-heading">
            <div>
                <div class="eyebrow">Kind words from customers</div>
                <h2>Good food is better shared.</h2>
            </div>
            <a class="text-link" href="contact.php">Talk to our team <span aria-hidden="true">→</span></a>
        </div>
        <div class="testimonial-grid">
            <article class="testimonial"><div class="rating" aria-label="5 out of 5 stars">★★★★★</div><p>“The eggs arrive beautifully packed and the yolks are genuinely golden.”</p><strong>— Amina, Lavington</strong></article>
            <article class="testimonial"><div class="rating" aria-label="5 out of 5 stars">★★★★★</div><p>“The chicks were lively and the team gave us a clear starter guide.”</p><strong>— Peter, Limuru</strong></article>
            <article class="testimonial"><div class="rating" aria-label="5 out of 5 stars">★★★★★</div><p>“Fast delivery, kind people, and thoughtful farm advice.”</p><strong>— Wanjiku, Karen</strong></article>
        </div>
    </div>
</section>

<section class="home-faq">
    <div class="container home-faq-layout">
        <div>
            <div class="eyebrow">A few helpful answers</div>
            <h2>Good to know.</h2>
            <p>Need more help? Our team is happy to talk through your order.</p>
            <a class="text-link" href="contact.php">Contact our farm <span aria-hidden="true">→</span></a>
        </div>
        <div class="home-faq-list">
            <details><summary>Where do you deliver?</summary><p>We arrange local delivery around Nairobi, Kiambu and nearby areas. Contact us to confirm delivery options for your location.</p></details>
            <details><summary>How can I check product availability?</summary><p>Current stock is shown on each product. If you need a larger quantity, please contact the farm before placing your order.</p></details>
            <details><summary>How do I follow my order?</summary><p>Sign in to your account to review your orders and their latest status.</p></details>
        </div>
    </div>
</section>

<section class="newsletter home-newsletter">
    <div class="container newsletter-inner">
        <div>
            <div class="eyebrow">A note from the farm</div>
            <h2>Fresh news, no clutter.</h2>
            <p>Get occasional farm updates, new arrivals and helpful poultry tips.</p>
        </div>
        <form class="newsletter-form" action="newsletter.php" method="post">
            <input type="email" name="email" placeholder="Your email address" aria-label="Your email address" autocomplete="email" required>
            <button class="btn btn-primary" type="submit">Subscribe</button>
        </form>
    </div>
</section>
=======
$pageTitle = 'Fresh from our family farm';
$featured = [];
try { $featured = db()->query('SELECT * FROM products WHERE featured = 1 ORDER BY id DESC LIMIT 6')->fetchAll(); } catch (Throwable $e) { $featured = []; }
include 'includes/header.php';
?>
<section class="hero"><div class="container hero-content"><div class="eyebrow">Raised with care · delivered with pride</div><h1>Good mornings start with better eggs.</h1><p>Fresh farm eggs, healthy chicks and the trusted essentials to grow your own flock. From our family farm in Kiambu to your kitchen.</p><div class="hero-actions"><a class="btn btn-primary" href="shop.php">Shop the farm →</a><a class="btn btn-light" href="about.php">Meet our flock</a></div></div></section>
<section class="section"><div class="container"><div class="section-heading"><div><div class="eyebrow">What are you looking for?</div><h2>Pick your kind of fresh.</h2></div><a class="text-link" href="shop.php">View all products →</a></div><div class="categories"><a class="category-card" href="shop.php?category=Fresh+Eggs"><h3>Fresh eggs</h3><p>Collected at sunrise, delivered with care.</p></a><a class="category-card" href="shop.php?category=Day-old+Chicks"><h3>Healthy chicks</h3><p>A confident start for your next flock.</p></a><a class="category-card" href="shop.php?category=Feed"><h3>Farm essentials</h3><p>Everything your birds need to thrive.</p></a></div></div></section>
<section class="feature-band"><div class="container feature-grid"><div class="feature"><div class="feature-icon">◉</div><h3>Farm fresh</h3><p>Collected and packed daily.</p></div><div class="feature"><div class="feature-icon">✓</div><h3>Healthy by nature</h3><p>Responsible, careful raising.</p></div><div class="feature"><div class="feature-icon">⌁</div><h3>Delivered simply</h3><p>Reliable local delivery.</p></div><div class="feature"><div class="feature-icon">♡</div><h3>Here to help</h3><p>Real advice from our farm.</p></div></div></section>
<section class="section favourites-section"><div class="container"><div class="section-heading"><div><div class="eyebrow">The farm stand</div><h2>Favourites this week.</h2></div><p>Small-batch picks, packed with care and ready for your kitchen or flock.</p></div><div class="product-grid home-product-grid"><?php foreach ($featured as $product): ?><article class="product-card home-product-card"><a href="product.php?id=<?= (int)$product['id'] ?>"><div class="product-image <?= str_contains(strtolower($product['name']), 'chick') ? 'chicks' : (str_contains(strtolower($product['name']), 'feed') || str_contains(strtolower($product['name']), 'mash') ? 'feed' : 'eggs') ?>"><img src="<?= e($product['image'] ?: 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=900&q=85') ?>" alt="<?= e($product['name']) ?>" width="900" height="620" loading="lazy"><span class="badge">Farm favourite</span></div></a><div class="product-info"><div class="rating">★★★★★ <span>(<?= e((string)$product['rating']) ?>)</span></div><h3><?= e($product['name']) ?></h3><p><?= e($product['description']) ?></p><div class="product-meta"><span class="price"><?= money($product['price']) ?></span><button class="btn btn-outline" data-add-cart="<?= (int)$product['id'] ?>" data-csrf="<?= e(csrf_token()) ?>">Add to cart</button></div></div></article><?php endforeach; ?></div></div></section>
<section class="section founder-section"><div class="container story"><div class="founder-portrait"><img src="https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=900&q=85" alt="Miriam, founder of Golden Eggs & Chicks Farm" width="900" height="1100" loading="lazy"></div><div><div class="eyebrow">A little more than a farm</div><h2>Raised slowly. Shared generously.</h2><p>Golden Eggs began with 40 hens, a borrowed incubator and a simple belief: when birds are cared for well, good food follows. Today, our founder Miriam works with families and small growers across Kiambu to make that care easier to bring home.</p><p>Her promise is simple: honest products, thoughtful guidance and a farm that feels personal from the first order to the next season.</p><a class="btn btn-primary" href="about.php">Our farm story →</a><div class="quote"><p>“You can taste the difference in the yolk, but you feel it in the way they show up for you.”</p><strong>— Miriam, founder</strong></div></div></div></section>
<section class="stats"><div class="container section stat-grid"><div class="stat"><strong>12+</strong><span>Years farming</span></div><div class="stat"><strong>4,800</strong><span>Happy customers</span></div><div class="stat"><strong>7 days</strong><span>Freshness promise</span></div><div class="stat"><strong>100%</strong><span>Traceable sourcing</span></div></div></section>
<section class="section table-section"><div class="container"><div class="section-heading"><div><div class="eyebrow">Kind words</div><h2>From our table to yours.</h2></div><p>Fresh food feels even better when it is shared.</p></div><div class="lifestyle-grid"><figure><img src="https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=900&q=85" alt="Friends sharing a meal at a farm table" width="900" height="620" loading="lazy"><figcaption>Good food brings people closer.</figcaption></figure><figure><img src="https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?auto=format&fit=crop&w=900&q=85" alt="Family enjoying a relaxed breakfast" width="900" height="620" loading="lazy"><figcaption>Simple mornings, thoughtfully made.</figcaption></figure><figure><img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=900&q=85" alt="Coffee and fresh bakery-style food on a table" width="900" height="620" loading="lazy"><figcaption>From our farm to your table.</figcaption></figure></div><div class="testimonial-grid compact-testimonials"><div class="testimonial"><div class="rating">★★★★★</div><p>“The eggs arrive beautifully packed and the yolks are genuinely golden.”</p><strong>— Amina, Lavington</strong></div><div class="testimonial"><div class="rating">★★★★★</div><p>“The chicks were lively and the team gave us a clear starter guide.”</p><strong>— Peter, Limuru</strong></div><div class="testimonial"><div class="rating">★★★★★</div><p>“Fast delivery, kind people, and thoughtful farm advice.”</p><strong>— Wanjiku, Karen</strong></div></div></div></section>
<section class="section"><div class="container"><div class="section-heading"><div><div class="eyebrow">From the farm journal</div><h2>Notes worth sharing.</h2></div><a class="text-link" href="about.php">Read our story →</a></div><div class="blog-grid"><article class="blog-card"><img src="https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=900&q=85" alt="Fresh eggs in a farm basket" width="900" height="620" loading="lazy"><div class="blog-content"><span class="blog-kicker">Farm basics</span><h3>How to spot a truly fresh egg</h3><p>Simple signs of freshness from shell to yolk, straight from our packing table.</p><a class="text-link" href="about.php">Read note →</a></div></article><article class="blog-card"><img src="https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?auto=format&fit=crop&w=900&q=85" alt="Healthy young poultry on the farm" width="900" height="620" loading="lazy"><div class="blog-content"><span class="blog-kicker">Flock care</span><h3>A kinder start for day-old chicks</h3><p>Our first-week checklist for warmth, clean water and confident growth.</p><a class="text-link" href="about.php">Read note →</a></div></article><article class="blog-card"><img src="https://images.unsplash.com/photo-1499529112087-3cb3b73cec95?auto=format&fit=crop&w=900&q=85" alt="Natural poultry feed and grain" width="900" height="620" loading="lazy"><div class="blog-content"><span class="blog-kicker">Good feed</span><h3>What belongs in a healthy flock diet?</h3><p>Choosing balanced feed for layers, broilers and the seasons in between.</p><a class="text-link" href="shop.php?category=Feed">Shop feed →</a></div></article></div></div></section><section class="section recent-section"><div class="container"><div class="section-heading"><div><div class="eyebrow">Back for another look?</div><h2>Recently viewed.</h2></div></div><div class="recent-strip" data-recent-products><div class="empty-state"><p>Products you view will appear here.</p><a class="btn btn-outline" href="shop.php">Browse the farm</a></div></div></div></section><section class="newsletter"><div class="container newsletter-inner"><div><div class="eyebrow">A note from the farm</div><h2>Fresh news, no clutter.</h2><p>Seasonal offers, new arrivals and useful poultry tips.</p></div><form class="newsletter-form" action="newsletter.php" method="post"><input type="email" name="email" placeholder="Your email address" required><button class="btn btn-primary">Subscribe</button></form></div></section>
>>>>>>> origin/main
<?php include 'includes/footer.php'; ?>
