<?php
require_once 'config.php';
if (!current_user()) { flash('error', 'Please sign in to leave a verified review.'); redirect('login.php'); }
$productId = (int) ($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$message = '';
$stmt = db()->prepare('SELECT p.* FROM products p WHERE p.id=?'); $stmt->execute([$productId]); $product = $stmt->fetch();
if (!$product) redirect('shop.php');
$purchase = db()->prepare("SELECT oi.id FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.product_id=? AND o.user_id=? AND o.status NOT IN ('cancelled','pending') LIMIT 1");
$purchase->execute([$productId, current_user()['id']]);
if (!$purchase->fetch()) $message = 'Only customers with a completed or processing order can review this product.';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message === '' && verify_csrf($_POST['csrf'] ?? null)) {
    $stmt = db()->prepare('INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?,?,?,?)');
    $stmt->execute([current_user()['id'], $productId, max(1, min(5, (int) $_POST['rating'])), trim($_POST['comment'])]);
    flash('success', 'Thank you. Your verified review has been added.'); redirect('product.php?id=' . $productId);
}
$pageTitle = 'Review ' . $product['name']; include 'includes/header.php';
?>
<section class="section"><div class="container" style="max-width:650px"><div class="form-card"><div class="eyebrow">Verified customer review</div><h1>Share your experience.</h1><p><?= e($product['name']) ?></p><?php if ($message): ?><div class="notice"><?= e($message) ?></div><a class="btn btn-outline" href="product.php?id=<?= $productId ?>">Back to product</a><?php else: ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="product_id" value="<?= $productId ?>"><div class="form-group"><label>Rating</label><select class="form-control" name="rating"><option value="5">★★★★★ Excellent</option><option value="4">★★★★ Very good</option><option value="3">★★★ Good</option><option value="2">★★ Fair</option><option value="1">★ Poor</option></select></div><div class="form-group"><label>Your review</label><textarea class="form-control" name="comment" rows="5" required></textarea></div><button class="btn btn-primary">Publish verified review →</button></form><?php endif; ?></div></div></section>
<?php include 'includes/footer.php'; ?>
