<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class GitCommandRunner
{
    private readonly string $repoPath;
    private readonly int $timeout;
    private string $lastError = '';

    public function __construct(string $repoPath, ?int $timeout = null)
    {
        $real = realpath($repoPath);
        if ($real === false || !is_dir($real) || !is_file($real . '/HEAD') || !is_dir($real . '/objects')) {
            throw new RuntimeException('Invalid bare repository path.');
        }
        $this->repoPath = $real;
        $configured = (int)($_ENV['GIT_COMMAND_TIMEOUT'] ?? 15);
        $this->timeout = max(1, min($timeout ?? $configured, 300));
    }

    /** @param list<string> $arguments */
    public function run(array $arguments, int &$exitCode = 0, ?int $maxBytes = null): string
    {
        $output = '';
        $exitCode = $this->execute($arguments, static function (string $chunk) use (&$output, $maxBytes): void {
            if ($maxBytes !== null) {
                $remaining = $maxBytes - strlen($output);
                if ($remaining <= 0) {
                    return;
                }
                $chunk = substr($chunk, 0, $remaining);
            }
            $output .= $chunk;
        });

        return $output;
    }

    /** @param list<string> $arguments @param callable(string):void $stdout */
    public function stream(array $arguments, callable $stdout): int
    {
        return $this->execute($arguments, $stdout);
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /** @param list<string> $arguments @param callable(string):void $stdout */
    private function execute(array $arguments, callable $stdout): int
    {
        foreach ($arguments as $argument) {
            if (str_contains($argument, "\0")) {
                throw new RuntimeException('Git argument contains a NUL byte.');
            }
        }

        $process = proc_open(
            array_merge(['git', '-C', $this->repoPath], $arguments),
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            ['GIT_CONFIG_NOSYSTEM' => '1', 'GIT_TERMINAL_PROMPT' => '0', 'PATH' => '/usr/bin:/bin'],
            ['bypass_shell' => true]
        );
        if (!is_resource($process)) {
            $this->lastError = 'Unable to start Git.';

            return 1;
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $open = [1 => $pipes[1], 2 => $pipes[2]];
        $stderr = '';
        $started = microtime(true);

        while ($open !== []) {
            if (microtime(true) - $started > $this->timeout) {
                proc_terminate($process, 9);
                $stderr .= 'Git command timed out.';
                break;
            }
            $read = array_values($open);
            $write = null;
            $except = null;
            $selected = stream_select($read, $write, $except, 0, 200000);
            if ($selected === false) {
                proc_terminate($process, 9);
                $stderr .= 'Unable to read Git process output.';
                break;
            }
            if ($selected === 0) {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    foreach ($open as $key => $pipe) {
                        $chunk = stream_get_contents($pipe);
                        if ($chunk !== false && $chunk !== '') {
                            $key === 1 ? $stdout($chunk) : $stderr .= $chunk;
                        }
                        unset($open[$key]);
                    }
                }
                continue;
            }
            foreach ($read as $pipe) {
                $key = $pipe === $pipes[1] ? 1 : 2;
                $chunk = fread($pipe, 8192);
                if ($chunk !== false && $chunk !== '') {
                    $key === 1 ? $stdout($chunk) : $stderr .= substr($chunk, 0, max(0, 65536 - strlen($stderr)));
                }
                if (feof($pipe)) {
                    fclose($pipe);
                    unset($open[$key]);
                }
            }
        }

        foreach ($open as $pipe) {
            fclose($pipe);
        }
        $exitCode = proc_close($process);
        $this->lastError = trim($stderr);

        return $exitCode === -1 && str_contains($stderr, 'timed out') ? 124 : $exitCode;
    }
}
