<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\Assignment;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard statistics
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Check if user is admin
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        try {
            // Get counts with error handling
            $totalStudents = 0;
            $totalTeachers = 0;
            $totalClasses = 0;
            $totalStaff = 0;

            try {
                $totalStudents = Student::count();
            } catch (\Exception $e) {
                \Log::debug('Student count error: ' . $e->getMessage());
            }

            try {
                $totalTeachers = Teacher::count();
            } catch (\Exception $e) {
                \Log::debug('Teacher count error: ' . $e->getMessage());
            }

            try {
                $totalClasses = SchoolClass::count();
            } catch (\Exception $e) {
                \Log::debug('Class count error: ' . $e->getMessage());
            }

            try {
                $totalStaff = User::whereHas('roles', function($query) {
                    $query->where('name', 'staff');
                })->count();
            } catch (\Exception $e) {
                \Log::debug('Staff count error: ' . $e->getMessage());
            }

            // Generate labels for the last 6 months
            $monthlyLabels = collect();
            for ($i = 5; $i >= 0; $i--) {
                $monthlyLabels->push(now()->subMonths($i)->format('M Y'));
            }

            // Initialize data arrays
            $monthlyRevenue = [];
            $monthlyNewStudents = [];
            $attendanceRates = [];
            $totalRevenue = 0;
            $classDistribution = [];
            $studentPerformance = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];

            // Calculate monthly data
            $monthlyData = $monthlyLabels->mapWithKeys(function ($month) use (&$monthlyRevenue, &$monthlyNewStudents, &$attendanceRates) {
                $date = Carbon::createFromFormat('M Y', $month);
                $startOfMonth = $date->copy()->startOfMonth();
                $endOfMonth = $date->copy()->endOfMonth();

                // Monthly Revenue
                $revenue = 0;
                try {
                    if (class_exists(Payment::class)) {
                        $revenue = Payment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                            ->where('payment_status', 'completed')
                            ->sum('total_amount');
                    }
                } catch (\Exception $e) {
                    \Log::debug('Revenue calculation error: ' . $e->getMessage());
                }
                $monthlyRevenue[] = (float) $revenue;

                // New Students
                $newStudents = 0;
                try {
                    $newStudents = Student::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
                } catch (\Exception $e) {
                    \Log::debug('New students count error: ' . $e->getMessage());
                }
                $monthlyNewStudents[] = $newStudents;

                // Attendance (sample data - replace with actual attendance calculation)
                $attendanceRates[] = rand(85, 98);

                return [
                    $month => [
                        'revenue' => $revenue,
                        'new_students' => $newStudents,
                        'attendance_rate' => $attendanceRates[count($attendanceRates) - 1]
                    ]
                ];
            });

            // Calculate total revenue
            try {
                if (class_exists(Payment::class)) {
                    $totalRevenue = Payment::where('payment_status', 'completed')->sum('total_amount');
                }
            } catch (\Exception $e) {
                \Log::debug('Total revenue calculation error: ' . $e->getMessage());
            }

            // Class distribution (sample data - replace with actual data)
            try {
                if (class_exists(SchoolClass::class)) {
                    $classDistribution = SchoolClass::withCount('students')
                        ->orderBy('name')
                        ->limit(10)
                        ->get()
                        ->mapWithKeys(function ($class) {
                            return [$class->name => $class->students_count ?? 0];
                        });
                }
            } catch (\Exception $e) {
                \Log::debug('Class distribution error: ' . $e->getMessage());
                $classDistribution = collect([
                    'Class 1' => 45,
                    'Class 2' => 42,
                    'Class 3' => 48,
                    'Class 4' => 50,
                    'Class 5' => 46,
                ]);
            }

            // Pending assignments (sample data - replace with actual data)
            $pendingAssignments = class_exists(Assignment::class) ?
                Assignment::where('due_date', '>=', now())->count() : 15;

            // Upcoming events (sample data - replace with actual data)
            $upcomingEvents = class_exists(Event::class) ?
                Event::where('start_date', '>=', now())->count() : 3;

            // Recent activity (sample data - replace with actual data)
            $recentActivity = [
                [
                    'id' => 1,
                    'type' => 'enrollment',
                    'title' => 'New Student Enrollment',
                    'message' => '5 new students enrolled',
                    'time' => now()->subHours(2)->diffForHumans(),
                    'icon' => 'user-plus',
                    'color' => 'success'
                ],
                [
                    'id' => 2,
                    'type' => 'payment',
                    'title' => 'Fee Payment Received',
                    'message' => 'Monthly fee collected from 12 students',
                    'time' => now()->subDay()->diffForHumans(),
                    'icon' => 'dollar-sign',
                    'color' => 'primary'
                ],
                [
                    'id' => 3,
                    'type' => 'attendance',
                    'title' => 'Attendance Marked',
                    'message' => 'Attendance marked for Class 10-A (95% present)',
                    'time' => now()->subDays(2)->diffForHumans(),
                    'icon' => 'clipboard-check',
                    'color' => 'info'
                ],
                [
                    'id' => 4,
                    'type' => 'assignment',
                    'title' => 'New Assignment',
                    'message' => 'New assignment added for Mathematics',
                    'time' => now()->subDays(3)->diffForHumans(),
                    'icon' => 'book',
                    'color' => 'warning'
                ],
                [
                    'id' => 5,
                    'type' => 'announcement',
                    'title' => 'School Announcement',
                    'message' => 'School will remain closed tomorrow due to public holiday',
                    'time' => now()->subDays(5)->diffForHumans(),
                    'icon' => 'bullhorn',
                    'color' => 'danger'
                ]
            ];

            // Quick actions
            $quickActions = [
                [
                    'id' => 1,
                    'title' => 'Add New Student',
                    'description' => 'Register a new student',
                    'icon' => 'user-plus',
                    'url' => '/admin/students/create',
                    'permission' => 'students.create'
                ],
                [
                    'id' => 2,
                    'title' => 'Create New Class',
                    'description' => 'Add a new class/section',
                    'icon' => 'layers',
                    'url' => '/admin/classes/create',
                    'permission' => 'classes.create'
                ],
                [
                    'id' => 3,
                    'title' => 'Record Payment',
                    'description' => 'Record manual payment',
                    'icon' => 'dollar-sign',
                    'url' => '/admin/payments/create',
                    'permission' => 'payments.create'
                ],
                [
                    'id' => 4,
                    'title' => 'Send Announcement',
                    'description' => 'Send notification to users',
                    'icon' => 'bell',
                    'url' => '/admin/announcements/create',
                    'permission' => 'announcements.create'
                ]
            ];

            return response()->json([
                'success' => true,
                'stats' => [
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
                ],
                'charts' => [
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
                        'labels' => $classDistribution->keys(),
                        'data' => $classDistribution->values(),
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
                ],
                'recentActivity' => $recentActivity,
                'quickActions' => $quickActions,
                'lastUpdated' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            // Log the error and return a meaningful message
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
                ],
            ], 500);
        }
    }
}
