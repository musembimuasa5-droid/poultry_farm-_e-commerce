<?php
require_once 'config.php';
require_once __DIR__ . '/includes/batches.php';
require_once __DIR__ . '/includes/delivery.php';

if (!current_user()) {
    flash('error', 'Please sign in before checkout.');
    redirect('login.php');
}

$pageTitle = 'Checkout';
$items = [];
$subtotal = 0.0;
$error = '';
$address = '';
$paymentMethod = 'cod';
$deliveryArea = is_string($_SESSION['delivery_area'] ?? null) ? $_SESSION['delivery_area'] : '';
$cartIds = array_map('intval', array_keys($_SESSION['cart'] ?? []));

try {
    if ($cartIds) {
        $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
        $statement = db()->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $statement->execute($cartIds);
        foreach ($statement as $item) {
            $item['quantity'] = (int) ($_SESSION['cart'][$item['id']] ?? 0);
            $item['line_total'] = $item['quantity'] * (float) $item['price'];
            $item['available_stock'] = product_available_stock(db(), (int) $item['id']);
            $item['next_batch'] = (int) $item['batch_tracking']
                ? next_product_batch(db(), (int) $item['id'])
                : null;
            $subtotal += $item['line_total'];
            $items[] = $item;
        }
    }
} catch (Throwable $exception) {
    error_log('Checkout cart could not be loaded: ' . $exception->getMessage());
    $error = 'We could not load your cart. Please try again shortly.';
}

if (!$cartIds && $error === '') {
    flash('error', 'Your cart is empty.');
    redirect('shop.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? null;
    if (!is_string($csrf) || !verify_csrf($csrf)) {
        $error = 'Your checkout request expired. Please try again.';
    } else {
        $addressInput = $_POST['address'] ?? '';
        $address = is_string($addressInput) ? trim($addressInput) : '';
        $paymentInput = $_POST['payment_method'] ?? '';
        $paymentMethod = is_string($paymentInput) ? $paymentInput : '';
        $deliveryInput = $_POST['delivery_area'] ?? '';
        $deliveryArea = is_string($deliveryInput) ? $deliveryInput : '';
        if ($address === '' || strlen($address) > 5000) {
            $error = 'Enter a delivery address of 1 to 5,000 characters.';
        } elseif (!in_array($paymentMethod, ['cod', 'mpesa', 'bank'], true)) {
            $error = 'Choose a valid payment method.';
        } elseif (!delivery_area_is_supported($deliveryArea)) {
            $error = 'Choose a supported Nairobi or Kiambu delivery area.';
        } else {
            try {
                $pdo = db();
                $pdo->beginTransaction();
                $cartIds = array_map('intval', array_keys($_SESSION['cart'] ?? []));
                if (!$cartIds) {
                    throw new DomainException('Your cart is empty.');
                }
                $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
                $statement = $pdo->prepare(
                    "SELECT id, name, price, stock, batch_tracking FROM products WHERE id IN ($placeholders) FOR UPDATE"
                );
                $statement->execute($cartIds);
                $lockedProducts = [];
                foreach ($statement->fetchAll() as $product) {
                    $lockedProducts[(int) $product['id']] = $product;
                }

                $orderItems = [];
                $orderSubtotal = 0.0;
                foreach ($_SESSION['cart'] as $productId => $quantity) {
                    $productId = (int) $productId;
                    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
                    if (!$quantity || $quantity < 1 || !isset($lockedProducts[$productId])) {
                        throw new DomainException('A cart item is no longer available. Review your cart and try again.');
                    }
                    $product = $lockedProducts[$productId];
                    $available = product_available_stock($pdo, $productId);
                    if ($available < $quantity) {
                        throw new DomainException(
                            'Only ' . max(0, $available) . ' of ' . $product['name'] .
                            ' are currently available. Update your cart and try again.'
                        );
                    }
                    $product['available_stock'] = $available;
                    $product['quantity'] = $quantity;
                    $product['line_total'] = $quantity * (float) $product['price'];
                    $orderSubtotal += $product['line_total'];
                    $orderItems[] = $product;
                }

                $tax = $orderSubtotal * .16;
                $delivery = delivery_quote($deliveryArea, $orderSubtotal);
                if ($delivery === null) {
                    throw new DomainException('Choose a supported Nairobi or Kiambu delivery area.');
                }
                $shipping = $delivery['fee'];
                $grandTotal = $orderSubtotal + $tax + $shipping;
                $orderStatement = $pdo->prepare(
                    'INSERT INTO orders (user_id, total, tax, shipping, address, delivery_area, payment_method)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $orderStatement->execute([
                    current_user()['id'],
                    $grandTotal,
                    $tax,
                    $shipping,
                    $address,
                    $deliveryArea,
                    $paymentMethod,
                ]);
                $orderId = (int) $pdo->lastInsertId();
                $lineStatement = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)'
                );
                $stockStatement = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
                foreach ($orderItems as $item) {
                    if ((int) $item['batch_tracking']) {
                        reserve_product_batches($pdo, (int) $item['id'], (int) $item['quantity']);
                    } else {
                        $stockStatement->execute([$item['quantity'], $item['id'], $item['quantity']]);
                        if ($stockStatement->rowCount() !== 1) {
                            throw new DomainException(
                                'Stock for ' . $item['name'] . ' changed while placing your order. Please review your cart.'
                            );
                        }
                    }
                    $lineStatement->execute([
                        $orderId,
                        $item['id'],
                        $item['quantity'],
                        $item['price'],
                    ]);
                }
                $clearCart = $pdo->prepare('DELETE FROM cart WHERE user_id = ?');
                $clearCart->execute([current_user()['id']]);
                $pdo->commit();
                log_admin_activity('Customer order placed', 'order', $orderId, 'Order created by user ' . current_user()['id']);
                $_SESSION['cart'] = [];
                $_SESSION['delivery_area'] = $deliveryArea;
                flash('success', 'Order #' . $orderId . ' received. We will be in touch shortly.');
                redirect('account.php');
            } catch (DomainException $exception) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $exception->getMessage();
            } catch (Throwable $exception) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Checkout order could not be placed: ' . $exception->getMessage());
                $error = 'We could not place that order. Please check your details and try again.';
            }
        }
    }
}

$tax = $subtotal * .16;
$delivery = delivery_area_is_supported($deliveryArea) ? delivery_quote($deliveryArea, $subtotal) : null;
$shipping = $delivery['fee'] ?? 0;
$grand = $subtotal + $tax + $shipping;
include 'includes/header.php';
?>
<section class="section">
    <div class="container cart-layout">
        <div class="form-card">
            <div class="eyebrow">Almost there</div>
            <h1>Checkout.</h1>
            <?php if ($error !== ''): ?><div class="notice" role="alert"><?= e($error) ?></div><?php endif; ?>
            <?php if ($items): ?>
                <form method="post" action="checkout.php">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <div class="form-group">
                        <label for="delivery-area">Delivery neighborhood</label>
                        <select class="form-control" id="delivery-area" name="delivery_area" data-checkout-delivery-area data-csrf="<?= e(csrf_token()) ?>" required>
                            <option value="">Select a Nairobi or Kiambu area</option>
                            <?php foreach (delivery_areas() as $region => $areas): ?>
                                <optgroup label="<?= e($region) ?>">
                                    <?php foreach ($areas as $area): ?>
                                        <option value="<?= e($area) ?>" <?= $deliveryArea === $area ? 'selected' : '' ?>><?= e($area) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <small class="checkout-delivery-feedback" data-checkout-delivery-feedback aria-live="polite"></small>
                    </div>
                    <div class="form-group">
                        <label for="delivery-address">Delivery address</label>
                        <textarea class="form-control" id="delivery-address" name="address" rows="4" maxlength="5000" required placeholder="Street, neighbourhood, town"><?= e($address) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="payment-method">Payment method</label>
                        <select class="form-control" id="payment-method" name="payment_method">
                            <option value="cod" <?= $paymentMethod === 'cod' ? 'selected' : '' ?>>Cash on delivery</option>
                            <option value="mpesa" <?= $paymentMethod === 'mpesa' ? 'selected' : '' ?>>M-Pesa</option>
                            <option value="bank" <?= $paymentMethod === 'bank' ? 'selected' : '' ?>>Bank transfer</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Place order · KSh <?= number_format($grand, 2) ?></button>
                </form>
            <?php else: ?>
                <p>Your cart has no available products. Review it before checkout.</p>
                <a class="btn btn-outline" href="cart.php">Review cart</a>
            <?php endif; ?>
        </div>
        <?php if ($items): ?>
            <aside class="summary">
                <h2>Your order</h2>
                <?php foreach ($items as $item): ?>
                    <div class="summary-line">
                        <span><?= e($item['name']) ?> × <?= (int) $item['quantity'] ?></span>
                        <span><?= money($item['line_total']) ?></span>
                    </div>
                    <?php if ((int) $item['stock'] < (int) $item['quantity']): ?>
                        <p class="stock-message low-stock">Only <?= max(0, (int) $item['stock']) ?> available. Update your cart before checkout.</p>
                    <?php endif; ?>
                <?php endforeach; ?>
                <div class="summary-line"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
                <div class="summary-line"><span>Tax (16%)</span><span><?= money($tax) ?></span></div>
                <div class="summary-line"><span>Delivery</span><span data-checkout-delivery-fee><?= $delivery ? ($shipping ? money($shipping) : 'Free') : 'Select your area' ?></span></div>
                <div class="summary-line summary-total"><span>Total</span><span data-checkout-grand-total><?= money($grand) ?></span></div>
            </aside>
        <?php endif; ?>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
