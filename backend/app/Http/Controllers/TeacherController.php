<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    /**
     * Display a listing of the teachers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Teacher::class);

        $query = Teacher::with(['user', 'subjects', 'classes']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('employee_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $teachers = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => TeacherResource::collection($teachers),
            'meta' => [
                'total' => $teachers->total(),
                'per_page' => $teachers->perPage(),
                'current_page' => $teachers->currentPage(),
                'last_page' => $teachers->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created teacher in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Teacher::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'employee_id' => 'required|string|unique:teachers,employee_id',
            'qualification' => 'nullable|string|max:255',
            'gender' => 'required|in:male,female,other',
            'blood_group' => 'nullable|string|max:5',
            'date_of_birth' => 'nullable|date',
            'religion' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'phone' => 'required|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'present_address' => 'required|string',
            'permanent_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'joining_date' => 'required|date',
            'leaving_date' => 'nullable|date|after_or_equal:joining_date',
            'status' => 'required|in:active,inactive,on_leave,retired',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:100',
            'salary' => 'required|numeric|min:0',
            'salary_type' => 'required|in:monthly,hourly,daily,weekly',
            'nid_number' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            'driving_license' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
            'classes' => 'nullable|array',
            'classes.*.class_id' => 'exists:school_classes,id',
            'classes.*.is_class_teacher' => 'boolean',
        ]);

        $teacher = DB::transaction(function () use ($validated) {
            // Create user account
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]);

            // Assign teacher role
            $user->assignRole('teacher');

            // Create teacher record
            $teacherData = array_merge(
                collect($validated)->except(['name', 'email', 'password', 'password_confirmation', 'subjects', 'classes'])->toArray(),
                ['user_id' => $user->id]
            );
            
            $teacher = Teacher::create($teacherData);

            // Attach subjects if provided
            if (!empty($validated['subjects'])) {
                $teacher->subjects()->sync($validated['subjects']);
            }

            // Attach classes if provided
            if (!empty($validated['classes'])) {
                $classData = [];
                foreach ($validated['classes'] as $classItem) {
                    $classData[$classItem['class_id']] = [
                        'is_class_teacher' => $classItem['is_class_teacher'] ?? false,
                        'academic_session_id' => 1, // TODO: Get current academic session
                    ];
                }
                $teacher->classes()->sync($classData);
            }

            return $teacher->load(['user', 'subjects', 'classes']);
        });

        return response()->json([
            'message' => 'Teacher created successfully',
            'data' => new TeacherResource($teacher)
        ], 201);
    }

    /**
     * Display the specified teacher.
     *
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Teacher $teacher)
    {
        $this->authorize('view', $teacher);
        
        return response()->json([
            'data' => new TeacherResource($teacher->load(['user', 'subjects', 'classes']))
        ]);
    }

    /**
     * Update the specified teacher in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Teacher $teacher)
    {
        $this->authorize('update', $teacher);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($teacher->user_id)
            ],
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'employee_id' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('teachers', 'employee_id')->ignore($teacher->id)
            ],
            'qualification' => 'nullable|string|max:255',
            'gender' => 'sometimes|required|in:male,female,other',
            'blood_group' => 'nullable|string|max:5',
            'date_of_birth' => 'nullable|date',
            'religion' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'phone' => 'sometimes|required|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'present_address' => 'sometimes|required|string',
            'permanent_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'joining_date' => 'sometimes|required|date',
            'leaving_date' => 'nullable|date|after_or_equal:joining_date',
            'status' => 'sometimes|required|in:active,inactive,on_leave,retired',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:100',
            'salary' => 'sometimes|required|numeric|min:0',
            'salary_type' => 'sometimes|required|in:monthly,hourly,daily,weekly',
            'nid_number' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            'driving_license' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'subjects' => 'sometimes|array',
            'subjects.*' => 'exists:subjects,id',
            'classes' => 'sometimes|array',
            'classes.*.class_id' => 'exists:school_classes,id',
            'classes.*.is_class_teacher' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $teacher) {
            // Update user account if needed
            if (isset($validated['name']) || isset($validated['email']) || isset($validated['password'])) {
                $userData = [];
                if (isset($validated['name'])) {
                    $userData['name'] = $validated['name'];
                    unset($validated['name']);
                }
                if (isset($validated['email'])) {
                    $userData['email'] = $validated['email'];
                    unset($validated['email']);
                }
                if (isset($validated['password'])) {
                    $userData['password'] = Hash::make($validated['password']);
                    unset($validated['password']);
                }
                $teacher->user->update($userData);
            }

            // Update teacher record
            $teacher->update($validated);

            // Update subjects if provided
            if (isset($validated['subjects'])) {
                $teacher->subjects()->sync($validated['subjects']);
            }

            // Update classes if provided
            if (isset($validated['classes'])) {
                $classData = [];
                foreach ($validated['classes'] as $classItem) {
                    $classData[$classItem['class_id']] = [
                        'is_class_teacher' => $classItem['is_class_teacher'] ?? false,
                        'academic_session_id' => 1, // TODO: Get current academic session
                    ];
                }
                $teacher->classes()->sync($classData);
            }
        });

        return response()->json([
            'message' => 'Teacher updated successfully',
            'data' => new TeacherResource($teacher->fresh()->load(['user', 'subjects', 'classes']))
        ]);
    }

    /**
     * Remove the specified teacher from storage.
     *
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Teacher $teacher)
    {
        $this->authorize('delete', $teacher);

        DB::transaction(function () use ($teacher) {
            // Soft delete the user
            $teacher->user->delete();
            
            // Soft delete the teacher
            $teacher->delete();
        });

        return response()->json([
            'message' => 'Teacher deleted successfully'
        ]);
    }

    /**
     * Get options for teacher filters.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        $this->authorize('viewAny', Teacher::class);

        return response()->json([
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
                ['value' => 'on_leave', 'label' => 'On Leave'],
                ['value' => 'retired', 'label' => 'Retired'],
            ],
            'subjects' => Subject::select('id', 'name', 'code')->get(),
            'classes' => SchoolClass::select('id', 'name', 'code')->get(),
        ]);
    }

    /**
     * Get the teacher's dashboard statistics.
     *
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardStats(Teacher $teacher)
    {
        $this->authorize('view', $teacher);

        $stats = [
            'total_classes' => $teacher->classes()->count(),
            'total_subjects' => $teacher->subjects()->count(),
            'total_students' => 0, // TODO: Implement student count based on classes
            'attendance_rate' => 0, // TODO: Implement attendance calculation
        ];

        return response()->json([
            'data' => $stats
        ]);
    }
}
