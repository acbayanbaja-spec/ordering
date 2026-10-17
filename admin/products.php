<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Admin');
$categories = fetch_all('SELECT * FROM Categories ORDER BY CategoryName');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) post('action');
    try {
        if ($action === 'create' || $action === 'update') {
            $name = trim((string) post('ProductName'));
            $cat = (int) post('CategoryID');
            $price = (float) post('Price');
            $stock = (int) post('Stock');
            if ($name === '' || $cat < 1 || $price < 0 || $stock < 0) {
                throw new InvalidArgumentException('Check product name, category, price, and stock.');
            }
            if ($action === 'create') {
                exec_sql(
                    'INSERT INTO Products (ProductName, CategoryID, Price, Stock) VALUES (?, ?, ?, ?)',
                    [$name, $cat, $price, $stock]
                );
                flash_set('success', 'Product added.');
            } else {
                $id = require_post_int('ProductID');
                exec_sql(
                    'UPDATE Products SET ProductName = ?, CategoryID = ?, Price = ?, Stock = ? WHERE ProductID = ?',
                    [$name, $cat, $price, $stock, $id]
                );
                flash_set('success', 'Product updated.');
            }
        } elseif ($action === 'delete') {
            $id = require_post_int('ProductID');
            exec_sql('DELETE FROM Products WHERE ProductID = ?', [$id]);
            flash_set('success', 'Product deleted.');
        }
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
    }
    redirect('admin/products.php');
}

$products = fetch_all(
    'SELECT p.*, c.CategoryName FROM Products p
     JOIN Categories c ON c.CategoryID = p.CategoryID
     ORDER BY p.ProductName'
);

render_header('Products', $user);
?>
<div class="d-flex justify-content-between mb-3">
    <input class="form-control w-auto" placeholder="Search" oninput="filterTable('q','tbl')" id="q">
    <button class="btn btn-pine" data-bs-toggle="modal" data-bs-target="#modalCreate">Add product</button>
</div>
<div class="panel">
    <div class="table-responsive">
        <table class="table align-middle" id="tbl">
            <thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= (int) $p['ProductID'] ?></td>
                    <td><?= h($p['ProductName']) ?></td>
                    <td><?= h($p['CategoryName']) ?></td>
                    <td><?= money_php((float) $p['Price']) ?></td>
                    <td class="<?= (int) $p['Stock'] === 0 ? 'stock-out' : ((int) $p['Stock'] <= LOW_STOCK_THRESHOLD ? 'stock-low' : '') ?>">
                        <?= (int) $p['Stock'] ?>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#edit<?= (int) $p['ProductID'] ?>">Edit</button>
                        <form class="d-inline" method="post" onsubmit="return confirm('Delete this product?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="ProductID" value="<?= (int) $p['ProductID'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content needs-validation" method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-header"><h5 class="modal-title">Add product</h5><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
            <div class="modal-body">
                <?php include __DIR__ . '/_product_fields.php'; ?>
            </div>
            <div class="modal-footer"><button class="btn btn-pine">Save</button></div>
        </form>
    </div>
</div>
<?php foreach ($products as $p): ?>
<div class="modal fade" id="edit<?= (int) $p['ProductID'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content needs-validation" method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="ProductID" value="<?= (int) $p['ProductID'] ?>">
            <div class="modal-header"><h5 class="modal-title">Edit product</h5><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
            <div class="modal-body">
                <?php $edit = $p; include __DIR__ . '/_product_fields.php'; unset($edit); ?>
            </div>
            <div class="modal-footer"><button class="btn btn-pine">Update</button></div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php
render_footer();
