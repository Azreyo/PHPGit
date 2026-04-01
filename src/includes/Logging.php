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

        $sanitized_message = preg_replace('/[\r\n\t\0]/', '', $message);
        if (!$isSecurityAlert) {
            $path = __DIR__ . '/../log/log-' . date('d-m-Y') . '.log';
            $pre_file = '[ ' . date(DATE_ATOM) . ' ] ' . '[' . $level_message . '] ' . $sanitized_message . "\n";
        } else {
            $path = __DIR__ . '/../log/security - ' . date('d-m-Y') . '.log';
            $pre_file = '[ ' . date(DATE_ATOM) . ' ] ' . '[' . $level_message . '] ' . $sanitized_message . ' [ ' . self::getClientIP() . ' ]' . "\n";
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
        $ip = null;

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedIps = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($forwardedIps as $forwardedIp) {
                $candidate = trim($forwardedIp);
                if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false) {
                    $ip = $candidate;
                    break;
                }
            }
        }

        if ($ip === null && !empty($_SERVER['REMOTE_ADDR'])) {
            $remoteAddr = (string) $_SERVER['REMOTE_ADDR'];
            if (filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false) {
                $ip = $remoteAddr;
            }
        }

        return $ip ?? '0.0.0.0';
    }
}