<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['is_logged_in'])) {
    $_SESSION['is_logged_in'] = false;
}

require 'config.php';
require __DIR__ . '/includes/error_handler.php';

$page = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'about', 'contact', 'explore', '404', '403', 'login', 'register', 'logout', 'dashboard'];
$restricted_pages = ['.env', '.htaccess', 'config'];
$page_titles = [
    'home'     => 'Home',
    'about'    => 'About us',
    'contact'  => 'Contact',
    'explore'  => 'Explore',
    'login'    => 'Login',
    'register' => 'Register',
    'logout'   => 'Logout',
    '404'      => 'Page not found',
    '403'      => 'Forbidden',
    'dashboard'=> 'Dashboard',
];

$page = preg_replace('/[^a-z0-9_]/', '', strtolower($page));

if (empty($page) || in_array($page, $restricted_pages, true)) {
    $page = '403';
} elseif (!in_array($page, $allowed_pages, true)) {
    $page = '404';
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-bs-theme',t);})();</script>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_titles[$page] ?? 'PHPGit', ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="PHPGit">
    <meta name="keywords" content="git, php">
    <meta name="author" content="Azreyo">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/style.css">
    <?php if ($is_dev): ?>
        <link rel="stylesheet" href="/assets/dev.css">
    <?php endif; ?>
</head>
<body>

<?php
include 'includes/header.php';
include 'pages/' . htmlspecialchars($page, ENT_QUOTES, 'UTF-8') . '.php';
include 'includes/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="/scripts/theme.js"></script>
<?php if (isset($is_dev) && $is_dev): ?>
    <?php include 'includes/dev_panel.php'; ?>
<?php endif; ?>

</body>
</html>