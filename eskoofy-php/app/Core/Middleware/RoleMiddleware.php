<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;

/**
 * Role-gate middleware. Supports parameterized middleware names:
 *
 *     ['Role:admin,super_admin']
 *
 * Any logged-in user whose role is in the list passes; everyone else gets a
 * 403. A missing role argument defaults to requiring 'admin'.
 */
class RoleMiddleware
{
    private string $roles = 'admin';

    public function __construct(string $roles = 'admin')
    {
        if ($roles !== '') {
            $this->roles = $roles;
        }
    }

    public function handle(): void
    {
        $allowed = array_values(array_filter(array_map('trim', explode(',', $this->roles))));
        Auth::requireRole(...($allowed !== [] ? $allowed : ['admin']));
    }
}