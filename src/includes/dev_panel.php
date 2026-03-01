<div class="fixed-bottom bg-dark text-light border border-dark border-2">
    <ul class="d-flex align-items-center gap-3 mb-0 list-unstyled">
    <?php
    function convert($size): string
    {
        $unit=array('b','KiB','MiB','GiB','TiB','PiB');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    $queryString = $_SERVER['QUERY_STRING'];
    $queryString = htmlspecialchars($queryString, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    parse_str($queryString, $queryParams);

    $status = http_response_code();
    switch ($status) {
        case 200:
    ?>
            <li>
                <span title="HTTP status" class="btn btn-success m-2 p-2">
                    <?php echo $status; ?> OK
                </span>
            </li>
        <?php
            break;
        case 400:
            ?>
            <li>
                <span title="HTTP status" class="btn btn-danger m-2 p-2">
                    <?php echo $status; ?> Bad Request
                </span>
            </li>
        <?php
            break;
        case 403:
            ?>
            <li>
                <span title="HTTP status" class="btn btn-danger m-2 p-2">
                    <?php echo $status; ?> Forbidden
                </span>
            </li>
        <?php
            break;
        case 404:
        ?>
            <li>
                <span title="HTTP status" class="btn btn-warning m-2 p-2">
                    <?php echo $status; ?> Not Found
                </span>
            </li>
        <?php
            break;
        case 500:
        ?>
            <li>
                <span title="HTTP status" class="btn btn-danger m-2 p-2">
                    <?php echo $status; ?> Internal Server Error
                </span>
            </li>
        <?php
            break;
        default:
        ?>
            <li>
                <span title="HTTP status" class="btn btn-warning m-2 p-2">
                    <?php echo $status; ?> Unknown Status
                </span>
            </li>
        <?php
        }
        ?>
        <li>
            <span title="Authenticated user" class="btn-dev"><?php echo htmlspecialchars($_SERVER['PHP_AUTH_USER'] ?? 'n/a'); ?></span>
        </li>

        <li class="align-items-end">
            <span title="Current page" class="btn-dev"><?php echo htmlspecialchars($queryParams['page'] ?? 'n/a'); ?></span>
        </li>

        <li>
            <span title="Request time" class="btn-dev"><?php echo round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . " ms"; ?></span>
        </li>

        <li>
            <span title="Memory usage" class="btn-dev"><?php echo convert(memory_get_usage(true));?></span>
        </li>

        <div class="ms-auto d-flex gap-3">
            <li>
                <span title="Session ID" class="btn-dev"><?php echo session_status() === PHP_SESSION_ACTIVE ? session_id() : 'no session'; ?></span>
            </li>
            <li>
                <?php
                require_once __DIR__ . '/../config.php';

                function serviceIndicator(bool $status): string {
                    $color = $status ? 'success' : 'danger';
                    $icon = $status ? 'Up' : 'Down';
                    return "<span class=\"btn btn-{$color} btn-sm\">$icon</span>";
                }

                $dbStatus = isset($pdo)
                        ? '<span class="text-success">✔ Connected</span>'
                        : '<span class="text-danger">✘ ' . htmlspecialchars($pdoError ?? 'Not connected') . '</span>';

                $opcacheState = function_exists('opcache_get_status') && opcache_get_status() !== false;
                $mailStatus = function_exists('mail');

                $isDbRunning = serviceIndicator($dbCurrentState);
                $isOpcacheRunning = serviceIndicator($opcacheState);
                $isMailRunning = serviceIndicator($mailStatus);


                $dbInfo = '
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td><strong>Status:</strong></td><td>' . $dbStatus . '</td></tr>
                        <tr><td><strong>Host</strong></td><td>' . $host . '</td></tr>
                        <tr><td><strong>Database</strong></td><td>' . $db . '</td></tr>
                        <tr><td><strong>User</strong></td><td>' . $user . '</td></tr>
                        <tr><td><strong>Charset</strong></td><td>' . $charset . '</td></tr>
                    </table>
                ';
                $phpInfo = '
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td><strong>PHP version:</strong></td><td>' . PHP_VERSION . '</td></tr>
                        <tr><td><strong>PHP SAPI</strong></td><td>' . php_sapi_name() . '</td></tr>
                        <tr><td><strong>Database: </strong></td><td>' . $isDbRunning . '</td></tr>
                        <tr><td><strong>Opcache: </strong></td><td>' . $isOpcacheRunning . '</td></tr>
                        <tr><td><strong>Mail: </strong></td><td>' . $isMailRunning . '</td></tr>
                    </table>
                ';

                ?>
                <span
                        class="btn-dev db-info"
                        data-bs-toggle="popover"
                        data-bs-trigger="hover"
                        data-bs-placement="top"
                        data-bs-html="true"
                        data-bs-content="<?php echo htmlspecialchars($dbInfo); ?>"
                >
                    <?php echo isset($pdo) ? 'DB connected' : 'DB not connected'; ?>
                </span>
            </li>
            <li>
                <span class="btn-dev db-info"
                data-bs-toggle="popover"
                data-bs-trigger="hover"
                data-bs-placement="top"
                data-bs-html="true"
                data-bs-content="<?php echo htmlspecialchars($phpInfo); ?>">
                    version
                </span>
            </li>
        </div>
    </ul>
</div>

<script src="../scripts/dev.js"></script>