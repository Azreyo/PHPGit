<?php
declare(strict_types=1);

use App\includes\Assets;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <script>(function () {
            let t = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', t);
        })();</script>
    <meta charset="UTF-8">
    <title><?= $page_title ?></title>
    <meta name="description" content="PHPGit">
    <meta name="keywords" content="git, php">
    <meta name="author" content="Azreyo">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= Assets::url('assets/css/style.css') ?>">
    <?php if (in_array($page, ['login', 'register'], true)): ?>
        <link rel="stylesheet" href="<?= Assets::url('assets/css/terminal.css') ?>">
    <?php endif; ?>
    <?php if ($is_dev): ?>
        <link rel="stylesheet" href="<?= Assets::url('assets/css/dev.css') ?>">
    <?php endif; ?>

    <?php if ($is_logged_in && $role === 'ADMIN'): ?>
        <link rel="stylesheet" href="<?= Assets::url('assets/css/admin.css') ?>">
    <?php endif; ?>
</head>
<body>

<?php
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../pages/' . htmlspecialchars($page, ENT_QUOTES, 'UTF-8') . '.php';
include __DIR__ . '/../includes/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
<script src="<?= Assets::url('assets/js/theme.js') ?>"></script>
<?php if (in_array($page, ['login', 'register'], true)): ?>
    <script src="<?= Assets::url('assets/js/terminal-animation.js') ?>"></script>
<?php endif; ?>
<?php $this->renderDevPanel(); ?>

</body>
</html>
