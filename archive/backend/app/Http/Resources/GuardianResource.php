<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->phone,
            'occupation' => $this->occupation,
            'company' => $this->company,
            'nid_number' => $this->nid_number,
            'passport_number' => $this->passport_number,
            'driving_license' => $this->driving_license,
            'nationality' => $this->nationality,
            'religion' => $this->religion,
            'blood_group' => $this->blood_group,
            'present_address' => $this->present_address,
            'permanent_address' => $this->permanent_address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'country' => $this->country,
            'office_phone' => $this->office_phone,
            'emergency_contact' => $this->emergency_contact,
            'relationship' => $this->relationship,
            'is_primary' => (bool) $this->is_primary,
            'monthly_income' => (float) $this->monthly_income,
            'education_level' => $this->education_level,
            'notes' => $this->notes,
            'full_address' => $this->full_address,
            'primary_contact' => $this->primary_contact,
            'emergency_contact_info' => $this->emergency_contact,
            'total_students' => $this->when(isset($this->total_students), $this->total_students),
            'total_outstanding_balance' => $this->when(isset($this->total_outstanding_balance), $this->total_outstanding_balance),
            'total_paid_amount' => $this->when(isset($this->total_paid_amount), $this->total_paid_amount),
            'status' => $this->user->is_active ? 'active' : 'inactive',
            'status_badge' => $this->status_badge,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->when($this->deleted_at, function () {
                return $this->deleted_at->format('Y-m-d H:i:s');
            }),
            'students' => $this->whenLoaded('students', function () {
                return $this->students->map(function ($student) {
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
                        'relationship' => $student->pivot->relationship,
                        'is_primary' => (bool) $student->pivot->is_primary,
                    ];
                });
            }),
            'links' => [
                'show' => route('api.guardians.show', $this->id),
                'edit' => route('admin.guardians.edit', $this->id),
                'students' => route('api.guardians.students', $this->id),
                'payments' => route('api.guardians.payments.index', $this->id),
                'invoices' => route('api.guardians.invoices.index', $this->id),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'meta' => [
                'can' => [
                    'view' => $request->user() ? $request->user()->can('view', $this->resource) : false,
                    'update' => $request->user() ? $request->user()->can('update', $this->resource) : false,
                    'delete' => $request->user() ? $request->user()->can('delete', $this->resource) : false,
                    'view_students' => $request->user() ? $request->user()->can('viewStudents', $this->resource) : false,
                    'manage_students' => $request->user() ? $request->user()->can('manageStudents', $this->resource) : false,
                    'view_payments' => $request->user() ? $request->user()->can('viewPayments', $this->resource) : false,
                    'manage_payments' => $request->user() ? $request->user()->can('managePayments', $this->resource) : false,
                ],
            ],
        ];
    }
}
