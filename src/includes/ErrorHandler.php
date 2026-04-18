<?php

declare(strict_types=1);

namespace App\includes;

use Dotenv\Dotenv;
use Throwable;

class ErrorHandler
{
    private bool $isDev = false;

    public function __construct(?string $appEnv = null)
    {
        $this->bootstrapEnv($appEnv);
    }

    public function register(): void
    {
        $this->configurePhp();

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    private function bootstrapEnv(?string $appEnv): void
    {
        if (! isset($_ENV['APP_ENV']) && class_exists(Dotenv::class)) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->safeLoad();
        }

        $resolved = $appEnv ?? ($_ENV['APP_ENV'] ?? 'prod');
        $this->isDev = strtolower((string) $resolved) === 'dev';
    }

    private function configurePhp(): void
    {
        if ($this->isDev) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }
    }

    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (! $this->isDev) {
            return true;
        }

        $errorTypes = [
            E_ERROR => ['danger', 'Fatal Error'],
            E_WARNING => ['warning', 'Warning'],
            E_NOTICE => ['info', 'Notice'],
            E_DEPRECATED => ['secondary', 'Deprecated'],
            E_USER_ERROR => ['danger', 'User Error'],
            E_USER_WARNING => ['warning', 'User Warning'],
            E_USER_NOTICE => ['info', 'User Notice'],
            E_RECOVERABLE_ERROR => ['danger',    'Recoverable Error'],
        ];

        [$variant, $label] = $errorTypes[$errno] ?? ['secondary', 'Unknown Error'];

        $fatalTypes = [E_USER_ERROR, E_RECOVERABLE_ERROR];
        if (in_array($errno, $fatalTypes, true)) {
            http_response_code(500);
        }

        $snippet = $this->sourceSnippet($errfile, $errline);
        $trace = $this->backtraceTable(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));

        $this->render(
            $variant,
            $label,
            htmlspecialchars($errstr, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($errfile, ENT_QUOTES, 'UTF-8'),
            $errline,
            $snippet,
            $trace,
            $errno
        );

        return true;
    }

    public function handleException(Throwable $e): void
    {
        if (! $this->isDev) {
            http_response_code(500);
            error_log(sprintf(
                'Unhandled %s: %s in %s:%d',
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
            echo '<h1>Internal Server Error</h1>';

            return;
        }

        $class = get_class($e);
        $isError = $e instanceof \Error;
        $variant = $isError ? 'danger' : 'warning';
        $label = $isError ? 'Error' : 'Exception';

        $frames = $e->getTrace();
        array_unshift($frames, [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'function' => '{throw}',
            'class' => $class,
            'type' => '',
        ]);

        $snippet = $this->sourceSnippet($e->getFile(), $e->getLine());
        $trace = $this->backtraceTable($frames);

        $code = $e->getCode() ? " (code: {$e->getCode()})" : '';
        $title = htmlspecialchars($class . ': ' . $e->getMessage() . $code, ENT_QUOTES, 'UTF-8');

        $this->render(
            $variant,
            $label,
            $title,
            htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8'),
            $e->getLine(),
            $snippet,
            $trace
        );
    }

    private function sourceSnippet(string $file, int $errorLine, int $context = 5): string
    {
        if (! is_readable($file)) {
            return '<p class="text-muted small mb-0">Source not readable.</p>';
        }

        $lines = file($file);
        $start = max(0, $errorLine - $context - 1);
        $end = min(count($lines), $errorLine + $context);
        $rows = '';

        for ($i = $start; $i < $end; $i++) {
            $lineNum = $i + 1;
            $code = htmlspecialchars($lines[$i], ENT_QUOTES, 'UTF-8');
            $isErrorLine = ($lineNum === $errorLine);

            $rowStyle = $isErrorLine
                ? 'background:rgba(220,53,69,.3);color:#fff;font-weight:600;'
                : 'color:#adb5bd;';

            $arrow = $isErrorLine ? '&#10141;' : '&nbsp;&nbsp;';

            $rows .= sprintf(
                '<tr style="%s">
                    <td style="padding:1px 12px 1px 8px;user-select:none;opacity:.55;text-align:right;min-width:40px;">%s %d</td>
                    <td style="padding:1px 12px 1px 4px;white-space:pre;width:100%%;">%s</td>
                </tr>',
                $rowStyle,
                $arrow,
                $lineNum,
                $code
            );
        }

        return '<table style="width:100%;border-collapse:collapse;font-size:.78rem;">' . $rows . '</table>';
    }

    private function backtraceTable(array $frames): string
    {
        array_shift($frames);

        if (empty($frames)) {
            return '<p class="text-muted small mb-0">No backtrace available.</p>';
        }

        $rows = '';
        foreach ($frames as $i => $frame) {
            $file = htmlspecialchars($frame['file'] ?? '[internal]', ENT_QUOTES, 'UTF-8');
            $line = $frame['line'] ?? '—';
            $fn = htmlspecialchars(
                ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '—'),
                ENT_QUOTES,
                'UTF-8'
            );
            $rowBg = $i % 2 === 0 ? 'rgba(255,255,255,.04)' : 'transparent';

            $rows .= sprintf(
                '<tr style="background:%s;">
                    <td style="padding:3px 10px;opacity:.45;user-select:none;text-align:right;">#%d</td>
                    <td style="padding:3px 10px;color:#6ea8fe;white-space:nowrap;">%s()</td>
                    <td style="padding:3px 10px;color:#adb5bd;word-break:break-all;">%s<strong style="color:#dee2e6;">:%s</strong></td>
                </tr>',
                $rowBg,
                $i,
                $fn,
                $file,
                $line
            );
        }

        return '<table style="width:100%;border-collapse:collapse;font-size:.78rem;">' . $rows . '</table>';
    }

    private function render(
        string $variant,
        string $label,
        string $title,
        string $file,
        int $line,
        string $snippet,
        string $trace,
        int $errno = 0
    ): void {
        $id = 'eh_' . substr(md5(uniqid('', true)), 0, 8);
        $errCode = $errno > 0 ? " · E{$errno}" : '';
        $error_level = match ($label) {
            'Fatal Error' => 5,
            'Error' => 4,
            'Warning' => 3,
            'Notice' => 2,
            'Debug' => 1,
            default => 0,
        };
        Logging::loggingToFile($title . ' ' . $line . ' ' . $file, $error_level);

        echo <<<HTML
        <div class="alert alert-{$variant} alert-dismissible fade show font-monospace small my-2 mx-2 shadow" role="alert" style="border-radius:6px;">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge bg-{$variant} text-uppercase" style="font-size:.7rem;letter-spacing:.05em;">{$label}{$errCode}</span>
                <span class="fw-bold" style="color:inherit;">{$title}</span>
            </div>
            <p class="mb-2 opacity-75">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="me-1 mb-1" viewBox="0 0 16 16">
                    <path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4zm0 1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/>
                    <path d="M4.5 12.5A.5.5 0 0 1 5 12h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zM4.5 10A.5.5 0 0 1 5 9.5h6a.5.5 0 0 1 0 1H5A.5.5 0 0 1 4.5 10zM4.5 7.5A.5.5 0 0 1 5 7h3a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/>
                </svg>
                <em>{$file}</em> &nbsp;·&nbsp; line <strong>{$line}</strong>
            </p>
            <div class="accordion accordion-flush rounded" id="{$id}">
                <div class="accordion-item bg-transparent border-0 mb-1">
                    <h2 class="accordion-header m-0">
                        <button class="accordion-button collapsed py-1 px-2 small rounded"
                                style="background:rgba(255,255,255,.07);color:inherit;"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#{$id}_src"
                                aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path d="M10.478 1.647a.5.5 0 1 0-.956-.294l-4 13a.5.5 0 0 0 .956.294l4-13zM4.854 4.146a.5.5 0 0 1 0 .708L1.707 8l3.147 3.146a.5.5 0 0 1-.708.708l-3.5-3.5a.5.5 0 0 1 0-.708l3.5-3.5a.5.5 0 0 1 .708 0zm6.292 0a.5.5 0 0 0 0 .708L14.293 8l-3.147 3.146a.5.5 0 0 0 .708.708l3.5-3.5a.5.5 0 0 0 0-.708l-3.5-3.5a.5.5 0 0 0-.708 0z"/>
                            </svg>
                            Source
                        </button>
                    </h2>
                    <div id="{$id}_src" class="accordion-collapse collapse" data-bs-parent="#{$id}">
                        <div class="bg-dark rounded mt-1 p-0 overflow-auto" style="max-height:240px;">
                            {$snippet}
                        </div>
                    </div>
                </div>
                <div class="accordion-item bg-transparent border-0">
                    <h2 class="accordion-header m-0">
                        <button class="accordion-button collapsed py-1 px-2 small rounded"
                                style="background:rgba(255,255,255,.07);color:inherit;"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#{$id}_trace"
                                aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path d="M0 1.5A.5.5 0 0 1 .5 1h15a.5.5 0 0 1 0 1H.5A.5.5 0 0 1 0 1.5zm0 5A.5.5 0 0 1 .5 6h15a.5.5 0 0 1 0 1H.5A.5.5 0 0 1 0 6.5zm0 5a.5.5 0 0 1 .5-.5h15a.5.5 0 0 1 0 1H.5a.5.5 0 0 1-.5-.5z"/>
                            </svg>
                            Stack Trace
                        </button>
                    </h2>
                    <div id="{$id}_trace" class="accordion-collapse collapse" data-bs-parent="#{$id}">
                        <div class="bg-dark rounded mt-1 p-0 overflow-auto" style="max-height:300px;">
                            {$trace}
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        HTML;
    }
}
