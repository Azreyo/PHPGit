<?php
declare(strict_types=1);

namespace App\includes;

final class Assets
{
    private const MANIFEST_PATH = __DIR__ . '/../assets/manifest.json';

    /** @var array<string,string>|null */
    private static ?array $manifest = null;

    private static function load(): void
    {
        if (self::$manifest !== null) {
            return;
        }

        if (file_exists(self::MANIFEST_PATH)) {
            $decoded = json_decode(
                (string)file_get_contents(self::MANIFEST_PATH),
                true
            );
            self::$manifest = is_array($decoded) ? $decoded : [];
        } else {
            self::$manifest = [];
        }
    }

    public static function url(string $path): string
    {
        self::load();

        $normalized = ltrim($path, '/');
        $versioned = self::$manifest[$normalized] ?? $normalized;

        return '/' . $versioned;
    }
}