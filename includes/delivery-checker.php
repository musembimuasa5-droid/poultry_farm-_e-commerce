<?php
$deliveryProductId = isset($deliveryProductId) ? (int) $deliveryProductId : 0;
$deliveryAreas = delivery_areas();
$selectedDeliveryArea = $_SESSION['delivery_area'] ?? '';
?>
<section class="delivery-checker" data-delivery-checker data-subtotal="<?= e((string) ($deliverySubtotal ?? 0)) ?>"<?= $deliveryProductId ? ' data-product-id="' . $deliveryProductId . '"' : '' ?>>
    <h3>Check delivery to your area</h3>
    <p>We deliver across Nairobi and nearby Kiambu communities.</p>
    <form class="delivery-checker-form">
        <label class="shop-sr-only" for="delivery-area-<?= $deliveryProductId ?: 'cart' ?>">Choose your neighborhood</label>
        <select id="delivery-area-<?= $deliveryProductId ?: 'cart' ?>" name="area" required>
            <option value="">Select a delivery area</option>
            <?php foreach ($deliveryAreas as $region => $areas): ?>
                <optgroup label="<?= e($region) ?>">
                    <?php foreach ($areas as $area): ?>
                        <option value="<?= e($area) ?>" <?= $selectedDeliveryArea === $area ? 'selected' : '' ?>><?= e($area) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline">Check delivery</button>
    </form>
    <p class="delivery-checker-result" data-delivery-result aria-live="polite"></p>
</section>
