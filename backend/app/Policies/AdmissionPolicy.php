<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Admission;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdmissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('view_any_admission');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Admission $admission)
    {
        // Allow if user has permission and is the applicant, or is an admin/staff
        $isApplicant = $user->id === $admission->created_by;
        $isStaff = $user->hasAnyRole(['admin', 'staff']);
        
        return $user->can('view_admission') && ($isApplicant || $isStaff);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Only allow creation if user has the permission
        return $user->can('create_admission');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Admission $admission)
    {
        // Only allow updates if the admission is in draft or under review status
        // and the user is the applicant or an admin/staff with permission
        $canEdit = in_array($admission->status, [
            Admission::STATUS_DRAFT, 
            Admission::STATUS_UNDER_REVIEW
        ]);
        
        $isOwner = $user->id === $admission->created_by;
        $isStaff = $user->hasAnyRole(['admin', 'staff']);
        
        return $user->can('edit_admission') && $canEdit && ($isOwner || $isStaff);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Admission $admission)
    {
        // Only allow deletion if the admission is in draft status
        // and the user is the applicant or an admin
        $canDelete = $admission->status === Admission::STATUS_DRAFT;
        $isOwner = $user->id === $admission->created_by;
        
        return $user->can('delete_admission') && $canDelete && ($isOwner || $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can submit the admission for review.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function submit(User $user, Admission $admission)
    {
        // Only allow submission if the admission is in draft status
        // and the user is the applicant
        $canSubmit = $admission->status === Admission::STATUS_DRAFT;
        $isOwner = $user->id === $admission->created_by;
        
        return $user->can('submit_admission') && $canSubmit && $isOwner;
    }

    /**
     * Determine whether the user can approve the admission.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function approve(User $user, Admission $admission)
    {
        // Only allow approval if the admission is submitted or under review
        // and the user is an admin or staff with permission
        $canApprove = in_array($admission->status, [
            Admission::STATUS_SUBMITTED,
            Admission::STATUS_UNDER_REVIEW
        ]);
        
        return $user->can('approve_admission') && $canApprove && $user->hasAnyRole(['admin', 'staff']);
    }

    /**
     * Determine whether the user can reject the admission.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function reject(User $user, Admission $admission)
    {
        // Only allow rejection if the admission is submitted or under review
        // and the user is an admin or staff with permission
        $canReject = in_array($admission->status, [
            Admission::STATUS_SUBMITTED,
            Admission::STATUS_UNDER_REVIEW
        ]);
        
        return $user->can('reject_admission') && $canReject && $user->hasAnyRole(['admin', 'staff']);
    }

    /**
     * Determine whether the user can enroll the student.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function enroll(User $user, Admission $admission)
    {
        // Only allow enrollment if the admission is approved
        // and the user is an admin or staff with permission
        $canEnroll = $admission->status === Admission::STATUS_APPROVED;
        
        return $user->can('enroll_student') && $canEnroll && $user->hasAnyRole(['admin', 'staff']);
    }

    /**
     * Determine whether the user can upload documents for the admission.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function uploadDocument(User $user, Admission $admission)
    {
        // Only allow document upload if the admission is in draft or under review
        // and the user is the applicant or an admin/staff
        $canUpload = in_array($admission->status, [
            Admission::STATUS_DRAFT,
            Admission::STATUS_UNDER_REVIEW
        ]);
        
        $isOwner = $user->id === $admission->created_by;
        $isStaff = $user->hasAnyRole(['admin', 'staff']);
        
        return $user->can('upload_admission_document') && $canUpload && ($isOwner || $isStaff);
    }

    /**
     * Determine whether the user can delete documents from the admission.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admission  $admission
     * @param  \App\Models\AdmissionDocument  $document
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function deleteDocument(User $user, Admission $admission, $document)
    {
        // Only allow document deletion if the admission is in draft or under review
        // and the user is the applicant or an admin/staff with permission
        $canDelete = in_array($admission->status, [
            Admission::STATUS_DRAFT,
            Admission::STATUS_UNDER_REVIEW
        ]);
        
        $isOwner = $user->id === $admission->created_by;
        $isStaff = $user->hasAnyRole(['admin', 'staff']);
        
        return $user->can('delete_admission_document') && $canDelete && ($isOwner || $isStaff);
    }

    /**
     * Determine whether the user can view the admission's documents.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admission  $admission
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewDocuments(User $user, Admission $admission)
    {
        // Allow viewing documents if the user is the applicant or an admin/staff
        $isOwner = $user->id === $admission->created_by;
        $isStaff = $user->hasAnyRole(['admin', 'staff']);
        
        return $user->can('view_admission_documents') && ($isOwner || $isStaff);
    }

    /**
     * Determine whether the user can export admissions.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function export(User $user)
    {
        return $user->can('export_admissions') && $user->hasAnyRole(['admin', 'staff']);
    }

    /**
     * Determine whether the user can import admissions.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function import(User $user)
    {
        return $user->can('import_admissions') && $user->hasRole('admin');
    }
}
