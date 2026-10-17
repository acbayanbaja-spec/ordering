<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Admin');

$type = (string) get('type', 'sales');
$from = (string) get('from', date('Y-m-01'));
$to = (string) get('to', date('Y-m-d'));
$export = (string) get('export');

if ($type === 'inventory') {
    $rows = fetch_all(
        'SELECT p.ProductID, p.ProductName, c.CategoryName, p.Price, p.Stock,
                (p.Price * p.Stock) AS InventoryValue
         FROM Products p JOIN Categories c ON c.CategoryID = p.CategoryID
         ORDER BY c.CategoryName, p.ProductName'
    );
    $headers = ['ProductID', 'ProductName', 'CategoryName', 'Price', 'Stock', 'InventoryValue'];
    $title = 'Inventory report';
} elseif ($type === 'employees') {
    $rows = fetch_all(
        'SELECT d.DepartmentName, d.Location, e.EmployeeID, e.FirstName, e.LastName, e.JobTitle, e.Role, e.Status, e.Salary
         FROM Departments d
         LEFT JOIN Employees e ON e.DepartmentID = d.DepartmentID
         ORDER BY d.DepartmentName, e.LastName'
    );
    $headers = ['DepartmentName', 'Location', 'EmployeeID', 'FirstName', 'LastName', 'JobTitle', 'Role', 'Status', 'Salary'];
    $title = 'Employees per department';
} else {
    $type = 'sales';
    $rows = fetch_all(
        "SELECT o.OrderID, o.OrderDate, c.CustomerName,
                CONCAT(e.FirstName, ' ', e.LastName) AS Cashier,
                o.Status, o.TotalAmount
         FROM Orders o
         JOIN Customers c ON c.CustomerID = o.CustomerID
         LEFT JOIN Employees e ON e.EmployeeID = o.EmployeeID
         WHERE o.OrderDate >= ? AND o.OrderDate <= ?
         ORDER BY o.OrderDate",
        [$from . ' 00:00:00', $to . ' 23:59:59']
    );
    $headers = ['OrderID', 'OrderDate', 'CustomerName', 'Cashier', 'Status', 'TotalAmount'];
    $title = 'Sales report';
}

if ($export === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $type . '-report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        $line = [];
        foreach ($headers as $h) {
            $line[] = $row[$h] ?? '';
        }
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

if ($export === 'pdf') {
    render_header($title, $user);
    echo '<div class="no-print mb-3"><button class="btn btn-pine" onclick="printPage()">Print / Save as PDF</button></div>';
    echo '<div class="panel"><h2>' . h($title) . '</h2>';
    if ($type === 'sales') {
        echo '<p>' . h($from) . ' to ' . h($to) . '</p>';
    }
    echo '<table class="table table-sm"><thead><tr>';
    foreach ($headers as $h) {
        echo '<th>' . h($h) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($headers as $h) {
            echo '<td>' . h((string) ($row[$h] ?? '')) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
    render_footer();
    exit;
}

render_header('Reports', $user);
?>
<form class="panel mb-3 row g-2 align-items-end" method="get">
    <div class="col-md-3">
        <label class="form-label">Report</label>
        <select class="form-select" name="type">
            <option value="sales" <?= $type === 'sales' ? 'selected' : '' ?>>Sales by date range</option>
            <option value="inventory" <?= $type === 'inventory' ? 'selected' : '' ?>>Inventory</option>
            <option value="employees" <?= $type === 'employees' ? 'selected' : '' ?>>Employees per department</option>
        </select>
    </div>
    <div class="col-md-2"><label class="form-label">From</label><input class="form-control" type="date" name="from" value="<?= h($from) ?>"></div>
    <div class="col-md-2"><label class="form-label">To</label><input class="form-control" type="date" name="to" value="<?= h($to) ?>"></div>
    <div class="col-md-5 d-flex gap-2">
        <button class="btn btn-pine">View</button>
        <a class="btn btn-outline-secondary" href="?<?= h(http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>">CSV</a>
        <a class="btn btn-outline-secondary" href="?<?= h(http_build_query(array_merge($_GET, ['export' => 'pdf']))) ?>">PDF / Print</a>
    </div>
</form>
<div class="panel table-responsive">
    <table class="table align-middle">
        <thead><tr><?php foreach ($headers as $h): ?><th><?= h($h) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($headers as $h): ?>
                    <td><?= h((string) ($row[$h] ?? '')) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($type === 'sales'): ?>
        <?php
        $completed = 0;
        foreach ($rows as $row) {
            if ($row['Status'] === 'Completed') {
                $completed += (float) $row['TotalAmount'];
            }
        }
        ?>
        <p class="mb-0"><strong>Completed sales in range:</strong> <?= money_php($completed) ?></p>
    <?php endif; ?>
</div>
<?php render_footer();
