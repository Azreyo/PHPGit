<div class="fixed-bottom bg-dark text-light border border-dark border-2">
    <ul class="d-flex align-items-center gap-3 mb-0 list-unstyled">
    <?php
    function convert($size): string
    {
        $unit=array('b','KiB','MiB','GiB','TiB','PiB');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    $status = http_response_code();
    switch ($status) {
        case 200:
            ?>
        <li>
            <span class="btn btn-success m-2 p-2">
                <?php echo $status; ?> OK
            </span>
        </li>
            <?php
            break;
        case 400:
            ?>
        <li>
            <span class="btn btn-danger m-2 p-2">
                <?php echo $status; ?> Bad Request
            </span>
        </li>
            <?php
            break;
        default:
            ?>
        <li>
            <span class="btn btn-warning m-2 p-2">
                <?php echo $status; ?> Unknown Status
            </span>
        </li>
        <?php
    }
    ?>
        <li>
            <span class="btn-dev"><?php echo $_SERVER['PHP_AUTH']?? 'n/a';?></span>
        </li>

        <li class="align-items-end">
            <span class="btn-dev"><?php echo $_SERVER['QUERY_STRING'];?></span>
        </li>

        <li>
            <span class="btn-dev"><?php echo microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]; ?></span>
        </li>

        <li>
            <span class="btn-dev"><?php echo convert(memory_get_usage(true));?></span>
        </li>
    </ul>
</div>