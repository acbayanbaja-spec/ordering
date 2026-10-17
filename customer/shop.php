<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Customer');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        $pid = require_post_int('ProductID');
        $qty = max(1, (int) post('qty', 1));
        $p = fetch_one('SELECT ProductID, ProductName, Stock FROM Products WHERE ProductID = ?', [$pid]);
        if (!$p) {
            throw new RuntimeException('Product not found.');
        }
        $newQty = (int) ($_SESSION['cart'][$pid] ?? 0) + $qty;
        if ($newQty > (int) $p['Stock']) {
            throw new RuntimeException($p['ProductName'] . ' only has ' . $p['Stock'] . ' left.');
        }
        $_SESSION['cart'][$pid] = $newQty;
        flash_set('success', 'Added to cart.');
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
    }
    redirect('customer/shop.php?' . http_build_query(array_filter(['q' => get('q'), 'cat' => get('cat')])));
}

$q = trim((string) get('q'));
$cat = (int) get('cat');
$sql = 'SELECT p.*, c.CategoryName FROM Products p JOIN Categories c ON c.CategoryID = p.CategoryID WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND p.ProductName LIKE ?';
    $params[] = '%' . $q . '%';
}
if ($cat > 0) {
    $sql .= ' AND p.CategoryID = ?';
    $params[] = $cat;
}
$sql .= ' ORDER BY p.ProductName';
$products = fetch_all($sql, $params);
$categories = fetch_all('SELECT * FROM Categories ORDER BY CategoryName');

render_header('Shop', $user);
?>
<form class="panel mb-3 row g-2" method="get">
    <div class="col-md-6"><input class="form-control" name="q" placeholder="Search pet food" value="<?= h($q) ?>"></div>
    <div class="col-md-4">
        <select class="form-select" name="cat">
            <option value="0">All categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['CategoryID'] ?>" <?= $cat === (int) $c['CategoryID'] ? 'selected' : '' ?>><?= h($c['CategoryName']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-pine w-100">Filter</button></div>
</form>
<div class="product-grid">
    <?php foreach ($products as $p): $out = (int) $p['Stock'] === 0; ?>
        <div class="product-card">
            <small><?= h($p['CategoryName']) ?></small>
            <h3><?= h($p['ProductName']) ?></h3>
            <div class="price"><?= money_php((float) $p['Price']) ?></div>
            <div class="<?= $out ? 'stock-out' : ((int) $p['Stock'] <= LOW_STOCK_THRESHOLD ? 'stock-low' : '') ?>">
                <?= $out ? 'Out of stock' : 'In stock: ' . (int) $p['Stock'] ?>
            </div>
            <form method="post" class="mt-auto d-flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="ProductID" value="<?= (int) $p['ProductID'] ?>">
                <input class="form-control form-control-sm" type="number" name="qty" value="1" min="1" max="<?= (int) $p['Stock'] ?>" <?= $out ? 'disabled' : '' ?>>
                <button class="btn btn-sm btn-clay" <?= $out ? 'disabled' : '' ?>><?= $out ? 'Out of stock' : 'Add' ?></button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
<?php render_footer();
