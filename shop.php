<?php
// pages/shop.php — Shop listing with filters & pagination
require_once '../includes/config.php';
$db = getDB();

// Filters from GET
$search    = sanitize($_GET['q'] ?? '');
$category  = sanitize($_GET['category'] ?? '');
$filter    = sanitize($_GET['filter'] ?? '');
$sort      = sanitize($_GET['sort'] ?? 'newest');
$minPrice  = (float)($_GET['min'] ?? 0);
$maxPrice  = (float)($_GET['max'] ?? 9999);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = PRODUCTS_PER_PAGE;
$offset    = ($page - 1) * $perPage;

// Build query
$where = ["p.status = 'active'"];
$params = [];

if ($search) { $where[] = "(p.name LIKE ? OR p.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($category) { $where[] = "c.slug = ?"; $params[] = $category; }
if ($filter === 'sale') { $where[] = "p.sale_price IS NOT NULL"; }
if ($filter === 'featured') { $where[] = "p.featured = 1"; }
if ($minPrice > 0) { $where[] = "COALESCE(p.sale_price, p.price) >= ?"; $params[] = $minPrice; }
if ($maxPrice < 9999) { $where[] = "COALESCE(p.sale_price, p.price) <= ?"; $params[] = $maxPrice; }

$orderBy = match($sort) {
    'price_asc'  => 'COALESCE(p.sale_price, p.price) ASC',
    'price_desc' => 'COALESCE(p.sale_price, p.price) DESC',
    'rating'     => 'p.rating DESC',
    'popular'    => 'p.reviews_count DESC',
    default      => 'p.created_at DESC',
};

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id=p.category_id $whereSQL");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages    = (int)ceil($totalProducts / $perPage);

// Products
$stmt = $db->prepare("
  SELECT p.*, c.name as category_name, c.slug as category_slug
  FROM products p
  LEFT JOIN categories c ON c.id = p.category_id
  $whereSQL
  ORDER BY $orderBy
  LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// All categories for sidebar
$categories = $db->query("SELECT c.*, COUNT(p.id) cnt FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.status='active' WHERE c.status='active' GROUP BY c.id ORDER BY c.sort_order")->fetchAll();

$pageTitle = ($category ? ucwords(str_replace('-', ' ', $category)) . ' — ' : '') . 'Shop — ' . SITE_NAME;
require_once '../includes/header.php';
?>

<div class="container" style="padding-top:32px;">

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="<?= SITE_URL ?>">Home</a>
    <span>/</span>
    <?php if ($category): ?>
      <a href="<?= SITE_URL ?>/pages/shop.php">Shop</a>
      <span>/</span>
      <strong><?= ucwords(str_replace('-', ' ', $category)) ?></strong>
    <?php else: ?>
      <strong>Shop<?= $filter ? ' — ' . ucfirst($filter) : '' ?></strong>
    <?php endif; ?>
  </nav>

  <div class="shop-layout">

    <!-- Sidebar Filters -->
    <aside class="filters-sidebar">
      <h3 style="font-family:'Playfair Display',serif;font-size:18px;margin-bottom:20px;">Filters</h3>

      <div class="filter-group">
        <h4>Categories</h4>
        <?php foreach ($categories as $cat): ?>
          <label class="filter-option">
            <input type="checkbox" name="category" value="<?= $cat['slug'] ?>"
              onchange="applyFilter('category', this.value)"
              <?= $category === $cat['slug'] ? 'checked' : '' ?>>
            <?= sanitize($cat['name']) ?>
            <span style="margin-left:auto;color:var(--text-muted);font-size:12px;"><?= $cat['cnt'] ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="filter-group">
        <h4>Price Range</h4>
        <div class="price-range">
          <input type="number" id="minPrice" placeholder="Min" value="<?= $minPrice ?: '' ?>" min="0">
          <input type="number" id="maxPrice" placeholder="Max" value="<?= $maxPrice < 9999 ? $maxPrice : '' ?>" min="0">
        </div>
        <button class="btn btn-outline btn-sm" style="margin-top:10px;width:100%;" onclick="applyPriceFilter()">Apply</button>
      </div>

      <div class="filter-group">
        <h4>Quick Filters</h4>
        <label class="filter-option">
          <input type="radio" name="filter" value="featured" onchange="applyFilter('filter', 'featured')" <?= $filter==='featured' ? 'checked' : '' ?>> Featured
        </label>
        <label class="filter-option">
          <input type="radio" name="filter" value="sale" onchange="applyFilter('filter', 'sale')" <?= $filter==='sale' ? 'checked' : '' ?>> On Sale
        </label>
      </div>

      <?php if ($category || $filter || $search || $minPrice || $maxPrice < 9999): ?>
        <a href="<?= SITE_URL ?>/pages/shop.php" class="btn btn-outline btn-sm btn-block" style="margin-top:8px;">✕ Clear Filters</a>
      <?php endif; ?>
    </aside>

    <!-- Main Content -->
    <div>
      <!-- Toolbar -->
      <div class="shop-toolbar">
        <p style="font-size:14px;color:var(--text-muted);">
          Showing <strong style="color:var(--navy)"><?= $totalProducts ?></strong> products
          <?= $search ? "for \"$search\"" : '' ?>
        </p>
        <select class="sort-select" onchange="applyFilter('sort', this.value)">
          <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Newest First</option>
          <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected':'' ?>>Price: Low → High</option>
          <option value="price_desc" <?= $sort==='price_desc' ? 'selected':'' ?>>Price: High → Low</option>
          <option value="rating"     <?= $sort==='rating'     ? 'selected':'' ?>>Top Rated</option>
          <option value="popular"    <?= $sort==='popular'    ? 'selected':'' ?>>Most Popular</option>
        </select>
      </div>

      <!-- Products -->
      <?php if (empty($products)): ?>
        <div class="empty-state">
          <div class="empty-state-icon">🔍</div>
          <h3>No Products Found</h3>
          <p>Try adjusting your filters or searching for something else.</p>
          <a href="<?= SITE_URL ?>/pages/shop.php" class="btn btn-primary">Browse All Products</a>
        </div>
      <?php else: ?>
        <div class="products-grid">
          <?php foreach ($products as $p): ?>
            <?php include '../includes/product-card.php'; ?>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div class="pagination">
            <?php if ($page > 1): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>" class="page-btn">‹</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                 class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>" class="page-btn">›</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function applyFilter(key, value) {
  const params = new URLSearchParams(window.location.search);
  params.set(key, value);
  params.delete('page');
  window.location.search = params.toString();
}
function applyPriceFilter() {
  const params = new URLSearchParams(window.location.search);
  const min = document.getElementById('minPrice').value;
  const max = document.getElementById('maxPrice').value;
  if (min) params.set('min', min); else params.delete('min');
  if (max) params.set('max', max); else params.delete('max');
  params.delete('page');
  window.location.search = params.toString();
}
</script>

<?php require_once '../includes/footer.php'; ?>
