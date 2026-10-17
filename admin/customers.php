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
            $name = trim((string) post('CustomerName'));
            $city = trim((string) post('City'));
            $country = trim((string) post('Country'));
            $email = trim((string) post('Email'));
            $password = (string) post('password');
            if ($name === '' || $city === '' || $country === '' || !validate_email($email)) {
                throw new InvalidArgumentException('Name, city, country, and a valid email are required.');
            }
            if ($action === 'create') {
                if (strlen($password) < 8) {
                    throw new InvalidArgumentException('Password must be at least 8 characters.');
                }
                exec_sql(
                    'INSERT INTO Customers (CustomerName, City, Country, Email, Password) VALUES (?, ?, ?, ?, ?)',
                    [$name, $city, $country, $email, password_hash($password, PASSWORD_DEFAULT)]
                );
                flash_set('success', 'Customer added.');
            } else {
                $id = require_post_int('CustomerID');
                if ($password !== '') {
                    exec_sql(
                        'UPDATE Customers SET CustomerName=?, City=?, Country=?, Email=?, Password=? WHERE CustomerID=?',
                        [$name, $city, $country, $email, password_hash($password, PASSWORD_DEFAULT), $id]
                    );
                } else {
                    exec_sql(
                        'UPDATE Customers SET CustomerName=?, City=?, Country=?, Email=? WHERE CustomerID=?',
                        [$name, $city, $country, $email, $id]
                    );
                }
                flash_set('success', 'Customer updated.');
            }
        } elseif ($action === 'delete') {
            $id = require_post_int('CustomerID');
            $used = fetch_one('SELECT COUNT(*) AS c FROM Orders WHERE CustomerID = ?', [$id]);
            if ((int) $used['c'] > 0) {
                throw new RuntimeException('Cannot delete a customer who already has orders.');
            }
            exec_sql('DELETE FROM Customers WHERE CustomerID = ?', [$id]);
            flash_set('success', 'Customer deleted.');
        }
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
    }
    redirect('admin/customers.php');
}

$rows = fetch_all('SELECT CustomerID, CustomerName, City, Country, Email FROM Customers ORDER BY CustomerName');
render_header('Customers', $user);
?>
<div class="d-flex justify-content-between mb-3">
    <input class="form-control w-auto" id="q" placeholder="Search" oninput="filterTable('q','tbl')">
    <button class="btn btn-pine" data-bs-toggle="modal" data-bs-target="#create">Add customer</button>
</div>
<div class="panel table-responsive">
    <table class="table align-middle" id="tbl">
        <thead><tr><th>ID</th><th>Name</th><th>City</th><th>Country</th><th>Email</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= (int) $r['CustomerID'] ?></td>
                <td><?= h($r['CustomerName']) ?></td>
                <td><?= h($r['City']) ?></td>
                <td><?= h($r['Country']) ?></td>
                <td><?= h($r['Email']) ?></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#e<?= (int) $r['CustomerID'] ?>">Edit</button>
                    <form class="d-inline" method="post" onsubmit="return confirm('Delete this customer?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="CustomerID" value="<?= (int) $r['CustomerID'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <div class="modal fade" id="e<?= (int) $r['CustomerID'] ?>">
                <div class="modal-dialog">
                    <form class="modal-content" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="CustomerID" value="<?= (int) $r['CustomerID'] ?>">
                        <div class="modal-header"><h5 class="modal-title">Edit customer</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="CustomerName" required value="<?= h($r['CustomerName']) ?>"></div>
                            <div class="mb-2"><label class="form-label">City</label><input class="form-control" name="City" required value="<?= h($r['City']) ?>"></div>
                            <div class="mb-2"><label class="form-label">Country</label><input class="form-control" name="Country" required value="<?= h($r['Country']) ?>"></div>
                            <div class="mb-2"><label class="form-label">Email</label><input class="form-control" type="email" name="Email" required value="<?= h($r['Email']) ?>"></div>
                            <div class="mb-2"><label class="form-label">New password (optional)</label><input class="form-control" type="password" name="password" minlength="8"></div>
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
            <div class="modal-header"><h5 class="modal-title">Add customer</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="CustomerName" required></div>
                <div class="mb-2"><label class="form-label">City</label><input class="form-control" name="City" required></div>
                <div class="mb-2"><label class="form-label">Country</label><input class="form-control" name="Country" required value="Philippines"></div>
                <div class="mb-2"><label class="form-label">Email</label><input class="form-control" type="email" name="Email" required></div>
                <div class="mb-2"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required minlength="8"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-pine">Save</button></div>
        </form>
    </div>
</div>
<?php render_footer();
