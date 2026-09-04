<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasAnyPermission(['view_sections', 'manage_sections']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Section $section)
    {
        // Admin and staff with permission can view any section
        if ($user->hasAnyPermission(['view_sections', 'manage_sections'])) {
            return true;
        }

        // Class teacher can view their section
        if ($user->hasRole('teacher') && $user->teacher->id === $section->teacher_id) {
            return true;
        }

        // Subject teachers can view sections they teach in
        if ($user->hasRole('teacher')) {
            return $section->teachers()->where('teacher_id', $user->teacher->id)->exists();
        }

        // Students can view their own section
        if ($user->hasRole('student') && $user->student->section_id === $section->id) {
            return true;
        }

        // Parents can view their children's sections
        if ($user->hasRole('parent')) {
            return $user->guardian->students()
                ->where('section_id', $section->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasAnyPermission(['create_sections', 'manage_sections']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ?Section $section = null)
    {
        // For route model binding, we need to handle null section for create/any operations
        if ($section === null) {
            return $user->hasAnyPermission(['update_sections', 'manage_sections']);
        }

        // Admin and staff with permission can update any section
        if ($user->hasAnyPermission(['update_sections', 'manage_sections'])) {
            return true;
        }

        // Class teacher can update their section
        if ($user->hasRole('teacher') && $user->teacher->id === $section->teacher_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ?Section $section = null)
    {
        // For route model binding, we need to handle null section for create/any operations
        if ($section === null) {
            return $user->hasAnyPermission(['delete_sections', 'manage_sections']);
        }

        // Only allow deletion if there are no students, attendances, or exam results
        if ($section->students()->exists() ||
            $section->attendances()->exists() ||
            $section->examResults()->exists()) {
            return false;
        }

        return $user->hasAnyPermission(['delete_sections', 'manage_sections']);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Section $section)
    {
        return $user->hasAnyPermission(['restore_sections', 'manage_sections']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Section $section)
    {
        return $user->hasAnyPermission(['force_delete_sections', 'manage_sections']);
    }

    /**
     * Determine whether the user can view students in the section.
     *
     * @return bool
     */
    public function viewStudents(User $user, Section $section)
    {
        // Admin and staff with permission can view any section's students
        if ($user->hasAnyPermission(['view_students', 'manage_students', 'manage_sections'])) {
            return true;
        }

        // Class teacher can view their section's students
        if ($user->hasRole('teacher') && $user->teacher->id === $section->teacher_id) {
            return true;
        }

        // Subject teachers can view students in sections they teach
        if ($user->hasRole('teacher')) {
            return $section->teachers()->where('teacher_id', $user->teacher->id)->exists();
        }

        // Students can view their own section's students
        if ($user->hasRole('student') && $user->student->section_id === $section->id) {
            return true;
        }

        // Parents can view their children's section's students
        if ($user->hasRole('parent')) {
            return $user->guardian->students()
                ->where('section_id', $section->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can manage section subjects.
     *
     * @return bool
     */
    public function manageSubjects(User $user, ?Section $section = null)
    {
        // For route model binding, we need to handle null section for create/any operations
        if ($section === null) {
            return $user->hasAnyPermission(['manage_section_subjects', 'manage_sections']);
        }

        // Admin and staff with permission can manage any section's subjects
        if ($user->hasAnyPermission(['manage_section_subjects', 'manage_sections'])) {
            return true;
        }

        // Class teacher can manage their section's subjects
        if ($user->hasRole('teacher') && $user->teacher->id === $section->teacher_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage section teachers.
     *
     * @return bool
     */
    public function manageTeachers(User $user, ?Section $section = null)
    {
        // For route model binding, we need to handle null section for create/any operations
        if ($section === null) {
            return $user->hasAnyPermission(['manage_section_teachers', 'manage_sections']);
        }

        // Admin and staff with permission can manage any section's teachers
        if ($user->hasAnyPermission(['manage_section_teachers', 'manage_sections'])) {
            return true;
        }

        // Class teacher can manage their section's teachers
        if ($user->hasRole('teacher') && $user->teacher->id === $section->teacher_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage section attendance.
     *
     * @return bool
     */
    public function manageAttendance(User $user, ?Section $section = null)
    {
        // For route model binding, we need to handle null section for create/any operations
        if ($section === null) {
            return $user->hasAnyPermission(['manage_attendance', 'manage_sections']);
        }

        // Admin and staff with permission can manage any section's attendance
        if ($user->hasAnyPermission(['manage_attendance', 'manage_sections'])) {
            return true;
        }

        // Class teacher can manage their section's attendance
        if ($user->hasRole('teacher') && $user->teacher->id === $section->teacher_id) {
            return true;
        }

        // Subject teachers can manage attendance for their subjects in the section
        if ($user->hasRole('teacher')) {
            return $section->teachers()->where('teacher_id', $user->teacher->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can manage section timetable.
     *
     * @return bool
     */
    public function manageTimetable(User $user, ?Section $section = null)
    {
        // For route model binding, we need to handle null section for create/any operations
        if ($section === null) {
            return $user->hasAnyPermission(['manage_timetable', 'manage_sections']);
        }

        // Admin and staff with permission can manage any section's timetable
        if ($user->hasAnyPermission(['manage_timetable', 'manage_sections'])) {
            return true;
        }

        // Class teacher can manage their section's timetable
        if ($user->hasRole('teacher') && $user->teacher->id === $section->teacher_id) {
            return true;
        }

        return false;
    }
}
