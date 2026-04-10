<?php
declare(strict_types=1);

namespace App\includes;


final class AssetManifestBuilder
{
    public const MANIFEST_FILE = 'assets/manifest.json';

    private const ASSET_PATTERNS = [
        'assets/img/*',
        'assets/css/*.css',
        'assets/js/*.js',
    ];

    public function __construct(private readonly string $srcDir)
    {
    }

    public function build(bool $verbose = false): array
    {
        $manifest = [];
        $ok = 0;
        $skipped = 0;

        foreach ($this->discoverAssets() as $asset) {
            $fullPath = $this->srcDir . $asset;

            if (!file_exists($fullPath)) {
                if ($verbose) {
                    fwrite(STDERR, "  [SKIP] $asset — file not found\n");
                }
                ++$skipped;
                continue;
            }

            $hash = self::hashFile($fullPath);
            $info = pathinfo($asset);
            $versioned = $info['dirname'] . '/' . $info['filename'] . '.' . $hash . '.' . $info['extension'];

            $manifest[$asset] = $versioned;

            if ($verbose) {
                printf("  [ OK ] %-40s → %s\n", $asset, basename($versioned));
            }
            ++$ok;
        }

        $this->writeManifest($manifest);

        if ($verbose) {
            $detail = "$ok hashed" . ($skipped ? ", $skipped skipped" : '');
            echo "\n  Manifest written to src/" . self::MANIFEST_FILE . "  ($detail)\n";
        }

        return $manifest;
    }


    public function manifestPath(): string
    {
        return $this->srcDir . self::MANIFEST_FILE;
    }

    public function discoverAssets(): array
    {
        $assets = [];

        foreach (self::ASSET_PATTERNS as $pattern) {
            $matches = glob($this->srcDir . $pattern);
            if ($matches === false) {
                continue;
            }
            foreach ($matches as $fullPath) {
                $assets[] = ltrim(str_replace($this->srcDir, '', $fullPath), '/');
            }
        }

        return $assets;
    }

    public static function hashFile(string $fullPath): string
    {
        return substr(md5_file($fullPath), 0, 8);
    }

    private function writeManifest(array $manifest): void
    {
        $path = $this->manifestPath();
        $content = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        if (!file_exists($path)) {
            Logging::loggingToFile("Asset manifest file not found at $path");
            throw new \RuntimeException("Asset manifest file not found at $path");
        }
        if (file_put_contents($path, $content) === false) {
            Logging::loggingToFile("Failed to write asset manifest to $path");
            throw new \RuntimeException("Failed to write asset manifest to $path");
        }
    }
}

