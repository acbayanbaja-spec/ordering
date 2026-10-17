<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        $id = require_post_int('OrderID');
        $status = (string) post('Status');
        update_order_status($id, $status);
        flash_set('success', 'Order #' . $id . ' is now ' . $status . '.');
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
    }
    redirect('admin/orders.php?' . http_build_query($_GET));
}

$from = (string) get('from');
$to = (string) get('to');
$status = (string) get('status');
$customer = (string) get('customer');
$cashier = (string) get('cashier');

$sql = 'SELECT o.*, c.CustomerName, CONCAT(e.FirstName, " ", e.LastName) AS CashierName
        FROM Orders o
        JOIN Customers c ON c.CustomerID = o.CustomerID
        LEFT JOIN Employees e ON e.EmployeeID = o.EmployeeID
        WHERE 1=1';
$params = [];
if ($from !== '') {
    $sql .= ' AND o.OrderDate >= ?';
    $params[] = $from . ' 00:00:00';
}
if ($to !== '') {
    $sql .= ' AND o.OrderDate <= ?';
    $params[] = $to . ' 23:59:59';
}
if (in_array($status, ['Completed', 'Cancelled', 'Returned'], true)) {
    $sql .= ' AND o.Status = ?';
    $params[] = $status;
}
if ($customer !== '') {
    $sql .= ' AND (c.CustomerName LIKE ? OR o.CustomerID = ?)';
    $params[] = '%' . $customer . '%';
    $params[] = ctype_digit($customer) ? (int) $customer : 0;
}
if ($cashier !== '') {
    $sql .= ' AND (CONCAT(e.FirstName, " ", e.LastName) LIKE ? OR o.EmployeeID = ?)';
    $params[] = '%' . $cashier . '%';
    $params[] = ctype_digit($cashier) ? (int) $cashier : 0;
}
$sql .= ' ORDER BY o.OrderDate DESC';
$orders = fetch_all($sql, $params);

$detailsMap = [];
if ($orders) {
    $ids = array_column($orders, 'OrderID');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $details = fetch_all(
        "SELECT od.*, p.ProductName FROM OrderDetails od
         JOIN Products p ON p.ProductID = od.ProductID
         WHERE od.OrderID IN ($in)",
        $ids
    );
    foreach ($details as $d) {
        $detailsMap[$d['OrderID']][] = $d;
    }
}

render_header('Orders', $user);
?>
<form class="panel mb-3 row g-2 align-items-end" method="get">
    <div class="col-md-2"><label class="form-label">From</label><input class="form-control" type="date" name="from" value="<?= h($from) ?>"></div>
    <div class="col-md-2"><label class="form-label">To</label><input class="form-control" type="date" name="to" value="<?= h($to) ?>"></div>
    <div class="col-md-2">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
            <option value="">All</option>
            <?php foreach (['Completed', 'Cancelled', 'Returned'] as $s): ?>
                <option <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><label class="form-label">Customer</label><input class="form-control" name="customer" value="<?= h($customer) ?>"></div>
    <div class="col-md-2"><label class="form-label">Cashier</label><input class="form-control" name="cashier" value="<?= h($cashier) ?>"></div>
    <div class="col-md-2"><button class="btn btn-pine w-100">Filter</button></div>
</form>

<?php foreach ($orders as $o): ?>
    <div class="panel mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-2">
            <div>
                <strong>Order #<?= (int) $o['OrderID'] ?></strong>
                · <?= h($o['CustomerName']) ?>
                · Cashier: <?= h($o['CashierName'] ?? 'Online') ?>
                · Payment: <?= h($o['PaymentMethod']) ?>
                · <?= h($o['OrderDate']) ?>
            </div>
            <div>
                <span class="badge text-bg-secondary"><?= h($o['Status']) ?></span>
                <strong><?= money_php((float) $o['TotalAmount']) ?></strong>
            </div>
        </div>
        <table class="table table-sm mt-2 mb-2">
            <thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Line</th></tr></thead>
            <tbody>
            <?php foreach ($detailsMap[$o['OrderID']] ?? [] as $d): ?>
                <tr>
                    <td><?= h($d['ProductName']) ?></td>
                    <td><?= (int) $d['Quantity'] ?></td>
                    <td><?= money_php((float) $d['UnitPrice']) ?></td>
                    <td><?= money_php((float) $d['UnitPrice'] * (int) $d['Quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <form method="post" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="OrderID" value="<?= (int) $o['OrderID'] ?>">
            <select class="form-select w-auto" name="Status">
                <?php foreach (['Completed', 'Cancelled', 'Returned'] as $s): ?>
                    <option <?= $o['Status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-secondary btn-sm">Update status</button>
        </form>
    </div>
<?php endforeach; ?>
<?php if (!$orders): ?>
    <div class="panel">No orders match those filters.</div>
<?php endif; ?>
<?php render_footer();
