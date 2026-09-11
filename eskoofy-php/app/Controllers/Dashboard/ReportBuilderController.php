<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class ReportBuilderController extends Controller
{
    private DatabaseInterface $db;

    private const CONFIG = [
        'students' => [
            'label' => 'Students',
            'columns' => [
                'id' => 'ID', 'first_name' => 'First name', 'last_name' => 'Last name',
                'roll_number' => 'Roll', 'gender' => 'Gender', 'status' => 'Status',
                'class_id' => 'Class ID', 'batch_id' => 'Batch ID', 'phone' => 'Phone',
                'address' => 'Address', 'created_at' => 'Registered',
            ],
        ],
        'payments' => [
            'label' => 'Payments',
            'columns' => [
                'id' => 'ID', 'paymentable_id' => 'Payer ID', 'amount' => 'Amount',
                'paid_amount' => 'Paid amount', 'payment_status' => 'Status',
                'payment_method' => 'Method', 'payment_date' => 'Date',
                'transaction_id' => 'Transaction ID',
            ],
        ],
        'fee_payments' => [
            'label' => 'Fee payments',
            'columns' => [
                'id' => 'ID', 'student_id' => 'Student ID', 'amount' => 'Amount',
                'paid_amount' => 'Paid amount', 'balance' => 'Balance', 'status' => 'Status',
                'payment_date' => 'Date', 'payment_method' => 'Method',
            ],
        ],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $classes = $this->db->fetchAll("SELECT id, name FROM school_classes ORDER BY name ASC");

        $this->view('dashboard.reports.builder', [
            'config'  => self::CONFIG,
            'classes' => $classes,
        ]);
    }

    public function export(): void
    {
        Auth::requireAuth();
        [$header, $rows] = $this->buildExport($_POST);
        if ($header === null) {
            Session::getInstance()->flash('error', 'Report configuration is invalid.');
            $this->redirect('/dashboard/reports/builder');
            return;
        }

        $filename = 'report-' . ($_POST['entity'] ?? 'report') . '-' . date('Ymd-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        fputcsv($out, $header);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    public function buildExport(array $input): array
    {
        $entity = $input['entity'] ?? '';
        $columns = $input['columns'] ?? [];
        $from = $input['date_from'] ?? '';
        $to = $input['date_to'] ?? '';
        $status = trim($input['status'] ?? '');
        $classId = (int) ($input['class_id'] ?? 0);

        if (!isset(self::CONFIG[$entity]) || empty($columns)) {
            return [null, []];
        }

        $validColumns = self::CONFIG[$entity]['columns'];
        $selected = [];
        foreach ($columns as $col) {
            if (isset($validColumns[$col])) {
                $selected[$col] = $validColumns[$col];
            }
        }
        if (empty($selected)) {
            return [null, []];
        }

        $select = implode(', ', array_map(fn ($c) => $entity . '.' . $c, array_keys($selected)));
        $where = '1=1';
        $params = [];

        if ($entity === 'students') {
            if ($status !== '') {
                $where .= ' AND students.status = ?';
                $params[] = $status;
            }
            if ($classId > 0) {
                $where .= ' AND students.class_id = ?';
                $params[] = $classId;
            }
            if ($from !== '') {
                $where .= ' AND students.created_at >= ?';
                $params[] = $from . ' 00:00:00';
            }
            if ($to !== '') {
                $where .= ' AND students.created_at <= ?';
                $params[] = $to . ' 23:59:59';
            }
        } else {
            $dateCol = 'payment_date';
            if ($status !== '') {
                $where .= " AND {$entity}.status = ?";
                $params[] = $status;
            }
            if ($from !== '') {
                $where .= " AND {$entity}.{$dateCol} >= ?";
                $params[] = $from;
            }
            if ($to !== '') {
                $where .= " AND {$entity}.{$dateCol} <= ?";
                $params[] = $to;
            }
        }

        $data = [];
        $offset = 0;
        while (true) {
            $rows = $this->db->fetchAll(
                "SELECT {$select} FROM {$entity} WHERE {$where} LIMIT 500 OFFSET {$offset}",
                $params
            );
            if (empty($rows)) {
                break;
            }
            foreach ($rows as $row) {
                $line = [];
                foreach (array_keys($selected) as $col) {
                    $val = $row[$col] ?? '';
                    if (is_array($val) || is_object($val)) {
                        $val = json_encode($val);
                    }
                    $line[] = (string) ($val ?? '');
                }
                $data[] = $line;
            }
            $offset += 500;
        }

        return [array_values($selected), $data];
    }
}