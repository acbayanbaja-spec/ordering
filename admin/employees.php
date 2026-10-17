<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Admin');
$departments = fetch_all('SELECT * FROM Departments ORDER BY DepartmentName');
$managers = fetch_all("SELECT EmployeeID, FirstName, LastName FROM Employees WHERE Status = 'Active' ORDER BY FirstName");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string) post('action');
    try {
        if ($action === 'create' || $action === 'update') {
            $first = trim((string) post('FirstName'));
            $last = trim((string) post('LastName'));
            $dept = (int) post('DepartmentID');
            $salary = (float) post('Salary');
            $hire = (string) post('HireDate');
            $title = trim((string) post('JobTitle'));
            $manager = post('ManagerID') === '' ? null : (int) post('ManagerID');
            $status = post('Status') === 'Inactive' ? 'Inactive' : 'Active';
            $password = (string) post('password');
            if ($first === '' || $last === '' || $dept < 1 || $salary < 0 || $title === '' || $hire === '') {
                throw new InvalidArgumentException('Please complete all employee fields.');
            }
            $role = role_from_title($title);
            if ($action === 'create') {
                if (strlen($password) < 8) {
                    throw new InvalidArgumentException('Password must be at least 8 characters.');
                }
                exec_sql(
                    'INSERT INTO Employees (FirstName, LastName, DepartmentID, Salary, HireDate, JobTitle, ManagerID, Status, Password, Role)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$first, $last, $dept, $salary, $hire, $title, $manager, $status, password_hash($password, PASSWORD_DEFAULT), $role]
                );
                flash_set('success', 'Employee added. Role set to ' . $role . ' from job title.');
            } else {
                $id = require_post_int('EmployeeID');
                if ($manager === $id) {
                    throw new InvalidArgumentException('An employee cannot manage themselves.');
                }
                if ($password !== '') {
                    exec_sql(
                        'UPDATE Employees SET FirstName=?, LastName=?, DepartmentID=?, Salary=?, HireDate=?, JobTitle=?, ManagerID=?, Status=?, Role=?, Password=?
                         WHERE EmployeeID=?',
                        [$first, $last, $dept, $salary, $hire, $title, $manager, $status, $role, password_hash($password, PASSWORD_DEFAULT), $id]
                    );
                } else {
                    exec_sql(
                        'UPDATE Employees SET FirstName=?, LastName=?, DepartmentID=?, Salary=?, HireDate=?, JobTitle=?, ManagerID=?, Status=?, Role=?
                         WHERE EmployeeID=?',
                        [$first, $last, $dept, $salary, $hire, $title, $manager, $status, $role, $id]
                    );
                }
                flash_set('success', 'Employee updated.');
            }
        } elseif ($action === 'delete') {
            $id = require_post_int('EmployeeID');
            if ($id === (int) $user['id']) {
                throw new RuntimeException('You cannot delete your own account.');
            }
            exec_sql('UPDATE Employees SET ManagerID = NULL WHERE ManagerID = ?', [$id]);
            exec_sql('UPDATE Orders SET EmployeeID = NULL WHERE EmployeeID = ?', [$id]);
            exec_sql('DELETE FROM Employees WHERE EmployeeID = ?', [$id]);
            flash_set('success', 'Employee deleted.');
        }
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
    }
    redirect('admin/employees.php');
}

$rows = fetch_all(
    'SELECT e.*, d.DepartmentName,
            CONCAT(m.FirstName, " ", m.LastName) AS ManagerName
     FROM Employees e
     JOIN Departments d ON d.DepartmentID = e.DepartmentID
     LEFT JOIN Employees m ON m.EmployeeID = e.ManagerID
     ORDER BY e.EmployeeID'
);
render_header('Employees', $user);
?>
<div class="d-flex justify-content-between mb-3">
    <p class="text-muted mb-0">Admin role is assigned to Manager/IT titles. Cashier role is assigned to Sales Rep.</p>
    <button class="btn btn-pine" data-bs-toggle="modal" data-bs-target="#create">Add employee</button>
</div>
<div class="panel table-responsive">
    <table class="table align-middle">
        <thead><tr><th>ID</th><th>Name</th><th>Dept</th><th>Title</th><th>Role</th><th>Status</th><th>Manager</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= (int) $r['EmployeeID'] ?></td>
                <td><?= h($r['FirstName'] . ' ' . $r['LastName']) ?></td>
                <td><?= h($r['DepartmentName']) ?></td>
                <td><?= h($r['JobTitle']) ?></td>
                <td><?= h($r['Role']) ?></td>
                <td><?= h($r['Status']) ?></td>
                <td><?= h($r['ManagerName'] ?? '—') ?></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#e<?= (int) $r['EmployeeID'] ?>">Edit</button>
                    <form class="d-inline" method="post" onsubmit="return confirm('Delete this employee?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="EmployeeID" value="<?= (int) $r['EmployeeID'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <div class="modal fade" id="e<?= (int) $r['EmployeeID'] ?>">
                <div class="modal-dialog">
                    <form class="modal-content" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="EmployeeID" value="<?= (int) $r['EmployeeID'] ?>">
                        <div class="modal-header"><h5 class="modal-title">Edit employee</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body"><?php $edit = $r; include __DIR__ . '/_employee_fields.php'; unset($edit); ?></div>
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
            <div class="modal-header"><h5 class="modal-title">Add employee</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><?php include __DIR__ . '/_employee_fields.php'; ?></div>
            <div class="modal-footer"><button class="btn btn-pine">Save</button></div>
        </form>
    </div>
</div>
<?php render_footer();
