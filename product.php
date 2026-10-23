<?php
require_once 'config.php';
require_once __DIR__ . '/includes/batches.php';
require_once __DIR__ . '/includes/delivery.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$product = null;
$related = [];
$reviews = [];
try {
    $statement = db()->prepare(
        'SELECT p.*, c.name AS category
         FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.id = ?'
    );
    $statement->execute([$id ?: 0]);
    $product = $statement->fetch();
    if (!$product) {
        flash('error', 'That product could not be found.');
        redirect('shop.php');
    }
    $product['stock'] = product_available_stock(db(), (int) $product['id']);
    $product['next_batch'] = (int) $product['batch_tracking']
        ? next_product_batch(db(), (int) $product['id'])
        : null;

    $statement = db()->prepare(
        'SELECT * FROM products
         WHERE category_id = ? AND id <> ?
         ORDER BY featured DESC, created_at DESC
         LIMIT 4'
    );
    $statement->execute([$product['category_id'], $id]);
    $related = $statement->fetchAll();

    $statement = db()->prepare(
        'SELECT r.*, u.full_name
         FROM reviews r
         JOIN users u ON u.id = r.user_id
         WHERE r.product_id = ?
         ORDER BY r.created_at DESC'
    );
    $statement->execute([$id]);
    $reviews = $statement->fetchAll();
} catch (Throwable $exception) {
    error_log('Product detail could not be loaded: ' . $exception->getMessage());
    flash('error', 'We could not load that product. Please try again shortly.');
    redirect('shop.php');
}

$pageTitle = $product['name'];
include 'includes/header.php';
$image = product_image_src($product['image']);
?>
<section class="section product-detail">
    <div class="container">
        <div class="product-detail-grid">
            <div>
                <div class="gallery-main" id="gallery-main">
                    <img id="main-product-image" src="<?= e($image) ?>" alt="<?= e($product['name']) ?>" width="900" height="700">
                </div>
                <div class="gallery-thumbs">
                    <button type="button" data-gallery-image="<?= e($image) ?>">
                        <img src="<?= e($image) ?>" alt="Product image" width="120" height="90" loading="lazy">
                    </button>
                    <button type="button" data-gallery-image="https://images.unsplash.com/photo-1569288063643-5d29ad64dfb5?auto=format&fit=crop&w=1000&q=85">
                        <img src="https://images.unsplash.com/photo-1569288063643-5d29ad64dfb5?auto=format&fit=crop&w=220&q=80" alt="Farm poultry" width="120" height="90" loading="lazy">
                    </button>
                    <button type="button" data-gallery-image="https://images.unsplash.com/photo-1516467508483-a7212febe31a?auto=format&fit=crop&w=1000&q=85">
                        <img src="https://images.unsplash.com/photo-1516467508483-a7212febe31a?auto=format&fit=crop&w=220&q=80" alt="Poultry on the farm" width="120" height="90" loading="lazy">
                    </button>
                </div>
            </div>
            <div class="product-detail-copy">
                <div class="eyebrow"><?= e($product['category']) ?></div>
                <h1><?= e($product['name']) ?></h1>
                <div class="rating" aria-label="<?= e((string) $product['rating']) ?> out of 5 stars">
                    ★★★★★ <span><?= e((string) $product['rating']) ?> · <?= count($reviews) ?> reviews</span>
                </div>
                <p class="detail-price"><?= money($product['price']) ?> <small>/ <?= e($product['unit']) ?></small></p>
                <p><?= e($product['description']) ?></p>
                <?php if ($product['next_batch']): ?>
                    <div class="batch-release-card">
                        <strong>Next batch release</strong>
                        <time datetime="<?= e($product['next_batch']['hatch_date']) ?>" data-batch-countdown="<?= e($product['next_batch']['hatch_date']) ?>">
                            <?= e(date('j F Y', strtotime($product['next_batch']['hatch_date']))) ?>
                        </time>
                        <span><?= (int) $product['next_batch']['available_units'] ?> available for pre-order</span>
                    </div>
                <?php else: ?>
                    <div class="stock-message <?= $product['stock'] <= LOW_STOCK_THRESHOLD ? 'low-stock' : '' ?>">
                        <?= e(stock_message((int) $product['stock'])) ?>
                    </div>
                <?php endif; ?>
                <?php include __DIR__ . '/includes/quality-badges.php'; ?>
                <?php if ((int) $product['stock'] > 0): ?>
                    <div class="product-detail-purchase">
                        <label class="home-quantity-label" for="product-quantity">Quantity
                            <input class="home-quantity" id="product-quantity" type="number" min="1" max="<?= (int) $product['stock'] ?>" value="1" inputmode="numeric">
                        </label>
                        <button class="btn btn-primary home-add-cart" type="button" data-add-cart="<?= (int) $product['id'] ?>" data-csrf="<?= e(csrf_token()) ?>">Add to cart</button>
                    </div>
                    <span class="home-cart-feedback" data-cart-feedback aria-live="polite"></span>
                    <?php $deliverySubtotal = cart_subtotal(db()); $deliveryProductId = (int) $product['id']; include __DIR__ . '/includes/delivery-checker.php'; ?>
                <?php else: ?>
                    <button class="btn btn-outline" type="button" disabled>Currently unavailable</button>
                <?php endif; ?>
                <div class="trust-row"><span>✓ Secure checkout</span><span>✓ Local delivery</span></div>
            </div>
        </div>

        <?php if ($related): ?>
            <section class="section related-section">
                <div class="section-heading">
                    <div><div class="eyebrow">Keep browsing</div><h2>You may also like.</h2></div>
                </div>
                <div class="product-grid">
                    <?php foreach ($related as $item): ?>
                        <article class="product-card">
                            <a href="product.php?id=<?= (int) $item['id'] ?>">
                                <div class="product-image">
                                    <img src="<?= e(product_image_src($item['image'])) ?>" alt="<?= e($item['name']) ?>" width="700" height="500" loading="lazy">
                                </div>
                            </a>
                            <div class="product-info">
                                <h3><a href="product.php?id=<?= (int) $item['id'] ?>"><?= e($item['name']) ?></a></h3>
                                <div class="product-meta"><span class="price"><?= money($item['price']) ?></span><a class="text-link" href="product.php?id=<?= (int) $item['id'] ?>">Details →</a></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="review-section">
            <div class="section-heading"><div><div class="eyebrow">Customer notes</div><h2>Reviews.</h2></div></div>
            <?php if (!$reviews): ?>
                <div class="empty-state"><div class="empty-icon">♡</div><h3>No reviews yet</h3><p>Be the first customer to share your experience.</p></div>
            <?php else: ?>
                <div class="testimonial-grid">
                    <?php foreach ($reviews as $review): ?>
                        <article class="testimonial">
                            <div class="rating" aria-label="<?= (int) $review['rating'] ?> out of 5 stars"><?= str_repeat('★', (int) $review['rating']) ?></div>
                            <p><?= e($review['comment']) ?></p>
                            <strong><?= e($review['full_name']) ?></strong>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
