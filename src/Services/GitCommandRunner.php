<?php

declare(strict_types=1);

namespace App\Services;

final class GitCommandRunner
{
    private string $repoPath;

    public function __construct(string $repoPath)
    {
        $this->repoPath = rtrim($repoPath, '/');
    }

    /**
     * @param list<string> $arguments
     */
    public function run(array $arguments, int &$exitCode = 0, ?int $maxBytes = null): string
    {
        $output = '';
        $exitCode = $this->stream($arguments, static function (string $chunk) use (&$output, $maxBytes): void {
            if ($maxBytes !== null && strlen($output) >= $maxBytes) {
                return;
            }

            if ($maxBytes !== null && strlen($output) + strlen($chunk) > $maxBytes) {
                $chunk = substr($chunk, 0, $maxBytes - strlen($output));
            }

            $output .= $chunk;
        });

        return $output;
    }

    /**
     * @param list<string> $arguments
     * @param callable(string): void $stdout
     */
    public function stream(array $arguments, callable $stdout): int
    {
        $command = array_merge(['git', '-C', $this->repoPath], $arguments);
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            $command,
            $descriptorSpec,
            $pipes,
            null,
            null,
            ['bypass_shell' => true]
        );

        if (!is_resource($process)) {
            return 1;
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $openPipes = [$pipes[1], $pipes[2]];
        while ($openPipes !== []) {
            $read = $openPipes;
            $write = null;
            $except = null;
            if (stream_select($read, $write, $except, 5) === false) {
                break;
            }

            foreach ($read as $pipe) {
                $chunk = fread($pipe, 8192);
                if ($chunk === false || $chunk === '') {
                    if (feof($pipe)) {
                        $openPipes = array_values(array_filter(
                            $openPipes,
                            static fn($openPipe): bool => $openPipe !== $pipe
                        ));
                    }
                    continue;
                }

                if ($pipe === $pipes[1]) {
                    $stdout($chunk);
                }
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }
}
