<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/batches.php';

if (!current_user() || current_user()['role'] !== 'admin') {
    flash('error', 'Admin access is required.');
    redirect('../login.php');
}

$pageError = '';
$pageSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? null;
    if (!is_string($csrf) || !verify_csrf($csrf)) {
        $pageError = 'Your request expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $batchId = filter_var($_POST['batch_id'] ?? null, FILTER_VALIDATE_INT);
        if ($action === 'close' && $batchId) {
            try {
                $statement = db()->prepare("UPDATE product_batches SET status = 'closed' WHERE id = ? AND status = 'open'");
                $statement->execute([$batchId]);
                $pageSuccess = $statement->rowCount() ? 'Batch closed.' : 'That batch is already closed or could not be found.';
            } catch (Throwable $exception) {
                error_log('Farm batch could not be closed: ' . $exception->getMessage());
                $pageError = 'The batch could not be closed. Please try again.';
            }
        } elseif ($action === 'create') {
            $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
            $batchCode = trim((string) ($_POST['batch_code'] ?? ''));
            $hatchDate = trim((string) ($_POST['hatch_date'] ?? ''));
            $units = filter_var($_POST['available_units'] ?? null, FILTER_VALIDATE_INT);
            $validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $hatchDate);
            if (!$productId || $productId < 1 || $batchCode === '' || strlen($batchCode) > 80
                || !$validDate || $validDate->format('Y-m-d') !== $hatchDate
                || $units === false || $units < 0 || $units > 1000000) {
                $pageError = 'Enter a valid product, batch code, hatch date, and quantity (0–1,000,000).';
            } else {
                try {
                    $pdo = db();
                    $pdo->beginTransaction();
                    $productStatement = $pdo->prepare('SELECT id FROM products WHERE id = ? FOR UPDATE');
                    $productStatement->execute([$productId]);
                    if (!$productStatement->fetchColumn()) {
                        throw new DomainException('That product could not be found.');
                    }
                    $pdo->prepare('UPDATE products SET batch_tracking = 1 WHERE id = ?')->execute([$productId]);
                    $insert = $pdo->prepare(
                        'INSERT INTO product_batches (product_id, batch_code, hatch_date, available_units, status)
                         VALUES (?, ?, ?, ?, \'open\')'
                    );
                    $insert->execute([$productId, $batchCode, $hatchDate, $units]);
                    $pdo->commit();
                    $pageSuccess = 'Batch saved and batch tracking enabled for this product.';
                } catch (DomainException $exception) {
                    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
                    $pageError = $exception->getMessage();
                } catch (Throwable $exception) {
                    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
                    error_log('Farm batch could not be created: ' . $exception->getMessage());
                    $pageError = 'The batch could not be saved. Check that its code is unique for this product.';
                }
            }
        } else {
            $pageError = 'That batch action is not supported.';
        }
    }
}

$products = [];
$batches = [];
try {
    $products = db()->query('SELECT id, name, batch_tracking FROM products ORDER BY name')->fetchAll();
    $batches = db()->query(
        'SELECT b.*, p.name AS product_name
         FROM product_batches b
         JOIN products p ON p.id = b.product_id
         ORDER BY b.hatch_date DESC, b.id DESC'
    )->fetchAll();
} catch (Throwable $exception) {
    error_log('Farm batches could not be loaded: ' . $exception->getMessage());
    $pageError = $pageError ?: 'Batch records could not be loaded. Check that the storefront migration has been applied.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Batch inventory | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="container admin-batch-page">
    <p><a class="text-link" href="index.php">← Admin dashboard</a></p>
    <div class="eyebrow">Inventory management</div>
    <h1>Hatch batches.</h1>
    <p>Open batches provide pre-order availability for batch-tracked products.</p>
    <?php if ($pageError !== ''): ?><div class="notice" role="alert"><?= e($pageError) ?></div><?php endif; ?>
    <?php if ($pageSuccess !== ''): ?><div class="notice" role="status"><?= e($pageSuccess) ?></div><?php endif; ?>

    <form class="form-card admin-batch-form" method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">
        <h2>Add a batch</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="batch-product">Product</label>
                <select class="form-control" id="batch-product" name="product_id" required>
                    <option value="">Choose a product</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) $product['id'] ?>"><?= e($product['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label for="batch-code">Batch code</label><input class="form-control" id="batch-code" name="batch_code" maxlength="80" required></div>
            <div class="form-group"><label for="hatch-date">Hatch/release date</label><input class="form-control" id="hatch-date" type="date" name="hatch_date" required></div>
            <div class="form-group"><label for="batch-units">Available units</label><input class="form-control" id="batch-units" type="number" name="available_units" min="0" max="1000000" required></div>
        </div>
        <button class="btn btn-primary" type="submit">Save batch</button>
    </form>

    <section class="section admin-batch-list">
        <div class="section-heading"><div><div class="eyebrow">Current inventory</div><h2>Product batches</h2></div></div>
        <?php if (!$batches): ?>
            <div class="empty-state"><h3>No batches entered</h3><p>Add a batch above to enable pre-orders for chicks or pullets.</p></div>
        <?php else: ?>
            <div class="account-table-wrap">
                <table class="cart-table">
                    <thead><tr><th>Product</th><th>Batch</th><th>Release date</th><th>Units</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($batches as $batch): ?>
                            <tr>
                                <td><?= e($batch['product_name']) ?></td>
                                <td><?= e($batch['batch_code']) ?></td>
                                <td><?= e(date('j M Y', strtotime($batch['hatch_date']))) ?></td>
                                <td><?= (int) $batch['available_units'] ?></td>
                                <td><?= e(ucfirst($batch['status'])) ?></td>
                                <td>
                                    <?php if ($batch['status'] === 'open'): ?>
                                        <form method="post">
                                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="close">
                                            <input type="hidden" name="batch_id" value="<?= (int) $batch['id'] ?>">
                                            <button class="btn btn-outline" type="submit">Close batch</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
