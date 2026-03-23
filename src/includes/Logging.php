<?php
declare(strict_types=1);
namespace App\includes;

class Logging
{
    private string $path;
    private string $level_message;

    private function __construct(string $path, string $level_message) {
        $this->path = $path;
        $this->level_message = $level_message;
    }
    function loggingToFile(string $message, int $level = 1, bool $isSecurityAlert = false): int
    {
        switch ($level) {
            case 1:
                $this->level_message = 'debug';
                break;
            case 2:
                $this->level_message = 'info';
                break;
            case 3:
                $this->level_message = 'warning';
                break;
        }
        try {
            $this->path = '../log/' . date('Y') . '/' . date('m') . '/';
            $prefile = '[ ' . date(DATE_ATOM) . ' ] ' . '[' . $this->level_message . '] ' . $message;
            $file = fopen($this->path, 'a');
            fwrite($file, $prefile);
            fclose($file);
            return 1;
        } catch (Exception $e) {
            echo $e->getMessage();
            return -1;
        }
    }
}