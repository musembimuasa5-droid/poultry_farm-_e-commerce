<?php

function product_quality_badges(array $product): array
{
    $badges = [];
    if (!empty($product['vaccinated'])) $badges[] = 'Vaccinated';
    if (!empty($product['farm_fresh_daily'])) $badges[] = 'Farm Fresh Daily';
    if (!empty($product['quality_assured'])) $badges[] = 'Quality Assured';
    return $badges;
}
?>
<?php $qualityBadges = product_quality_badges($product); ?>
<?php if ($qualityBadges): ?>
<div class="quality-badges" aria-label="Product quality information">
<?php foreach ($qualityBadges as $badge): ?>
    <span class="quality-badge quality-badge-<?= e(str_replace(' ', '-', strtolower($badge))) ?>">
        <span aria-hidden="true">✓</span><?= e($badge) ?>
    </span>
<?php endforeach; ?>
</div>
<?php endif; ?>
