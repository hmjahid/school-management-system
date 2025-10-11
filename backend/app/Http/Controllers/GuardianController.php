<?php

namespace App\Http\Controllers;

use App\Http\Resources\GuardianResource;
use App\Models\Guardian;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GuardianController extends Controller
{
    /**
     * Display a listing of the guardians.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Guardian::class);

        $query = Guardian::with(['user', 'students']);

        // Apply filters
        if ($request->has('is_active')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            });
        }

        if ($request->has('has_students')) {
            if (filter_var($request->has_students, FILTER_VALIDATE_BOOLEAN)) {
                $query->has('students');
            } else {
                $query->doesntHave('students');
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('phone', 'like', "%{$search}%");
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        
        if ($sortField === 'name' || $sortField === 'email') {
            $query->join('users', 'guardians.user_id', '=', 'users.id')
                  ->orderBy("users.{$sortField}", $sortOrder)
                  ->select('guardians.*');
        } elseif (in_array($sortField, ['created_at', 'updated_at'])) {
            $query->orderBy($sortField, $sortOrder);
        }

        $guardians = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => GuardianResource::collection($guardians),
            'meta' => [
                'total' => $guardians->total(),
                'per_page' => $guardians->perPage(),
                'current_page' => $guardians->currentPage(),
                'last_page' => $guardians->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created guardian in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Guardian::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'occupation' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:100',
            'nid_number' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            'driving_license' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:50',
            'blood_group' => 'nullable|string|max:5',
            'present_address' => 'required|string',
            'permanent_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'office_phone' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'relationship' => 'nullable|string|max:50',
            'is_primary' => 'boolean',
            'monthly_income' => 'nullable|numeric|min:0',
            'education_level' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'students' => 'sometimes|array',
            'students.*.student_id' => 'exists:students,id',
            'students.*.relationship' => 'required_with:students.*.student_id|string|max:50',
            'students.*.is_primary' => 'boolean',
        ]);

        $guardian = DB::transaction(function () use ($validated) {
            // Create user account
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]);

            // Assign guardian role
            $user->assignRole('guardian');

            // Create guardian record
            $guardianData = array_merge(
                collect($validated)->except(['name', 'email', 'password', 'password_confirmation', 'students'])->toArray(),
                ['user_id' => $user->id]
            );
            
            $guardian = Guardian::create($guardianData);

            // Attach students if provided
            if (!empty($validated['students'])) {
                $studentData = [];
                foreach ($validated['students'] as $student) {
                    $studentData[$student['student_id']] = [
                        'relationship' => $student['relationship'],
                        'is_primary' => $student['is_primary'] ?? false,
                    ];
                }
                $guardian->students()->attach($studentData);
            }

            return $guardian->load(['user', 'students']);
        });

        return response()->json([
            'message' => 'Guardian created successfully',
            'data' => new GuardianResource($guardian)
        ], 201);
    }

    /**
     * Display the specified guardian.
     *
     * @param  \App\Models\Guardian  $guardian
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Guardian $guardian)
    {
        $this->authorize('view', $guardian);
        
        return response()->json([
            'data' => new GuardianResource($guardian->load(['user', 'students.user', 'students.class']))
        ]);
    }

    /**
     * Update the specified guardian in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Guardian  $guardian
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Guardian $guardian)
    {
        $this->authorize('update', $guardian);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($guardian->user_id)
            ],
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'phone' => 'sometimes|required|string|max:20',
            'occupation' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:100',
            'nid_number' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            'driving_license' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:50',
            'blood_group' => 'nullable|string|max:5',
            'present_address' => 'sometimes|required|string',
            'permanent_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'office_phone' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'relationship' => 'nullable|string|max:50',
            'is_primary' => 'sometimes|boolean',
            'monthly_income' => 'nullable|numeric|min:0',
            'education_level' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'students' => 'sometimes|array',
            'students.*.student_id' => 'exists:students,id',
            'students.*.relationship' => 'required_with:students.*.student_id|string|max:50',
            'students.*.is_primary' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $guardian) {
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
                $guardian->user->update($userData);
            }

            // Update guardian record
            $guardian->update($validated);

            // Sync students if provided
            if (isset($validated['students'])) {
                $studentData = [];
                foreach ($validated['students'] as $student) {
                    $studentData[$student['student_id']] = [
                        'relationship' => $student['relationship'],
                        'is_primary' => $student['is_primary'] ?? false,
                    ];
                }
                $guardian->students()->sync($studentData);
            }
        });

        return response()->json([
            'message' => 'Guardian updated successfully',
            'data' => new GuardianResource($guardian->fresh()->load(['user', 'students.user', 'students.class']))
        ]);
    }

    /**
     * Remove the specified guardian from storage.
     *
     * @param  \App\Models\Guardian  $guardian
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Guardian $guardian)
    {
        $this->authorize('delete', $guardian);

        // Check if guardian has any students
        if ($guardian->students()->exists()) {
            return response()->json([
                'message' => 'Cannot delete guardian with students. Please remove all student associations first.'
            ], 422);
        }

        // Check if guardian has any fee payments
        if ($guardian->feePayments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete guardian with fee payment history.'
            ], 422);
        }

        DB::transaction(function () use ($guardian) {
            // Soft delete the user
            $guardian->user->delete();
            
            // Soft delete the guardian
            $guardian->delete();
        });

        return response()->json([
            'message' => 'Guardian deleted successfully'
        ]);
    }

    /**
     * Get options for guardian filters.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        $this->authorize('viewAny', Guardian::class);

        return response()->json([
            'statuses' => [
                ['value' => '1', 'label' => 'Active'],
                ['value' => '0', 'label' => 'Inactive'],
            ],
            'has_students' => [
                ['value' => '1', 'label' => 'With Students'],
                ['value' => '0', 'label' => 'Without Students'],
            ],
            'relationships' => [
                'Father', 'Mother', 'Brother', 'Sister', 'Uncle', 'Aunt', 'Grandfather', 'Grandmother', 'Other'
            ]
        ]);
    }

    /**
     * Get guardian statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics()
    {
        $this->authorize('viewAny', Guardian::class);

        $stats = [
            'total_guardians' => Guardian::count(),
            'active_guardians' => User::whereHas('roles', function($q) {
                $q->where('name', 'guardian');
            })->where('is_active', true)->count(),
            'guardians_with_students' => Guardian::has('students')->count(),
            'guardians_without_students' => Guardian::doesntHave('students')->count(),
            'primary_guardians' => DB::table('guardian_student')
                ->where('is_primary', true)
                ->distinct('guardian_id')
                ->count('guardian_id'),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Get students available for guardian association.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableStudents()
    {
        $this->authorize('manageStudents', Guardian::class);

        $students = Student::with(['user', 'class'])
            ->whereDoesntHave('guardians', function($q) {
                $q->where('is_primary', true);
            })
            ->get()
            ->map(function($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->user->name,
                    'admission_number' => $student->admission_number,
                    'class' => $student->class ? $student->class->name : null,
                ];
            });

        return response()->json([
            'data' => $students
        ]);
    }

    /**
     * Get guardian's students with details.
     *
     * @param  \App\Models\Guardian  $guardian
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudents(Guardian $guardian)
    {
        $this->authorize('viewStudents', $guardian);

        $students = $guardian->students()
            ->with(['user', 'class', 'section'])
            ->get()
            ->map(function($student) use ($guardian) {
                $pivot = $student->pivot;
                return [
                    'id' => $student->id,
                    'name' => $student->user->name,
                    'admission_number' => $student->admission_number,
                    'roll_number' => $student->roll_number,
                    'class' => $student->class ? [
                        'id' => $student->class->id,
                        'name' => $student->class->name,
                    ] : null,
                    'section' => $student->section ? [
                        'id' => $student->section->id,
                        'name' => $student->section->name,
                    ] : null,
                    'relationship' => $pivot->relationship,
                    'is_primary' => (bool) $pivot->is_primary,
                    'attendance_rate' => 0, // TODO: Implement attendance calculation
                    'last_attendance' => null, // TODO: Implement last attendance
                ];
            });

        return response()->json([
            'data' => $students
        ]);
    }
}
