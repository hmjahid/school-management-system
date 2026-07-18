<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AdmissionDocument;
use App\Models\AcademicSession;
use App\Models\Batch;
use App\Http\Resources\AdmissionResource;
use App\Http\Resources\AdmissionDocumentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class AdmissionController extends Controller
{
    /**
     * Display a listing of the admissions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Admission::class);

        $query = Admission::with([
            'academicSession',
            'batch',
            'createdBy',
            'updatedBy',
            'documents'
        ]);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('academic_session_id')) {
            $query->where('academic_session_id', $request->academic_session_id);
        }

        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->input('sort_field', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        if (in_array($sortField, ['application_number', 'first_name', 'last_name', 'email', 'phone', 'status', 'created_at'])) {
            $query->orderBy($sortField, $sortOrder);
        }

        $perPage = $request->per_page ?? 20;
        $admissions = $query->paginate($perPage);

        return AdmissionResource::collection($admissions);
    }

    /**
     * Store a newly created admission in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Admission::class);

        $validated = $this->validateAdmission($request);

        // Handle file uploads
        $validated = $this->handleFileUploads($request, $validated);

        // Create admission
        $admission = DB::transaction(function () use ($validated) {
            $admission = Admission::create($validated);
            
            // Create document records
            $this->createDocumentRecords($admission, $validated);
            
            return $admission->load('documents');
        });

        return response()->json([
            'message' => 'Admission application submitted successfully',
            'data' => new AdmissionResource($admission)
        ], 201);
    }

    /**
     * Display the specified admission.
     *
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Admission $admission)
    {
        $this->authorize('view', $admission);

        return new AdmissionResource($admission->load([
            'academicSession', 
            'batch', 
            'documents',
            'createdBy',
            'updatedBy'
        ]));
    }

    /**
     * Update the specified admission in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Admission $admission)
    {
        $this->authorize('update', $admission);

        // Only allow updates for draft or under review admissions
        if (!in_array($admission->status, [Admission::STATUS_DRAFT, Admission::STATUS_UNDER_REVIEW])) {
            return response()->json([
                'message' => 'Cannot update admission with current status: ' . $admission->status
            ], 403);
        }

        $validated = $this->validateAdmission($request, $admission->id);
        
        // Handle file uploads
        $validated = $this->handleFileUploads($request, $validated, $admission);

        // Update admission
        $admission = DB::transaction(function () use ($admission, $validated) {
            $admission->update($validated);
            
            // Update or create document records
            $this->createDocumentRecords($admission, $validated);
            
            return $admission->load('documents');
        });

        return response()->json([
            'message' => 'Admission application updated successfully',
            'data' => new AdmissionResource($admission)
        ]);
    }

    /**
     * Submit the admission for review.
     *
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Admission $admission)
    {
        $this->authorize('submit', $admission);

        if ($admission->submit()) {
            // TODO: Send notification to admin
            
            return response()->json([
                'message' => 'Admission submitted successfully',
                'data' => new AdmissionResource($admission->fresh())
            ]);
        }

        return response()->json([
            'message' => 'Unable to submit admission with current status: ' . $admission->status
        ], 422);
    }

    /**
     * Approve the admission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, Admission $admission)
    {
        $this->authorize('approve', $admission);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($admission->approve($validated['notes'] ?? null)) {
            // TODO: Send notification to applicant
            
            return response()->json([
                'message' => 'Admission approved successfully',
                'data' => new AdmissionResource($admission->fresh())
            ]);
        }

        return response()->json([
            'message' => 'Unable to approve admission with current status: ' . $admission->status
        ], 422);
    }

    /**
     * Reject the admission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, Admission $admission)
    {
        $this->authorize('reject', $admission);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        if ($admission->reject($validated['reason'])) {
            // TODO: Send notification to applicant
            
            return response()->json([
                'message' => 'Admission rejected',
                'data' => new AdmissionResource($admission->fresh())
            ]);
        }

        return response()->json([
            'message' => 'Unable to reject admission with current status: ' . $admission->status
        ], 422);
    }

    /**
     * Enroll the student.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Http\JsonResponse
     */
    public function enroll(Request $request, Admission $admission)
    {
        $this->authorize('enroll', $admission);

        $validated = $request->validate([
            'roll_number' => 'nullable|string|max:50|unique:students,roll_number',
            'admission_date' => 'required|date',
            'section_id' => 'required|exists:sections,id',
            'house' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'caste' => 'nullable|string|max:100',
            'aadhar_number' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'ifsc_code' => 'nullable|string|max:20',
            'rte_act' => 'boolean',
            'disability' => 'boolean',
            'disability_type' => 'nullable|string|max:100',
            'disability_percentage' => 'nullable|numeric|min:0|max:100',
            'medical_conditions' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $student = $admission->enroll($validated);

        if ($student) {
            // TODO: Send enrollment confirmation to student/parent
            
            return response()->json([
                'message' => 'Student enrolled successfully',
                'data' => [
                    'admission' => new AdmissionResource($admission->fresh()),
                    'student' => $student
                ]
            ]);
        }

        return response()->json([
            'message' => 'Unable to enroll student. Admission status: ' . $admission->status
        ], 422);
    }

    /**
     * Get filter options for admissions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterOptions()
    {
        $this->authorize('viewAny', Admission::class);

        $academicSessions = AcademicSession::active()
            ->orderBy('name', 'desc')
            ->get(['id', 'name']);

        $batches = Batch::active()
            ->with('course')
            ->orderBy('name')
            ->get(['id', 'name', 'course_id']);

        return response()->json([
            'academic_sessions' => $academicSessions,
            'batches' => $batches,
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'submitted', 'label' => 'Submitted'],
                ['value' => 'under_review', 'label' => 'Under Review'],
                ['value' => 'approved', 'label' => 'Approved'],
                ['value' => 'rejected', 'label' => 'Rejected'],
                ['value' => 'waitlisted', 'label' => 'Waitlisted'],
                ['value' => 'enrolled', 'label' => 'Enrolled'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ]
        ]);
    }

    /**
     * Upload admission document.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocument(Request $request, Admission $admission)
    {
        $this->authorize('uploadDocument', $admission);

        $validated = $request->validate([
            'type' => ['required', Rule::in([
                'transfer_certificate',
                'birth_certificate',
                'photo',
                'mark_sheet',
                'character_certificate',
                'migration_certificate',
                'other'
            ])],
            'file' => 'required|file|max:10240', // Max 10MB
            'description' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $path = $file->store("admissions/{$admission->id}/documents", 'public');

        $document = $admission->documents()->create([
            'type' => $validated['type'],
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'data' => new AdmissionDocumentResource($document)
        ], 201);
    }

    /**
     * Delete admission document.
     *
     * @param  \App\Models\Admission  $admission
     * @param  \App\Models\AdmissionDocument  $document
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDocument(Admission $admission, AdmissionDocument $document)
    {
        $this->authorize('deleteDocument', [$admission, $document]);

        // Delete the file from storage
        Storage::disk('public')->delete($document->file_path);
        
        // Delete the document record
        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully'
        ]);
    }

    /**
     * Validate admission data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|null  $admissionId
     * @return array
     */
    protected function validateAdmission(Request $request, $admissionId = null)
    {
        $rules = [
            // Application Information
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'batch_id' => 'required|exists:batches,id',
            
            // Student Information
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date|before:today',
            'blood_group' => 'nullable|string|max:10',
            'religion' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:50|default:Bangladeshi',
            'photo' => 'nullable|string',
            
            // Contact Information
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('admissions', 'email')->ignore($admissionId)
            ],
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100|default:Bangladesh',
            'postal_code' => 'required|string|max:20',
            
            // Parent/Guardian Information
            'father_name' => 'required|string|max:100',
            'father_phone' => 'required|string|max:20',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'required|string|max:100',
            'mother_phone' => 'required|string|max:20',
            'mother_occupation' => 'nullable|string|max:100',
            'guardian_name' => 'nullable|string|max:100',
            'guardian_relation' => 'nullable|string|max:50',
            'guardian_phone' => 'nullable|string|max:20',
            
            // Previous Education
            'previous_school' => 'nullable|string|max:255',
            'previous_class' => 'nullable|string|max:100',
            'previous_grade' => 'nullable|string|max:50',
            
            // Documents
            'transfer_certificate' => 'nullable|string',
            'birth_certificate' => 'nullable|string',
            'other_documents' => 'nullable|array',
            
            // Status
            'status' => 'sometimes|in:draft,submitted,under_review,approved,rejected,waitlisted,enrolled,cancelled',
            'admission_notes' => 'nullable|string|max:1000',
        ];

        return $request->validate($rules);
    }

    /**
     * Handle file uploads for admission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $validated
     * @param  \App\Models\Admission|null  $admission
     * @return array
     */
    protected function handleFileUploads(Request $request, array $validated, ?Admission $admission = null)
    {
        $fileFields = ['photo', 'transfer_certificate', 'birth_certificate'];
        $uploadedFiles = [];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $path = $file->store("admissions/{$admission?->id}/documents", 'public');
                $validated[$field] = $path;
                $uploadedFiles[] = [
                    'type' => $field === 'photo' ? 'photo' : $field,
                    'name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ];
            }
        }

        // Handle other documents
        if ($request->hasFile('other_documents')) {
            foreach ($request->file('other_documents') as $file) {
                $path = $file->store("admissions/{$admission?->id}/documents", 'public');
                $uploadedFiles[] = [
                    'type' => 'other',
                    'name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ];
            }
        }

        // Store uploaded files in metadata for later processing
        if (!empty($uploadedFiles)) {
            $validated['metadata'] = array_merge(
                $validated['metadata'] ?? [],
                ['uploaded_files' => $uploadedFiles]
            );
        }

        return $validated;
    }

    /**
     * Create document records from uploaded files.
     *
     * @param  \App\Models\Admission  $admission
     * @param  array  $validated
     * @return void
     */
    protected function createDocumentRecords(Admission $admission, array $validated)
    {
        if (isset($validated['metadata']['uploaded_files'])) {
            foreach ($validated['metadata']['uploaded_files'] as $file) {
                $admission->documents()->updateOrCreate(
                    ['file_path' => $file['file_path']],
                    [
                        'type' => $file['type'],
                        'name' => $file['name'],
                        'file_type' => $file['file_type'],
                        'file_size' => $file['file_size'],
                    ]
                );
            }
        }
    }
}
