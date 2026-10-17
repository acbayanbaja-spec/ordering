<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('customer/shop.php');
}

$errors = [];
$values = [
    'CustomerName' => '',
    'City' => '',
    'Country' => 'Philippines',
    'Email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $values['CustomerName'] = trim((string) post('CustomerName'));
    $values['City'] = trim((string) post('City'));
    $values['Country'] = trim((string) post('Country'));
    $values['Email'] = trim((string) post('Email'));
    $password = (string) post('password');
    $confirm = (string) post('confirm');

    if (strlen($values['CustomerName']) < 2) {
        $errors[] = 'Name must be at least 2 characters.';
    }
    if ($values['City'] === '' || $values['Country'] === '') {
        $errors[] = 'City and country are required.';
    }
    if (!validate_email($values['Email'])) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    $exists = fetch_one('SELECT CustomerID FROM Customers WHERE Email = ?', [$values['Email']]);
    if ($exists) {
        $errors[] = 'That email is already registered.';
    }

    if (!$errors) {
        exec_sql(
            'INSERT INTO Customers (CustomerName, City, Country, Email, Password) VALUES (?, ?, ?, ?, ?)',
            [
                $values['CustomerName'],
                $values['City'],
                $values['Country'],
                $values['Email'],
                password_hash($password, PASSWORD_DEFAULT),
            ]
        );
        flash_set('success', 'Account created. You can log in now.');
        redirect('index.php?tab=customer');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register · PawBowl</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= h(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
<div class="container py-5" style="max-width: 560px;">
    <div class="auth-card m-0">
        <h1 class="h3">Create customer account</h1>
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><?= h($err) ?></div>
        <?php endforeach; ?>
        <form method="post" class="needs-validation" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Full name</label>
                <input class="form-control" name="CustomerName" required minlength="2" value="<?= h($values['CustomerName']) ?>">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">City</label>
                    <input class="form-control" name="City" required value="<?= h($values['City']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Country</label>
                    <input class="form-control" name="Country" required value="<?= h($values['Country']) ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="Email" required value="<?= h($values['Email']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input class="form-control" type="password" name="password" required minlength="8">
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm password</label>
                <input class="form-control" type="password" name="confirm" required minlength="8">
            </div>
            <button class="btn btn-clay w-100" type="submit">Register</button>
        </form>
        <p class="mt-3 mb-0"><a href="<?= h(base_url('index.php?tab=customer')) ?>">Back to login</a></p>
    </div>
</div>
<script src="<?= h(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
