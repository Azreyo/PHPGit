<?php
    require __DIR__ . '/../vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();


    $page = $_GET['page'] ?? 'home';
    $allowed_pages = ['home', 'about', 'contact'];

    if(!in_array($page, $allowed_pages, true)) {
        $page = 'home';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHPGit - <?php echo $_GET['page'];?></title>
    <meta name="description" content="PHPGit">
    <meta name="keywords" content="git, php">
    <meta name="author" content="Azreyo">

    <?php
        if($_ENV['APP_ENV'] === 'dev') {
    ?>
        <link rel="stylesheet" href="assets/dev.css">
    <?php
        }
    ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/style.css">

</head>
<body>

<?php include 'includes/header.php'; ?>
<main class="container">
    <?php include "pages/$page.php"; ?>
</main>

<?php
    include 'includes/footer.php';
?>

<?php
    if($_ENV['APP_ENV'] === 'dev') {
        include 'includes/dev_panel.php';
        echo $_ENV['APP_ENV'];
    }
?>

</body>
</html>