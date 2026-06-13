<?php
// includes/product-card.php — reusable product card
// $p must be in scope (product array from DB)
$currentPrice = $p['sale_price'] ?? $p['price'];
$hasDiscount  = !empty($p['sale_price']);
$discountPct  = $hasDiscount ? round((1 - $p['sale_price']/$p['price'])*100) : 0;
?>
<div class="product-card">
  <div class="product-image-wrap">
    <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($p['slug']) ?>">
      <img src="<?= sanitize($p['image'] ?? 'https://via.placeholder.com/400x400?text=No+Image') ?>"
           alt="<?= sanitize($p['name']) ?>"
           loading="lazy">
    </a>

    <div class="product-badges">
      <?php if ($hasDiscount): ?><span class="badge-sale">-<?= $discountPct ?>%</span><?php endif; ?>
      <?php if ($p['featured']): ?><span class="badge-popular">⭐ Featured</span><?php endif; ?>
      <?php if ($p['stock'] <= 5 && $p['stock'] > 0): ?><span class="badge-sale">Low Stock</span><?php endif; ?>
    </div>

    <div class="product-actions-hover">
      <button class="icon-btn add-to-wishlist" data-id="<?= $p['id'] ?>" title="Add to Wishlist">♡</button>
      <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($p['slug']) ?>" class="icon-btn" title="Quick View">👁</a>
      <button class="icon-btn add-to-cart" data-id="<?= $p['id'] ?>" title="Add to Cart" <?= $p['stock'] == 0 ? 'disabled' : '' ?>>🛒</button>
    </div>
  </div>

  <div class="product-info">
    <?php if ($p['brand']): ?>
      <div class="product-brand"><?= sanitize($p['brand']) ?></div>
    <?php endif; ?>

    <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($p['slug']) ?>">
      <div class="product-name"><?= sanitize($p['name']) ?></div>
    </a>

    <div class="product-rating">
      <?= starRating($p['rating']) ?>
      <span class="rating-count">(<?= $p['reviews_count'] ?>)</span>
    </div>

    <div class="product-price">
      <span class="price-current"><?= price($currentPrice) ?></span>
      <?php if ($hasDiscount): ?>
        <span class="price-original"><?= price($p['price']) ?></span>
        <span class="price-discount">Save <?= $discountPct ?>%</span>
      <?php endif; ?>
    </div>

    <button class="product-add-btn add-to-cart" data-id="<?= $p['id'] ?>" <?= $p['stock'] == 0 ? 'disabled' : '' ?>>
      <?= $p['stock'] == 0 ? 'Out of Stock' : '+ Add to Cart' ?>
    </button>
  </div>
</div>
