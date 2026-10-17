<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Cashier');
$products = fetch_all(
    'SELECT p.*, c.CategoryName FROM Products p
     JOIN Categories c ON c.CategoryID = p.CategoryID
     ORDER BY c.CategoryName, p.ProductName'
);
render_header('Stock list', $user);
?>
<p class="text-muted">View only. Prices cannot be edited from the cashier desk.</p>
<div class="panel table-responsive">
    <table class="table align-middle" id="tbl">
        <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= h($p['ProductName']) ?></td>
                <td><?= h($p['CategoryName']) ?></td>
                <td><?= money_php((float) $p['Price']) ?></td>
                <td class="<?= (int) $p['Stock'] === 0 ? 'stock-out' : ((int) $p['Stock'] <= LOW_STOCK_THRESHOLD ? 'stock-low' : '') ?>">
                    <?= (int) $p['Stock'] === 0 ? 'Out of stock' : (int) $p['Stock'] ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php render_footer();
