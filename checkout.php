<?php
// pages/checkout.php
require_once '../includes/config.php';
$db = getDB();

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = SITE_URL . '/pages/checkout.php';
    flashMessage('info', 'Please login to checkout.');
    redirect('pages/login.php');
}

// Get cart
$stmt = $db->prepare("SELECT c.quantity, p.* FROM cart c JOIN products p ON p.id=c.product_id WHERE c.user_id=? AND p.status='active'");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    flashMessage('info', 'Your cart is empty.');
    redirect('pages/cart.php');
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += ($item['sale_price'] ?? $item['price']) * $item['quantity'];
}
$shipping = $subtotal < FREE_SHIPPING_THRESHOLD ? SHIPPING_COST : 0;
$tax      = round($subtotal * TAX_RATE / 100, 2);
$discount = 0;
$coupon   = $_SESSION['coupon'] ?? null;
if ($coupon && $subtotal >= $coupon['min_amount']) {
    $discount = $coupon['type'] === 'percentage' ? round($subtotal * $coupon['value'] / 100, 2) : min($coupon['value'], $subtotal);
}
$total = max(0, $subtotal + $shipping + $tax - $discount);
$user  = currentUser();

// Process order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['shipping_name'] ?? '');
    $email   = sanitize($_POST['shipping_email'] ?? '');
    $phone   = sanitize($_POST['shipping_phone'] ?? '');
    $address = sanitize($_POST['shipping_address'] ?? '');
    $city    = sanitize($_POST['shipping_city'] ?? '');
    $state   = sanitize($_POST['shipping_state'] ?? '');
    $zip     = sanitize($_POST['shipping_zip'] ?? '');
    $country = sanitize($_POST['shipping_country'] ?? '');
    $payment = sanitize($_POST['payment_method'] ?? 'cod');
    $notes   = sanitize($_POST['notes'] ?? '');

    if (!$name || !$email || !$address || !$city || !$country) {
        flashMessage('error', 'Please fill in all required shipping fields.');
    } else {
        $orderNumber = generateOrderNumber();
        $db->prepare("
            INSERT INTO orders (order_number, user_id, status, subtotal, shipping, tax, discount, total,
              coupon_code, payment_method, shipping_name, shipping_email, shipping_phone,
              shipping_address, shipping_city, shipping_state, shipping_zip, shipping_country, notes)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $orderNumber, $_SESSION['user_id'], 'pending', $subtotal, $shipping, $tax, $discount, $total,
            $coupon['code'] ?? null, $payment, $name, $email, $phone,
            $address, $city, $state, $zip, $country, $notes
        ]);
        $orderId = $db->lastInsertId();

        // Insert order items
        foreach ($cartItems as $item) {
            $unitPrice = $item['sale_price'] ?? $item['price'];
            $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_image, quantity, price, total) VALUES (?,?,?,?,?,?,?)")
               ->execute([$orderId, $item['id'], $item['name'], $item['image'], $item['quantity'], $unitPrice, $unitPrice * $item['quantity']]);
            // Reduce stock
            $db->prepare("UPDATE products SET stock = stock - ? WHERE id=?")->execute([$item['quantity'], $item['id']]);
        }

        // Clear cart & coupon
        $db->prepare("DELETE FROM cart WHERE user_id=?")->execute([$_SESSION['user_id']]);
        unset($_SESSION['coupon']);

        flashMessage('success', '🎉 Order #' . $orderNumber . ' placed successfully!');
        redirect('pages/order-success.php?order=' . $orderNumber);
    }
}

$pageTitle = 'Checkout — ' . SITE_NAME;
require_once '../includes/header.php';
?>

<div class="container" style="padding:40px 24px 80px;">
  <h1 style="font-family:'Playfair Display',serif;font-size:32px;color:var(--navy);margin-bottom:32px;">Checkout</h1>

  <form method="POST">
  <div class="checkout-grid">
    <!-- Left: Shipping + Payment -->
    <div>
      <div class="checkout-section">
        <h3>Shipping Details</h3>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="shipping_name" class="form-control" required value="<?= sanitize($user['name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="shipping_email" class="form-control" required value="<?= sanitize($user['email'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="tel" name="shipping_phone" class="form-control" value="<?= sanitize($user['phone'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Country *</label>
            <select name="shipping_country" class="form-control" required>
              <option value="">Select Country</option>
              <?php foreach (['United States','United Kingdom','Canada','Australia','Germany','France','Japan','India','Brazil','Other'] as $c): ?>
                <option value="<?= $c ?>" <?= ($user['country'] ?? '')===$c ? 'selected':'' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Street Address *</label>
          <input type="text" name="shipping_address" class="form-control" required placeholder="123 Main Street" value="<?= sanitize($user['address'] ?? '') ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">City *</label>
            <input type="text" name="shipping_city" class="form-control" required value="<?= sanitize($user['city'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">State / Province</label>
            <input type="text" name="shipping_state" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">ZIP Code</label>
            <input type="text" name="shipping_zip" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Order Notes (optional)</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Special instructions..."></textarea>
        </div>
      </div>

      <div class="checkout-section">
        <h3>Payment Method</h3>
        <label class="payment-method selected" id="pm-cod">
          <input type="radio" name="payment_method" value="cod" checked onchange="selectPayment(this, 'pm-cod')">
          <span>💵</span>
          <div>
            <strong>Cash on Delivery</strong>
            <div style="font-size:13px;color:var(--text-muted);">Pay when your order arrives</div>
          </div>
        </label>
        <label class="payment-method" id="pm-card">
          <input type="radio" name="payment_method" value="card" onchange="selectPayment(this, 'pm-card')">
          <span>💳</span>
          <div>
            <strong>Credit / Debit Card</strong>
            <div style="font-size:13px;color:var(--text-muted);">Visa, Mastercard, Amex</div>
          </div>
        </label>
        <label class="payment-method" id="pm-paypal">
          <input type="radio" name="payment_method" value="paypal" onchange="selectPayment(this, 'pm-paypal')">
          <span>🅿</span>
          <div>
            <strong>PayPal</strong>
            <div style="font-size:13px;color:var(--text-muted);">Fast and secure</div>
          </div>
        </label>
        <p style="font-size:12px;color:var(--text-muted);margin-top:12px;">🔒 Your payment information is encrypted and secure.</p>
      </div>
    </div>

    <!-- Right: Order Summary -->
    <div>
      <div class="cart-summary">
        <h3>Order Summary</h3>
        <?php foreach ($cartItems as $item):
          $unitPrice = $item['sale_price'] ?? $item['price'];
        ?>
          <div style="display:flex;gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);">
            <img src="<?= sanitize($item['image'] ?? '') ?>" style="width:50px;height:50px;object-fit:cover;border-radius:8px;flex-shrink:0;" alt="">
            <div style="flex:1;min-width:0;">
              <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($item['name']) ?></div>
              <div style="font-size:12px;color:var(--text-muted);">Qty: <?= $item['quantity'] ?></div>
            </div>
            <div style="font-size:14px;font-weight:700;flex-shrink:0;"><?= price($unitPrice * $item['quantity']) ?></div>
          </div>
        <?php endforeach; ?>

        <div class="summary-row"><span>Subtotal</span><span><?= price($subtotal) ?></span></div>
        <div class="summary-row"><span>Shipping</span><span><?= $shipping > 0 ? price($shipping) : '<span style="color:var(--success);">FREE</span>' ?></span></div>
        <div class="summary-row"><span>Tax (<?= TAX_RATE ?>%)</span><span><?= price($tax) ?></span></div>
        <?php if ($discount > 0): ?>
          <div class="summary-row" style="color:var(--success);"><span>Discount</span><span>−<?= price($discount) ?></span></div>
        <?php endif; ?>
        <div class="summary-row total"><span>Total</span><span><?= price($total) ?></span></div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px;font-size:16px;">
          🔒 Place Order — <?= price($total) ?>
        </button>
        <div style="text-align:center;margin-top:12px;font-size:12px;color:var(--text-muted);">
          By placing your order, you agree to our Terms of Service
        </div>
      </div>
    </div>
  </div>
  </form>
</div>

<script>
function selectPayment(radio, id) {
  document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('selected'));
  document.getElementById(id).classList.add('selected');
}
</script>

<?php require_once '../includes/footer.php'; ?>
