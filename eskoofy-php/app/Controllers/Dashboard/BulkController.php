<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class BulkController extends Controller
{
    private DatabaseInterface $db;

    private array $resources = [
        'students' => ['label' => 'Students', 'columns' => ['admission_number', 'name', 'class', 'roll']],
        'teachers' => ['label' => 'Teachers', 'columns' => ['employee_id', 'name', 'email', 'phone']],
        'fees'     => ['label' => 'Fees',     'columns' => ['name', 'amount', 'fee_type']],
        'classes'  => ['label' => 'Classes',  'columns' => ['name', 'code']],
        'notices'  => ['label' => 'Notices',  'columns' => ['title', 'content']],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.bulk.index', ['resources' => $this->resources]);
    }

    public function export(string $resource): void
    {
        Auth::requireAuth();
        if (!isset($this->resources[$resource])) {
            Session::getInstance()->flash('error', 'Unknown resource.');
            $this->redirect('/dashboard/bulk');
            return;
        }
        $cfg = $this->resources[$resource];
        $rows = $this->fetchRows($resource);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $resource . '_' . date('Ymd') . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, $cfg['columns']);
        foreach ($rows as $r) {
            $line = [];
            foreach ($cfg['columns'] as $col) {
                $line[] = $r[$col] ?? '';
            }
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }

    public function import(string $resource): void
    {
        Auth::requireAuth();
        if (!isset($this->resources[$resource])) {
            Session::getInstance()->flash('error', 'Unknown resource.');
            $this->redirect('/dashboard/bulk');
            return;
        }
        $this->view('dashboard.bulk.import', [
            'resource' => $resource,
            'config'   => $this->resources[$resource],
        ]);
    }

    public function importStore(string $resource): void
    {
        Auth::requireAuth();
        if (!isset($this->resources[$resource]) || empty($_FILES['csv']['tmp_name'])) {
            Session::getInstance()->flash('error', 'Invalid request.');
            $this->back();
            return;
        }
        $cfg = $this->resources[$resource];
        $handle = fopen($_FILES['csv']['tmp_name'], 'r');
        $header = fgetcsv($handle);
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if ($resource === 'students') {
                $this->importStudent($data);
                $count++;
            } elseif ($resource === 'notices') {
                $this->db->insert('notices', [
                    'title'      => $data['title'] ?? '',
                    'content'    => $data['content'] ?? '',
                    'pinned'     => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $count++;
            }
        }
        fclose($handle);
        Session::getInstance()->flash('success', "Imported {$count} record(s).");
        $this->redirect('/dashboard/bulk');
    }

    private function fetchRows(string $resource): array
    {
        switch ($resource) {
            case 'students':
                return $this->db->fetchAll(
                    "SELECT s.admission_number, u.name, c.name as class, s.roll_number as roll
                     FROM students s
                     LEFT JOIN users u ON s.user_id = u.id
                     LEFT JOIN school_classes c ON s.class_id = c.id"
                );
            case 'teachers':
                return $this->db->fetchAll(
                    "SELECT t.employee_id, u.name, u.email, u.phone
                     FROM teachers t LEFT JOIN users u ON t.user_id = u.id"
                );
            case 'fees':
                return $this->db->fetchAll("SELECT name, amount, fee_type FROM fees");
            case 'classes':
                return $this->db->fetchAll("SELECT name, code FROM school_classes");
            case 'notices':
                return $this->db->fetchAll("SELECT title, content FROM notices");
        }
        return [];
    }

    private function importStudent(array $data): void
    {
        $email = strtolower(str_replace(' ', '.', ($data['name'] ?? 'user')) . '_' . substr(bin2hex(random_bytes(2)), 0, 4) . '@example.com');
        $userId = $this->db->insert('users', [
            'name'       => $data['name'] ?? '',
            'email'      => $email,
            'role'       => 'student',
            'role_id'     => \App\Core\Auth::roleId('student'),
            'password'   => \App\Core\Auth::hashPassword('password'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $classRow = $this->db->fetch("SELECT id FROM school_classes WHERE name = ? LIMIT 1", [$data['class'] ?? '']);

        $this->db->insert('students', [
            'user_id'           => $userId,
            'admission_number'  => $data['admission_number'] ?? ('STU-' . date('Ymd') . '-' . $userId),
            'class_id'          => $classRow['id'] ?? null,
            'roll_number'       => $data['roll'] ?? null,
            'admission_date'    => date('Y-m-d'),
            'status'            => 'active',
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
    }
}
