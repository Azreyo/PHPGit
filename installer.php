<?php
declare(strict_types=1);

use App\Config;
use App\includes\Security;

require __DIR__ . '/vendor/autoload.php';


$config = new Config();

function loadQuery($filename): string
{
    return file_get_contents($filename);
}

function envValue(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    if (!is_string($value)) {
        return $default;
    }

    return trim($value);
}

function envBool(string $key, bool $default = false): bool
{
    $value = envValue($key);
    if ($value === null || $value === '') {
        return $default;
    }

    return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
}

function sanitizeHost(string $host): string
{
    $clean = preg_replace('/[^a-zA-Z0-9.-]/', '', strtolower($host));
    return $clean !== '' ? $clean : 'phpgit.local';
}

function askInput(string $prompt, ?string $default = null): string
{
    if ($default !== null && $default !== '') {
        echo $prompt . " [{$default}]: ";
    } else {
        echo $prompt . ': ';
    }

    $value = trim((string)fgets(STDIN));
    if ($value === '' && $default !== null) {
        return $default;
    }

    return $value;
}

function askYesNo(string $prompt, bool $default = false): bool
{
    $defaultLabel = $default ? 'Y/n' : 'y/N';

    while (true) {
        echo $prompt . " ({$defaultLabel}): ";
        $raw = strtolower(trim((string)fgets(STDIN)));

        if ($raw === '') {
            return $default;
        }

        if (in_array($raw, ['y', 'yes'], true)) {
            return true;
        }

        if (in_array($raw, ['n', 'no'], true)) {
            return false;
        }

        echo "Please answer y/yes or n/no.\n";
    }
}

function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);

    $inSingleQuote = false;
    $inDoubleQuote = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';
        $prev = $i > 0 ? $sql[$i - 1] : '';

        if ($inLineComment) {
            $buffer .= $char;
            if ($char === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        if ($inBlockComment) {
            $buffer .= $char;
            if ($char === '*' && $next === '/') {
                $buffer .= '/';
                $inBlockComment = false;
                $i++;
            }
            continue;
        }

        if (!$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
            if ($char === '-' && $next === '-') {
                $inLineComment = true;
                $buffer .= $char;
                continue;
            }

            if ($char === '#') {
                $inLineComment = true;
                $buffer .= $char;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $buffer .= $char;
                continue;
            }
        }

        if ($char === "'" && !$inDoubleQuote && !$inBacktick && $prev !== '\\') {
            $inSingleQuote = !$inSingleQuote;
        } elseif ($char === '"' && !$inSingleQuote && !$inBacktick && $prev !== '\\') {
            $inDoubleQuote = !$inDoubleQuote;
        } elseif ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
            $inBacktick = !$inBacktick;
        }

        if ($char === ';' && !$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }
        $buffer .= $char;
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return $statements;
}

function queryRun(string $query): void
{
    try {
        $config = new Config();
        $pdo = $config->getPdo();
        if ($pdo === null || !$pdo instanceof PDO) {
            throw new RuntimeException('Database connection is not available. Check src/.env DB_* values.');
        }

        $statements = splitSqlStatements($query);
        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }
    } catch (PDOException|RuntimeException $e) {
        echo "Error executing query: " . $e->getMessage() . "\n";
    }
}

function commandExists(string $command): bool
{
    $exitCode = 1;
    exec('command -v ' . escapeshellarg($command) . ' >/dev/null 2>&1', $unused, $exitCode);
    return $exitCode === 0;
}

function ensureDirectory(string $directory): void
{
    if (is_dir($directory)) {
        return;
    }

    if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException("Failed to create directory: {$directory}");
    }
}

function generateSelfSignedCertificate(string $serverName, string $certFile, string $keyFile): bool
{
    if (!commandExists('openssl')) {
        echo "OpenSSL command not found. Skipping certificate generation.\n";
        return false;
    }

    $subject = '/CN=' . $serverName;
    $command = sprintf(
        'openssl req -x509 -nodes -newkey rsa:2048 -sha256 -days 365 -subj %s -keyout %s -out %s 2>/dev/null',
        escapeshellarg($subject),
        escapeshellarg($keyFile),
        escapeshellarg($certFile)
    );

    $exitCode = 1;
    exec($command, $unused, $exitCode);

    if ($exitCode !== 0) {
        echo "Failed to generate self-signed certificate for {$serverName}.\n";
        return false;
    }

    @chmod($keyFile, 0600);
    @chmod($certFile, 0644);
    echo "Generated self-signed certificate at {$certFile}\n";
    echo "Generated private key at {$keyFile}\n";
    return true;
}

function buildApacheConfig(
    string $serverName,
    string $documentRoot,
    bool   $httpsEnabled,
    string $certFile,
    string $keyFile
): string
{
    $apacheLogDir = '${APACHE_LOG_DIR}';

    $httpBlock = <<<CONF
<VirtualHost *:80>
    ServerName {$serverName}
    DocumentRoot {$documentRoot}

    <Directory {$documentRoot}>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog {$apacheLogDir}/phpgit_error.log
    CustomLog {$apacheLogDir}/phpgit_access.log combined
</VirtualHost>
CONF;

    if (!$httpsEnabled) {
        return $httpBlock . "\n";
    }

    $httpsBlock = <<<CONF

<IfModule mod_ssl.c>
    <VirtualHost *:443>
        ServerName {$serverName}
        DocumentRoot {$documentRoot}

        <Directory {$documentRoot}>
            AllowOverride All
            Require all granted
        </Directory>

        SSLEngine on
        SSLCertificateFile {$certFile}
        SSLCertificateKeyFile {$keyFile}

        ErrorLog {$apacheLogDir}/phpgit_ssl_error.log
        CustomLog {$apacheLogDir}/phpgit_ssl_access.log combined
    </VirtualHost>
</IfModule>
CONF;

    return $httpBlock . $httpsBlock . "\n";
}

function writeApacheConfig(string $configPath, string $content): void
{
    $written = file_put_contents($configPath, $content);
    if ($written === false) {
        throw new RuntimeException("Failed to write Apache config: {$configPath}");
    }
}

function extractApacheModulesFromHtaccess(string $htaccessPath, bool $httpsEnabled): array
{
    if (!is_readable($htaccessPath)) {
        throw new RuntimeException("Missing or unreadable .htaccess: {$htaccessPath}");
    }

    $content = file_get_contents($htaccessPath);
    if ($content === false) {
        throw new RuntimeException("Failed to read .htaccess: {$htaccessPath}");
    }

    $modules = [];
    if (preg_match_all('/<IfModule\s+mod_([a-z0-9_]+)\.c>/i', $content, $matches) === 1 || !empty($matches[1])) {
        foreach ($matches[1] as $module) {
            $module = strtolower(trim($module));
            if ($module !== '') {
                $modules[$module] = true;
            }
        }
    }

    if ($httpsEnabled) {
        $modules['ssl'] = true;
    }

    ksort($modules);
    return array_keys($modules);
}

function runShellCommand(string $command): void
{
    $exitCode = 1;
    exec($command . ' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        throw new RuntimeException("Command failed: " . implode("\n", $output));
    }
}

function runShellCommandSafe(string $command): bool
{
    $exitCode = 1;
    exec($command . ' 2>&1', $output, $exitCode);
    if ($exitCode !== 0) {
        echo "Warning: command failed: " . implode("\n", $output) . "\n";
        return false;
    }

    return true;
}

function configureApacheSite(string $apacheConfigPath, string $serverName, bool $httpsEnabled, array $modules): void
{
    if (!commandExists('sudo')) {
        throw new RuntimeException('sudo command not found. Install/configure sudo before running Apache setup.');
    }

    $safeConfigPath = Security::sanitizeShellInput($apacheConfigPath);
    $safeServerName = Security::sanitizeShellInput($serverName);
    $hostsEntry = "127.0.0.1 {$safeServerName}";

    foreach ($modules as $module) {
        runShellCommandSafe('sudo a2enmod ' . escapeshellarg($module));
    }
    runShellCommand(
        'sudo cp ' . escapeshellarg($safeConfigPath) . ' /etc/apache2/sites-available/phpgit.local.conf'
    );
    runShellCommand('sudo a2ensite phpgit.local.conf');
    runShellCommand(
        'grep -qxF ' . escapeshellarg($hostsEntry) .
        ' /etc/hosts || echo ' . escapeshellarg($hostsEntry) . ' | sudo tee -a /etc/hosts >/dev/null'
    );
    runShellCommand('sudo systemctl reload apache2');
}


function installPhpGit(): void
{
    if (PHP_SAPI === 'cli' && PHP_OS_FAMILY === 'Linux') {
        $config = new Config();
        $projectRoot = __DIR__;
        $documentRoot = $projectRoot . '/src';
        $apacheConfigPath = $projectRoot . '/apache/phpgit.local.conf';
        $htaccessPath = $documentRoot . '/.htaccess';
        $certDirectory = $documentRoot . '/certs';
        $assetsDirectory = $documentRoot . '/assets';
        $manifestPath = $assetsDirectory . '/manifest.json';

        $defaultHost = sanitizeHost(envValue('APP_HOST', 'phpgit.local') ?? 'phpgit.local');
        $serverName = sanitizeHost(askInput('Apache ServerName', $defaultHost));
        $httpsEnabled = askYesNo('Enable HTTPS', envBool('HTTPS'));
        $generateSelfSigned = false;
        $certFile = $certDirectory . '/' . $serverName . '.crt';
        $keyFile = $certDirectory . '/' . $serverName . '.key';

        if ($httpsEnabled) {
            $generateSelfSigned = askYesNo('Generate self-signed SSL certificate', true);

            if (!$generateSelfSigned) {
                $certFile = askInput('Path to existing SSL certificate (.crt)', $certFile);
                $keyFile = askInput('Path to existing SSL private key (.key)', $keyFile);
            }
        }

        $scheme = "phpgit_scheme.sql";

        echo "Connecting to {$config->getDb()} as {$config->getDbUser()}@{$config->getHost()}\n";
        echo "Creating table...\n";
        if (file_exists($scheme)) {
            $query_scheme = loadQuery($scheme);
            queryRun($query_scheme);
        }

        echo "Do you want to load data to database? (y/n): ";
        $input = trim(fgets(STDIN));
        if ($input === 'y') {
            $data = "phpgit_data.sql";
            if (file_exists($data)) {
                $query_data = loadQuery($data);
                queryRun($query_data);
            }
        }

        try {
            $apacheModules = extractApacheModulesFromHtaccess($htaccessPath, $httpsEnabled);

            ensureDirectory(dirname($apacheConfigPath));

            if ($httpsEnabled && $generateSelfSigned) {
                ensureDirectory($certDirectory);
                generateSelfSignedCertificate($serverName, $certFile, $keyFile);
            }

            $apacheConfig = buildApacheConfig($serverName, $documentRoot, $httpsEnabled, $certFile, $keyFile);
            writeApacheConfig($apacheConfigPath, $apacheConfig);

            echo "Apache config generated at {$apacheConfigPath}\n";
            echo "HTTPS enabled: " . ($httpsEnabled ? 'true' : 'false') . "\n";
            echo "Modules enabled from .htaccess: " . implode(', ', $apacheModules) . "\n";
            configureApacheSite($apacheConfigPath, $serverName, $httpsEnabled, $apacheModules);
            echo "Apache site configured successfully.\n";
        } catch (RuntimeException $e) {
            echo "Configuration error: " . $e->getMessage() . "\n";
        }

        try {
            if (!is_dir($assetsDirectory)) {
                if (mkdir($assetsDirectory, 0755, true)) {
                    echo "Created assets directory at {$assetsDirectory}\n";
                } else {
                    echo "Failed to create assets directory at {$assetsDirectory}. Check permissions.\n";
                }
            }
            if (!file_exists($manifestPath)) {
                if (file_put_contents($manifestPath, '') !== false) {
                    echo "Copied manifest.json to {$manifestPath}\n";
                } else {
                    echo "Failed to copy manifest.json to {$manifestPath}. Check permissions.\n";
                }
            }

        } catch (RuntimeException $e) {
            echo "Error creating assets directory: " . $e->getMessage() . "\n";
        }

        echo "\n--- Git Server Setup ---\n";
        $setupGitServer = askYesNo('Set up SSH git server (creates a system git user)', true);

        if ($setupGitServer) {
            installGitServer($projectRoot);
        } else {
            echo "Skipping git server setup.\n";
        }

        echo "Done!\n";
    }
}

/**
 * Set up the git system user and SSH infrastructure.
 */
function installGitServer(string $projectRoot): void
{
    if (!commandExists('sudo')) {
        echo "sudo not found – skipping git server setup.\n";
        return;
    }

    $gitUser = askInput('Git system username', 'git');
    $gitUser = preg_replace('/[^a-z0-9_-]/', '', strtolower($gitUser)) ?: 'git';
    $dataRoot = askInput('Data root (bare repos storage path)', $projectRoot . '/data');
    $wrapperPath = $projectRoot . '/bin/git-shell-wrapper.php';
    $gitShell = trim((string)shell_exec('which git-shell')) ?: '/usr/bin/git-shell';
    $phpBin = PHP_BINARY;

    exec('id ' . escapeshellarg($gitUser) . ' >/dev/null 2>&1', $unused, $exitCode);
    if ($exitCode !== 0) {
        echo "Creating system user '{$gitUser}'…\n";
        runShellCommandSafe(
            'sudo adduser --system --shell ' . escapeshellarg($gitShell) .
            ' --gecos ' . escapeshellarg('PHPGit SSH user') .
            ' --group --disabled-password ' . escapeshellarg($gitUser)
        );
    } else {
        echo "System user '{$gitUser}' already exists.\n";
    }

    $homeDir = trim((string)shell_exec('getent passwd ' . escapeshellarg($gitUser) . " | cut -d: -f6")) ?: "/home/{$gitUser}";

    $sshDir = $homeDir . '/.ssh';
    $authorizedKeys = $sshDir . '/authorized_keys';

    runShellCommandSafe('sudo mkdir -p ' . escapeshellarg($sshDir));
    runShellCommandSafe('sudo touch ' . escapeshellarg($authorizedKeys));
    runShellCommandSafe('sudo chown -R ' . escapeshellarg($gitUser . ':' . $gitUser) . ' ' . escapeshellarg($sshDir));
    runShellCommandSafe('sudo chmod 700 ' . escapeshellarg($sshDir));
    runShellCommandSafe('sudo chmod 600 ' . escapeshellarg($authorizedKeys));

    echo "SSH directory configured at {$sshDir}\n";

    if (is_file($wrapperPath)) {
        chmod($wrapperPath, 0755);
    }

    if (!is_dir($dataRoot)) {
        runShellCommandSafe('sudo mkdir -p ' . escapeshellarg($dataRoot));
    }
    runShellCommandSafe('sudo chown -R ' . escapeshellarg($gitUser . ':' . $gitUser) . ' ' . escapeshellarg($dataRoot));
    runShellCommandSafe('sudo chmod 755 ' . escapeshellarg($dataRoot));

    $envFile = $projectRoot . '/src/.env';
    if (is_file($envFile)) {
        $envContent = file_get_contents($envFile) ?: '';
        $updates = [
            'GIT_SYSTEM_USER' => $gitUser,
            'AUTHORIZED_KEYS_PATH' => $authorizedKeys,
            'GIT_SHELL_WRAPPER' => $wrapperPath,
            'DATA_ROOT' => $dataRoot,
            'SSH_HOST' => $_ENV['APP_HOST'] ?? 'phpgit.local',
        ];
        foreach ($updates as $key => $value) {
            $escapedVal = str_replace(['"', '$'], ['\\"', '\\$'], $value);
            if (preg_match('/^' . preg_quote($key, '/') . '=/m', $envContent)) {
                $envContent = preg_replace(
                    '/^' . preg_quote($key, '/') . '=.*/m',
                    $key . '=' . $escapedVal,
                    $envContent
                ) ?? $envContent;
            } else {
                $envContent .= "\n{$key}={$escapedVal}";
            }
        }
        file_put_contents($envFile, $envContent);
        echo ".env updated with git server variables.\n";
    }

    echo "\n✔  Git server setup complete.\n";
    echo "   Bare repos will be stored in:   {$dataRoot}\n";
    echo "   authorized_keys file:            {$authorizedKeys}\n";
    echo "   Shell wrapper:                   {$wrapperPath}\n";
    echo "\n   SSH clone URL format:\n";
    echo "   {$gitUser}@<host>:username/repository.git\n\n";
    echo "   HTTP clone URL format (after setting up Apache):\n";
    echo "   http://<host>/username/repository.git\n\n";
}

installPhpGit();