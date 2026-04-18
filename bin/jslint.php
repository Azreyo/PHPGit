#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 *
 * Usage:
 *   php bin/jslint.php [--fix-cache] [<dir>]
 *
 * Options:
 *   --fix-cache   Delete the cached jslint.js and re-download it.
 *   <dir>         Directory to scan (default: src/assets/js).
 *
 * Exit codes:
 *   0 – all files passed
 *   1 – one or more lint errors found
 *   2 – tool/setup error (Node missing, download failed, …)
 */

$posixTty = function_exists('posix_isatty') && posix_isatty(STDOUT);
$ansi = static fn(string $code, string $text): string => $posixTty ? "\033[{$code}m{$text}\033[0m" : $text;

$red = static fn(string $t): string => $ansi('0;31', $t);
$green = static fn(string $t): string => $ansi('0;32', $t);
$yellow = static fn(string $t): string => $ansi('1;33', $t);
$bold = static fn(string $t): string => $ansi('1', $t);

$binDir = __DIR__;
$projectRoot = dirname($binDir);
$jslintCache = $binDir . '/.jslint.mjs';
$jslintUrl = 'https://www.jslint.com/jslint.js';

$args = array_slice($argv, 1);
$fixCache = in_array('--fix-cache', $args, true);
$args = array_values(array_filter($args, static fn($a) => $a !== '--fix-cache'));
$jsDir = $args[0] ?? $projectRoot . '/src/assets/js';

if (!is_dir($jsDir)) {
    fwrite(STDERR, $red("Error: directory not found: {$jsDir}\n"));
    exit(2);
}

echo $bold("JSLint Checker") . "  (no npm)\n";
echo str_repeat('─', 50) . "\n";

exec('node --version 2>&1', $nodeOut, $nodeCode);
if ($nodeCode !== 0) {
    fwrite(STDERR, $red("Error: Node.js is not installed or not in PATH.\n"));
    exit(2);
}
echo 'Node.js : ' . trim($nodeOut[0]) . "\n";

if ($fixCache && file_exists($jslintCache)) {
    unlink($jslintCache);
}

if (!file_exists($jslintCache)) {
    echo "Downloading jslint.js from {$jslintUrl} …\n";
    $ctx = stream_context_create(['http' => ['timeout' => 30, 'user_agent' => 'phpgit-jslint/1.0']]);
    $src = @file_get_contents($jslintUrl, false, $ctx);

    if ($src === false || strlen($src) < 1000) {
        fwrite(STDERR, $red("Error: failed to download jslint.js (check network).\n"));
        exit(2);
    }

    file_put_contents($jslintCache, $src);
    echo 'Downloaded  ' . number_format(strlen($src)) . " bytes → bin/.jslint.mjs\n";
} else {
    echo 'jslint.js  : cached (' . number_format(filesize($jslintCache)) . " bytes)\n";
}

$jslintAbs = realpath($jslintCache);
if ($jslintAbs === false) {
    fwrite(STDERR, $red("Error: cannot resolve path to jslint.js.\n"));
    exit(2);
}

$jslintUrl4node = 'file://' . $jslintAbs;

$runnerSrc = <<<'JS'
import { readFileSync } from "fs";

const { default: jslint } = await import("JSLINT_URL");

const filePath = process.argv[2];
const options  = JSON.parse(process.argv[3] ?? "{}");
const globals  = JSON.parse(process.argv[4] ?? "[]");
const source   = readFileSync(filePath, "utf8");

const result   = jslint(source, options, globals);
const warnings = (result.warnings ?? []).filter(Boolean);

warnings.forEach(function (w) {
    process.stderr.write(
        "  line " + ((w.line ?? 0) + 1) +
        ", col "  + ((w.column ?? 0) + 1) +
        ": "      + w.message + "\n"
    );
});

process.exit(warnings.length > 0 ? 1 : 0);
JS;

$runnerSrc = str_replace('JSLINT_URL', $jslintUrl4node, $runnerSrc);
$runnerFile = tempnam(sys_get_temp_dir(), 'jslint_') . '.mjs';
file_put_contents($runnerFile, $runnerSrc);

$jslintOptions = json_encode([
        'browser' => true,
        'devel' => true,
]);

$jslintGlobals = json_encode(['bootstrap']);

$iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($jsDir, FilesystemIterator::SKIP_DOTS)
);

$jsFiles = [];
foreach ($iterator as $file) {
    if (strtolower($file->getExtension()) === 'js') {
        $jsFiles[] = $file->getPathname();
    }
}
sort($jsFiles);

if ($jsFiles === []) {
    echo $yellow("No JS files found in {$jsDir}\n");
    unlink($runnerFile);
    exit(0);
}

echo "\nChecking " . count($jsFiles) . " file(s) in {$jsDir} …\n\n";

$totalErrors = 0;
$filesWithErr = 0;

foreach ($jsFiles as $jsFile) {
    $relPath = ltrim(str_replace($projectRoot, '', $jsFile), '/');
    echo $bold($relPath) . "\n";

    $cmd = sprintf(
            'node %s %s %s %s 2>&1',
            escapeshellarg($runnerFile),
            escapeshellarg($jsFile),
            escapeshellarg($jslintOptions),
            escapeshellarg($jslintGlobals)
    );

    exec($cmd, $output, $exitCode);

    if ($exitCode === 0) {
        echo '  ' . $green('✓ OK') . "\n";
    } else {
        $filesWithErr++;
        $totalErrors += count($output);
        foreach ($output as $line) {
            echo $red($line) . "\n";
        }
    }

    $output = [];
}

unlink($runnerFile);

echo "\n" . str_repeat('─', 50) . "\n";

if ($filesWithErr === 0) {
    echo $green('All ' . count($jsFiles) . ' file(s) passed JSLint!') . "\n";
    exit(0);
}

echo $red("JSLint found issues in {$filesWithErr}/" . count($jsFiles) . ' file(s)') . "\n";
exit(1);