<?php
// pages/order-success.php
require_once '../includes/config.php';
if (!isLoggedIn()) redirect('');

$orderNumber = sanitize($_GET['order'] ?? '');
$db = getDB();
$order = null;
if ($orderNumber) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_number=? AND user_id=?");
    $stmt->execute([$orderNumber, $_SESSION['user_id']]);
    $order = $stmt->fetch();
}

$pageTitle = 'Order Confirmed — ' . SITE_NAME;
require_once '../includes/header.php';
?>

<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:60px 20px;">
  <div style="text-align:center;max-width:540px;">
    <div style="font-size:80px;margin-bottom:20px;">✅</div>
    <h1 style="font-family:'Playfair Display',serif;font-size:36px;color:var(--navy);margin-bottom:12px;">Order Confirmed!</h1>
    <?php if ($order): ?>
      <p style="color:var(--text-muted);font-size:16px;margin-bottom:8px;">
        Thank you for your order. We've received it and will start processing it shortly.
      </p>
      <p style="margin-bottom:32px;">
        Order Number: <strong style="color:var(--indigo);font-size:18px;"><?= sanitize($order['order_number']) ?></strong>
      </p>
      <div style="background:white;border-radius:var(--radius-lg);padding:24px;border:1px solid var(--border);margin-bottom:28px;text-align:left;">
        <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:14px;">
          <span style="color:var(--text-muted);">Order Date</span>
          <strong><?= formatDate($order['created_at']) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:10px;font-size:14px;">
          <span style="color:var(--text-muted);">Payment Method</span>
          <strong><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:14px;">
          <span style="color:var(--text-muted);">Order Total</span>
          <strong style="font-size:18px;color:var(--navy);"><?= price($order['total']) ?></strong>
        </div>
      </div>
    <?php endif; ?>
    <div style="display:flex;gap:14px;justify-content:center;">
      <a href="<?= SITE_URL ?>/pages/orders.php" class="btn btn-outline">View My Orders</a>
      <a href="<?= SITE_URL ?>/pages/shop.php" class="btn btn-primary">Continue Shopping →</a>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
