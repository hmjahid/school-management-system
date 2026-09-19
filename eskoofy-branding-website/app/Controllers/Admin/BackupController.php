<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\ActivityLog;
use App\Services\BackupService;

class BackupController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $this->view('admin.backups', [
            'admin'   => Auth::user(),
            'backups' => (new BackupService())->list(),
        ]);
    }

    public function create(string $type): void
    {
        $service = new BackupService();
        $created = $service->create($type);

        ActivityLog::log('backup.created', 'admin', (int) Auth::id(), [
            'type' => $created['type'],
            'file' => $created['file'],
        ]);

        $this->withSuccess('Backup created: ' . $created['file'] . ' (' . number_format($created['size'] / 1024, 1) . ' KB)');
        $this->redirect('/admin/backup');
    }

    public function download(): void
    {
        $file = (string) ($_GET['file'] ?? '');
        $path = (new BackupService())->download($file);
        if ($path === null) {
            $this->withError('Backup not found.');
            $this->redirect('/admin/backup');
        }

        ActivityLog::log('backup.downloaded', 'admin', (int) Auth::id(), ['file' => basename((string) $path)]);

        header('Content-Type: ' . (str_ends_with((string) $path, '.zip') ? 'application/zip' : 'application/sql'));
        header('Content-Disposition: attachment; filename="' . basename((string) $path) . '"');
        header('Content-Length: ' . filesize((string) $path));
        header('X-Content-Type-Options: nosniff');
        readfile((string) $path);
        exit;
    }

    public function delete(): void
    {
        $file = (string) ($_POST['file'] ?? '');
        if ((new BackupService())->delete($file)) {
            ActivityLog::log('backup.deleted', 'admin', (int) Auth::id(), ['file' => basename($file)]);
            $this->withSuccess('Backup deleted.');
        } else {
            $this->withError('Could not delete backup.');
        }

        $this->redirect('/admin/backup');
    }
}