<?php
// pages/ajax-cart.php — AJAX endpoint for add to cart / wishlist
require_once '../includes/config.php';
header('Content-Type: application/json');

$action    = $_POST['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? 0);
$qty       = max(1, (int)($_POST['qty'] ?? 1));

if (!$productId) { echo json_encode(['success' => false, 'message' => 'Invalid product.']); exit; }

$db   = getDB();
$prod = $db->prepare("SELECT * FROM products WHERE id=? AND status='active'");
$prod->execute([$productId]);
$prod = $prod->fetch();

if (!$prod) { echo json_encode(['success' => false, 'message' => 'Product not found.']); exit; }
if ($prod['stock'] <= 0) { echo json_encode(['success' => false, 'message' => 'Out of stock.']); exit; }

if ($action === 'add_to_cart') {
    if (isLoggedIn()) {
        $check = $db->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND product_id=?");
        $check->execute([$_SESSION['user_id'], $productId]);
        $existing = $check->fetch();
        if ($existing) {
            $db->prepare("UPDATE cart SET quantity=? WHERE id=?")->execute([$existing['quantity'] + $qty, $existing['id']]);
        } else {
            $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)")->execute([$_SESSION['user_id'], $productId, $qty]);
        }
    } else {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$productId] = ['product_id' => $productId, 'qty' => $qty];
        }
    }
    $count = cartCount();
    echo json_encode(['success' => true, 'message' => '✓ Added to cart!', 'cart_count' => $count]);

} elseif ($action === 'add_to_wishlist') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to use wishlist.', 'redirect' => SITE_URL . '/pages/login.php']);
        exit;
    }
    $check = $db->prepare("SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
    $check->execute([$_SESSION['user_id'], $productId]);
    if ($check->fetch()) {
        $db->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?")->execute([$_SESSION['user_id'], $productId]);
        echo json_encode(['success' => true, 'message' => 'Removed from wishlist.', 'wishlisted' => false]);
    } else {
        $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?,?)")->execute([$_SESSION['user_id'], $productId]);
        echo json_encode(['success' => true, 'message' => '♥ Added to wishlist!', 'wishlisted' => true]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
