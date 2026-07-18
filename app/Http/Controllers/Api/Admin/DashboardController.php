<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Http\Resources\DashboardResource;
use App\Models\UserWidgetPreference;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    /**
     * @var DashboardService
     */
    protected $dashboardService;

    /**
     * Create a new controller instance.
     *
     * @param DashboardService $dashboardService
     * @return void
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin|super-admin');
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get admin dashboard statistics
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\DashboardResource|\Illuminate\Http\JsonResponse
     */
    /**
     * Get dashboard data with optional filters
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\DashboardResource|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'cache_ttl' => 'nullable|integer|min:0',
                'include' => 'nullable|array',
                'include.*' => 'string|in:totals,monthly_data,class_distribution,recent_activity,upcoming_events,pending_assignments,user_activity,performance_metrics,quick_stats,widget_config',
                'activity_limit' => 'nullable|integer|min:1|max:50',
                'events_limit' => 'nullable|integer|min:1|max:20',
                'assignments_limit' => 'nullable|integer|min:1|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = $request->user();
            $options = [
                'start_date' => $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null,
                'end_date' => $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null,
                'cache_time' => $request->input('cache_ttl', 60), // Default 1 minute cache
                'include' => $request->input('include', [
                    'totals',
                    'monthly_data',
                    'class_distribution',
                    'recent_activity',
                    'upcoming_events',
                    'pending_assignments',
                    'user_activity',
                    'performance_metrics',
                    'quick_stats',
                    'widget_config'
                ]),
                'activity_limit' => min($request->input('activity_limit', 10), 50), // Max 50 items
                'events_limit' => min($request->input('events_limit', 5), 20), // Max 20 items
                'assignments_limit' => min($request->input('assignments_limit', 5), 20), // Max 20 items
                'user_id' => $user->id,
                'role' => $user->roles->first()?->name
            ];

            // Get dashboard data from service with the specified options
            $dashboardData = $this->dashboardService->getDashboardData($options);

            return new DashboardResource($dashboardData);
            
        } catch (\Exception $e) {
            Log::error('DashboardController error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get widget configuration for the authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWidgetConfig(Request $request)
    {
        try {
            $user = $request->user();
            $widgets = $this->dashboardService->getUserWidgetConfig($user->id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'widgets' => $widgets,
                    'defaults' => UserWidgetPreference::getDefaultWidgets()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting widget config: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load widget configuration',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Save widget configuration for the authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveWidgetConfig(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'widgets' => 'required|array',
                'widgets.*.id' => 'required|string',
                'widgets.*.enabled' => 'required|boolean',
                'widgets.*.position' => 'required|integer|min:1|max:20',
                'widgets.*.settings' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = $request->user();
            $widgets = collect($request->input('widgets'))
                ->map(function ($widget) {
                    return [
                        'id' => $widget['id'],
                        'enabled' => $widget['enabled'],
                        'position' => $widget['position'],
                        'settings' => $widget['settings'] ?? []
                    ];
                })
                ->toArray();

            // Save the widget configuration
            $result = $this->dashboardService->saveUserWidgetConfig($user->id, $widgets);

            if (!$result) {
                throw new \Exception('Failed to save widget configuration');
            }
            
            // Return the updated widget configuration
            return response()->json([
                'success' => true,
                'message' => 'Widget configuration saved successfully',
                'data' => [
                    'widgets' => $this->dashboardService->getUserWidgetConfig($user->id)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error saving widget config: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save widget configuration',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reset widget configuration to defaults for the authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetWidgetConfig(Request $request)
    {
        try {
            $user = $request->user();
            
            // Delete all widget preferences for the user
            UserWidgetPreference::where('user_id', $user->id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Widget configuration reset to defaults',
                'data' => [
                    'widgets' => $this->dashboardService->getUserWidgetConfig($user->id)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error resetting widget config: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset widget configuration',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Get dashboard statistics
     * 
     * @return array
     */
    protected function getDashboardStats()
    {
        // These variables should be populated with actual data from your database
        $totalStudents = 0;
        $totalTeachers = 0;
        $totalClasses = 0;
        $totalStaff = 0;
        $totalRevenue = 0;
        $attendanceRates = [];
        $pendingAssignments = 0;
        $upcomingEvents = 0;
        $monthlyNewStudents = [];
        $monthlyRevenue = [];

        return [
            'totalStudents' => (int) $totalStudents,
            'totalTeachers' => (int) $totalTeachers,
            'totalClasses' => (int) $totalClasses,
            'totalStaff' => (int) $totalStaff,
            'totalRevenue' => (float) $totalRevenue,
            'attendanceRate' => (float) ($attendanceRates[count($attendanceRates) - 1] ?? 0),
            'pendingAssignments' => (int) $pendingAssignments,
            'upcomingEvents' => (int) $upcomingEvents,
            'newStudentsThisMonth' => (int) ($monthlyNewStudents[count($monthlyNewStudents) - 1] ?? 0),
            'revenueGrowth' => count($monthlyRevenue) > 1 ?
                round((($monthlyRevenue[count($monthlyRevenue) - 1] - $monthlyRevenue[count($monthlyRevenue) - 2]) /
                      max($monthlyRevenue[count($monthlyRevenue) - 2], 1)) * 100, 1) : 0,
        ];
    }
    
    /**
     * Get dashboard charts data
     * 
     * @return array
     */
    protected function getDashboardCharts()
    {
        // Initialize data arrays
        $monthlyLabels = [];
        $monthlyRevenue = [];
        $monthlyNewStudents = [];
        $attendanceRates = [];
        
        // Generate sample data (replace with actual data from your database)
        for ($i = 0; $i < 6; $i++) {
            $monthlyLabels[] = now()->subMonths(5 - $i)->format('M Y');
            $monthlyRevenue[] = rand(1000, 10000);
            $monthlyNewStudents[] = rand(5, 50);
            $attendanceRates[] = rand(80, 100);
        }
        
        // Sample class distribution
        $classDistribution = collect([
            'Class 1' => rand(20, 40),
            'Class 2' => rand(20, 40),
            'Class 3' => rand(20, 40),
            'Class 4' => rand(20, 40),
            'Class 5' => rand(20, 40),
        ]);

        // Sample student performance data
        $studentPerformance = [
            'A' => rand(5, 15),
            'B' => rand(10, 20),
            'C' => rand(15, 25),
            'D' => rand(5, 15),
            'F' => rand(0, 5),
        ];

        return [
            'monthlyRevenue' => [
                'labels' => $monthlyLabels,
                'data' => $monthlyRevenue,
                'title' => 'Monthly Revenue',
                'type' => 'line',
                'color' => 'primary'
            ],
            'newStudents' => [
                'labels' => $monthlyLabels,
                'data' => $monthlyNewStudents,
                'title' => 'New Students',
                'type' => 'bar',
                'color' => 'success'
            ],
            'attendanceTrend' => [
                'labels' => $monthlyLabels,
                'data' => $attendanceRates,
                'title' => 'Attendance Rate',
                'type' => 'line',
                'color' => 'info',
                'suffix' => '%'
            ],
            'classDistribution' => [
                'labels' => $classDistribution->keys()->toArray(),
                'data' => $classDistribution->values()->toArray(),
                'title' => 'Students by Class',
                'type' => 'doughnut',
                'color' => 'warning'
            ],
            'studentPerformance' => [
                'labels' => array_keys($studentPerformance),
                'data' => array_values($studentPerformance),
                'title' => 'Student Performance',
                'type' => 'bar',
                'color' => 'danger'
            ]
        ];
    }
    
    /**
     * Get recent activity data
     * 
     * @return array
     */
    protected function getRecentActivity()
    {
        // Sample recent activity data (replace with actual data from your database)
        return [
            [
                'id' => 1,
                'type' => 'new_student',
                'message' => 'New student registered: John Doe',
                'time' => '2 minutes ago',
                'icon' => 'user-add'
            ],
            [
                'id' => 2,
                'type' => 'payment_received',
                'message' => 'Payment received from Jane Smith',
                'amount' => 250.00,
                'time' => '1 hour ago',
                'icon' => 'currency-dollar'
            ],
            // Add more sample activities as needed
        ];
    }
    
    /**
     * Get quick actions for the dashboard
     * 
     * @return array
     */
    protected function getQuickActions()
    {
        return [
            [
                'title' => 'Add New Student',
                'icon' => 'user-add',
                'url' => '/students/create',
                'color' => 'indigo'
            ],
            [
                'title' => 'Record Payment',
                'icon' => 'currency-dollar',
                'url' => '/payments/create',
                'color' => 'green'
            ],
            [
                'title' => 'Send Announcement',
                'icon' => 'announcement',
                'url' => '/announcements/create',
                'color' => 'blue'
            ],
            [
                'title' => 'Generate Report',
                'icon' => 'document-report',
                'url' => '/reports',
                'color' => 'purple'
            ]
        ];
    }
    
    /**
     * Get error response for dashboard errors
     * 
     * @param \Exception $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function getErrorResponse(\Exception $e)
    {
        \Log::error('Dashboard error: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error fetching dashboard data',
            'error' => config('app.debug') ? $e->getMessage() : 'Please try again later.',
            'stats' => [
                'totalStudents' => 0,
                'totalTeachers' => 0,
                'totalClasses' => 0,
                'totalStaff' => 0,
                'totalRevenue' => 0,
                'attendanceRate' => 0,
                'pendingAssignments' => 0,
                'upcomingEvents' => 0,
                'newStudentsThisMonth' => 0,
                'revenueGrowth' => 0
            ]
        ], 500);
    }
}
