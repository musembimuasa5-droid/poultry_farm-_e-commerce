<?php
require_once 'config.php';
require_once __DIR__ . '/includes/batches.php';

if (!current_user()) {
    redirect('login.php');
}

$pageTitle = 'My account';
$orders = [];
$purchases = [];
$accountError = false;
try {
    $pdo = db();
    $statement = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $statement->execute([current_user()['id']]);
    $orders = $statement->fetchAll();

    $statement = $pdo->prepare(
        'SELECT p.id, p.name, p.unit, p.stock, p.batch_tracking, p.image, p.price,
                SUM(oi.quantity) AS quantity_purchased,
                MAX(o.created_at) AS last_purchased
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         JOIN products p ON p.id = oi.product_id
         WHERE o.user_id = ? AND o.status <> \'cancelled\'
         GROUP BY p.id, p.name, p.unit, p.stock, p.batch_tracking, p.image, p.price
         ORDER BY last_purchased DESC, p.name ASC'
    );
    $statement->execute([current_user()['id']]);
    $purchases = $statement->fetchAll();
    foreach ($purchases as &$purchase) {
        $purchase['available_stock'] = product_available_stock($pdo, (int) $purchase['id']);
    }
    unset($purchase);
} catch (Throwable $exception) {
    error_log('Account orders could not be loaded: ' . $exception->getMessage());
    $accountError = true;
}

include 'includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <div class="eyebrow">Green Garden Farm account</div>
        <h1>Welcome,<br><?= e(explode(' ', current_user()['full_name'])[0]) ?>.</h1>
        <p>Keep track of your orders and quickly restock your farm essentials.</p>
        <a class="btn btn-outline" href="logout.php">Sign out</a>
    </div>
</section>

<section class="section account-dashboard">
    <div class="container">
        <?php if ($accountError): ?>
            <div class="notice" role="alert">We could not load your account activity. Please refresh the page or try again shortly.</div>
        <?php else: ?>
            <div class="section-heading">
                <div><div class="eyebrow">Your farm essentials</div><h2>Quick reorder</h2><p>Products from your previous orders, ready to add to your cart.</p></div>
                <a class="btn btn-primary" href="shop.php">Shop all products</a>
            </div>
            <?php if ($purchases): ?>
                <div class="account-table-wrap">
                    <table class="cart-table quick-reorder-table">
                        <thead><tr><th>Product</th><th>Last purchased</th><th>Previously ordered</th><th>Availability</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($purchases as $purchase): ?>
                                <tr>
                                    <td>
                                        <a class="quick-reorder-product" href="product.php?id=<?= (int) $purchase['id'] ?>">
                                            <img src="<?= e($purchase['image'] ?: 'assets/images/product-placeholder.svg') ?>" alt="" width="54" height="48" loading="lazy">
                                            <span><strong><?= e($purchase['name']) ?></strong><small><?= e($purchase['unit']) ?></small></span>
                                        </a>
                                    </td>
                                    <td><?= e(date('d M Y', strtotime($purchase['last_purchased']))) ?></td>
                                    <td><?= (int) $purchase['quantity_purchased'] ?></td>
                                    <td><?= e(stock_message((int) $purchase['available_stock'])) ?></td>
                                    <td>
                                        <?php if ((int) $purchase['available_stock'] > 0): ?>
                                            <form method="post" action="cart.php">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="product_id" value="<?= (int) $purchase['id'] ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                <button class="btn btn-primary" type="submit">Reorder</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-outline" type="button" disabled>Unavailable</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="notice">You have no completed purchases to reorder yet. Your first farm delivery is just a few clicks away.</div>
            <?php endif; ?>

            <div class="section-heading account-orders-heading">
                <div><div class="eyebrow">Order history</div><h2>Your orders.</h2></div>
            </div>
            <?php if (!$orders): ?>
                <div class="notice">No orders yet. Your first farm delivery is just a few clicks away.</div>
            <?php else: ?>
                <div class="account-table-wrap">
                    <table class="cart-table">
                        <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= (int) $order['id'] ?></td>
                                    <td><?= e(date('d M Y', strtotime($order['created_at']))) ?></td>
                                    <td><?= money($order['total']) ?></td>
                                    <td><?= e(ucfirst($order['status'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
