<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class BackupController extends Controller
{
    private DatabaseInterface $db;
    private string $backupDir;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->backupDir = __DIR__ . '/../../../storage/backups';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function index(): void
    {
        Auth::requireAuth();
        $files = glob($this->backupDir . '/*.sql') ?: [];
        rsort($files);

        $backups = array_map(function (string $path) {
            return [
                'name'       => basename($path),
                'filename'   => basename($path),
                'size'       => number_format(filesize($path)),
                'bytes'      => filesize($path),
                'type'       => 'Full',
                'created_at' => date('M d, Y H:i', filemtime($path)),
            ];
        }, $files);

        $totalBytes = array_sum(array_map('filesize', $files));

        $this->view('dashboard.backup.index', [
            'rows'        => $backups,
            'backups'     => $backups,
            'lastBackup'  => isset($backups[0]) ? $backups[0]['created_at'] : null,
            'totalBackups' => count($backups),
            'diskUsage'   => number_format($totalBytes / 1048576, 1) . ' MB',
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_{$timestamp}.sql";

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

        $filepath = $this->backupDir . '/' . $filename;
        file_put_contents($filepath, $output);

        Session::getInstance()->flash('success', "Backup created: {$filename} (" . number_format(filesize($filepath)) . " bytes)");
        $this->redirect('/dashboard/backups');
    }

    public function download(string $file): void
    {
        Auth::requireAuth();
        $path = $this->backupDir . '/' . basename((string) $file);
        if (!file_exists($path)) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename=' . basename($path));
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function destroy(string $file): void
    {
        Auth::requireAuth();
        $path = $this->backupDir . '/' . basename((string) $file);
        if (file_exists($path)) {
            @unlink($path);
        }

        Session::getInstance()->flash('success', 'Backup deleted.');
        $this->redirect('/dashboard/backups');
    }
}