<?php
// pages/login.php
require_once '../includes/config.php';
if (isLoggedIn()) redirect('');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Merge session cart into DB
            if (!empty($_SESSION['cart'])) {
                $db2 = getDB();
                foreach ($_SESSION['cart'] as $pid => $item) {
                    $check = $db2->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND product_id=?");
                    $check->execute([$user['id'], $pid]);
                    $existing = $check->fetch();
                    if ($existing) {
                        $db2->prepare("UPDATE cart SET quantity=? WHERE id=?")->execute([$existing['quantity'] + $item['qty'], $existing['id']]);
                    } else {
                        $db2->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)")->execute([$user['id'], $pid, $item['qty']]);
                    }
                }
                unset($_SESSION['cart']);
            }

            flashMessage('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '!');
            redirect($_SESSION['redirect_after_login'] ?? '');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login — ' . SITE_NAME;
require_once '../includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card">
    <h2>Welcome Back</h2>
    <p>Sign in to your account to continue shopping.</p>

    <?php if ($error): ?>
      <div class="flash-message flash-error" style="position:static;margin-bottom:20px;"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required
               value="<?= sanitize($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
        <a href="#" style="font-size:13px;color:var(--indigo);">Forgot password?</a>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>

    <div class="auth-divider"><span>or</span></div>
    <div class="auth-link">
      Don't have an account? <a href="<?= SITE_URL ?>/pages/register.php">Create one free</a>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
