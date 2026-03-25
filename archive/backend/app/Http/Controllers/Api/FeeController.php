<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeeResource;
use App\Models\Fee;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FeeController extends Controller
{
    /**
     * Display a listing of the fees.
     */
    public function index(Request $request)
    {
        $query = Fee::with(['schoolClass', 'section', 'student']);

        // Filter by class
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        // Filter by section
        if ($request->has('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        // Filter by student
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Filter by fee type
        if ($request->has('fee_type')) {
            $query->where('fee_type', $request->fee_type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Order and paginate
        $fees = $query->latest()->paginate($request->per_page ?? 15);

        return FeeResource::collection($fees);
    }

    /**
     * Store a newly created fee in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateFee($request);

        $fee = Fee::create($validated);

        return new FeeResource($fee->load(['schoolClass', 'section', 'student']));
    }

    /**
     * Display the specified fee.
     */
    public function show(Fee $fee)
    {
        return new FeeResource($fee->load(['schoolClass', 'section', 'student']));
    }

    /**
     * Update the specified fee in storage.
     */
    public function update(Request $request, Fee $fee)
    {
        $validated = $this->validateFee($request, $fee->id);

        $fee->update($validated);

        return new FeeResource($fee->load(['schoolClass', 'section', 'student']));
    }

    /**
     * Remove the specified fee from storage.
     */
    public function destroy(Fee $fee)
    {
        // Prevent deletion if there are payments
        if ($fee->payments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete fee with existing payments.'
            ], 422);
        }

        $fee->delete();

        return response()->noContent();
    }

    /**
     * Get fee types.
     */
    public function getFeeTypes()
    {
        return response()->json([
            'data' => Fee::getFeeTypes()
        ]);
    }

    /**
     * Get fee frequencies.
     */
    public function getFrequencies()
    {
        return response()->json([
            'data' => Fee::getFrequencies()
        ]);
    }

    /**
     * Validate fee data.
     */
    protected function validateFee(Request $request, $id = null)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('fees')->ignore($id)],
            'description' => ['nullable', 'string'],
            'class_id' => ['required', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'student_id' => ['nullable', 'exists:students,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'fee_type' => ['required', 'string', Rule::in(array_keys(Fee::getFeeTypes()))],
            'frequency' => ['required', 'string', Rule::in(array_keys(Fee::getFrequencies()))],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'fine_amount' => ['nullable', 'numeric', 'min:0'],
            'fine_type' => ['required_with:fine_amount', 'in:fixed,percentage'],
            'fine_grace_days' => ['nullable', 'integer', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['required_with:discount_amount', 'in:fixed,percentage'],
            'status' => ['required', 'in:active,inactive,archived'],
            'metadata' => ['nullable', 'array'],
        ];

        // Conditional validation for end_date based on frequency
        if ($request->frequency === Fee::FREQUENCY_ONE_TIME) {
            $rules['start_date'][] = 'required';
            $rules['end_date'][] = 'required';
        }

        return $request->validate($rules);
    }

    /**
     * Get fee structure for a student.
     */
    public function getStudentFeeStructure(Student $student)
    {
        $fees = Fee::query()
            ->where(function($query) use ($student) {
                $query->where('class_id', $student->class_id)
                    ->whereNull('student_id')
                    ->where('status', 'active');
                
                if ($student->section_id) {
                    $query->orWhere('section_id', $student->section_id)
                        ->whereNull('student_id')
                        ->where('status', 'active');
                }
                
                $query->orWhere('student_id', $student->id)
                    ->where('status', 'active');
            })
            ->with(['schoolClass', 'section'])
            ->get();

        return FeeResource::collection($fees);
    }

    /**
     * Get fee summary for a class.
     */
    public function getClassFeeSummary(SchoolClass $class)
    {
        $fees = Fee::where('class_id', $class->id)
            ->whereNull('student_id')
            ->where('status', 'active')
            ->with(['schoolClass', 'section'])
            ->get();

        return FeeResource::collection($fees);
    }
}
