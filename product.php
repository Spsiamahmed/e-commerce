<?php
// pages/product.php
require_once '../includes/config.php';
$db = getDB();

$slug = sanitize($_GET['slug'] ?? '');
if (!$slug) { redirect('pages/shop.php'); }

$stmt = $db->prepare("SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.slug=? AND p.status='active'");
$stmt->execute([$slug]);
$product = $stmt->fetch();
if (!$product) { redirect('pages/shop.php'); }

// Reviews
$reviews = $db->prepare("SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON u.id=r.user_id WHERE r.product_id=? AND r.status='approved' ORDER BY r.created_at DESC");
$reviews->execute([$product['id']]);
$reviews = $reviews->fetchAll();

// Related products
$related = $db->prepare("SELECT * FROM products WHERE category_id=? AND id!=? AND status='active' LIMIT 4");
$related->execute([$product['category_id'], $product['id']]);
$related = $related->fetchAll();

// Handle Add to Cart POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if (isLoggedIn()) {
        $check = $db->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND product_id=?");
        $check->execute([$_SESSION['user_id'], $product['id']]);
        $existing = $check->fetch();
        if ($existing) {
            $db->prepare("UPDATE cart SET quantity=? WHERE id=?")->execute([$existing['quantity'] + $qty, $existing['id']]);
        } else {
            $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)")->execute([$_SESSION['user_id'], $product['id'], $qty]);
        }
    } else {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $pid = $product['id'];
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$pid] = ['product_id' => $pid, 'qty' => $qty];
        }
    }
    flashMessage('success', '🛒 Added to cart!');
    redirect('pages/product.php?slug=' . urlencode($slug));
}

$currentPrice = $product['sale_price'] ?? $product['price'];
$hasDiscount  = !empty($product['sale_price']);
$discountPct  = $hasDiscount ? round((1 - $product['sale_price']/$product['price'])*100) : 0;
$pageTitle    = sanitize($product['name']) . ' — ' . SITE_NAME;
require_once '../includes/header.php';
?>

<div class="container">
  <nav class="breadcrumb">
    <a href="<?= SITE_URL ?>">Home</a><span>/</span>
    <a href="<?= SITE_URL ?>/pages/shop.php?category=<?= $product['cat_slug'] ?>"><?= sanitize($product['cat_name']) ?></a>
    <span>/</span><strong><?= sanitize($product['name']) ?></strong>
  </nav>
</div>

<section class="product-detail">
  <div class="container">
    <div class="product-detail-grid">

      <!-- Gallery -->
      <div class="product-gallery">
        <img src="<?= sanitize($product['image'] ?? '') ?>" alt="<?= sanitize($product['name']) ?>" id="mainImg" loading="lazy">
        <div class="product-gallery-thumbs">
          <img src="<?= sanitize($product['image'] ?? '') ?>" alt="thumb 1" class="active" onclick="setImg(this.src, this)">
        </div>
      </div>

      <!-- Details -->
      <div class="product-detail-info">
        <?php if ($product['brand']): ?>
          <div class="product-detail-brand"><?= sanitize($product['brand']) ?></div>
        <?php endif; ?>
        <h1 class="product-detail-name"><?= sanitize($product['name']) ?></h1>

        <div class="product-rating" style="margin-bottom:16px;">
          <?= starRating($product['rating']) ?>
          <span style="font-size:14px;color:var(--text-muted);"><?= $product['rating'] ?> (<?= $product['reviews_count'] ?> reviews)</span>
        </div>

        <div class="product-detail-price">
          <?= price($currentPrice) ?>
          <?php if ($hasDiscount): ?>
            <span class="price-original"><?= price($product['price']) ?></span>
            <span class="badge-sale" style="font-size:14px;padding:4px 10px;border-radius:6px;">-<?= $discountPct ?>% OFF</span>
          <?php endif; ?>
        </div>

        <?php if ($product['stock'] > 0): ?>
          <span class="stock-badge <?= $product['stock'] <= 5 ? 'stock-low' : 'stock-in' ?>">
            <?= $product['stock'] <= 5 ? "⚠️ Only {$product['stock']} left!" : "✓ In Stock" ?>
          </span>
        <?php else: ?>
          <span class="stock-badge stock-out">✗ Out of Stock</span>
        <?php endif; ?>

        <p class="product-detail-desc"><?= nl2br(sanitize($product['short_description'] ?? $product['description'])) ?></p>

        <?php if ($product['stock'] > 0): ?>
        <form method="POST">
          <div class="add-to-cart-row">
            <div class="qty-control" style="border:1.5px solid var(--border);border-radius:var(--radius);padding:6px 10px;">
              <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
              <input type="number" name="qty" id="qtyInput" class="qty-input" value="1" min="1" max="<?= $product['stock'] ?>">
              <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
            </div>
            <button type="submit" name="add_to_cart" class="btn btn-primary" style="flex:1;">
              🛒 Add to Cart
            </button>
          </div>
          <div style="display:flex;gap:10px;margin-top:10px;">
            <button type="button" class="btn btn-outline btn-sm add-to-wishlist" data-id="<?= $product['id'] ?>" style="flex:1;">
              ♡ Wishlist
            </button>
            <button type="button" class="btn btn-dark btn-sm" style="flex:1;" onclick="navigator.share ? navigator.share({title:'<?= sanitize($product['name']) ?>',url:window.location.href}) : copyUrl()">
              ↗ Share
            </button>
          </div>
        </form>
        <?php else: ?>
          <button class="btn btn-outline btn-block" disabled>Out of Stock</button>
        <?php endif; ?>

        <!-- Product Meta -->
        <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--border);font-size:13px;color:var(--text-muted);">
          <?php if ($product['sku']): ?><p>SKU: <strong><?= sanitize($product['sku']) ?></strong></p><?php endif; ?>
          <p>Category: <a href="<?= SITE_URL ?>/pages/shop.php?category=<?= $product['cat_slug'] ?>" style="color:var(--indigo);"><?= sanitize($product['cat_name']) ?></a></p>
        </div>
      </div>
    </div>

    <!-- Description & Reviews -->
    <div style="margin-top:64px;">
      <div style="border-bottom:2px solid var(--border);margin-bottom:32px;display:flex;gap:0;">
        <button class="tab-btn active" onclick="showTab('desc', this)" style="padding:14px 28px;font-weight:600;border-bottom:2px solid var(--indigo);margin-bottom:-2px;color:var(--indigo);">Description</button>
        <button class="tab-btn" onclick="showTab('reviews', this)" style="padding:14px 28px;font-weight:600;color:var(--text-muted);">Reviews (<?= count($reviews) ?>)</button>
      </div>

      <div id="tab-desc">
        <div style="max-width:720px;line-height:1.9;color:var(--text-muted);">
          <?= nl2br(sanitize($product['description'])) ?>
        </div>
      </div>

      <div id="tab-reviews" style="display:none;">
        <?php if (empty($reviews)): ?>
          <p style="color:var(--text-muted);">No reviews yet. Be the first to review this product!</p>
        <?php else: ?>
          <div style="display:grid;gap:20px;max-width:720px;">
            <?php foreach ($reviews as $r): ?>
              <div style="background:white;border-radius:var(--radius-lg);padding:20px;border:1px solid var(--border);">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                  <strong><?= sanitize($r['user_name']) ?></strong>
                  <span style="font-size:12px;color:var(--text-muted);"><?= formatDate($r['created_at']) ?></span>
                </div>
                <?= starRating($r['rating']) ?>
                <?php if ($r['title']): ?><p style="font-weight:600;margin:8px 0 4px;"><?= sanitize($r['title']) ?></p><?php endif; ?>
                <p style="color:var(--text-muted);font-size:14px;"><?= sanitize($r['comment']) ?></p>
                <?php if ($r['verified_purchase']): ?><span style="font-size:12px;color:var(--success);font-weight:600;">✓ Verified Purchase</span><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (isLoggedIn()): ?>
          <div style="margin-top:32px;background:white;border-radius:var(--radius-lg);padding:28px;border:1px solid var(--border);max-width:540px;">
            <h4 style="font-size:18px;margin-bottom:20px;">Write a Review</h4>
            <form method="POST" action="<?= SITE_URL ?>/pages/review.php">
              <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
              <div class="form-group">
                <label class="form-label">Rating</label>
                <select name="rating" class="form-control" required>
                  <option value="">Select rating</option>
                  <?php for ($i=5; $i>=1; $i--): ?><option value="<?= $i ?>"><?= str_repeat('★', $i) ?></option><?php endfor; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" placeholder="Summarize your review">
              </div>
              <div class="form-group">
                <label class="form-label">Review</label>
                <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience..."></textarea>
              </div>
              <button type="submit" class="btn btn-primary">Submit Review</button>
            </form>
          </div>
        <?php else: ?>
          <p style="margin-top:20px;"><a href="<?= SITE_URL ?>/pages/login.php" style="color:var(--indigo);font-weight:600;">Login</a> to write a review.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related)): ?>
    <div style="margin-top:64px;">
      <h2 style="font-family:'Playfair Display',serif;font-size:28px;color:var(--navy);margin-bottom:28px;">You Might Also Like</h2>
      <div class="products-grid">
        <?php foreach ($related as $p): ?>
          <?php include '../includes/product-card.php'; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<script>
function changeQty(delta) {
  const input = document.getElementById('qtyInput');
  const val = parseInt(input.value) + delta;
  input.value = Math.max(1, Math.min(parseInt(input.max), val));
}
function showTab(tab, btn) {
  document.getElementById('tab-desc').style.display = tab==='desc' ? '' : 'none';
  document.getElementById('tab-reviews').style.display = tab==='reviews' ? '' : 'none';
  document.querySelectorAll('.tab-btn').forEach(b => {
    b.style.borderBottom = 'none';
    b.style.color = 'var(--text-muted)';
  });
  btn.style.borderBottom = '2px solid var(--indigo)';
  btn.style.color = 'var(--indigo)';
}
function setImg(src, el) {
  document.getElementById('mainImg').src = src;
  document.querySelectorAll('.product-gallery-thumbs img').forEach(i => i.classList.remove('active'));
  el.classList.add('active');
}
function copyUrl() {
  navigator.clipboard.writeText(window.location.href);
  alert('Link copied!');
}
</script>

<?php require_once '../includes/footer.php'; ?>
