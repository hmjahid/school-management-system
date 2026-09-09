<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

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
            'admission_number' => 'required',
            'password'         => 'required',
        ]);

        $db = Database::getInstance();

        $student = $db->fetch(
            "SELECT s.*, u.password as user_password, u.name
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             WHERE s.admission_number = ? LIMIT 1",
            [$data['admission_number']]
        );

        if (!$student) {
            Session::getInstance()->flash('error', 'Invalid admission number or password.');
            $this->back();
            return;
        }

        $passwordOk = false;
        if (!empty($student['user_password']) && password_verify($data['password'], $student['user_password'])) {
            $passwordOk = true;
        }
        if (!$passwordOk && !empty($student['password_hash']) && password_verify($data['password'], $student['password_hash'])) {
            $passwordOk = true;
        }
        if (!$passwordOk && isset($student['date_of_birth']) && $data['password'] === $student['date_of_birth']) {
            $passwordOk = true;
        }

        if (!$passwordOk) {
            Session::getInstance()->flash('error', 'Invalid admission number or password.');
            $this->back();
            return;
        }

        Session::getInstance()->set('student_id', $student['id']);
        Session::getInstance()->set('student_user_id', $student['user_id']);
        Session::getInstance()->regenerate();
        Session::getInstance()->flash('success', 'Welcome ' . $student['name']);
        $this->redirect('/portal');
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

        $guardian = $db->fetch(
            "SELECT g.*, u.password as user_password, u.name
             FROM guardians g
             LEFT JOIN users u ON g.user_id = u.id
             WHERE u.email = ? LIMIT 1",
            [$data['email']]
        );

        if (!$guardian || empty($guardian['user_password']) || !password_verify($data['password'], $guardian['user_password'])) {
            Session::getInstance()->flash('error', 'Invalid email or password.');
            $this->back();
            return;
        }

        Session::getInstance()->set('guardian_id', $guardian['id']);
        Session::getInstance()->set('guardian_user_id', $guardian['user_id']);
        Session::getInstance()->regenerate();
        Session::getInstance()->flash('success', 'Welcome ' . $guardian['name']);
        $this->redirect('/portal');
    }

    public function guardianLogout(): void
    {
        Session::getInstance()->remove('guardian_id');
        Session::getInstance()->remove('guardian_user_id');
        Session::getInstance()->flash('success', 'Logged out.');
        $this->redirect('/guardian/login');
    }
}
