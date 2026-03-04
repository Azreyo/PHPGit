<?php

declare(strict_types=1);

function convert(int $size): string
{
    $unit = ['b', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
    $i    = (int) floor(log($size, 1024));

    return round($size / pow(1024, $i), 2) . ' ' . $unit[$i];
}

function serviceIndicator(bool $status): string
{
    $color = $status ? 'success' : 'danger';
    $icon  = $status ? 'Up' : 'Down';

    return "<span class=\"btn btn-{$color} btn-sm\">{$icon}</span>";
}

function checkStatus(bool $status): string {
    $color = $status ? 'success' : 'danger';
    $icon  = $status ? 'Yes' : 'No';

    return "<span class=\"btn btn-{$color} btn-sm\">{$icon}</span>";
}

?>
<div class="fixed-bottom bg-dark text-light border border-dark border-2">
    <ul class="d-flex align-items-center gap-3 mb-0 list-unstyled">
    <?php

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
    $auth_username = $_SESSION['username'] ?? 'n/a';
        ?>
        <li>
            <span title="Authenticated user"
                  class="btn-dev"><?php echo htmlspecialchars((string)$auth_username); ?></span>
        </li>

        <li class="align-items-end">
            <span title="Current page"
                  class="btn-dev"><?php echo htmlspecialchars($_GET['page'] ?? 'n/a', ENT_QUOTES, 'UTF-8'); ?></span>
        </li>

        <li>
            <span title="Request time" class="btn-dev"><?php echo round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . ' ms'; ?></span>
        </li>

        <li>
            <span title="Memory usage" class="btn-dev"><?php echo convert(memory_get_usage(true)); ?></span>
        </li>

        <div class="ms-auto d-flex gap-3">
            <?php
            require_once __DIR__ . '/../config.php';

            $db_status = isset($pdo)
                    ? '<span class="text-success">✔ Connected</span>'
                    : '<span class="text-danger">✘ ' . htmlspecialchars($pdo_error ?? 'Not connected', ENT_QUOTES, 'UTF-8') . '</span>';

            $opcache_state = function_exists('opcache_get_status') && opcache_get_status() !== false;
            $mail_status = function_exists('mail');

            $is_db_running = serviceIndicator($db_current_state);
            $is_logged_in = checkStatus($_SESSION['is_logged_in'] ?? false);
            $is_opcache_running = serviceIndicator($opcache_state);
            $is_mail_running = serviceIndicator($mail_status);
            $session_username = $_SESSION['username'] ?? 'n/a';
            $session_role = $_SESSION['role'] ?? 'n/a';

            $db_info = '
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td><strong>Status:</strong></td><td>' . $db_status . '</td></tr>
                        <tr><td><strong>Host</strong></td><td>' . htmlspecialchars($host, ENT_QUOTES, 'UTF-8') . '</td></tr>
                        <tr><td><strong>Database</strong></td><td>' . htmlspecialchars($db, ENT_QUOTES, 'UTF-8') . '</td></tr>
                        <tr><td><strong>User</strong></td><td>' . htmlspecialchars($db_user, ENT_QUOTES, 'UTF-8') . '</td></tr>
                        <tr><td><strong>Charset</strong></td><td>' . htmlspecialchars($charset, ENT_QUOTES, 'UTF-8') . '</td></tr>
                    </table>
                ';

            $php_info = '
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td><strong>PHP version:</strong></td><td>' . PHP_VERSION . '</td></tr>
                        <tr><td><strong>PHP SAPI</strong></td><td>' . php_sapi_name() . '</td></tr>
                        <tr><td><strong>Database: </strong></td><td>' . $is_db_running . '</td></tr>
                        <tr><td><strong>Opcache: </strong></td><td>' . $is_opcache_running . '</td></tr>
                        <tr><td><strong>Mail: </strong></td><td>' . $is_mail_running . '</td></tr>
                    </table>
                ';

            $session_info = '
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td><strong>Authorized:</strong></td><td>' . $is_logged_in . '</td></tr>
                        <tr><td><strong>Username:</strong></td><td>' . htmlspecialchars((string)$session_username, ENT_QUOTES, 'UTF-8') . '</td></tr>
                        <tr><td><strong>Role:</strong></td><td>' . htmlspecialchars((string)$session_role, ENT_QUOTES, 'UTF-8') . '</td></tr>
                    </table>
                ';
            ?>
            <li>
                <span class="btn-dev db-info"
                      data-bs-toggle="popover"
                      data-bs-trigger="hover"
                      data-bs-placement="top"
                      data-bs-html="true"
                      data-bs-content='<?php echo $session_info; ?>'>
                    <?php echo session_status() === PHP_SESSION_ACTIVE ? session_id() : 'no session'; ?>
                </span>
            </li>
            <li>
                <span
                    class="btn-dev db-info"
                    data-bs-toggle="popover"
                    data-bs-trigger="hover"
                    data-bs-placement="top"
                    data-bs-html="true"
                    data-bs-content='<?php echo $db_info; ?>'
                >
                    <?php echo isset($pdo) ? 'DB connected' : 'DB not connected'; ?>
                </span>
            </li>
            <li>
                <span
                    class="btn-dev db-info"
                    data-bs-toggle="popover"
                    data-bs-trigger="hover"
                    data-bs-placement="top"
                    data-bs-html="true"
                    data-bs-content='<?php echo $php_info; ?>'
                >
                    version
                </span>
            </li>
        </div>
    </ul>
</div>

<script src="/scripts/dev.js"></script>

