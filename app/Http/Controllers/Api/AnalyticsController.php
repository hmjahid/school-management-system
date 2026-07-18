<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Fee;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin|super-admin');
    }

    public function overview(Request $request): JsonResponse
    {
        $cacheKey = 'admin_analytics_overview';
        $cacheTtl = now()->addMinutes(5);

        $data = Cache::remember($cacheKey, $cacheTtl, function () {
            $totalStudents = Student::count();
            $totalTeachers = Teacher::count();
            $totalClasses = SchoolClass::count();
            $totalUsers = User::count();

            $recentStudents = Student::where('created_at', '>=', now()->subDays(30))->count();
            $recentTeachers = Teacher::where('created_at', '>=', now()->subDays(30))->count();

            $totalFeesCollected = Fee::where('status', 'active')->sum('paid_amount');
            $pendingFees = Fee::where('status', 'active')->sum(DB::raw('GREATEST(0, amount - COALESCE(paid_amount, 0))'));

            $attendanceRate = Attendance::where('date', '>=', now()->subDays(30))
                ->selectRaw('(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as rate', ['present'])
                ->value('rate') ?? 0;

            $classDistribution = SchoolClass::withCount('students')
                ->get()
                ->map(fn($class) => ['name' => $class->name, 'count' => $class->students_count]);

            return [
                'success' => true,
                'data' => [
                    'totals' => [
                        'students' => $totalStudents,
                        'teachers' => $totalTeachers,
                        'classes' => $totalClasses,
                        'users' => $totalUsers,
                    ],
                    'recent' => [
                        'new_students_30d' => $recentStudents,
                        'new_teachers_30d' => $recentTeachers,
                    ],
                    'financial' => [
                        'total_collected' => round($totalFeesCollected, 2),
                        'pending_fees' => round($pendingFees, 2),
                    ],
                    'attendance_rate' => round($attendanceRate, 1),
                    'class_distribution' => $classDistribution,
                ],
            ];
        });

        return response()->json($data);
    }
}
