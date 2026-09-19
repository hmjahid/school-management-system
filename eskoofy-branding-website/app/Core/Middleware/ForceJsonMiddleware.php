<?php
declare(strict_types=1);

namespace App\Core\Middleware;

class ForceJsonMiddleware
{
    public function handle(): void
    {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json');
        }
    }
}
