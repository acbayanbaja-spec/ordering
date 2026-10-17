<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role('Admin');

$sales = fetch_one("SELECT COALESCE(SUM(TotalAmount),0) AS t FROM Orders WHERE Status = 'Completed'")['t'];
$orders = fetch_one('SELECT COUNT(*) AS c FROM Orders')['c'];
$customers = fetch_one('SELECT COUNT(*) AS c FROM Customers')['c'];
$lowStock = fetch_one('SELECT COUNT(*) AS c FROM Products WHERE Stock <= ? AND Stock > 0', [LOW_STOCK_THRESHOLD])['c'];
$outStock = fetch_one('SELECT COUNT(*) AS c FROM Products WHERE Stock = 0')['c'];
$activeEmp = fetch_one("SELECT COUNT(*) AS c FROM Employees WHERE Status = 'Active'")['c'];

$lowItems = fetch_all(
    'SELECT ProductID, ProductName, Stock FROM Products WHERE Stock <= ? ORDER BY Stock ASC, ProductName',
    [LOW_STOCK_THRESHOLD]
);

$salesMonth = fetch_all(
    "SELECT DATE_FORMAT(OrderDate, '%Y-%m') AS ym, SUM(TotalAmount) AS total
     FROM Orders WHERE Status = 'Completed'
     GROUP BY ym ORDER BY ym"
);
$salesCat = fetch_all(
    "SELECT c.CategoryName, SUM(od.Quantity * od.UnitPrice) AS total
     FROM OrderDetails od
     JOIN Products p ON p.ProductID = od.ProductID
     JOIN Categories c ON c.CategoryID = p.CategoryID
     JOIN Orders o ON o.OrderID = od.OrderID
     WHERE o.Status = 'Completed'
     GROUP BY c.CategoryID, c.CategoryName
     ORDER BY total DESC"
);
$topProducts = fetch_all(
    "SELECT p.ProductName, SUM(od.Quantity) AS qty
     FROM OrderDetails od
     JOIN Products p ON p.ProductID = od.ProductID
     JOIN Orders o ON o.OrderID = od.OrderID
     WHERE o.Status = 'Completed'
     GROUP BY p.ProductID, p.ProductName
     ORDER BY qty DESC
     LIMIT 5"
);
$statusBreak = fetch_all(
    'SELECT Status, COUNT(*) AS c FROM Orders GROUP BY Status'
);

render_header('Admin dashboard', $user);
?>
<div class="stat-grid mb-4">
    <div class="stat-card"><span>Total sales</span><strong><?= money_php((float) $sales) ?></strong></div>
    <div class="stat-card"><span>Orders</span><strong><?= (int) $orders ?></strong></div>
    <div class="stat-card"><span>Customers</span><strong><?= (int) $customers ?></strong></div>
    <div class="stat-card"><span>Low stock</span><strong><?= (int) $lowStock ?></strong></div>
    <div class="stat-card"><span>Active employees</span><strong><?= (int) $activeEmp ?></strong></div>
</div>

<?php if ($lowItems): ?>
    <div class="alert alert-warning">
        <strong>Low-stock alerts</strong> (<?= (int) $outStock ?> out of stock).
        Threshold is <?= (int) LOW_STOCK_THRESHOLD ?> units.
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-lg-6"><div class="panel"><h2 class="h5">Sales per month</h2><canvas id="chartMonth"></canvas></div></div>
    <div class="col-lg-6"><div class="panel"><h2 class="h5">Sales per category</h2><canvas id="chartCat"></canvas></div></div>
    <div class="col-lg-6"><div class="panel"><h2 class="h5">Top 5 best-selling products</h2><canvas id="chartTop"></canvas></div></div>
    <div class="col-lg-6"><div class="panel"><h2 class="h5">Order status</h2><canvas id="chartStatus"></canvas></div></div>
</div>

<div class="panel">
    <h2 class="h5">Inventory watch</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Product</th><th>Stock</th></tr></thead>
            <tbody>
            <?php foreach ($lowItems as $item): ?>
                <tr>
                    <td><?= h($item['ProductName']) ?></td>
                    <td class="<?= (int) $item['Stock'] === 0 ? 'stock-out' : 'stock-low' ?>">
                        <?= (int) $item['Stock'] === 0 ? 'Out of stock' : (int) $item['Stock'] ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$lowItems): ?>
                <tr><td colspan="2">All products are above the low-stock threshold.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
  const month = <?= json_encode($salesMonth) ?>;
  const cats = <?= json_encode($salesCat) ?>;
  const top = <?= json_encode($topProducts) ?>;
  const st = <?= json_encode($statusBreak) ?>;
  const pine = '#2f5d50';
  const clay = '#c45c26';
  const gold = '#e2b15a';

  new Chart(document.getElementById('chartMonth'), {
    type: 'line',
    data: {
      labels: month.map(r => r.ym),
      datasets: [{ label: 'Sales', data: month.map(r => Number(r.total)), borderColor: pine, tension: 0.3 }]
    }
  });
  new Chart(document.getElementById('chartCat'), {
    type: 'bar',
    data: {
      labels: cats.map(r => r.CategoryName),
      datasets: [{ label: 'Sales', data: cats.map(r => Number(r.total)), backgroundColor: clay }]
    }
  });
  new Chart(document.getElementById('chartTop'), {
    type: 'bar',
    data: {
      labels: top.map(r => r.ProductName),
      datasets: [{ label: 'Qty sold', data: top.map(r => Number(r.qty)), backgroundColor: gold }]
    },
    options: { indexAxis: 'y' }
  });
  new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
      labels: st.map(r => r.Status),
      datasets: [{ data: st.map(r => Number(r.c)), backgroundColor: [pine, clay, '#6e6256'] }]
    }
  });
});
</script>
<?php
render_footer(true);
