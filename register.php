<?php
// pages/register.php
require_once '../includes/config.php';
if (isLoggedIn()) redirect('');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $db = getDB();
        $exists = $db->prepare("SELECT id FROM users WHERE email=?");
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $errors[] = 'This email is already registered. <a href="login.php">Login instead?</a>';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $db->prepare("INSERT INTO users (name, email, password) VALUES (?,?,?)")->execute([$name, $email, $hash]);
            $userId = $db->lastInsertId();
            $_SESSION['user_id']   = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'customer';
            flashMessage('success', '🎉 Account created! Welcome to ' . SITE_NAME . '!');
            redirect('');
        }
    }
}

$pageTitle = 'Create Account — ' . SITE_NAME;
require_once '../includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card">
    <h2>Create Account</h2>
    <p>Join thousands of happy shoppers today.</p>

    <?php foreach ($errors as $e): ?>
      <div class="flash-message flash-error" style="position:static;margin-bottom:12px;"><?= $e ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
      <div class="form-group">
        <label class="form-label" for="name">Full Name</label>
        <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" required
               value="<?= sanitize($_POST['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required
               value="<?= sanitize($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Min 8 chars" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm">Confirm Password</label>
          <input type="password" id="confirm" name="confirm" class="form-control" placeholder="Repeat password" required>
        </div>
      </div>
      <p style="font-size:12px;color:var(--text-muted);margin-bottom:16px;">
        By creating an account, you agree to our <a href="#" style="color:var(--indigo);">Terms of Service</a> and <a href="#" style="color:var(--indigo);">Privacy Policy</a>.
      </p>
      <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>

    <div class="auth-divider"><span>or</span></div>
    <div class="auth-link">
      Already have an account? <a href="<?= SITE_URL ?>/pages/login.php">Sign in</a>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
