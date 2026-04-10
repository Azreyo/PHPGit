<?php
declare(strict_types=1);

final class AssetManifestBuilder
{
    private const MANIFEST_FILE = 'assets/manifest.json';

    /** @var array<string,string> */
    private array $manifest = [];
    private int $ok = 0;
    private int $skipped = 0;


    public function __construct(
        private readonly string $srcDir,
        private readonly array  $assets,
    )
    {
    }

    public function build(): void
    {
        foreach ($this->assets as $asset) {
            $this->processAsset($asset);
        }

        $this->writeManifest();
        $this->printSummary();
    }

    private function processAsset(string $asset): void
    {
        $fullPath = $this->srcDir . $asset;

        if (!file_exists($fullPath)) {
            fwrite(STDERR, "  [SKIP] $asset — file not found\n");
            ++$this->skipped;
            return;
        }

        $hash = substr(md5_file($fullPath), 0, 8);
        $info = pathinfo($asset);
        $versioned = $info['dirname'] . '/' . $info['filename'] . '.' . $hash . '.' . $info['extension'];

        $this->manifest[$asset] = $versioned;
        printf("  [ OK ] %-40s → %s\n", $asset, basename($versioned));
        ++$this->ok;
    }

    private function writeManifest(): void
    {
        $dest = $this->srcDir . self::MANIFEST_FILE;
        $content = json_encode($this->manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

        if (file_put_contents($dest, $content) === false) {
            fwrite(STDERR, "  [ERR]  Failed to write manifest to $dest\n");
            exit(1);
        }
    }

    private function printSummary(): void
    {
        $detail = $this->ok . ' hashed' . ($this->skipped ? ", {$this->skipped} skipped" : '');
        echo "\n  Manifest written to src/" . self::MANIFEST_FILE . "  ($detail)\n";
    }
}

new AssetManifestBuilder(
    srcDir: dirname(__DIR__) . '/src/',
    assets: [
        'assets/css/style.css',
        'assets/css/terminal.css',
        'assets/css/dev.css',
        'assets/css/admin.css',
        'assets/css/easter.css',
        'assets/js/theme.js',
        'assets/js/terminal-animation.js',
        'assets/js/dev.js',
        'assets/js/easter.js',
        'assets/js/overview.js',
        'assets/js/users.js',
    ],
)->build();
