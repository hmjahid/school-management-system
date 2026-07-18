<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin|super-admin');
    }

    public function index(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 20), 50);

        $activities = Activity::with('user')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'type' => $log->type ?? 'activity',
                'message' => $log->message ?? $log->title,
                'causer' => $log->user?->name ?? 'System',
                'time' => $log->created_at->diffForHumans(),
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }
}
