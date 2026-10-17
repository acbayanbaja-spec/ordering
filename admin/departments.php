<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) post('action');
    try {
        if ($action === 'create' || $action === 'update') {
            $name = trim((string) post('DepartmentName'));
            $loc = trim((string) post('Location'));
            $budget = (float) post('Budget');
            if ($name === '' || $loc === '' || $budget < 0) {
                throw new InvalidArgumentException('Name, location, and a non-negative budget are required.');
            }
            if ($action === 'create') {
                exec_sql(
                    'INSERT INTO Departments (DepartmentName, Location, Budget) VALUES (?, ?, ?)',
                    [$name, $loc, $budget]
                );
                flash_set('success', 'Department added.');
            } else {
                $id = require_post_int('DepartmentID');
                exec_sql(
                    'UPDATE Departments SET DepartmentName = ?, Location = ?, Budget = ? WHERE DepartmentID = ?',
                    [$name, $loc, $budget, $id]
                );
                flash_set('success', 'Department updated.');
            }
        } elseif ($action === 'delete') {
            $id = require_post_int('DepartmentID');
            $used = fetch_one('SELECT COUNT(*) AS c FROM Employees WHERE DepartmentID = ?', [$id]);
            if ((int) $used['c'] > 0) {
                throw new RuntimeException('Move employees out of this department before deleting it.');
            }
            exec_sql('DELETE FROM Departments WHERE DepartmentID = ?', [$id]);
            flash_set('success', 'Department deleted.');
        }
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
    }
    redirect('admin/departments.php');
}

$rows = fetch_all('SELECT * FROM Departments ORDER BY DepartmentName');
render_header('Departments', $user);
?>
<div class="d-flex justify-content-between mb-3">
    <h2 class="h5 mb-0">Departments</h2>
    <button class="btn btn-pine" data-bs-toggle="modal" data-bs-target="#create">Add department</button>
</div>
<div class="panel table-responsive">
    <table class="table align-middle">
        <thead><tr><th>ID</th><th>Name</th><th>Location</th><th>Budget</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= (int) $r['DepartmentID'] ?></td>
                <td><?= h($r['DepartmentName']) ?></td>
                <td><?= h($r['Location']) ?></td>
                <td><?= money_php((float) $r['Budget']) ?></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#e<?= (int) $r['DepartmentID'] ?>">Edit</button>
                    <form class="d-inline" method="post" onsubmit="return confirm('Delete this department?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="DepartmentID" value="<?= (int) $r['DepartmentID'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <div class="modal fade" id="e<?= (int) $r['DepartmentID'] ?>">
                <div class="modal-dialog">
                    <form class="modal-content" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="DepartmentID" value="<?= (int) $r['DepartmentID'] ?>">
                        <div class="modal-header"><h5 class="modal-title">Edit department</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="DepartmentName" required value="<?= h($r['DepartmentName']) ?>"></div>
                            <div class="mb-2"><label class="form-label">Location</label><input class="form-control" name="Location" required value="<?= h($r['Location']) ?>"></div>
                            <div class="mb-2"><label class="form-label">Budget</label><input class="form-control" type="number" step="0.01" min="0" name="Budget" required value="<?= h((string) $r['Budget']) ?>"></div>
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
            <div class="modal-header"><h5 class="modal-title">Add department</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="DepartmentName" required></div>
                <div class="mb-2"><label class="form-label">Location</label><input class="form-control" name="Location" required></div>
                <div class="mb-2"><label class="form-label">Budget</label><input class="form-control" type="number" step="0.01" min="0" name="Budget" required value="0"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-pine">Save</button></div>
        </form>
    </div>
</div>
<?php render_footer();
