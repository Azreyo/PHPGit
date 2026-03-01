<?php
    require 'config.php';
    $app_env = $_ENV['APP_ENV'] ?? 'prod';
    require __DIR__ . '/includes/error_handler.php';


    $page = $_GET['page'] ?? 'home';
    $allowed_pages = ['home', 'about', 'contact', 'explore', '404', '403', 'login', 'register'];
    $restricted_pages = ['.env', '.htaccess', 'config'];
    $pageTitles = [
            'home' => 'Home',
            'about' => 'About us',
            'contact' => 'Contact',
            'explore' => 'Explore',
            'login' => 'Login',
            'register' => 'Register',
            '404' => 'Page not found',
            '403' => 'Forbidden'
    ];
    $page = preg_replace('/[^a-z0-9_]/','', strtolower($page));
    if (empty($page) || in_array($page, $restricted_pages, true)) {
        $page = '403';
        http_response_code(403);
    } else if (!in_array($page, $allowed_pages, true)) {
        $page = '404';
        http_response_code(404);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitles[$page] ?? 'PHPGit'); ?></title>
    <meta name="description" content="PHPGit">
    <meta name="keywords" content="git, php">
    <meta name="author" content="Azreyo">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/style.css">
    <?php
    if($app_env === 'dev') {
        ?>
        <link rel="stylesheet" href="assets/dev.css">
        <?php
    }
    ?>

</head>
<body>

<?php include 'includes/header.php'; ?>
<main class="container">
    <?php include "pages/$page.php"; ?>
</main>

<?php
    include 'includes/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<?php
    if(isset($app_env) && $app_env === 'dev') {

        include 'includes/dev_panel.php';
    }
?>
</body>
</html>