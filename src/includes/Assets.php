<?php

declare(strict_types=1);

namespace App\includes;

final class Assets
{
    private const MANIFEST_PATH = __DIR__ . '/../assets/manifest.json';
    private const SRC_DIR = __DIR__ . '/../';

    /** @var array<string,string>|null */
    private static ?array $manifest = null;

    private static function load(): void
    {
        if (self::$manifest !== null) {
            return;
        }

        if (self::isStale()) {
            self::$manifest = (new AssetManifestBuilder(self::SRC_DIR))->build();

            return;
        }

        $decoded = json_decode(
            (string) file_get_contents(self::MANIFEST_PATH),
            true
        );
        self::$manifest = is_array($decoded) ? $decoded : [];
    }

    private static function isStale(): bool
    {
        if (! file_exists(self::MANIFEST_PATH)) {
            return true;
        }

        $manifestMtime = filemtime(self::MANIFEST_PATH);
        $builder = new AssetManifestBuilder(self::SRC_DIR);

        foreach ($builder->discoverAssets() as $asset) {
            $fullPath = self::SRC_DIR . $asset;
            if (file_exists($fullPath) && filemtime($fullPath) > $manifestMtime) {
                return true;
            }
        }

        return false;
    }

    public static function url(string $path): string
    {
        self::load();

        $normalized = ltrim($path, '/');
        $versioned = self::$manifest[$normalized] ?? $normalized;

        return '/' . $versioned;
    }
}
