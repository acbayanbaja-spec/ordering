<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) post('action');
    try {
        $name = trim((string) post('CategoryName'));
        if ($action === 'create') {
            if ($name === '') {
                throw new InvalidArgumentException('Category name is required.');
            }
            exec_sql('INSERT INTO Categories (CategoryName) VALUES (?)', [$name]);
            flash_set('success', 'Category added.');
        } elseif ($action === 'update') {
            $id = require_post_int('CategoryID');
            if ($name === '') {
                throw new InvalidArgumentException('Category name is required.');
            }
            exec_sql('UPDATE Categories SET CategoryName = ? WHERE CategoryID = ?', [$name, $id]);
            flash_set('success', 'Category updated.');
        } elseif ($action === 'delete') {
            $id = require_post_int('CategoryID');
            $used = fetch_one('SELECT COUNT(*) AS c FROM Products WHERE CategoryID = ?', [$id]);
            if ((int) $used['c'] > 0) {
                throw new RuntimeException('Cannot delete a category that still has products.');
            }
            exec_sql('DELETE FROM Categories WHERE CategoryID = ?', [$id]);
            flash_set('success', 'Category deleted.');
        }
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
    }
    redirect('admin/categories.php');
}

$cats = fetch_all(
    'SELECT c.*, (SELECT COUNT(*) FROM Products p WHERE p.CategoryID = c.CategoryID) AS ProductCount
     FROM Categories c ORDER BY CategoryName'
);
render_header('Categories', $user);
?>
<div class="d-flex justify-content-between mb-3">
    <h2 class="h5 mb-0">Pet food categories</h2>
    <button class="btn btn-pine" data-bs-toggle="modal" data-bs-target="#create">Add category</button>
</div>
<div class="panel">
    <table class="table align-middle">
        <thead><tr><th>ID</th><th>Name</th><th>Products</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cats as $c): ?>
            <tr>
                <td><?= (int) $c['CategoryID'] ?></td>
                <td><?= h($c['CategoryName']) ?></td>
                <td><?= (int) $c['ProductCount'] ?></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#e<?= (int) $c['CategoryID'] ?>">Edit</button>
                    <form class="d-inline" method="post" onsubmit="return confirm('Delete this category?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="CategoryID" value="<?= (int) $c['CategoryID'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <div class="modal fade" id="e<?= (int) $c['CategoryID'] ?>">
                <div class="modal-dialog">
                    <form class="modal-content" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="CategoryID" value="<?= (int) $c['CategoryID'] ?>">
                        <div class="modal-header"><h5 class="modal-title">Edit category</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <input class="form-control" name="CategoryName" required value="<?= h($c['CategoryName']) ?>">
                        </div>
                        <div class="modal-footer"><button class="btn btn-pine">Save</button></div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="modal fade" id="create">
    <div class="modal-dialog">
        <form class="modal-content" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-header"><h5 class="modal-title">Add category</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input class="form-control" name="CategoryName" required>
            </div>
            <div class="modal-footer"><button class="btn btn-pine">Save</button></div>
        </form>
    </div>
</div>
<?php render_footer();
