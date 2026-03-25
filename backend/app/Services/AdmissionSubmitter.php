<?php

namespace App\Services;

use App\Models\Admission;
use App\Notifications\AdmissionSubmittedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/**
 * Guest-safe admission submission for the public website (no API authorization).
 */
class AdmissionSubmitter
{
    public function submitPublicApplication(Request $request): Admission
    {
        $validated = $this->validateAdmission($request);
        $validated = $this->handleFileUploads($request, $validated, null);

        $admission = DB::transaction(function () use ($validated) {
            $validated['status'] = Admission::STATUS_SUBMITTED;
            $validated['submitted_at'] = now();

            $admission = Admission::create($validated);
            $this->createDocumentRecords($admission, $validated);

            return $admission->load('documents');
        });

        Notification::route('mail', $admission->email)
            ->notify(new AdmissionSubmittedNotification($admission));

        return $admission;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateAdmission(Request $request): array
    {
        return $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'batch_id' => 'required|exists:batches,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date|before:today',
            'blood_group' => 'nullable|string|max:10',
            'religion' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:50',
            'photo' => 'nullable|image|max:5120',
            'email' => ['required', 'email', 'max:100', Rule::unique('admissions', 'email')],
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:20',
            'father_name' => 'required|string|max:100',
            'father_phone' => 'required|string|max:20',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'required|string|max:100',
            'mother_phone' => 'required|string|max:20',
            'mother_occupation' => 'nullable|string|max:100',
            'guardian_name' => 'nullable|string|max:100',
            'guardian_relation' => 'nullable|string|max:50',
            'guardian_phone' => 'nullable|string|max:20',
            'previous_school' => 'nullable|string|max:255',
            'previous_class' => 'nullable|string|max:100',
            'previous_grade' => 'nullable|string|max:50',
            'transfer_certificate' => 'nullable|file|max:10240',
            'birth_certificate' => 'nullable|file|max:10240',
            'other_documents' => 'nullable|array',
            'other_documents.*' => 'file|max:10240',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function handleFileUploads(Request $request, array $validated, ?Admission $admission = null): array
    {
        $fileFields = ['photo', 'transfer_certificate', 'birth_certificate'];
        $uploadedFiles = [];
        $prefix = $admission ? "admissions/{$admission->id}/documents" : 'admissions/public/documents';

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $path = $file->store($prefix, 'public');
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

        if ($request->hasFile('other_documents')) {
            foreach ($request->file('other_documents', []) as $file) {
                if (! $file) {
                    continue;
                }
                $path = $file->store($prefix, 'public');
                $uploadedFiles[] = [
                    'type' => 'other',
                    'name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ];
            }
        }

        if ($uploadedFiles !== []) {
            $validated['metadata'] = array_merge(
                $validated['metadata'] ?? [],
                ['uploaded_files' => $uploadedFiles]
            );
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function createDocumentRecords(Admission $admission, array $validated): void
    {
        if (! isset($validated['metadata']['uploaded_files'])) {
            return;
        }

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
