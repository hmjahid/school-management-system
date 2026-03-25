<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'name' => 'School Management System API',
            'version' => '1.0.0',
            'endpoints' => [
                'auth' => [
                    'login' => url('/api/login'),
                    'register' => url('/api/register'),
                    'logout' => url('/api/logout'),
                    'refresh' => url('/api/auth/refresh-token'),
                ],
                'user' => [
                    'profile' => url('/api/user'),
                    'me' => url('/api/me'),
                ],
                'admin' => [
                    'dashboard' => url('/api/admin/dashboard'),
                ]
            ]
        ]);
    }

    /**
     * Return a success JSON response
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse($data, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response, $statusCode);
    }
}
