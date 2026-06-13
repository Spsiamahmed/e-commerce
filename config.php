<?php
// ============================================
// includes/config.php — Site Configuration
// ============================================
// Database Configuration — InfinityFree
define('DB_HOST', 'sql105.infinityfree.com');
define('DB_USER', 'if0_41206619');
define('DB_PASS', 'AEUObAd5zHCu');
define('DB_NAME', 'if0_41206619_ecommerce_db');

// Site Configuration
define('SITE_NAME', 'লুমে');
define('SITE_URL', 'http://splive.fast-page.org/shop');
define('SITE_EMAIL', 'hello@lume.com');
define('CURRENCY', '৳');
define('CURRENCY_CODE', 'BDT');

// Session & Security
define('SESSION_NAME', 'lume_session');
define('SECRET_KEY', 'lume-secret-key-2025');

// Pagination
define('PRODUCTS_PER_PAGE', 12);

// Upload paths
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Tax rate (percentage)
define('TAX_RATE', 8);

// Shipping
define('FREE_SHIPPING_THRESHOLD', 3000);
define('SHIPPING_COST', 120);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// ============================================
// Database Connection (PDO)
// ============================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

// ============================================
// Helper Functions
// ============================================

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function price(float $amount): string {
    return CURRENCY . number_format($amount, 2);
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: " . SITE_URL . "/" . ltrim($url, '/'));
    exit;
}

function flashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function generateOrderNumber(): string {
    return 'LME-' . strtoupper(substr(uniqid(), -6)) . rand(100, 999);
}

function cartCount(): int {
    if (isLoggedIn()) {
        $db = getDB();
        $stmt = $db->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return (int)$stmt->fetchColumn();
    }
    return 0;
}
?>
