<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\ActivityLog;
use App\Services\BackupService;
use App\Services\CloudBackupService;

class BackupController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        // The page holds both local backups and the cloud-backup panel.
        $cloudService = new CloudBackupService(\App\Core\Database::getInstance());
        $cloudSettings = $cloudService->settings();
        $remote = $cloudService->listRemote();

        $this->view('admin.backups', [
            'admin' => Auth::user(),
            'backups' => (new BackupService())->list(),
            'tab' => (($_GET['tab'] ?? 'local') === 'cloud') ? 'cloud' : 'local',
            'cloudSettings' => $cloudSettings,
            'providers' => $this->cloudProviders(),
            'remote' => $remote['files'],
            'remoteNotice' => $remote['ok'] ? null : $remote['message'],
            'isConfigured' => $cloudService->isConfigured(),
            'runs' => $cloudService->recentRuns(15),
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function cloudProviders(): array
    {
        return [
            'local' => ['label' => 'This server (local folder)', 'fields' => []],
            'google_drive' => ['label' => 'Google Drive', 'fields' => [
                'client_id' => 'Client ID',
                'client_secret' => 'Client secret',
                'refresh_token' => 'Refresh token',
                'service_account_email' => 'Service account email',
                'service_account_private_key' => 'Service account private key',
                'scope' => 'OAuth scope (optional)',
            ]],
            'dropbox' => ['label' => 'Dropbox', 'fields' => [
                'app_key' => 'App key',
                'app_secret' => 'App secret',
                'refresh_token' => 'Refresh token',
                'access_token' => 'Access token (short-lived alternative)',
            ]],
            '4shared' => ['label' => '4shared', 'fields' => [
                'api_key' => 'API key',
                'username' => 'Username',
                'password' => 'Password',
            ]],
            's3' => ['label' => 'Amazon S3 (or compatible)', 'fields' => [
                'endpoint' => 'Endpoint',
                'bucket' => 'Bucket',
                'key' => 'Access key',
                'secret' => 'Secret key',
                'region' => 'Region',
            ]],
        ];
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