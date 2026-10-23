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
<div class="announcement-bar" aria-live="polite" aria-atomic="true">
    <div class="container announcement-inner">
        <span class="announcement-message is-visible">Free delivery on orders over KSh 5,000</span>
        <span class="announcement-message">Farm fresh, every morning</span>
        <span class="announcement-message">Fresh eggs delivered to your door</span>
    </div>
</div>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php" aria-label="Golden Eggs & Chicks Farm home page">
            <img class="brand-logo" src="golden-egg.jpg" alt="Golden Eggs & Chicks Farm logo" width="160" height="64" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex'">
            <span class="brand-fallback" aria-hidden="true">
                <span class="brand-mark">✦</span>
                <span class="brand-copy">
                    <span class="brand-name">Golden Eggs</span>
                    <small>&amp; Chicks Farm</small>
                </span>
            </span>
        </a>

        <nav class="main-nav" aria-label="Main navigation">
            <div class="nav-item">
                <a class="<?= $active === 'index.php' ? 'active' : '' ?>" href="index.php">Home</a>
            </div>
            <div class="nav-item nav-shop">
                <a class="<?= $active === 'shop.php' ? 'active' : '' ?>" href="shop.php" aria-expanded="false">Shop</a>
                <div class="nav-dropdown" role="menu" aria-label="Shop categories">
                    <a href="shop.php?category=Fresh+Eggs">Fresh Eggs</a>
                    <a href="shop.php?category=Day-old+Chicks">Day-old Chicks</a>
                    <a href="shop.php?category=Poultry+Feed">Poultry Feed</a>
                    <a href="shop.php?category=Farm+Equipment">Farm Equipment</a>
                    <a href="shop.php?featured=1">Featured Products</a>
                </div>
            </div>
            <div class="nav-item">
                <a class="<?= $active === 'about.php' ? 'active' : '' ?>" href="about.php">About</a>
            </div>
            <div class="nav-item">
                <a class="<?= $active === 'contact.php' ? 'active' : '' ?>" href="contact.php">Contact</a>
            </div>
        </nav>

        <div class="nav-actions">
            <div class="nav-search">
                <div class="search-shell">
                    <span class="search-icon" aria-hidden="true">⌕</span>
                    <input type="search" data-live-search placeholder="Search eggs, chicks, feed..." aria-label="Search products">
                    <button type="button" class="search-submit" aria-label="Search products">Search</button>
                </div>
                <div class="search-results" data-search-results></div>
            </div>

            <?php if ($user): ?>
                <div class="account-menu">
                    <button class="account-trigger" type="button" aria-label="Account menu">
                        <?= e(explode(' ', $user['full_name'])[0]) ?>
                    </button>
                    <div class="account-dropdown" role="menu">
                        <a href="account.php">My Account</a>
                        <a href="account.php">My Orders</a>
                        <a href="account.php">Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-actions">
                    <a class="btn btn-outline header-login" href="login.php" aria-label="Login">Login</a>
                    <a class="btn btn-primary header-register" href="register.php" aria-label="Register">Register</a>
                </div>
            <?php endif; ?>

            <a class="icon-link cart-link" href="cart.php" aria-label="Shopping cart">
                <span aria-hidden="true">🛒</span>
                <span class="cart-count"><?= cart_count() ?></span>
            </a>
        </div>

        <button class="menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <div class="mobile-menu" aria-label="Mobile navigation">
        <div class="container mobile-menu-inner">
            <a href="index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <?php if ($user): ?>
                <a href="account.php">My Account</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Account</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main>
<?php if (!empty($_SESSION['flash'])): $notice = $_SESSION['flash']; unset($_SESSION['flash']); ?>
<div class="toast toast-<?= e($notice['type']) ?>"><?= e($notice['message']) ?></div>
<?php endif; ?>
