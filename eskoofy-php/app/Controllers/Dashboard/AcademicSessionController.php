<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class AcademicSessionController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll(
            "SELECT * FROM academic_sessions ORDER BY is_current DESC, start_date DESC"
        );

        $this->view('dashboard.academic-sessions.index', ['rows' => $rows]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'        => 'required|max:100',
            'start_date'  => 'required',
            'end_date'    => 'required',
            'is_current'  => 'numeric',
        ]);

        if (($data['is_current'] ?? 0) == 1) {
            $this->db->update('academic_sessions', ['is_current' => 0], '1=1');
        }

        $this->db->insert('academic_sessions', [
            'name'        => $data['name'],
            'start_date'  => $data['start_date'],
            'end_date'    => $data['end_date'],
            'is_current'  => $data['is_current'] ?? 0,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Academic session created.');
        $this->redirect('/dashboard/academic-sessions');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $session = $this->db->fetch("SELECT * FROM academic_sessions WHERE id = ? LIMIT 1", [$id]);
        if (!$session) {
            Session::getInstance()->flash('error', 'Session not found.');
            $this->redirect('/dashboard/academic-sessions');
            return;
        }

        $data = $this->validate([
            'name'        => 'required|max:100',
            'start_date'  => 'required',
            'end_date'    => 'required',
            'is_current'  => 'numeric',
        ]);

        if (($data['is_current'] ?? 0) == 1) {
            $this->db->update('academic_sessions', ['is_current' => 0], '1=1');
        }

        $this->db->update('academic_sessions', [
            'name'        => $data['name'],
            'start_date'  => $data['start_date'],
            'end_date'    => $data['end_date'],
            'is_current'  => $data['is_current'] ?? 0,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Session updated.');
        $this->redirect('/dashboard/academic-sessions');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('academic_sessions', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Session deleted.');
        $this->redirect('/dashboard/academic-sessions');
    }
}
