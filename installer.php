<?php
declare(strict_types=1);

use App\Config;

require __DIR__ . '/vendor/autoload.php';


$config = new Config();

function loadQuery($filename): string
{
    return file_get_contents($filename);
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

function queryRun($query): void
{
    try {
        $config = new Config();
        $pdo = $config->getPDO();
        $statements = splitSqlStatements($query);
        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }
    } catch (PDOException $e) {
        echo "Error executing query: " . $e->getMessage() . "\n";
    }
}

if (PHP_SAPI === 'cli' && PHP_OS_FAMILY === 'Linux') {
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

    echo "Done!\n";
}