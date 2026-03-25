<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
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
        return $user->hasAnyPermission(['view_attendances', 'manage_attendances']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Attendance $attendance)
    {
        // Admin and staff with permission can view any attendance
        if ($user->hasAnyPermission(['view_attendances', 'manage_attendances'])) {
            return true;
        }

        // Teachers can view attendance for their own classes/sections
        if ($user->hasRole('teacher')) {
            // Check if the teacher is the class teacher or subject teacher
            $isClassTeacher = $attendance->batch && $attendance->batch->teacher_id === $user->teacher->id || 
                             $attendance->section && $attendance->section->teacher_id === $user->teacher->id;
            
            $isAssistantTeacher = $attendance->batch && $attendance->batch->assistant_teacher_id === $user->teacher->id;
            
            $isSubjectTeacher = $attendance->subject && $attendance->subject->teachers()->where('teacher_id', $user->teacher->id)->exists();
            
            return $isClassTeacher || $isAssistantTeacher || $isSubjectTeacher;
        }

        // Students can view their own attendance
        if ($user->hasRole('student') && $user->student && $attendance->student_id === $user->student->id) {
            return true;
        }

        // Parents can view their children's attendance
        if ($user->hasRole('parent') && $user->guardian) {
            return $user->guardian->students()->where('id', $attendance->student_id)->exists();
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
        return $user->hasAnyPermission(['create_attendances', 'manage_attendances']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Attendance $attendance = null)
    {
        // For route model binding, we need to handle null attendance for create/any operations
        if ($attendance === null) {
            return $user->hasAnyPermission(['update_attendances', 'manage_attendances']);
        }
        
        // Admin and staff with permission can update any attendance
        if ($user->hasAnyPermission(['update_attendances', 'manage_attendances'])) {
            return true;
        }

        // Teachers can update attendance for their own classes/sections
        if ($user->hasRole('teacher')) {
            // Check if the teacher is the class teacher or subject teacher
            $isClassTeacher = $attendance->batch && $attendance->batch->teacher_id === $user->teacher->id || 
                             $attendance->section && $attendance->section->teacher_id === $user->teacher->id;
            
            $isAssistantTeacher = $attendance->batch && $attendance->batch->assistant_teacher_id === $user->teacher->id;
            
            $isSubjectTeacher = $attendance->subject && $attendance->subject->teachers()->where('teacher_id', $user->teacher->id)->exists();
            
            return $isClassTeacher || $isAssistantTeacher || $isSubjectTeacher;
        }

        // Allow updating for a short period after creation (e.g., 1 hour)
        $canEditWithinTime = $attendance->created_at->diffInHours(now()) < 1;
        
        // Students can update their own attendance within a short time if allowed
        if ($user->hasRole('student') && $user->student && $attendance->student_id === $user->student->id && $canEditWithinTime) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Attendance $attendance = null)
    {
        // For route model binding, we need to handle null attendance for create/any operations
        if ($attendance === null) {
            return $user->hasAnyPermission(['delete_attendances', 'manage_attendances']);
        }
        
        // Admin and staff with permission can delete any attendance
        if ($user->hasAnyPermission(['delete_attendances', 'manage_attendances'])) {
            return true;
        }

        // Teachers can delete attendance for their own classes/sections within a certain time
        if ($user->hasRole('teacher')) {
            // Check if the teacher is the class teacher or subject teacher
            $isClassTeacher = $attendance->batch && $attendance->batch->teacher_id === $user->teacher->id || 
                             $attendance->section && $attendance->section->teacher_id === $user->teacher->id;
            
            $isAssistantTeacher = $attendance->batch && $attendance->batch->assistant_teacher_id === $user->teacher->id;
            
            $isSubjectTeacher = $attendance->subject && $attendance->subject->teachers()->where('teacher_id', $user->teacher->id)->exists();
            
            // Allow deletion for a short period after creation (e.g., 24 hours)
            $canDeleteWithinTime = $attendance->created_at->diffInHours(now()) < 24;
            
            return ($isClassTeacher || $isAssistantTeacher || $isSubjectTeacher) && $canDeleteWithinTime;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Attendance $attendance)
    {
        return $user->hasAnyPermission(['restore_attendances', 'manage_attendances']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Attendance $attendance)
    {
        return $user->hasAnyPermission(['force_delete_attendances', 'manage_attendances']);
    }

    /**
     * Determine whether the user can record bulk attendance.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function bulkRecord(User $user)
    {
        return $user->hasAnyPermission(['bulk_record_attendances', 'manage_attendances']);
    }

    /**
     * Determine whether the user can view attendance reports.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewReports(User $user)
    {
        return $user->hasAnyPermission(['view_attendance_reports', 'manage_attendances']);
    }

    /**
     * Determine whether the user can export attendance data.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function export(User $user)
    {
        return $user->hasAnyPermission(['export_attendances', 'manage_attendances']);
    }

    /**
     * Determine whether the user can import attendance data.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function import(User $user)
    {
        return $user->hasAnyPermission(['import_attendances', 'manage_attendances']);
    }

    /**
     * Determine whether the user can manage attendance settings.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function manageSettings(User $user)
    {
        return $user->hasAnyPermission(['manage_attendance_settings', 'manage_attendances']);
    }

    /**
     * Determine whether the user can view attendance calendar.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewCalendar(User $user)
    {
        return $user->hasAnyPermission(['view_attendance_calendar', 'manage_attendances']);
    }

    /**
     * Determine whether the user can view attendance summary.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewSummary(User $user)
    {
        return $user->hasAnyPermission(['view_attendance_summary', 'manage_attendances']);
    }

    /**
     * Determine whether the user can view attendance statistics.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewStatistics(User $user)
    {
        return $user->hasAnyPermission(['view_attendance_statistics', 'manage_attendances']);
    }

    /**
     * Determine whether the user can manage attendance for a specific batch.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return bool
     */
    public function manageBatchAttendance(User $user, $batch = null)
    {
        // For route model binding, we need to handle null batch for create/any operations
        if ($batch === null) {
            return $user->hasAnyPermission(['manage_batch_attendance', 'manage_attendances']);
        }
        
        // Admin and staff with permission can manage attendance for any batch
        if ($user->hasAnyPermission(['manage_batch_attendance', 'manage_attendances'])) {
            return true;
        }

        // Teachers can manage attendance for batches they teach
        if ($user->hasRole('teacher')) {
            return $batch->teacher_id === $user->teacher->id || 
                   $batch->assistant_teacher_id === $user->teacher->id ||
                   $batch->subjects()->whereHas('teachers', function($q) use ($user) {
                       $q->where('teacher_id', $user->teacher->id);
                   })->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can manage attendance for a specific section.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Section  $section
     * @return bool
     */
    public function manageSectionAttendance(User $user, $section = null)
    {
        // For route model binding, we need to handle null section for create/any operations
        if ($section === null) {
            return $user->hasAnyPermission(['manage_section_attendance', 'manage_attendances']);
        }
        
        // Admin and staff with permission can manage attendance for any section
        if ($user->hasAnyPermission(['manage_section_attendance', 'manage_attendances'])) {
            return true;
        }

        // Teachers can manage attendance for sections they are assigned to
        if ($user->hasRole('teacher')) {
            return $section->teacher_id === $user->teacher->id || 
                   $section->subjects()->whereHas('teachers', function($q) use ($user) {
                       $q->where('teacher_id', $user->teacher->id);
                   })->exists();
        }

        return false;
    }
}
