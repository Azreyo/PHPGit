<?php declare(strict_types=1);

$username = $_SESSION['username'] ?? '';
?>

<main>
    <div class="container">
        <h1>Welcome Home! <?php echo htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h1>
        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias, aspernatur atque doloribus excepturi ipsum iusto libero nam nisi quibusdam quo, tempore totam vero voluptatibus?</p>
    </div>
</main>