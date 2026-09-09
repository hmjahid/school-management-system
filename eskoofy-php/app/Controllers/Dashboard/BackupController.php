<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class BackupController extends Controller
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
            "SELECT * FROM backups ORDER BY id DESC"
        );

        $this->view('dashboard.backups.index', [
            'rows' => $rows,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_{$timestamp}.sql";
        $backupDir = __DIR__ . '/../../../storage/backups';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $tables = $this->db->fetchAll(
            "SELECT TABLE_NAME AS `name` FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME ASC"
        );

        $output = "-- Eskoofy Backup {$timestamp}\n\n";

        foreach ($tables as $table) {
            $tableName = $table['name'];
            $output .= "-- Table: {$tableName}\n";

            $createSql = $this->db->fetch(
                "SHOW CREATE TABLE `{$tableName}`"
            );
            if ($createSql) {
                $create = array_values($createSql)[1] ?? '';
                if ($create) {
                    $output .= $create . ";\n\n";
                }
            }

            $rows = $this->db->fetchAll("SELECT * FROM `{$tableName}`");
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_map(function ($v) {
                    if ($v === null) return 'NULL';
                    return "'" . str_replace("'", "''", (string) $v) . "'";
                }, array_values($row));
                $output .= "INSERT INTO `{$tableName}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }

        $filepath = $backupDir . '/' . $filename;
        file_put_contents($filepath, $output);

        $this->db->insert('backups', [
            'filename'    => $filename,
            'path'        => $filepath,
            'size'        => filesize($filepath),
            'created_by'  => Auth::id(),
            'notes'       => 'Manual backup',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', "Backup created: {$filename} (" . number_format(filesize($filepath)) . " bytes)");
        $this->redirect('/dashboard/backups');
    }
}
