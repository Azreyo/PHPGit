<?php

declare(strict_types=1);

use App\Services\GitReaderService;
use PHPUnit\Framework\TestCase;

final class GitReaderServiceTest extends TestCase
{
    private string $root;
    private string $bare;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/phpgit-test-' . bin2hex(random_bytes(6));
        $work = $this->root . '/work';
        $this->bare = $this->root . '/repo';
        mkdir($work, 0700, true);
        $this->command(['git', 'init', '--bare', '-b', 'main', $this->bare]);
        $this->command(['git', 'init', '-b', 'main', $work]);
        $this->command(['git', '-C', $work, 'config', 'user.email', 'test@example.com']);
        $this->command(['git', '-C', $work, 'config', 'user.name', 'Test']);
        file_put_contents($work . '/README.md', "# Safe\n");
        $this->command(['git', '-C', $work, 'add', 'README.md']);
        $this->command(['git', '-C', $work, 'commit', '-m', 'initial']);
        $this->command(['git', '-C', $work, 'remote', 'add', 'origin', $this->bare]);
        $this->command(['git', '-C', $work, 'push', 'origin', 'main']);
    }

    protected function tearDown(): void
    {
        if (isset($this->root) && is_dir($this->root)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($this->root);
        }
    }

    public function testResolvesRefsAndReadsVerifiedObjects(): void
    {
        $git = new GitReaderService($this->bare);
        $oid = $git->resolveRef('main');
        self::assertNotNull($oid);
        self::assertNull($git->resolveRef('--upload-pack=evil'));
        self::assertSame('blob', $git->getObjectType($oid, 'README.md'));
        self::assertSame("# Safe\n", $git->getFileContent($oid, 'README.md')['content']);
        self::assertNull($git->getObjectType($oid, '../HEAD'));
    }

    /** @param list<string> $command */
    private function command(array $command): void
    {
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), ($stdout ?: '') . ($stderr ?: ''));
    }
}
