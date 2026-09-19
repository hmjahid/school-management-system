<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Schema;
use App\Core\Session;
use App\Core\Support\Collection;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\ExamResult;
use App\Models\FeePayment;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;

class StudentGuardianAuthController extends Controller
{
    public function showStudentLogin(): void
    {
        if (Session::getInstance()->has('student_id')) {
            $this->redirect('/portal');
            return;
        }
        $this->view('auth.student_login');
    }

    public function studentLogin(): void
    {
        $data = $this->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $db = Database::getInstance();

        $user = $db->fetch(
            "SELECT u.* FROM users u WHERE u.email = ? LIMIT 1",
            [$data['email']]
        );

        $passwordOk = false;
        if ($user && !empty($user['password']) && password_verify($data['password'], $user['password'])) {
            $passwordOk = true;
        }
        if (!$passwordOk && $user && !empty($user['date_of_birth']) && $data['password'] === $user['date_of_birth']) {
            $passwordOk = true;
        }

        if (!$user || !$passwordOk) {
            Session::getInstance()->flash('error', 'Invalid email or password.');
            $this->back();
            return;
        }

        // The account must belong to the student role (app parity).
        if ((int) $user['role_id'] !== \App\Core\Auth::roleId('student') && (string) $user['role'] !== 'student') {
            Session::getInstance()->flash('error', 'You do not have permission to access this portal.');
            $this->back();
            return;
        }

        $student = $db->fetch(
            "SELECT id FROM students WHERE user_id = ? LIMIT 1",
            [(int) $user['id']]
        );

        Session::getInstance()->set('student_id', $student ? (int) $student['id'] : null);
        Session::getInstance()->set('student_user_id', (int) $user['id']);
        Session::getInstance()->regenerate();
        Session::getInstance()->flash('success', 'Welcome ' . $user['name']);
        $this->redirect('/student/dashboard');
    }

    public function studentLogout(): void
    {
        Session::getInstance()->remove('student_id');
        Session::getInstance()->remove('student_user_id');
        Session::getInstance()->flash('success', 'Logged out.');
        $this->redirect('/student/login');
    }

    public function showGuardianLogin(): void
    {
        if (Session::getInstance()->has('guardian_id')) {
            $this->redirect('/portal');
            return;
        }
        $this->view('auth.guardian_login');
    }

    public function guardianLogin(): void
    {
        $data = $this->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $db = Database::getInstance();

        $user = $db->fetch(
            "SELECT u.* FROM users u WHERE u.email = ? LIMIT 1",
            [$data['email']]
        );

        if (!$user || empty($user['password']) || !password_verify($data['password'], $user['password'])) {
            Session::getInstance()->flash('error', 'Invalid email or password.');
            $this->back();
            return;
        }

        // The account must belong to the guardian role (app parity).
        if ((int) $user['role_id'] !== \App\Core\Auth::roleId('guardian') && (string) $user['role'] !== 'guardian') {
            Session::getInstance()->flash('error', 'You do not have permission to access this portal.');
            $this->back();
            return;
        }

        $guardian = $db->fetch(
            "SELECT id FROM guardians WHERE user_id = ? LIMIT 1",
            [(int) $user['id']]
        );

        Session::getInstance()->set('guardian_id', $guardian ? (int) $guardian['id'] : null);
        Session::getInstance()->set('guardian_user_id', (int) $user['id']);
        Session::getInstance()->regenerate();
        Session::getInstance()->flash('success', 'Welcome ' . $user['name']);
        $this->redirect('/guardian/dashboard');
    }

    public function guardianLogout(): void
    {
        Session::getInstance()->remove('guardian_id');
        Session::getInstance()->remove('guardian_user_id');
        Session::getInstance()->flash('success', 'Logged out.');
        $this->redirect('/guardian/login');
    }

    public function studentDashboard(): void
    {
        $session = Session::getInstance();
        if (!$session->has('student_user_id')) {
            $this->redirect('/student/login');
            return;
        }

        $student = null;
        try {
            $student = Student::find((int) $session->get('student_id'));
        } catch (\Throwable) {
            $student = null;
        }

        $user = $this->sessionUser();

        $stats = [
            'attendance_rate'   => 0,
            'exam_count'        => 0,
            'fee_paid'          => 0,
            'certificate_count' => 0,
        ];

        $recentResults = new Collection();
        if ($student) {
            try {
                if (Schema::hasTable('attendances')) {
                    $totalAtt = (int) Attendance::query()->where('student_id', $student->id)->count();
                    $presentAtt = (int) Attendance::query()
                        ->where('student_id', $student->id)
                        ->whereIn('status', ['present', 'late', 'half_day'])
                        ->count();
                    $stats['attendance_rate'] = $totalAtt > 0 ? (int) round(100 * $presentAtt / $totalAtt) : 0;
                }
            } catch (\Throwable) {
                $stats['attendance_rate'] = 0;
            }

            try {
                if (Schema::hasTable('exam_results')) {
                    $stats['exam_count'] = (int) ExamResult::query()->where('student_id', $student->id)->count();
                }
            } catch (\Throwable) {
                $stats['exam_count'] = 0;
            }

            try {
                if (Schema::hasTable('fee_payments')) {
                    $stats['fee_paid'] = (int) FeePayment::query()
                        ->where('student_id', $student->id)
                        ->whereIn('status', ['paid', 'completed'])
                        ->count();
                }
            } catch (\Throwable) {
                $stats['fee_paid'] = 0;
            }

            try {
                if (Schema::hasTable('certificates')) {
                    $stats['certificate_count'] = (int) Certificate::query()->where('student_id', $student->id)->count();
                }
            } catch (\Throwable) {
                $stats['certificate_count'] = 0;
            }

            try {
                if (Schema::hasTable('exam_results')) {
                    $recentResults = new Collection(ExamResult::query()
                        ->where('student_id', $student->id)
                        ->where('is_published', true)
                        ->orderByDesc('id')
                        ->limit(5)
                        ->get());
                }
            } catch (\Throwable) {
                $recentResults = new Collection();
            }
        }

        $this->view('student.dashboard', compact('user', 'student', 'stats', 'recentResults'));
    }

    public function guardianDashboard(): void
    {
        $session = Session::getInstance();
        if (!$session->has('guardian_user_id')) {
            $this->redirect('/guardian/login');
            return;
        }

        $guardian = null;
        try {
            $guardian = Guardian::find((int) $session->get('guardian_id'));
        } catch (\Throwable) {
            $guardian = null;
        }

        $user = $this->sessionUser();
        $students = new Collection();
        if ($guardian) {
            try {
                $students = $guardian->students;
            } catch (\Throwable) {
                $students = new Collection();
            }
        }
        $studentCount = $students->count();
        $pendingFees = 0;
        $noticeCount = 0;

        $assignments = new Collection();
        if ($guardian && $students->isNotEmpty()) {
            try {
                $studentIds = $students->pluck('id')->map(static fn ($v) => (int) $v)->all();
                $assignmentIds = array_values(array_unique(array_map(
                    static fn ($v) => (int) $v,
                    AssignmentSubmission::query()->whereIn('student_id', $studentIds)->pluck('assignment_id')
                )));
                if ($assignmentIds !== []) {
                    $assignments = new Collection(Assignment::query()
                        ->whereIn('id', $assignmentIds)
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get());

                    $subs = AssignmentSubmission::query()
                        ->whereIn('assignment_id', $assignmentIds)
                        ->whereIn('student_id', $studentIds)
                        ->get();
                    $byAssignment = [];
                    foreach ($subs as $sub) {
                        $byAssignment[(int) $sub->assignment_id][] = $sub;
                    }
                    foreach ($assignments as $assignment) {
                        $assignment->setRelation('submissions', new Collection($byAssignment[(int) $assignment->id] ?? []));
                    }
                }
            } catch (\Throwable) {
                $assignments = new Collection();
            }
        }

        $this->view('guardian.dashboard', compact('user', 'guardian', 'students', 'studentCount', 'pendingFees', 'noticeCount', 'assignments'));
    }

    public function guardianNotes(int $submissionId): void
    {
        $session = Session::getInstance();
        $guardianId = (int) $session->get('guardian_id');
        if ($guardianId <= 0) {
            $this->redirect('/guardian/login');
            return;
        }

        $guardian = null;
        try {
            $guardian = Guardian::find($guardianId);
        } catch (\Throwable) {
            $guardian = null;
        }
        if (!$guardian) {
            Session::getInstance()->flash('error', __('Guardian not found.'));
            $this->back();
            return;
        }

        $submission = null;
        try {
            $submission = AssignmentSubmission::find((int) $submissionId);
        } catch (\Throwable) {
            $submission = null;
        }
        if (!$submission) {
            Session::getInstance()->flash('error', __('Submission not found.'));
            $this->back();
            return;
        }

        $studentIds = [];
        try {
            $studentIds = $guardian->students->pluck('id')->map(static fn ($v) => (int) $v)->all();
        } catch (\Throwable) {
            $studentIds = [];
        }

        if (!in_array((int) $submission->student_id, $studentIds, true)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }

        $assignment = null;
        try {
            $assignment = $submission->assignment;
        } catch (\Throwable) {
            $assignment = null;
        }
        if (!$assignment || !$assignment->allow_guardian_notes) {
            Session::getInstance()->flash('error', __('Guardian notes are not allowed for this assignment.'));
            $this->back();
            return;
        }

        $data = $this->validate([
            'guardian_notes' => 'required|max:2000',
        ]);

        try {
            $submission->update([
                'guardian_notes'       => $data['guardian_notes'],
                'guardian_id'          => $guardianId,
                'guardian_notified_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            Session::getInstance()->flash('error', __('Could not save notes.'));
            $this->back();
            return;
        }

        Session::getInstance()->flash('success', __('Guardian notes saved.'));
        $this->back();
    }

    /**
     * Build a user facade from the portal session keys (student/guardian).
     */
    private function sessionUser(): User
    {
        $session = Session::getInstance();
        $user = new User();
        $user->id = 0;
        $user->name = '';
        $user->email = '';
        $user->role = '';

        if ($session->has('student_id')) {
            $user->role = 'student';
            $user->id = (int) ($session->get('student_user_id') ?: 0);
            $student = null;
            try {
                $student = Student::find((int) $session->get('student_id'));
            } catch (\Throwable) {
                $student = null;
            }
            $user->name = $student?->name
                ?? trim((string) ($student?->first_name ?? '') . ' ' . (string) ($student?->last_name ?? ''));
            $user->email = (string) ($student?->email ?? '');
        } elseif ($session->has('guardian_id')) {
            $user->role = 'parent';
            $user->id = (int) ($session->get('guardian_user_id') ?: 0);
            $guardian = null;
            try {
                $guardian = Guardian::find((int) $session->get('guardian_id'));
            } catch (\Throwable) {
                $guardian = null;
            }
            $user->name = (string) ($guardian?->name ?? '');
            $user->email = (string) ($guardian?->email ?? '');
        }

        if ($user->id > 0) {
            try {
                $linked = User::find($user->id);
                if ($linked) {
                    $user->name = $linked->name ?? $user->name;
                    $user->email = $linked->email ?? $user->email;
                }
            } catch (\Throwable) {
                //
            }
        }
        return $user;
    }
}
