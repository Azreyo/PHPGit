<?php
declare(strict_types=1);
namespace App\includes;

class Logging
{
    public static function loggingToFile(string $message, int $level = 1, bool $isSecurityAlert = false): void
    {
        $level_message = match ($level) {
            1 => 'Debug',
            2 => 'Info',
            3 => 'Warning',
            4 => 'Error',
            5 => 'Critical',
            default => 'Unknown',
        };
        if (!$isSecurityAlert) {
            $path = __DIR__ . '/../log/log-' . date('d-m-Y') . '.log';
            $pre_file = '[ ' . date(DATE_ATOM) . ' ] ' . '[' . $level_message . '] ' . $message . "\n";
        } else {
            $path = __DIR__ . '/../log/security - ' . date('d-m-Y') . '.log';
            $pre_file = '[ ' . date(DATE_ATOM) . ' ] ' . '[' . $level_message . '] ' . $message . ' [ ' . self::getClientIP() . ' ]' . "\n";
        }

        if (!is_dir(__DIR__ . '/../log/')) {
            if (!mkdir(__DIR__ . '/../log/', 0775, true)) {
                error_log("Cannot create directory log: invalid permissions");
            }
        }
        $file = fopen($path, 'a');
        if ($file) {
            fwrite($file, $pre_file);
            fclose($file);
        } else {
            error_log("Cannot open log file " . $path);
        }
    }

    public static function getClientIP(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}