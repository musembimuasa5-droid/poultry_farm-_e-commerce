<?php
require_once 'config.php';
require_once __DIR__ . '/includes/delivery.php';
require_once __DIR__ . '/includes/batches.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'message' => 'Use POST to check delivery.']);
    exit;
}

$csrf = $_POST['csrf'] ?? null;
if (!is_string($csrf) || !verify_csrf($csrf)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Your request expired. Refresh the page and try again.']);
    exit;
}

$areaInput = $_POST['area'] ?? null;
if (!is_string($areaInput) || !delivery_area_is_supported($areaInput)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Choose a supported Nairobi or Kiambu delivery area.']);
    exit;
}

try {
    $pdo = db();
    $subtotal = cart_subtotal($pdo);
    if (isset($_POST['product_id'])) {
        $productIdInput = $_POST['product_id'];
        $quantityInput = $_POST['quantity'] ?? 1;
        $productId = is_scalar($productIdInput) ? filter_var($productIdInput, FILTER_VALIDATE_INT) : false;
        $quantity = is_scalar($quantityInput) ? filter_var($quantityInput, FILTER_VALIDATE_INT) : false;
        if (!$productId || !$quantity || $quantity < 1) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Choose a valid product quantity.']);
            exit;
        }
        $statement = $pdo->prepare('SELECT price FROM products WHERE id = ?');
        $statement->execute([$productId]);
        $product = $statement->fetch();
        $cartQuantity = (int) ($_SESSION['cart'][$productId] ?? 0);
        if (!$product || $cartQuantity + $quantity > product_available_stock($pdo, (int) $productId)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'That quantity is not currently available.']);
            exit;
        }
        $subtotal += (float) $product['price'] * $quantity;
    }

    $quote = delivery_quote($areaInput, $subtotal);
    $_SESSION['delivery_area'] = $areaInput;
    echo json_encode([
        'success' => true,
        'quote' => $quote,
        'subtotal' => $subtotal,
        'message' => $quote['free']
            ? 'Your delivery is free for this order.'
            : 'Estimated local delivery: ' . money($quote['fee']) . '.',
    ]);
} catch (Throwable $exception) {
    error_log('Delivery quote could not be calculated: ' . $exception->getMessage());
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Delivery estimates are temporarily unavailable.']);
}
