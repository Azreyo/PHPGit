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
     * @return never
     */
    protected function json(array $data, int $statusCode = 200, array $headers = []): never
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
     * @return never
     */
    protected function success(array $data, int $statusCode = 200): never
    {
        $this->json($data, $statusCode);
    }

    /**
     * @param  string $message
     * @param  int    $statusCode
     * @return never
     */
    protected function error(string $message, int $statusCode = 500): never
    {
        $this->json(['error' => $message], $statusCode);
    }

    /**
     * @param  string $message
     * @return never
     */
    protected function badRequest(string $message = 'Bad request'): never
    {
        $this->error($message, 400);
    }

    /**
     * @param  string $message
     * @return never
     */
    protected function notFound(string $message = 'Not found'): never
    {
        $this->error($message, 404);
    }

    /**
     * @param  array<string> $allowedMethods
     * @return never
     */
    protected function methodNotAllowed(array $allowedMethods): never
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
