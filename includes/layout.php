<?php
declare(strict_types=1);

function nav_items(string $role): array
{
    if ($role === 'Admin') {
        return [
            ['admin/dashboard.php', 'bi-speedometer2', 'Dashboard'],
            ['admin/products.php', 'bi-box-seam', 'Products'],
            ['admin/categories.php', 'bi-tags', 'Categories'],
            ['admin/orders.php', 'bi-receipt', 'Orders'],
            ['admin/customers.php', 'bi-people', 'Customers'],
            ['admin/employees.php', 'bi-person-badge', 'Employees'],
            ['admin/departments.php', 'bi-building', 'Departments'],
            ['admin/reports.php', 'bi-graph-up', 'Reports'],
        ];
    }
    if ($role === 'Cashier') {
        return [
            ['cashier/pos.php', 'bi-cash-register', 'Point of Sale'],
            ['cashier/orders.php', 'bi-clock-history', 'Orders'],
            ['cashier/products.php', 'bi-box-seam', 'Stock list'],
        ];
    }
    return [
        ['customer/shop.php', 'bi-shop', 'Shop'],
        ['customer/cart.php', 'bi-bag', 'Cart'],
        ['customer/orders.php', 'bi-clipboard-check', 'My orders'],
        ['customer/profile.php', 'bi-person', 'Profile'],
    ];
}

function current_script(): string
{
    $uri = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = base_url('');
    if ($base !== '' && str_starts_with($uri, $base)) {
        return ltrim(substr($uri, strlen($base)), '/');
    }
    return ltrim($uri, '/');
}

function render_header(string $title, array $user): void
{
    $role = $user['role'];
    $items = nav_items($role);
    $current = current_script();
    $flash = flash_get();
    $cartCount = 0;
    if ($role === 'Customer') {
        $cartCount = array_sum(array_map('intval', cart()));
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · PawBowl</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= h(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="app-body">
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="<?= h(base_url($items[0][0])) ?>">
            <span class="brand-mark">🐾</span>
            <span>
                <strong>PawBowl</strong>
                <small>Pet food ordering</small>
            </span>
        </a>
        <nav class="side-nav">
            <?php foreach ($items as [$href, $icon, $label]): ?>
                <a class="side-link <?= $current === $href ? 'active' : '' ?>" href="<?= h(base_url($href)) ?>">
                    <i class="bi <?= h($icon) ?>"></i>
                    <span><?= h($label) ?></span>
                    <?php if ($label === 'Cart' && $cartCount > 0): ?>
                        <span class="badge-count"><?= (int) $cartCount ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="side-user">
            <div>
                <strong><?= h($user['name']) ?></strong>
                <small><?= h($role) ?></small>
            </div>
            <a class="btn btn-sm btn-outline-light" href="<?= h(base_url('logout.php')) ?>">Logout</a>
        </div>
    </aside>
    <div class="app-main">
        <header class="topbar">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav">
                <i class="bi bi-list"></i>
            </button>
            <h1><?= h($title) ?></h1>
        </header>
        <div class="offcanvas offcanvas-start d-lg-none" id="mobileNav">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
                <?php foreach ($items as [$href, $icon, $label]): ?>
                    <a class="d-block py-2" href="<?= h(base_url($href)) ?>"><?= h($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <main class="page-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= h($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
    <?php
}

function render_footer(bool $charts = false): void
{
    ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($charts): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script src="<?= h(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
    <?php
}
