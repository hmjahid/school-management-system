<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Exam;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamPolicy
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
        // Any authenticated user can view exams, but filtered by their access level
        return $user->hasAnyPermission([
            'view_exams', 
            'manage_exams',
            'view_own_exams',
            'view_batch_exams',
            'view_section_exams'
        ]);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Exam $exam)
    {
        // Admin and staff with permission can view any exam
        if ($user->hasAnyPermission(['view_exams', 'manage_exams'])) {
            return true;
        }

        // Teachers can view exams they are assigned to or created
        if ($user->hasRole('teacher')) {
            // Check if teacher is assigned to this exam
            $isAssigned = $exam->teachers()->where('teacher_id', $user->teacher->id)->exists();
            
            // Check if teacher is the creator
            $isCreator = $exam->created_by === $user->id;
            
            // Check if teacher is the class teacher or subject teacher
            $isClassTeacher = $exam->batch && $exam->batch->teacher_id === $user->teacher->id || 
                             $exam->section && $exam->section->teacher_id === $user->teacher->id;
            
            $isAssistantTeacher = $exam->batch && $exam->batch->assistant_teacher_id === $user->teacher->id;
            
            $isSubjectTeacher = $exam->subject && $exam->subject->teachers()
                ->where('teacher_id', $user->teacher->id)->exists();
            
            return $isAssigned || $isCreator || $isClassTeacher || $isAssistantTeacher || $isSubjectTeacher;
        }

        // Students can view their own exams
        if ($user->hasRole('student') && $user->student) {
            // Check if student is in the batch or section of the exam
            if ($exam->batch_id && $user->student->batch_id === $exam->batch_id) {
                return true;
            }
            
            if ($exam->section_id && $user->student->section_id === $exam->section_id) {
                return true;
            }
            
            // Check if student has any result for this exam
            return $exam->results()->where('student_id', $user->student->id)->exists();
        }

        // Parents can view their children's exams
        if ($user->hasRole('parent') && $user->guardian) {
            $studentIds = $user->guardian->students()->pluck('id');
            return $exam->results()->whereIn('student_id', $studentIds)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasAnyPermission(['create_exams', 'manage_exams']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Exam $exam = null)
    {
        // For route model binding, we need to handle null exam for create/any operations
        if ($exam === null) {
            return $user->hasAnyPermission(['update_exams', 'manage_exams']);
        }
        
        // Cannot update published exams
        if ($exam->is_published) {
            return false;
        }
        
        // Admin and staff with permission can update any exam
        if ($user->hasAnyPermission(['update_exams', 'manage_exams'])) {
            return true;
        }

        // Teachers can update their own exams or exams they are assigned to
        if ($user->hasRole('teacher')) {
            // Check if teacher is the creator
            if ($exam->created_by === $user->id) {
                return true;
            }
            
            // Check if teacher is assigned to this exam
            if ($exam->teachers()->where('teacher_id', $user->teacher->id)
                ->where('is_chief_examiner', true)->exists()) {
                return true;
            }
            
            // Check if teacher is the class teacher or subject teacher
            $isClassTeacher = $exam->batch && $exam->batch->teacher_id === $user->teacher->id || 
                             $exam->section && $exam->section->teacher_id === $user->teacher->id;
            
            $isSubjectTeacher = $exam->subject && $exam->subject->teachers()
                ->where('teacher_id', $user->teacher->id)->exists();
            
            return $isClassTeacher || $isSubjectTeacher;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Exam $exam = null)
    {
        // For route model binding, we need to handle null exam for create/any operations
        if ($exam === null) {
            return $user->hasAnyPermission(['delete_exams', 'manage_exams']);
        }
        
        // Cannot delete published exams or exams with results
        if ($exam->is_published || $exam->results()->exists()) {
            return false;
        }
        
        // Admin and staff with permission can delete any exam
        if ($user->hasAnyPermission(['delete_exams', 'manage_exams'])) {
            return true;
        }

        // Teachers can delete their own drafts or scheduled exams
        if ($user->hasRole('teacher') && $exam->created_by === $user->id) {
            return in_array($exam->status, [Exam::STATUS_DRAFT, Exam::STATUS_SCHEDULED]);
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Exam $exam)
    {
        return $user->hasAnyPermission(['restore_exams', 'manage_exams']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Exam  $exam
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Exam $exam)
    {
        // Only admins can force delete exams
        return $user->hasRole('admin') && $user->hasPermissionTo('manage_exams');
    }

    /**
     * Determine whether the user can publish the exam.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Exam  $exam
     * @return bool
     */
    public function publish(User $user, Exam $exam): bool
    {
        // Only completed exams can be published
        if ($exam->status !== Exam::STATUS_COMPLETED) {
            return false;
        }
        
        // Admin and staff with permission can publish any exam
        if ($user->hasAnyPermission(['publish_exams', 'manage_exams'])) {
            return true;
        }
        
        // Teachers can publish their own exams if they have permission
        if ($user->hasRole('teacher') && $user->hasPermissionTo('publish_own_exams')) {
            return $exam->created_by === $user->id || 
                   $exam->teachers()->where('teacher_id', $user->teacher->id)
                       ->where('is_chief_examiner', true)->exists();
        }
        
        return false;
    }

    /**
     * Determine whether the user can unpublish the exam.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Exam  $exam
     * @return bool
     */
    public function unpublish(User $user, Exam $exam): bool
    {
        // Only published exams can be unpublished
        if (!$exam->is_published) {
            return false;
        }
        
        // Admin and staff with permission can unpublish any exam
        if ($user->hasAnyPermission(['unpublish_exams', 'manage_exams'])) {
            return true;
        }
        
        // Teachers can unpublish their own exams if they have permission
        if ($user->hasRole('teacher') && $user->hasPermissionTo('unpublish_own_exams')) {
            return $exam->created_by === $user->id || 
                   $exam->teachers()->where('teacher_id', $user->teacher->id)
                       ->where('is_chief_examiner', true)->exists();
        }
        
        return false;
    }

    /**
     * Determine whether the user can view exam statistics.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Exam  $exam
     * @return bool
     */
    public function viewStatistics(User $user, Exam $exam): bool
    {
        // Admin and staff with permission can view statistics for any exam
        if ($user->hasAnyPermission(['view_exam_statistics', 'manage_exams'])) {
            return true;
        }
        
        // Teachers can view statistics for their own exams or exams they are assigned to
        if ($user->hasRole('teacher')) {
            if ($exam->created_by === $user->id) {
                return true;
            }
            
            // Check if teacher is assigned to this exam
            if ($exam->teachers()->where('teacher_id', $user->teacher->id)->exists()) {
                return true;
            }
            
            // Check if teacher is the class teacher or subject teacher
            $isClassTeacher = $exam->batch && $exam->batch->teacher_id === $user->teacher->id || 
                             $exam->section && $exam->section->teacher_id === $user->teacher->id;
            
            $isSubjectTeacher = $exam->subject && $exam->subject->teachers()
                ->where('teacher_id', $user->teacher->id)->exists();
            
            return $isClassTeacher || $isSubjectTeacher;
        }
        
        return false;
    }

    /**
     * Determine whether the user can manage exam questions.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Exam  $exam
     * @return bool
     */
    public function manageQuestions(User $user, Exam $exam): bool
    {
        // Cannot manage questions for published exams
        if ($exam->is_published) {
            return false;
        }
        
        // Admin and staff with permission can manage questions for any exam
        if ($user->hasAnyPermission(['manage_exam_questions', 'manage_exams'])) {
            return true;
        }
        
        // Teachers can manage questions for their own exams or exams they are assigned to
        if ($user->hasRole('teacher')) {
            if ($exam->created_by === $user->id) {
                return true;
            }
            
            // Check if teacher is assigned as chief examiner
            if ($exam->teachers()->where('teacher_id', $user->teacher->id)
                ->where('is_chief_examiner', true)->exists()) {
                return true;
            }
            
            // Check if teacher is the subject teacher
            return $exam->subject && $exam->subject->teachers()
                ->where('teacher_id', $user->teacher->id)->exists();
        }
        
        return false;
    }

    /**
     * Determine whether the user can manage exam results.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Exam  $exam
     * @return bool
     */
    public function manageResults(User $user, Exam $exam = null): bool
    {
        // For route model binding, we need to handle null exam for create/any operations
        if ($exam === null) {
            return $user->hasAnyPermission(['manage_exam_results', 'manage_exams']);
        }
        
        // Cannot manage results for published exams
        if ($exam->is_published) {
            return false;
        }
        
        // Admin and staff with permission can manage results for any exam
        if ($user->hasAnyPermission(['manage_exam_results', 'manage_exams'])) {
            return true;
        }
        
        // Teachers can manage results for their own exams or exams they are assigned to
        if ($user->hasRole('teacher')) {
            if ($exam->created_by === $user->id) {
                return true;
            }
            
            // Check if teacher is assigned to this exam
            if ($exam->teachers()->where('teacher_id', $user->teacher->id)->exists()) {
                return true;
            }
            
            // Check if teacher is the class teacher or subject teacher
            $isClassTeacher = $exam->batch && $exam->batch->teacher_id === $user->teacher->id || 
                             $exam->section && $exam->section->teacher_id === $user->teacher->id;
            
            $isSubjectTeacher = $exam->subject && $exam->subject->teachers()
                ->where('teacher_id', $user->teacher->id)->exists();
            
            return $isClassTeacher || $isSubjectTeacher;
        }
        
        return false;
    }

    /**
     * Determine whether the user can view exam results.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Exam  $exam
     * @return bool
     */
    public function viewResults(User $user, Exam $exam): bool
    {
        // Admin and staff with permission can view results for any exam
        if ($user->hasAnyPermission(['view_exam_results', 'manage_exams'])) {
            return true;
        }
        
        // Teachers can view results for their own exams or exams they are assigned to
        if ($user->hasRole('teacher')) {
            if ($exam->created_by === $user->id) {
                return true;
            }
            
            // Check if teacher is assigned to this exam
            if ($exam->teachers()->where('teacher_id', $user->teacher->id)->exists()) {
                return true;
            }
            
            // Check if teacher is the class teacher or subject teacher
            $isClassTeacher = $exam->batch && $exam->batch->teacher_id === $user->teacher->id || 
                             $exam->section && $exam->section->teacher_id === $user->teacher->id;
            
            $isSubjectTeacher = $exam->subject && $exam->subject->teachers()
                ->where('teacher_id', $user->teacher->id)->exists();
            
            return $isClassTeacher || $isSubjectTeacher;
        }
        
        // Students can view their own results
        if ($user->hasRole('student') && $user->student) {
            return $exam->results()->where('student_id', $user->student->id)->exists();
        }
        
        // Parents can view their children's results
        if ($user->hasRole('parent') && $user->guardian) {
            $studentIds = $user->guardian->students()->pluck('id');
            return $exam->results()->whereIn('student_id', $studentIds)->exists();
        }
        
        return false;
    }

    /**
     * Determine whether the user can export exam data.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function export(User $user): bool
    {
        return $user->hasAnyPermission(['export_exams', 'manage_exams']);
    }

    /**
     * Determine whether the user can import exam data.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function import(User $user): bool
    {
        return $user->hasAnyPermission(['import_exams', 'manage_exams']);
    }

    /**
     * Determine whether the user can manage exam settings.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function manageSettings(User $user): bool
    {
        return $user->hasAnyPermission(['manage_exam_settings', 'manage_exams']);
    }
}
