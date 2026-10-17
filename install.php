<?php
declare(strict_types=1);

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$sqlFile = __DIR__ . '/sql/schema.sql';

$ok = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            throw new RuntimeException('Could not read sql/schema.sql');
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $mysqli = new mysqli($host, $user, $pass);
        $mysqli->set_charset('utf8mb4');
        if (!$mysqli->multi_query($sql)) {
            throw new RuntimeException($mysqli->error);
        }
        while ($mysqli->more_results()) {
            $mysqli->next_result();
        }
        $mysqli->close();
        $ok = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Install PawBowl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
<div class="container" style="max-width: 640px;">
    <h1>Install database</h1>
    <p>This creates the <code>petfood_ordering</code> database and loads sample pet-food data. Default XAMPP MySQL user is <code>root</code> with an empty password.</p>
    <?php if ($ok): ?>
        <div class="alert alert-success">Install complete. You can delete <code>install.php</code> after this.</div>
        <a class="btn btn-success" href="index.php">Go to login</a>
    <?php else: ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <button class="btn btn-primary" type="submit">Run SQL install</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
