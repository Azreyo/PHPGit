<?php
$page = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'about', 'contact'];

if(!in_array($page, $allowed_pages)) {
    $page = 'home';
}
?>

<?php include 'includes/header.php'; ?>
<main class="container">
    <?php include "pages/$page.php"; ?>
</main>

<?php include 'includes/footer.php'; ?>

