<<<<<<< HEAD
<?php
require_once 'config.php';
require_once __DIR__ . '/includes/batches.php';
require_once __DIR__ . '/includes/delivery.php';

$wantsJson = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
$respond = static function (bool $success, string $message = '', int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'count' => cart_count(),
    ]);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? null;
    if (!is_string($csrf) || !verify_csrf($csrf)) {
        if ($wantsJson) {
            $respond(false, 'Your cart request expired. Please try again.', 400);
        }
        flash('error', 'Your cart request expired. Please try again.');
        redirect('cart.php');
    }

    $_SESSION['cart'] ??= [];
    $action = $_POST['action'] ?? '';
    $persistUserCart = static function (int $productId, ?int $quantity): void {
        $user = current_user();
        if (!$user) {
            return;
        }
        if ($quantity === null) {
            $statement = db()->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
            $statement->execute([$user['id'], $productId]);
            return;
        }
        $statement = db()->prepare(
            'INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
        );
        $statement->execute([$user['id'], $productId, $quantity]);
    };

    if ($action === 'sync') {
        $incomingCart = $_POST['cart'] ?? '{}';
        $incoming = is_string($incomingCart) ? json_decode($incomingCart, true) : [];
        if (!is_array($incoming)) {
            $incoming = [];
        }

        foreach ($incoming as $productId => $quantity) {
            $productId = filter_var($productId, FILTER_VALIDATE_INT);
            $requested = filter_var($quantity, FILTER_VALIDATE_INT);
            if (!$productId || !$requested || $requested < 1) {
                continue;
            }
            $stock = product_available_stock(db(), (int) $productId);
            if ($stock < 1) {
                unset($_SESSION['cart'][$productId]);
                $persistUserCart($productId, null);
                continue;
            }
            $_SESSION['cart'][$productId] = min(
                $stock,
                max($_SESSION['cart'][$productId] ?? 0, $requested)
            );
            $persistUserCart($productId, $_SESSION['cart'][$productId]);
        }
    } elseif ($action === 'add' || $action === 'update') {
        $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$productId || $productId < 1) {
            if ($wantsJson) {
                $respond(false, 'Please choose a valid product.', 400);
            }
            flash('error', 'Please choose a valid product.');
            redirect('cart.php');
        }

        $stock = product_available_stock(db(), (int) $productId);
        $currentQuantity = (int) ($_SESSION['cart'][$productId] ?? 0);
        $requested = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT);
        if (!$requested || $requested < 1) {
            if ($wantsJson) {
                $respond(false, 'Enter a quantity of at least one.', 400);
            }
            flash('error', 'Enter a quantity of at least one.');
            redirect('cart.php');
        }
        $newQuantity = $action === 'add' ? $currentQuantity + $requested : $requested;

        if ($stock < 1 || $newQuantity > $stock) {
            $remaining = max(0, $stock - $currentQuantity);
            $message = $stock < 1
                ? 'This product is currently unavailable.'
                : 'Only ' . $remaining . ' more available for your cart.';
            if ($wantsJson) {
                $respond(false, $message, 409);
            }
            flash('error', $message);
            redirect('cart.php');
        }
        $_SESSION['cart'][$productId] = $newQuantity;
        $persistUserCart($productId, $newQuantity);
    } elseif ($action === 'remove') {
        $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        if ($productId) {
            unset($_SESSION['cart'][$productId]);
            $persistUserCart($productId, null);
        }
    } else {
        if ($wantsJson) {
            $respond(false, 'That cart action is not supported.', 400);
        }
        flash('error', 'That cart action is not supported.');
        redirect('cart.php');
    }

    if ($wantsJson) {
        $respond(true, 'Your cart has been updated.');
    }
    redirect('cart.php');
}

$items = [];
$subtotal = 0;
$cartError = false;
try {
    if (!empty($_SESSION['cart'])) {
        $ids = array_keys($_SESSION['cart']);
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare(
            "SELECT p.*, c.name AS category, p.batch_tracking
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE p.id IN ($marks)"
        );
        $stmt->execute($ids);
        foreach ($stmt as $item) {
            $item['quantity'] = $_SESSION['cart'][$item['id']];
            $item['line_total'] = $item['quantity'] * $item['price'];
            $item['available_stock'] = product_available_stock(db(), (int) $item['id']);
            $item['next_batch'] = (int) $item['batch_tracking']
                ? next_product_batch(db(), (int) $item['id'])
                : null;
            $subtotal += $item['line_total'];
            $items[] = $item;
        }
    }
} catch (Throwable $exception) {
    error_log('Cart contents could not be loaded: ' . $exception->getMessage());
    $cartError = true;
}
$tax = $subtotal * .16;
$deliveryArea = is_string($_SESSION['delivery_area'] ?? null) ? $_SESSION['delivery_area'] : '';
$deliveryQuote = $deliveryArea !== '' ? delivery_quote($deliveryArea, $subtotal) : null;
$shipping = $deliveryQuote['fee'] ?? 0;
$pageTitle = 'Your cart';
include 'includes/header.php';
?>
<section class="page-hero"><div class="container"><div class="eyebrow">Your farm basket</div><h1>Ready when<br>you are.</h1></div></section>
<section class="section">
    <div class="container cart-layout">
        <div>
            <?php if ($cartError): ?>
                <div class="notice" role="alert">We could not load your cart right now. Please refresh the page or try again shortly.</div>
            <?php elseif (!$items): ?>
                <div class="empty-state"><div class="empty-icon">♧</div><h3>Your cart is empty</h3><p>There is always room for something fresh.</p><a class="btn btn-primary" href="shop.php">Browse the farm →</a></div>
            <?php else: ?>
                <table class="cart-table">
                    <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= e($item['name']) ?></strong><br><small><?= e($item['unit']) ?></small>
                                    <?php if ($item['next_batch']): ?>
                                        <span class="batch-release-note">Next batch <?= e(date('j M Y', strtotime($item['next_batch']['hatch_date']))) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form class="cart-quantity-form" method="post" action="cart.php">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <label class="shop-sr-only" for="cart-quantity-<?= (int) $item['id'] ?>">Quantity for <?= e($item['name']) ?></label>
                                        <input id="cart-quantity-<?= (int) $item['id'] ?>" type="number" name="quantity" min="1" max="<?= max(1, (int) $item['available_stock']) ?>" value="<?= (int) $item['quantity'] ?>" required>
                                        <button class="btn btn-outline" type="submit">Update</button>
                                    </form>
                                </td>
                                <td><?= money($item['line_total']) ?></td>
                                <td><form method="post" action="cart.php"><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="btn btn-outline" type="submit">Remove</button></form></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php if ($items): ?>
            <aside class="summary">
                <h3>Order summary</h3>
                <?php $deliverySubtotal = $subtotal; include __DIR__ . '/includes/delivery-checker.php'; ?>
                <div class="summary-line"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
                <div class="summary-line"><span>Tax (16%)</span><span><?= money($tax) ?></span></div>
                <div class="summary-line"><span>Delivery</span><span data-cart-delivery-fee><?= $deliveryQuote ? ($shipping ? money($shipping) : 'Free') : 'Check your area' ?></span></div>
                <div class="summary-line summary-total"><span>Estimated total</span><span data-cart-grand-total><?= money($subtotal + $tax + $shipping) ?></span></div>
                <?php if (!$deliveryQuote): ?><p class="delivery-note">Select your neighborhood above to get the correct local delivery fee.</p><?php endif; ?>
                <div class="trust-row"><span>🔒 Secure payment</span><span>↺ Replacement promise</span><span>⌁ 1–2 day delivery</span></div>
                <a class="btn btn-primary" style="width:100%;margin-top:15px" href="checkout.php">Continue to checkout →</a>
            </aside>
        <?php endif; ?>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
=======
<?php require_once 'config.php'; if($_SERVER['REQUEST_METHOD']==='POST'){if(!verify_csrf($_POST['csrf']??null)){if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])){header('Content-Type: application/json');echo json_encode(['success'=>false]);exit;}flash('error','Your cart request expired. Please try again.');redirect('cart.php');}$_SESSION['cart']??=[];if($_POST['action']==='sync'){$incoming=json_decode($_POST['cart']??'{}',true)?:[];foreach($incoming as $productId=>$quantity){$productId=(int)$productId;$quantity=max(1,(int)$quantity);$_SESSION['cart'][$productId]=max($_SESSION['cart'][$productId]??0,$quantity);if(current_user()){try{$stmt=db()->prepare('INSERT INTO cart(user_id,product_id,quantity) VALUES(?,?,?) ON DUPLICATE KEY UPDATE quantity=GREATEST(quantity,VALUES(quantity))');$stmt->execute([current_user()['id'],$productId,$quantity]);}catch(Throwable $e){}}}}else{$id=(int)($_POST['product_id']??0);if($_POST['action']==='add')$_SESSION['cart'][$id]=($_SESSION['cart'][$id]??0)+1;if($_POST['action']==='remove')unset($_SESSION['cart'][$id]);if($_POST['action']==='update')$_SESSION['cart'][$id]=max(1,(int)$_POST['quantity']);}if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])||str_contains($_SERVER['HTTP_ACCEPT']??'','application/json')){header('Content-Type: application/json');echo json_encode(['success'=>true,'count'=>cart_count()]);exit;}redirect('cart.php');} $items=[];$subtotal=0;try{if(!empty($_SESSION['cart'])){$ids=array_keys($_SESSION['cart']);$marks=implode(',',array_fill(0,count($ids),'?'));$stmt=db()->prepare("SELECT * FROM products WHERE id IN ($marks)");$stmt->execute($ids);foreach($stmt as $item){$item['quantity']=$_SESSION['cart'][$item['id']];$item['line_total']=$item['quantity']*$item['price'];$subtotal+=$item['line_total'];$items[]=$item;}}}catch(Throwable $e){}$tax=$subtotal*.16;$shipping=$subtotal>=5000?0:250;$pageTitle='Your cart';include 'includes/header.php'; ?>
<section class="page-hero"><div class="container"><div class="eyebrow">Your farm basket</div><h1>Ready when<br>you are.</h1></div></section><section class="section"><div class="container cart-layout"><div><?php if(!$items): ?><div class="empty-state"><div class="empty-icon">♧</div><h3>Your cart is empty</h3><p>There is always room for something fresh.</p><a class="btn btn-primary" href="shop.php">Browse the farm →</a></div><?php else: ?><table class="cart-table"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th></th></tr></thead><tbody><?php foreach($items as $item): ?><tr><td><strong><?= e($item['name']) ?></strong><br><small><?= e($item['unit']) ?></small></td><td><?= (int)$item['quantity'] ?></td><td><?= money($item['line_total']) ?></td><td><form method="post" action="cart.php"><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="btn btn-outline">Remove</button></form></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div><?php if($items): ?><aside class="summary"><h3>Order summary</h3><div class="summary-line"><span>Subtotal</span><span><?= money($subtotal) ?></span></div><div class="summary-line"><span>Tax (16%)</span><span><?= money($tax) ?></span></div><div class="summary-line"><span>Delivery</span><span><?= $shipping?money($shipping):'Free' ?></span></div><div class="summary-line summary-total"><span>Total</span><span><?= money($subtotal+$tax+$shipping) ?></span></div><div class="trust-row"><span>🔒 Secure payment</span><span>↺ Replacement promise</span><span>⌁ 1–2 day delivery</span></div><a class="btn btn-primary" style="width:100%;margin-top:15px" href="checkout.php">Continue to checkout →</a></aside><?php endif; ?></div></section><?php include 'includes/footer.php'; ?>
>>>>>>> origin/main
