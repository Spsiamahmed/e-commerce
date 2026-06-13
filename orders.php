<?php // pages/orders.php
require_once '../includes/config.php';
if (!isLoggedIn()) { redirect('pages/login.php'); }
$db = getDB();
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
$pageTitle = 'My Orders — ' . SITE_NAME;
require_once '../includes/header.php';
?>

<div class="container" style="padding:40px 24px 80px;">
  <h1 style="font-family:'Playfair Display',serif;font-size:32px;color:var(--navy);margin-bottom:28px;">My Orders</h1>

  <?php if (empty($orders)): ?>
    <div class="empty-state">
      <div class="empty-state-icon">📦</div>
      <h3>No Orders Yet</h3>
      <p>You haven't placed any orders. Start shopping!</p>
      <a href="<?= SITE_URL ?>/pages/shop.php" class="btn btn-primary">Shop Now</a>
    </div>
  <?php else: ?>
    <div class="orders-table">
      <table>
        <thead>
          <tr>
            <th>Order #</th>
            <th>Date</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o):
            $itemCount = $db->prepare("SELECT SUM(quantity) FROM order_items WHERE order_id=?");
            $itemCount->execute([$o['id']]);
            $count = (int)$itemCount->fetchColumn();
          ?>
          <tr>
            <td><strong style="color:var(--indigo);"><?= sanitize($o['order_number']) ?></strong></td>
            <td><?= formatDate($o['created_at']) ?></td>
            <td><?= $count ?> item<?= $count !== 1 ? 's':'' ?></td>
            <td><strong><?= price($o['total']) ?></strong></td>
            <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
            <td><?= ucwords(str_replace('_', ' ', $o['payment_method'])) ?></td>
            <td><a href="<?= SITE_URL ?>/pages/order-detail.php?id=<?= $o['id'] ?>" style="color:var(--indigo);font-weight:600;font-size:13px;">View →</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
