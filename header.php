<?php
// includes/header.php
require_once __DIR__ . '/config.php';
$flash = getFlash();
$cartQty = cartCount();
$user = currentUser();
$categories = getDB()->query("SELECT * FROM categories WHERE status='active' ORDER BY sort_order")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? SITE_NAME ?></title>
<meta name="description" content="<?= $pageDesc ?? 'Modern online store — shop the best products' ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>

<!-- Announcement Bar -->
<div class="announcement-bar">
  <p>🚀 Free shipping on orders over <?= price(FREE_SHIPPING_THRESHOLD) ?> &nbsp;|&nbsp; Use code <strong>WELCOME10</strong> for 10% off</p>
</div>

<!-- Header -->
<header class="header" id="header">
  <div class="container">
    <div class="header-inner">

      <!-- Logo -->
      <a href="<?= SITE_URL ?>" class="logo">
        <span class="logo-icon">◈</span>
        <span class="logo-text"><?= SITE_NAME ?></span>
      </a>

      <!-- Search -->
      <form class="search-form" action="<?= SITE_URL ?>/pages/search.php" method="GET">
        <input type="text" name="q" placeholder="Search products, brands..." value="<?= sanitize($_GET['q'] ?? '') ?>">
        <button type="submit" aria-label="Search">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </button>
      </form>

      <!-- Header Actions -->
      <div class="header-actions">
        <?php if (isLoggedIn()): ?>
          <div class="dropdown">
            <button class="icon-btn">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <span class="btn-label"><?= explode(' ', $user['name'])[0] ?></span>
            </button>
            <div class="dropdown-menu">
              <a href="<?= SITE_URL ?>/pages/account.php">My Account</a>
              <a href="<?= SITE_URL ?>/pages/orders.php">My Orders</a>
              <a href="<?= SITE_URL ?>/pages/wishlist.php">Wishlist</a>
              <?php if (isAdmin()): ?>
                <a href="<?= SITE_URL ?>/admin/">Admin Panel</a>
              <?php endif; ?>
              <hr>
              <a href="<?= SITE_URL ?>/pages/logout.php">Logout</a>
            </div>
          </div>
        <?php else: ?>
          <a href="<?= SITE_URL ?>/pages/login.php" class="icon-btn">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span class="btn-label">Login</span>
          </a>
        <?php endif; ?>

        <a href="<?= SITE_URL ?>/pages/wishlist.php" class="icon-btn">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </a>

        <a href="<?= SITE_URL ?>/pages/cart.php" class="icon-btn cart-btn">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <?php if ($cartQty > 0): ?>
            <span class="cart-badge"><?= $cartQty ?></span>
          <?php endif; ?>
        </a>

        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="nav" id="mainNav">
      <a href="<?= SITE_URL ?>">Home</a>
      <?php foreach ($categories as $cat): ?>
        <a href="<?= SITE_URL ?>/pages/shop.php?category=<?= $cat['slug'] ?>"><?= sanitize($cat['name']) ?></a>
      <?php endforeach; ?>
      <a href="<?= SITE_URL ?>/pages/shop.php?filter=sale">🔥 Sale</a>
      <a href="<?= SITE_URL ?>/pages/contact.php">Contact</a>
    </nav>
  </div>
</header>

<!-- Flash Messages -->
<?php if ($flash): ?>
<div class="flash-message flash-<?= $flash['type'] ?>" id="flashMsg">
  <?= sanitize($flash['message']) ?>
  <button onclick="this.parentElement.remove()">×</button>
</div>
<?php endif; ?>
