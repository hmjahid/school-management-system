<?php
declare(strict_types=1);

namespace App\Core\Middleware;

class AdminMiddleware
{
    public function handle(): void
    {
        \App\Core\Auth::requireRole('admin', 'super_admin');
    }
}
