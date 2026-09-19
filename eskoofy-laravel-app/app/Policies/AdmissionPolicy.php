<?php

namespace App\Policies;

use App\Models\Admission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdmissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('view_admissions');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Admission $admission)
    {
        // Allow if user has permission and is the applicant, or is an admin/staff
        $isApplicant = $user->id === $admission->created_by;
        $isStaff = $user->hasAnyRole(['admin', 'staff']);

        return $user->can('view_admissions') && ($isApplicant || $isStaff);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Only allow creation if user has the permission
        return $user->can('create_admissions');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Admission $admission)
    {
        // Only allow updates if the admission is in draft or under review status
        // and the user is the applicant or an admin/staff with permission
        $canEdit = in_array($admission->status, [
            Admission::STATUS_DRAFT,
            Admission::STATUS_UNDER_REVIEW,
        ]);

        $isOwner = $user->id === $admission->created_by;
        $isStaff = $user->hasAnyRole(['admin', 'staff']);

        return $user->can('edit_admissions') && $canEdit && ($isOwner || $isStaff);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Admission $admission)
    {
        // Only allow deletion if the admission is in draft status
        // and the user is the applicant or an admin
        $canDelete = $admission->status === Admission::STATUS_DRAFT;
        $isOwner = $user->id === $admission->created_by;

        return $user->can('delete_admissions') && $canDelete && ($isOwner || $user->hasRole('admin'));
    }

    /**
     * Determine whether the user can submit the admission for review.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function submit(User $user, Admission $admission)
    {
        // Only allow submission if the admission is in draft status
        // and the user is the applicant
        $canSubmit = $admission->status === Admission::STATUS_DRAFT;
        $isOwner = $user->id === $admission->created_by;

        // Public admissions are submitted in one step; for internal use, treat as edit permission.
        return $user->can('edit_admissions') && $canSubmit && $isOwner;
    }

    /**
     * Determine whether the user can approve the admission.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function approve(User $user, Admission $admission)
    {
        // Only allow approval if the admission is submitted or under review
        // and the user is an admin or staff with permission
        $canApprove = in_array($admission->status, [
            Admission::STATUS_SUBMITTED,
            Admission::STATUS_UNDER_REVIEW,
        ]);

        return $user->can('edit_admissions') && $canApprove && $user->hasAnyRole(['admin', 'staff']);
    }

    /**
     * Determine whether the user can reject the admission.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function reject(User $user, Admission $admission)
    {
        // Only allow rejection if the admission is submitted or under review
        // and the user is an admin or staff with permission
        $canReject = in_array($admission->status, [
            Admission::STATUS_SUBMITTED,
            Admission::STATUS_UNDER_REVIEW,
        ]);

        return $user->can('edit_admissions') && $canReject && $user->hasAnyRole(['admin', 'staff']);
    }

    /**
     * Determine whether the user can enroll the student.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function enroll(User $user, Admission $admission)
    {
        // Only allow enrollment if the admission is approved
        // and the user is an admin or staff with permission
        $canEnroll = $admission->status === Admission::STATUS_APPROVED;

        return $user->can('edit_admissions') && $canEnroll && $user->hasAnyRole(['admin', 'staff']);
    }

    /**
     * Determine whether the user can upload documents for the admission.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function uploadDocument(User $user, Admission $admission)
    {
        // Only allow document upload if the admission is in draft or under review
        // and the user is the applicant or an admin/staff
        $canUpload = in_array($admission->status, [
            Admission::STATUS_DRAFT,
            Admission::STATUS_UNDER_REVIEW,
        ]);

        $isOwner = $user->id === $admission->created_by;
        $isStaff = $user->hasAnyRole(['admin', 'staff']);

        return $user->can('edit_admissions') && $canUpload && ($isOwner || $isStaff);
    }

    /**
     * Determine whether the user can delete documents from the admission.
     *
     * @param  \App\Models\AdmissionDocument  $document
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function deleteDocument(User $user, Admission $admission, $document)
    {
        // Only allow document deletion if the admission is in draft or under review
        // and the user is the applicant or an admin/staff with permission
        $canDelete = in_array($admission->status, [
            Admission::STATUS_DRAFT,
            Admission::STATUS_UNDER_REVIEW,
        ]);

        $isOwner = $user->id === $admission->created_by;
        $isStaff = $user->hasAnyRole(['admin', 'staff']);

        return $user->can('edit_admissions') && $canDelete && ($isOwner || $isStaff);
    }

    /**
     * Determine whether the user can view the admission's documents.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewDocuments(User $user, Admission $admission)
    {
        // Allow viewing documents if the user is the applicant or an admin/staff
        $isOwner = $user->id === $admission->created_by;
        $isStaff = $user->hasAnyRole(['admin', 'staff']);

        return $user->can('view_admissions') && ($isOwner || $isStaff);
    }

    /**
     * Determine whether the user can export admissions.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function export(User $user)
    {
        return $user->can('view_admissions') && $user->hasAnyRole(['admin', 'staff']);
    }

    /**
     * Determine whether the user can import admissions.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function import(User $user)
    {
        return $user->can('edit_admissions') && $user->hasRole('admin');
    }
}
