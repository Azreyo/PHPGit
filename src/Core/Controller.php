<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    /**
     * @param array<mixed> $data
     * @param array<string, string> $headers
     * @return never
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
     * @param array<mixed> $data
     * @return never
     */
    protected function success(array $data, int $statusCode = 200): void
    {
        $this->json($data, $statusCode);
    }

    /** @return never */
    protected function error(string $message, int $statusCode = 500): void
    {
        $this->json(['error' => $message], $statusCode);
    }

    /** @return never */
    protected function badRequest(string $message = 'Bad request'): void
    {
        $this->error($message, 400);
    }

    /** @return never */
    protected function notFound(string $message = 'Not found'): void
    {
        $this->error($message, 404);
    }

    /**
     * @param array<string> $allowedMethods
     * @return never
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
