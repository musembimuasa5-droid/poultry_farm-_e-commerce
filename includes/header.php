<?php
require_once __DIR__ . '/../config.php';
$pageTitle = $pageTitle ?? APP_NAME;
$active = basename($_SERVER['PHP_SELF']);
$user = current_user();
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <meta name="description" content="<?= e(APP_TAGLINE) ?> Shop fresh eggs, fertilized eggs, healthy chicks and trusted poultry essentials from our family farm.">
    <meta name="keywords" content="fresh eggs, chicks, poultry farm, fertilized eggs, Kenya">
    <meta property="og:title" content="<?= e($pageTitle) ?> | <?= e(APP_NAME) ?>">
    <meta property="og:description" content="<?= e(APP_TAGLINE) ?>">
    <meta property="og:type" content="website">
    <link rel="canonical" href="<?= e((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <script defer src="<?= e(BASE_URL) ?>assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
</head>
<body data-csrf="<?= e(csrf_token()) ?>" data-authenticated="<?= $user ? '1' : '0' ?>">
<div class="announcement">Free delivery on orders over KSh 5,000 <span>•</span> Farm fresh, every morning</div>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php"><img class="brand-logo" src="golden-egg.jpg" alt="Golden Eggs & Chicks Farm" width="160" height="64" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex'"><span class="brand-fallback"><span class="brand-mark">✦</span><span class="brand-copy"><span class="brand-name">Golden Eggs</span><small>& Chicks Farm</small></span></span></a>
        <button class="menu-toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
        <nav class="main-nav" aria-label="Main navigation">
            <a class="<?= $active === 'index.php' ? 'active' : '' ?>" href="index.php">Home</a>
            <a class="<?= $active === 'shop.php' ? 'active' : '' ?>" href="shop.php">Shop</a>
            <a class="<?= $active === 'about.php' ? 'active' : '' ?>" href="about.php">About</a>
            <a class="<?= $active === 'contact.php' ? 'active' : '' ?>" href="contact.php">Contact</a>
        </nav>
        <div class="nav-actions">
            <div class="nav-search"><input type="search" data-live-search placeholder="Search products" aria-label="Search products"><div class="search-results" data-search-results></div></div>
            <a class="icon-link cart-link" href="cart.php" aria-label="Shopping cart">♧<span><?= cart_count() ?></span></a>
            <?php if ($user): ?>
                <a class="account-link" href="account.php">Hi, <?= e(explode(' ', $user['full_name'])[0]) ?></a>
            <?php else: ?>
                <a class="account-link" href="login.php">Sign in</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main>
<?php if (!empty($_SESSION['flash'])): $notice = $_SESSION['flash']; unset($_SESSION['flash']); ?>
<div class="toast toast-<?= e($notice['type']) ?>"><?= e($notice['message']) ?></div>
<?php endif; ?>
