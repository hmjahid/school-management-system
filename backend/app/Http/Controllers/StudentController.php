<?php

namespace App\Http\Controllers;

use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Batch;
use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    /**
     * Display a listing of the students.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Student::class);

        $query = Student::with(['user', 'class', 'section', 'batch', 'guardian']);

        // Apply filters
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('admission_number', 'like', "%{$search}%")
                  ->orWhere('roll_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $students = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => StudentResource::collection($students),
            'meta' => [
                'total' => $students->total(),
                'per_page' => $students->perPage(),
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created student in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Student::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'batch_id' => 'nullable|exists:batches,id',
            'guardian_id' => 'nullable|exists:guardians,id',
            'admission_number' => 'required|string|unique:students,admission_number',
            'admission_date' => 'required|date',
            'roll_number' => 'nullable|string|max:50',
            'blood_group' => 'nullable|string|max:5',
            'religion' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'nid_number' => 'nullable|string|max:50',
            'birth_certificate_number' => 'nullable|string|max:50',
            'permanent_address' => 'nullable|string',
            'present_address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone_1' => 'nullable|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'parent_name' => 'nullable|string|max:100',
            'parent_phone' => 'nullable|string|max:20',
            'parent_email' => 'nullable|email|max:100',
            'parent_occupation' => 'nullable|string|max:100',
            'parent_address' => 'nullable|string',
            'monthly_fee' => 'nullable|numeric|min:0',
            'transport_fee' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive,graduated,transferred',
            'notes' => 'nullable|string',
        ]);

        $student = DB::transaction(function () use ($validated) {
            // Create user account
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]);

            // Assign student role
            $user->assignRole('student');

            // Create student record
            $studentData = array_merge(
                $validated,
                ['user_id' => $user->id]
            );
            
            // Remove user creation fields
            unset($studentData['name'], $studentData['email'], $studentData['password'], $studentData['password_confirmation']);
            
            return Student::create($studentData);
        });

        return response()->json([
            'message' => 'Student created successfully',
            'data' => new StudentResource($student->load(['user', 'class', 'section', 'batch', 'guardian']))
        ], 201);
    }

    /**
     * Display the specified student.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Student $student)
    {
        $this->authorize('view', $student);
        
        return response()->json([
            'data' => new StudentResource($student->load(['user', 'class', 'section', 'batch', 'guardian']))
        ]);
    }

    /**
     * Update the specified student in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Student $student)
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($student->user_id)
            ],
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'class_id' => 'sometimes|required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'batch_id' => 'nullable|exists:batches,id',
            'guardian_id' => 'nullable|exists:guardians,id',
            'admission_number' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('students', 'admission_number')->ignore($student->id)
            ],
            'admission_date' => 'sometimes|required|date',
            'roll_number' => 'nullable|string|max:50',
            'blood_group' => 'nullable|string|max:5',
            'religion' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'nid_number' => 'nullable|string|max:50',
            'birth_certificate_number' => 'nullable|string|max:50',
            'permanent_address' => 'nullable|string',
            'present_address' => 'sometimes|required|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone_1' => 'nullable|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'parent_name' => 'nullable|string|max:100',
            'parent_phone' => 'nullable|string|max:20',
            'parent_email' => 'nullable|email|max:100',
            'parent_occupation' => 'nullable|string|max:100',
            'parent_address' => 'nullable|string',
            'monthly_fee' => 'nullable|numeric|min:0',
            'transport_fee' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'status' => 'sometimes|required|in:active,inactive,graduated,transferred',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $student) {
            // Update user account if needed
            if (isset($validated['name']) || isset($validated['email'])) {
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
                $student->user->update($userData);
            }

            // Update student record
            $student->update($validated);
        });

        return response()->json([
            'message' => 'Student updated successfully',
            'data' => new StudentResource($student->fresh()->load(['user', 'class', 'section', 'batch', 'guardian']))
        ]);
    }

    /**
     * Remove the specified student from storage.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Student $student)
    {
        $this->authorize('delete', $student);

        DB::transaction(function () use ($student) {
            // Soft delete the user
            $student->user->delete();
            
            // Soft delete the student
            $student->delete();
        });

        return response()->json([
            'message' => 'Student deleted successfully'
        ]);
    }

    /**
     * Get options for student filters.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterOptions()
    {
        return response()->json([
            'classes' => SchoolClass::select('id', 'name', 'code')->get(),
            'sections' => Section::select('id', 'name', 'class_id')->get(),
            'batches' => Batch::select('id', 'name', 'academic_year')->get(),
            'guardians' => Guardian::with('user:id,name')
                ->select('id', 'user_id', 'phone')
                ->get()
                ->map(function($guardian) {
                    return [
                        'id' => $guardian->id,
                        'name' => $guardian->user->name,
                        'phone' => $guardian->phone
                    ];
                }),
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
                ['value' => 'graduated', 'label' => 'Graduated'],
                ['value' => 'transferred', 'label' => 'Transferred'],
            ]
        ]);
    }
}
