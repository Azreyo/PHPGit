<div class="fixed-bottom bg-dark text-light border border-dark border-2">
    <?php
    $status = http_response_code();
    switch ($status) {
        case 200:
            ?>
            <p class="btn btn-success m-2 p-2">
                <?php echo $status; ?> OK
            </p>
            <?php
            break;
        case 400:
            ?>
            <p class="btn btn-danger m-2 p-2">
                <?php echo $status; ?> Bad Request
            </p>
            <?php
            break;
        default:
            ?>
            <p class="btn btn-warning m-2 p-2">
                <?php echo $status; ?> Unknown Status
            </p>
        <?php
    }
    ?>
</div>