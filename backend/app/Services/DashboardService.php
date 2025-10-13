<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Payment;
use App\Models\Activity;
use App\Models\Event;
use App\Models\Assignment;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Get dashboard data with caching
     *
     * @param array $options
     * @return array
     */
    public function getDashboardData(array $options = []): array
    {
        $cacheKey = 'dashboard_data_' . md5(json_encode($options));
        $cacheTime = $options['cache_time'] ?? 60; // Default cache for 1 minute

        return Cache::remember($cacheKey, $cacheTime, function () use ($options) {
            $startDate = $options['start_date'] ?? now()->subMonth();
            $endDate = $options['end_date'] ?? now();
            $userId = $options['user_id'] ?? null;

            return [
                'totals' => $this->getTotalCounts(),
                'monthly_data' => $this->getMonthlyData($startDate, $endDate),
                'class_distribution' => $this->getClassDistribution(),
                'recent_activity' => $this->getRecentActivity($options['activity_limit'] ?? 10),
                'upcoming_events' => $this->getUpcomingEvents($options['events_limit'] ?? 5),
                'pending_assignments' => $this->getPendingAssignments($options['assignments_limit'] ?? 5),
                'user_activity' => $userId ? $this->getUserActivityStats($userId, $startDate, $endDate) : null,
                'performance_metrics' => $this->getPerformanceMetrics($startDate, $endDate),
                'quick_stats' => $this->getQuickStats(),
                'widget_config' => $this->getUserWidgetConfig($userId)
            ];
        });
    }

    protected function getTotalCounts()
    {
        return [
            'students' => Student::count(),
            'teachers' => Teacher::count(),
            'classes' => SchoolClass::count(),
            'staff' => User::whereHas('roles', function($q) {
                $q->where('name', 'staff');
            })->count(),
        ];
    }

    /**
     * Get monthly data for the given date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getMonthlyData(Carbon $startDate, Carbon $endDate): array
    {
        $period = CarbonPeriod::create($startDate, '1 month', $endDate);
        $monthlyData = [];

        foreach ($period as $date) {
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            $monthKey = $date->format('M Y');

            $revenue = Payment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                ->where('payment_status', 'completed')
                ->sum('total_amount');

            $newStudents = Student::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $activeStudents = $this->getActiveStudentsCount($startOfMonth, $endOfMonth);

            $monthlyData[] = [
                'month' => $monthKey,
                'revenue' => (float) $revenue,
                'new_students' => $newStudents,
                'active_students' => $activeStudents,
                'attendance_rate' => $this->calculateAttendanceRate($startOfMonth, $endOfMonth),
                'completion_rate' => $this->calculateCourseCompletionRate($startOfMonth, $endOfMonth),
                'start_date' => $startOfMonth->toDateString(),
                'end_date' => $endOfMonth->toDateString()
            ];
        }

        return $monthlyData;
    }

    protected function getClassDistribution()
    {
        return SchoolClass::withCount('students')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn($class) => [$class->name => $class->students_count]);
    }

    protected function getRecentActivity($limit = 5)
    {
        return Activity::with('user')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'title' => $activity->title,
                    'message' => $activity->message,
                    'time' => $activity->created_at->diffForHumans(),
                    'icon' => $activity->icon,
                    'color' => $activity->color
                ];
            });
    }

    /**
     * Get upcoming events with additional metadata
     *
     * @param int $limit
     * @return Collection
     */
    protected function getUpcomingEvents(int $limit = 5): Collection
    {
        return Event::with(['createdBy', 'attendees'])
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->limit($limit)
            ->get()
            ->map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_date' => $event->start_date->format('Y-m-d H:i'),
                    'end_date' => $event->end_date?->format('Y-m-d H:i'),
                    'location' => $event->location,
                    'description' => $event->description,
                    'organizer' => $event->createdBy?->name,
                    'attendee_count' => $event->attendees->count(),
                    'is_virtual' => $event->is_virtual ?? false,
                    'registration_deadline' => $event->registration_deadline?->format('Y-m-d H:i')
                ];
            });
    }

    protected function getPendingAssignments()
    {
        return Assignment::with('class', 'subject')
            ->where('due_date', '>=', now())
            ->orderBy('due_date')
            ->get()
            ->map(function($assignment) {
                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'due_date' => $assignment->due_date->format('Y-m-d H:i'),
                    'class_name' => $assignment->class->name ?? 'N/A',
                    'subject' => $assignment->subject->name ?? 'N/A'
                ];
            });
    }

    /**
     * Calculate attendance rate for the given period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function calculateAttendanceRate(Carbon $startDate, Carbon $endDate): float
    {
        try {
            $attendance = DB::table('attendances')
                ->select(DB::raw('COUNT(*) as total, SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present'))
                ->whereBetween('date', [$startDate, $endDate])
                ->first();

            if ($attendance && $attendance->total > 0) {
                return round(($attendance->present / $attendance->total) * 100, 2);
            }
            return 0;
        } catch (\Exception $e) {
            Log::error('Error calculating attendance rate: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get active students count for the period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    protected function getActiveStudentsCount(Carbon $startDate, Carbon $endDate): int
    {
        return Student::where('status', 'active')
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('enrollment_date', [$startDate, $endDate])
                    ->orWhereHas('attendances', function($q) use ($startDate, $endDate) {
                        $q->whereBetween('date', [$startDate, $endDate]);
                    });
            })
            ->count();
    }

    /**
     * Calculate course completion rate
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function calculateCourseCompletionRate(Carbon $startDate, Carbon $endDate): float
    {
        try {
            $result = DB::table('student_courses')
                ->select(DB::raw('COUNT(*) as total, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'))
                ->whereBetween('enrolled_at', [$startDate, $endDate])
                ->first();

            if ($result && $result->total > 0) {
                return round(($result->completed / $result->total) * 100, 2);
            }
            return 0;
        } catch (\Exception $e) {
            Log::error('Error calculating course completion rate: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get user activity statistics
     *
     * @param int $userId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getUserActivityStats(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        $activities = Activity::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total_activities' => $activities->sum('count'),
            'activity_trend' => $activities->pluck('count', 'date'),
            'most_active_day' => $activities->sortByDesc('count')->first(),
            'average_daily_activities' => $activities->avg('count')
        ];
    }

    /**
     * Get performance metrics
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $previousPeriodStart = $startDate->copy()->subMonths(3);
        $previousPeriodEnd = $startDate->copy()->subDay();

        $currentStats = [
            'enrollments' => Student::whereBetween('enrollment_date', [$startDate, $endDate])->count(),
            'revenue' => Payment::whereBetween('payment_date', [$startDate, $endDate])
                ->where('payment_status', 'completed')
                ->sum('total_amount'),
            'attendance' => $this->calculateAttendanceRate($startDate, $endDate)
        ];

        $previousStats = [
            'enrollments' => Student::whereBetween('enrollment_date', [$previousPeriodStart, $previousPeriodEnd])->count(),
            'revenue' => Payment::whereBetween('payment_date', [$previousPeriodStart, $previousPeriodEnd])
                ->where('payment_status', 'completed')
                ->sum('total_amount'),
            'attendance' => $this->calculateAttendanceRate($previousPeriodStart, $previousPeriodEnd)
        ];

        return [
            'current' => $currentStats,
            'previous' => $previousStats,
            'growth' => [
                'enrollments' => $this->calculateGrowth($currentStats['enrollments'], $previousStats['enrollments']),
                'revenue' => $this->calculateGrowth($currentStats['revenue'], $previousStats['revenue']),
                'attendance' => $this->calculateGrowth($currentStats['attendance'], $previousStats['attendance'])
            ]
        ];
    }

    /**
     * Calculate growth percentage
     *
     * @param float $current
     * @param float $previous
     * @return float
     */
    protected function calculateGrowth(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get quick stats for the dashboard
     *
     * @return array
     */
    protected function getQuickStats(): array
    {
        return [
            'pending_approvals' => User::where('status', 'pending_approval')->count(),
            'unpaid_invoices' => Payment::where('payment_status', 'pending')
                ->where('due_date', '<', now())
                ->count(),
            'upcoming_events' => Event::where('start_date', '>=', now())
                ->where('start_date', '<=', now()->addDays(7))
                ->count(),
            'expiring_soon' => Student::where('status', 'active')
                ->where('membership_expiry', '>=', now())
                ->where('membership_expiry', '<=', now()->addDays(30))
                ->count()
        ];
    }

    /**
     * Get user's widget configuration
     *
     * @param int|null $userId
     * @return array
     */
    protected function getUserWidgetConfig(?int $userId = null): array
    {
        if (!$userId) {
            return $this->getDefaultWidgets();
        }

        return UserWidgetPreference::getForUser($userId);
    }

    /**
     * Get default widget configuration
     * 
     * @return array
     */
    protected function getDefaultWidgets(): array
    {
        $defaults = UserWidgetPreference::getDefaultWidgets();
        
        return array_map(function ($widget, $widgetId) {
            return [
                'id' => $widgetId,
                'enabled' => $widget['enabled'],
                'position' => $widget['position'],
                'settings' => $widget['settings']
            ];
        }, $defaults, array_keys($defaults));
    }
    
    /**
     * Save user's widget configuration
     *
     * @param int $userId
     * @param array $widgets
     * @return bool
     */
    public function saveUserWidgetConfig(int $userId, array $widgets): bool
    {
        try {
            UserWidgetPreference::saveForUser($userId, $widgets);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to save widget config: ' . $e->getMessage());
            return false;
        }
    }
}
