<?php

declare(strict_types=1);

function delivery_areas(): array
{
    return [
        'Nairobi' => [
            'Westlands', 'Kasarani', 'Karen', 'Kilimani', 'Lavington',
            'Eastleigh', 'Roysambu', 'Langata', 'CBD', 'Embakasi',
        ],
        'Kiambu & nearby' => [
            'Kiambu Town', 'Limuru', 'Ruiru', 'Thika', 'Kikuyu',
            'Juja', 'Githunguri',
        ],
    ];
}

function delivery_area_is_supported(string $area): bool
{
    foreach (delivery_areas() as $areas) {
        if (in_array($area, $areas, true)) {
            return true;
        }
    }
    return false;
}

function delivery_quote(string $area, float $subtotal): ?array
{
    if (!delivery_area_is_supported($area)) {
        return null;
    }

    $free = $subtotal >= FREE_DELIVERY_THRESHOLD;
    return [
        'area' => $area,
        'fee' => $free ? 0 : DELIVERY_STANDARD_FEE,
        'free' => $free,
        'threshold' => FREE_DELIVERY_THRESHOLD,
        'remaining' => max(0, FREE_DELIVERY_THRESHOLD - $subtotal),
    ];
}

function cart_subtotal(PDO $pdo): float
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return 0.0;
    }
    $validItems = [];
    foreach ($cart as $productId => $quantity) {
        $productId = filter_var($productId, FILTER_VALIDATE_INT);
        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
        if ($productId && $quantity && $quantity > 0) {
            $validItems[$productId] = $quantity;
        }
    }
    if (!$validItems) {
        return 0.0;
    }

    $placeholders = implode(',', array_fill(0, count($validItems), '?'));
    $statement = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
    $statement->execute(array_keys($validItems));
    $subtotal = 0.0;
    foreach ($statement->fetchAll() as $product) {
        $subtotal += (float) $product['price'] * $validItems[(int) $product['id']];
    }
    return $subtotal;
}
