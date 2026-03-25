<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiInfoController extends Controller
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
}
