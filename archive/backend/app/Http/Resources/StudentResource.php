<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'class_id' => $this->class_id,
            'class' => $this->whenLoaded('class', function () {
                return [
                    'id' => $this->class->id,
                    'name' => $this->class->name,
                    'code' => $this->class->code,
                ];
            }),
            'section_id' => $this->section_id,
            'section' => $this->whenLoaded('section', function () {
                return $this->section ? [
                    'id' => $this->section->id,
                    'name' => $this->section->name,
                ] : null;
            }),
            'batch_id' => $this->batch_id,
            'batch' => $this->whenLoaded('batch', function () {
                return $this->batch ? [
                    'id' => $this->batch->id,
                    'name' => $this->batch->name,
                    'academic_year' => $this->batch->academic_year,
                ] : null;
            }),
            'guardian_id' => $this->guardian_id,
            'guardian' => $this->whenLoaded('guardian', function () {
                return $this->guardian ? [
                    'id' => $this->guardian->id,
                    'name' => $this->guardian->user->name,
                    'phone' => $this->guardian->phone,
                    'email' => $this->guardian->user->email,
                ] : null;
            }),
            'admission_number' => $this->admission_number,
            'admission_date' => $this->admission_date->format('Y-m-d'),
            'roll_number' => $this->roll_number,
            'blood_group' => $this->blood_group,
            'religion' => $this->religion,
            'nationality' => $this->nationality,
            'nid_number' => $this->nid_number,
            'birth_certificate_number' => $this->birth_certificate_number,
            'permanent_address' => $this->permanent_address,
            'present_address' => $this->present_address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'country' => $this->country,
            'phone_1' => $this->phone_1,
            'phone_2' => $this->phone_2,
            'parent_name' => $this->parent_name,
            'parent_phone' => $this->parent_phone,
            'parent_email' => $this->parent_email,
            'parent_occupation' => $this->parent_occupation,
            'parent_address' => $this->parent_address,
            'monthly_fee' => (float) $this->monthly_fee,
            'transport_fee' => (float) $this->transport_fee,
            'discount' => (float) $this->discount,
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'notes' => $this->notes,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->when($this->deleted_at, function () {
                return $this->deleted_at->format('Y-m-d H:i:s');
            }),
            'links' => [
                'profile' => route('api.students.show', $this->id),
                'edit' => route('admin.students.edit', $this->id),
                'attendance' => route('api.students.attendance.index', $this->id),
                'results' => route('api.students.results.index', $this->id),
                'fees' => route('api.students.fees.index', $this->id),
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
                ],
            ],
        ];
    }
}
