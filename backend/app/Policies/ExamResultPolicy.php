<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ExamResult;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamResultPolicy
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
        return $user->can('view_any_exam_result');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExamResult  $examResult
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ExamResult $examResult)
    {
        // Allow if user has permission and is the student, or is a parent of the student,
        // or is a teacher assigned to the exam, or is an admin
        $isStudent = $user->student && $user->student->id === $examResult->student_id;
        $isParent = $user->guardian && $user->guardian->students()->where('students.id', $examResult->student_id)->exists();
        $isTeacher = $examResult->exam->teachers()->where('teacher_id', $user->teacher?->id)->exists();
        $isAdmin = $user->hasRole('admin');
        
        return $user->can('view_exam_result') && ($isStudent || $isParent || $isTeacher || $isAdmin);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Only teachers and admins can create exam results
        return $user->hasAnyRole(['teacher', 'admin']) && $user->can('create_exam_result');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExamResult  $examResult
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ExamResult $examResult)
    {
        // Only teachers assigned to the exam or admins can update results
        $isAssignedTeacher = $examResult->exam->teachers()->where('teacher_id', $user->teacher?->id)->exists();
        $isAdmin = $user->hasRole('admin');
        
        return $user->can('edit_exam_result') && ($isAssignedTeacher || $isAdmin);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExamResult  $examResult
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ExamResult $examResult)
    {
        // Only admins can delete exam results
        return $user->hasRole('admin') && $user->can('delete_exam_result');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExamResult  $examResult
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, ExamResult $examResult)
    {
        return $user->hasRole('admin') && $user->can('restore_exam_result');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExamResult  $examResult
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, ExamResult $examResult)
    {
        return $user->hasRole('admin') && $user->can('force_delete_exam_result');
    }

    /**
     * Determine whether the user can publish the result.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExamResult  $examResult
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function publish(User $user, ExamResult $examResult)
    {
        // Only teachers who can review results or admins can publish
        $canReview = $user->can('review_exam_results');
        $isAssignedTeacher = $examResult->exam->teachers()->where('teacher_id', $user->teacher?->id)->exists();
        $isAdmin = $user->hasRole('admin');
        
        return $canReview && ($isAssignedTeacher || $isAdmin);
    }

    /**
     * Determine whether the user can unpublish the result.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExamResult  $examResult
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function unpublish(User $user, ExamResult $examResult)
    {
        // Only the teacher who published it or an admin can unpublish
        $isPublisher = $examResult->published_by === $user->staff?->id;
        $isAdmin = $user->hasRole('admin');
        
        return $user->can('unpublish_exam_result') && ($isPublisher || $isAdmin);
    }

    /**
     * Determine whether the user can view the student's own results.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExamResult  $examResult
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewOwn(User $user)
    {
        return $user->hasRole('student') && $user->can('view_own_exam_results');
    }

    /**
     * Determine whether the user can view their child's results.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExamResult  $examResult
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewChild(User $user, ExamResult $examResult)
    {
        $isParent = $user->guardian && $user->guardian->students()->where('students.id', $examResult->student_id)->exists();
        return $isParent && $user->can('view_child_exam_results');
    }
}
