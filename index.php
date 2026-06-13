<?php
// index.php — Homepage
$pageTitle = SITE_NAME . ' — Shop Premium Products';
$pageDesc = 'Discover thousands of premium products at unbeatable prices.';
require_once 'includes/config.php';
require_once 'includes/header.php';

$db = getDB();

// Featured Products
$featured = $db->query("SELECT * FROM products WHERE featured=1 AND status='active' LIMIT 8")->fetchAll();

// Sale Products
$onSale = $db->query("SELECT * FROM products WHERE sale_price IS NOT NULL AND status='active' LIMIT 4")->fetchAll();

// Categories with product count
$cats = $db->query("
  SELECT c.*, COUNT(p.id) as product_count
  FROM categories c
  LEFT JOIN products p ON p.category_id = c.id AND p.status='active'
  WHERE c.status='active'
  GROUP BY c.id
  ORDER BY c.sort_order
")->fetchAll();

$categoryIcons = ['electronics'=>'💻', 'clothing'=>'👕', 'home-living'=>'🏠', 'sports'=>'⚽', 'beauty'=>'💄', 'books'=>'📚'];
?>

<!-- ——— Hero ——— -->
<section class="hero">
  <div class="container">
    <div class="hero-content">
      <div class="hero-badge">
        ✨ New arrivals every week
      </div>
      <h1>Shop the <em>Future</em><br>of Style & Tech</h1>
      <p>Discover thousands of premium products — from cutting-edge electronics to timeless fashion, all at prices that make sense.</p>
      <div class="hero-actions">
        <a href="pages/shop.php" class="btn btn-primary">Shop Now →</a>
        <a href="pages/shop.php?filter=sale" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,0.4)">🔥 View Deals</a>
      </div>
      <div class="hero-stats">
        <div>
          <div class="hero-stat-number">50K+</div>
          <div class="hero-stat-label">Happy Customers</div>
        </div>
        <div>
          <div class="hero-stat-number">8K+</div>
          <div class="hero-stat-label">Products</div>
        </div>
        <div>
          <div class="hero-stat-number">4.9★</div>
          <div class="hero-stat-label">Average Rating</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ——— Features Strip ——— -->
<section class="features-strip section-sm">
  <div class="container">
    <div class="features-grid">
      <div class="feature-item">
        <div class="feature-icon">🚚</div>
        <div>
          <div class="feature-title">Free Shipping</div>
          <div class="feature-desc">On orders over <?= price(FREE_SHIPPING_THRESHOLD) ?></div>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon">↩️</div>
        <div>
          <div class="feature-title">Easy Returns</div>
          <div class="feature-desc">30-day hassle-free returns</div>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon">🔒</div>
        <div>
          <div class="feature-title">Secure Payment</div>
          <div class="feature-desc">256-bit SSL encryption</div>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon">💬</div>
        <div>
          <div class="feature-title">24/7 Support</div>
          <div class="feature-desc">We're always here to help</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ——— Categories ——— -->
<section class="section" style="background:white;">
  <div class="container">
    <div class="section-title">
      <span class="section-eyebrow">Browse by Category</span>
      <h2>Shop What You Love</h2>
      <p>Find exactly what you're looking for across our curated collections.</p>
    </div>
    <div class="categories-grid">
      <?php foreach ($cats as $cat): ?>
        <a href="pages/shop.php?category=<?= $cat['slug'] ?>" class="category-card">
          <div class="category-icon"><?= $categoryIcons[$cat['slug']] ?? '🛍️' ?></div>
          <div class="category-name"><?= sanitize($cat['name']) ?></div>
          <div class="category-count"><?= $cat['product_count'] ?> items</div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ——— Featured Products ——— -->
<section class="section">
  <div class="container">
    <div class="section-title">
      <span class="section-eyebrow">Hand-Picked For You</span>
      <h2>Featured Products</h2>
      <p>Our most loved items, selected for quality and value.</p>
    </div>
    <div class="products-grid">
      <?php foreach ($featured as $p): ?>
        <?php include 'includes/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:40px;">
      <a href="pages/shop.php" class="btn btn-outline">View All Products →</a>
    </div>
  </div>
</section>

<!-- ——— Promo Banner ——— -->
<section class="promo-banner">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr auto;gap:40px;align-items:center;">
      <div class="promo-content">
        <span class="section-eyebrow" style="color:var(--gold)">Limited Time Offer</span>
        <h2>Flash Sale — Up to 50% Off</h2>
        <p>Don't miss out on our biggest sale of the season. Premium products at incredible prices.</p>
        <div class="countdown" id="countdown">
          <div class="countdown-item"><div class="countdown-num" id="cd-h">08</div><div class="countdown-label">Hours</div></div>
          <div class="countdown-item"><div class="countdown-num" id="cd-m">34</div><div class="countdown-label">Mins</div></div>
          <div class="countdown-item"><div class="countdown-num" id="cd-s">22</div><div class="countdown-label">Secs</div></div>
        </div>
        <a href="pages/shop.php?filter=sale" class="btn btn-primary">Shop the Sale</a>
      </div>
      <div style="font-size:120px;opacity:0.15;user-select:none;">🛍️</div>
    </div>
  </div>
</section>

<!-- ——— On Sale ——— -->
<?php if (!empty($onSale)): ?>
<section class="section" style="background:white;">
  <div class="container">
    <div class="section-title">
      <span class="section-eyebrow">🔥 Hot Deals</span>
      <h2>On Sale Now</h2>
    </div>
    <div class="products-grid">
      <?php foreach ($onSale as $p): ?>
        <?php include 'includes/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
