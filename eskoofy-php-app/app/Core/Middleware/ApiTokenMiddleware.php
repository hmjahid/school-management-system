<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Models\PersonalAccessToken;

/**
 * Sanctum-style API token authentication.
 *
 * Reads the `Authorization: Bearer <token>` header and resolves the user from
 * the personal_access_tokens table. On success the user id is stored so
 * \App\Core\Auth::user() can be read from within API controllers. Fails with
 * a JSON 401 (no session redirect) so JSON clients get a machine-readable error.
 */
class ApiTokenMiddleware
{
    public function handle(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            $plain = trim($m[1]);
        } else {
            $plain = $_GET['api_token'] ?? '';
        }

        $token = $plain !== '' ? PersonalAccessToken::findByToken($plain) : null;

        if ($token === null || ! $token->isValid()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data'    => null,
            ]);
            exit;
        }

        // Store the authenticated user id for \App\Core\Auth::user().
        \App\Core\Session::getInstance()->set('user_id', (int) $token->tokenable_id);
        \App\Core\Session::getInstance()->set('api_token_id', (int) $token->getKey());
        $token->touchLastUsed();
    }
}