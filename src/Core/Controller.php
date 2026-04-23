<?php

/** @noinspection PhpNoReturnAttributeCanBeAddedInspection */

declare(strict_types=1);

namespace App\Core;

class Controller
{
    /**
     * @param  array<mixed>          $data
     * @param  int                   $statusCode
     * @param  array<string, string> $headers
     * @return void
     */
    protected function json(array $data, int $statusCode = 200, array $headers = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo json_encode($data, JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * @param  array<mixed> $data
     * @param  int          $statusCode
     * @return void
     */
    protected function success(array $data, int $statusCode = 200): void
    {
        $this->json($data, $statusCode);
    }

    /**
     * @param  string $message
     * @param  int    $statusCode
     * @return void
     */
    protected function error(string $message, int $statusCode = 500): void
    {
        $this->json(['error' => $message], $statusCode);
    }

    /**
     * @param  string $message
     * @return void
     */
    protected function badRequest(string $message = 'Bad request'): void
    {
        $this->error($message, 400);
    }

    /**
     * @param  string $message
     * @return void
     */
    protected function notFound(string $message = 'Not found'): void
    {
        $this->error($message, 404);
    }

    /**
     * @param  array<string> $allowedMethods
     * @return void
     */
    protected function methodNotAllowed(array $allowedMethods): void
    {
        $methods = array_values(array_unique(array_map('strtoupper', $allowedMethods)));
        $allowHeader = implode(', ', $methods);

        $this->json(
            ['error' => 'Method not allowed'],
            405,
            ['Allow' => $allowHeader]
        );
    }

    protected function requireMethod(string $method): void
    {
        $expectedMethod = strtoupper($method);
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($requestMethod !== $expectedMethod) {
            $this->methodNotAllowed([$expectedMethod]);
        }
    }
}
