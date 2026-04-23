<?php

declare(strict_types=1);

namespace App\includes;

use App\Config;

class Logging
{
    public static function loggingToFile(string $message, int $level = 1, bool $is_security_alert = false, bool $save_to_database = false): void
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
        if (! $save_to_database) {
            if (! $is_security_alert) {
                $path = __DIR__ . '/../log/log-' . date('d-m-Y') . '.log';
                $pre_file = '[ ' . date(DATE_ATOM) . ' ] ' . '[' . $level_message . '] ' . $sanitized_message . "\n";
            } else {
                $path = __DIR__ . '/../log/security - ' . date('d-m-Y') . '.log';
                $pre_file = '[ ' . date(DATE_ATOM) . ' ] ' . '[' . $level_message . '] ' . $sanitized_message . ' [ ' . self::getClientIP() . ' ]' . "\n";
            }

            if (! is_dir(__DIR__ . '/../log/')) {
                if (! mkdir(__DIR__ . '/../log/', 0775, true)) {
                    error_log('Cannot create directory log: invalid permissions');
                }
            }
            $file = fopen($path, 'a');
            if ($file) {
                fwrite($file, $pre_file);
                fclose($file);
            } else {
                error_log('Cannot open log file ' . $path);
            }
        } else {
            $ip = $is_security_alert ? self::getClientIP() : null;
            $pdo = new Config()->getPDO();

            try {
                if ($pdo === null) {
                    self::loggingToFile('Cannot log into database', 4);

                    return;
                }
                $pdo->beginTransaction();
                $stmt = new Config()->getPDO()->prepare('INSERT INTO log (level, message, security, ip) VALUES (?, ?, ?, ?)');
                $stmt->execute([$level_message, $sanitized_message, (int) $is_security_alert, $ip]);
                $pdo->commit();
            } catch (\PDOException $e) {
                $pdo->rollBack();
                self::loggingToFile('Database error: ' . $e->getMessage(), 4);
            }
        }
    }

    public static function getClientIP(): string
    {
        $ip = null;

        if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($forwardedIps as $forwardedIp) {
                $candidate = trim($forwardedIp);
                if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false) {
                    $ip = $candidate;
                    break;
                }
            }
        }

        if ($ip === null && ! empty($_SERVER['REMOTE_ADDR'])) {
            $remoteAddr = (string) $_SERVER['REMOTE_ADDR'];
            if (filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false) {
                $ip = $remoteAddr;
            }
        }

        return $ip ?? '0.0.0.0';
    }
}
