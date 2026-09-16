<?php
declare(strict_types=1);

namespace App\Core\Middleware;

class AuthMiddleware
{
    public function handle(): void
    {
        // Allow Bearer-token API auth as well as session auth (Sanctum parity).
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            (new ApiTokenMiddleware())->handle();
            return;
        }

        if (!\App\Core\Auth::check()) {
            // JSON requests get a machine-readable 401; HTML gets a redirect.
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            if (str_contains($accept, 'application/json')) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthenticated.', 'data' => null]);
                exit;
            }
            \App\Core\Session::getInstance()->flash('error', 'Please login to continue.');
            header('Location: /login');
            exit;
        }
    }
}
