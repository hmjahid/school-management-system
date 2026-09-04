<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

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

        $activities = Activity::with('causer')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'type' => $log->log_name ?? 'activity',
                'message' => $log->description ?? $log->log_name,
                'causer' => $log->causer?->name ?? 'System',
                'time' => $log->created_at->diffForHumans(),
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }
}
