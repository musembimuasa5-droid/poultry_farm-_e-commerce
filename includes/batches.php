<?php

declare(strict_types=1);

function product_available_stock(PDO $pdo, int $productId): int
{
    $statement = $pdo->prepare('SELECT stock, batch_tracking FROM products WHERE id = ?');
    $statement->execute([$productId]);
    $product = $statement->fetch();
    if (!$product) {
        return 0;
    }
    if (!(int) $product['batch_tracking']) {
        return max(0, (int) $product['stock']);
    }

    $statement = $pdo->prepare(
        "SELECT COALESCE(SUM(available_units), 0)
         FROM product_batches
         WHERE product_id = ? AND status = 'open'"
    );
    $statement->execute([$productId]);
    return (int) $statement->fetchColumn();
}

function next_product_batch(PDO $pdo, int $productId): ?array
{
    $statement = $pdo->prepare(
        "SELECT batch_code, hatch_date, available_units
         FROM product_batches
         WHERE product_id = ? AND status = 'open' AND available_units > 0
         ORDER BY hatch_date ASC, id ASC
         LIMIT 1"
    );
    $statement->execute([$productId]);
    $batch = $statement->fetch();
    return $batch ?: null;
}

function reserve_product_batches(PDO $pdo, int $productId, int $quantity): void
{
    $statement = $pdo->prepare(
        "SELECT id, available_units
         FROM product_batches
         WHERE product_id = ? AND status = 'open' AND available_units > 0
         ORDER BY hatch_date ASC, id ASC
         FOR UPDATE"
    );
    $statement->execute([$productId]);
    $batches = $statement->fetchAll();
    $remaining = $quantity;
    $update = $pdo->prepare(
        'UPDATE product_batches SET available_units = available_units - ? WHERE id = ? AND available_units >= ?'
    );
    foreach ($batches as $batch) {
        if ($remaining <= 0) {
            break;
        }
        $take = min($remaining, (int) $batch['available_units']);
        $update->execute([$take, $batch['id'], $take]);
        if ($update->rowCount() !== 1) {
            throw new DomainException('Batch availability changed. Please review your cart and try again.');
        }
        $remaining -= $take;
    }
    if ($remaining > 0) {
        throw new DomainException('The selected batch quantity is no longer available.');
    }
}
