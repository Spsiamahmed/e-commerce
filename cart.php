<?php
// pages/cart.php
require_once '../includes/config.php';
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'remove') {
        $pid = (int)$_POST['product_id'];
        if (isLoggedIn()) {
            $db->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?")->execute([$_SESSION['user_id'], $pid]);
        } else {
            unset($_SESSION['cart'][$pid]);
        }
        flashMessage('info', 'Item removed from cart.');
    } elseif ($action === 'update') {
        $pid = (int)$_POST['product_id'];
        $qty = max(1, (int)$_POST['qty']);
        if (isLoggedIn()) {
            $db->prepare("UPDATE cart SET quantity=? WHERE user_id=? AND product_id=?")->execute([$qty, $_SESSION['user_id'], $pid]);
        } else {
            if (isset($_SESSION['cart'][$pid])) $_SESSION['cart'][$pid]['qty'] = $qty;
        }
    } elseif ($action === 'coupon') {
        $code = strtoupper(trim($_POST['coupon'] ?? ''));
        $coupon = $db->prepare("SELECT * FROM coupons WHERE code=? AND status='active' AND (expires_at IS NULL OR expires_at >= CURDATE()) AND (max_uses IS NULL OR uses < max_uses)");
        $coupon->execute([$code]);
        $coupon = $coupon->fetch();
        if ($coupon) {
            $_SESSION['coupon'] = $coupon;
            flashMessage('success', '🎉 Coupon applied: ' . sanitize($code));
        } else {
            flashMessage('error', 'Invalid or expired coupon code.');
        }
    } elseif ($action === 'remove_coupon') {
        unset($_SESSION['coupon']);
    }
    redirect('pages/cart.php');
}

// Get cart items
$cartItems = [];
if (isLoggedIn()) {
    $stmt = $db->prepare("SELECT c.quantity, p.* FROM cart c JOIN products p ON p.id=c.product_id WHERE c.user_id=? AND p.status='active'");
    $stmt->execute([$_SESSION['user_id']]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) { $cartItems[] = ['product' => $row, 'qty' => $row['quantity']]; }
} else {
    foreach ($_SESSION['cart'] ?? [] as $pid => $item) {
        $stmt = $db->prepare("SELECT * FROM products WHERE id=? AND status='active'");
        $stmt->execute([$pid]);
        $prod = $stmt->fetch();
        if ($prod) $cartItems[] = ['product' => $prod, 'qty' => $item['qty']];
    }
}

// Totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += ($item['product']['sale_price'] ?? $item['product']['price']) * $item['qty'];
}
$shipping = ($subtotal > 0 && $subtotal < FREE_SHIPPING_THRESHOLD) ? SHIPPING_COST : 0;
$tax = round($subtotal * TAX_RATE / 100, 2);
$discount = 0;
$coupon = $_SESSION['coupon'] ?? null;
if ($coupon && $subtotal >= $coupon['min_amount']) {
    $discount = $coupon['type'] === 'percentage' ? round($subtotal * $coupon['value'] / 100, 2) : min($coupon['value'], $subtotal);
}
$total = max(0, $subtotal + $shipping + $tax - $discount);

$pageTitle = 'Cart — ' . SITE_NAME;
require_once '../includes/header.php';
?>

<div class="container cart-page">
  <h1 style="font-family:'Playfair Display',serif;font-size:32px;color:var(--navy);margin-bottom:28px;">
    Shopping Cart <span style="font-size:18px;color:var(--text-muted);font-family:Inter,sans-serif;">(<?= count($cartItems) ?> items)</span>
  </h1>

  <?php if (empty($cartItems)): ?>
    <div class="empty-state">
      <div class="empty-state-icon">🛒</div>
      <h3>Your cart is empty</h3>
      <p>Looks like you haven't added anything yet.</p>
      <a href="<?= SITE_URL ?>/pages/shop.php" class="btn btn-primary">Start Shopping</a>
    </div>
  <?php else: ?>
    <div class="cart-grid">

      <!-- Cart Table -->
      <div>
        <div class="cart-table">
          <table>
            <thead>
              <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cartItems as $item):
                $prod = $item['product'];
                $unitPrice = $prod['sale_price'] ?? $prod['price'];
                $itemTotal = $unitPrice * $item['qty'];
              ?>
              <tr>
                <td>
                  <div class="cart-product">
                    <img src="<?= sanitize($prod['image'] ?? '') ?>" alt="<?= sanitize($prod['name']) ?>">
                    <div>
                      <div class="cart-product-name">
                        <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= urlencode($prod['slug']) ?>" style="color:var(--navy);">
                          <?= sanitize($prod['name']) ?>
                        </a>
                      </div>
                      <?php if ($prod['brand']): ?><div style="font-size:12px;color:var(--indigo);"><?= sanitize($prod['brand']) ?></div><?php endif; ?>
                    </div>
                  </div>
                </td>
                <td>
                  <strong><?= price($unitPrice) ?></strong>
                  <?php if ($prod['sale_price']): ?><br><small style="text-decoration:line-through;color:var(--text-muted);"><?= price($prod['price']) ?></small><?php endif; ?>
                </td>
                <td>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                    <div class="qty-control">
                      <button type="button" class="qty-btn" onclick="changeQty(this, -1)">−</button>
                      <input type="number" name="qty" class="qty-input" value="<?= $item['qty'] ?>" min="1" max="<?= $prod['stock'] ?>"
                             onchange="this.closest('form').submit()">
                      <button type="button" class="qty-btn" onclick="changeQty(this, 1)">+</button>
                    </div>
                  </form>
                </td>
                <td><strong><?= price($itemTotal) ?></strong></td>
                <td>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                    <button type="submit" style="color:var(--danger);font-size:18px;" title="Remove">×</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:center;">
          <a href="<?= SITE_URL ?>/pages/shop.php" style="color:var(--indigo);font-weight:600;font-size:14px;">← Continue Shopping</a>
        </div>
      </div>

      <!-- Summary -->
      <div class="cart-summary">
        <h3>Order Summary</h3>

        <div class="summary-row"><span>Subtotal</span><span><?= price($subtotal) ?></span></div>
        <div class="summary-row"><span>Shipping</span><span><?= $shipping > 0 ? price($shipping) : '<span style="color:var(--success);">FREE</span>' ?></span></div>
        <div class="summary-row"><span>Tax (<?= TAX_RATE ?>%)</span><span><?= price($tax) ?></span></div>
        <?php if ($discount > 0): ?>
          <div class="summary-row" style="color:var(--success);">
            <span>Discount (<?= sanitize($coupon['code']) ?>)</span>
            <span>−<?= price($discount) ?></span>
          </div>
        <?php endif; ?>
        <div class="summary-row total"><span>Total</span><span><?= price($total) ?></span></div>

        <!-- Coupon -->
        <form method="POST" class="coupon-form">
          <input type="hidden" name="action" value="coupon">
          <input type="text" name="coupon" placeholder="Coupon code" value="<?= sanitize($coupon['code'] ?? '') ?>">
          <button type="submit">Apply</button>
        </form>
        <?php if ($coupon): ?>
          <form method="POST" style="margin-top:-8px;margin-bottom:8px;">
            <input type="hidden" name="action" value="remove_coupon">
            <button type="submit" style="font-size:12px;color:var(--danger);">× Remove coupon</button>
          </form>
        <?php endif; ?>

        <?php if ($shipping > 0): ?>
          <p style="font-size:12px;color:var(--text-muted);margin-bottom:16px;">
            Add <?= price(FREE_SHIPPING_THRESHOLD - $subtotal) ?> more for free shipping!
          </p>
        <?php endif; ?>

        <a href="<?= SITE_URL ?>/pages/checkout.php" class="btn btn-primary btn-block" style="margin-top:12px;">
          Proceed to Checkout →
        </a>

        <div style="margin-top:16px;text-align:center;font-size:12px;color:var(--text-muted);">
          🔒 Secure checkout &nbsp;|&nbsp; SSL encrypted
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
function changeQty(btn, delta) {
  const input = btn.parentElement.querySelector('input[type=number]');
  const val = parseInt(input.value) + delta;
  input.value = Math.max(1, Math.min(parseInt(input.max || 999), val));
  input.dispatchEvent(new Event('change'));
}
</script>

<?php require_once '../includes/footer.php'; ?>
