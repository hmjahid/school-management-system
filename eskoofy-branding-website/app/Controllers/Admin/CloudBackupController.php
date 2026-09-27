<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Services\CloudBackupService;
use App\Services\ActivityLog;

/**
 * Cloud backup admin surface for the branding website — provider credentials,
 * test, on-demand upload, remote list with restore/delete, run history.
 *
 * Port of the school products' DashboardCloudBackupController, scoped to the
 * website's own data (see App\Services\CloudBackupService).
 */
class CloudBackupController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        // The cloud-backup admin lives inside the Backups page (Local / Cloud
        // tabs). Keep this GET route for old links by redirecting to the tab.
        $this->redirect('/admin/backup?tab=cloud');
    }

    public function update(): void
    {
        $service = new CloudBackupService(\App\Core\Database::getInstance());
        $service->saveSettings([
            'provider' => (string) ($_POST['provider'] ?? ''),
            'folder' => (string) ($_POST['folder'] ?? ''),
            'is_enabled' => ! empty($_POST['is_enabled']),
            'auto_enabled' => ! empty($_POST['auto_enabled']),
            'interval_minutes' => (int) ($_POST['interval_minutes'] ?? 60),
            'keep' => (int) ($_POST['keep'] ?? 7),
            'credentials' => $_POST['credentials'] ?? [],
        ]);

        ActivityLog::log('cloud_backup_save', 'Cloud backup settings updated.');
        Session::getInstance()->flash('success', 'Cloud backup settings saved.');
        $this->redirect('/admin/backup?tab=cloud');
    }

    public function test(): void
    {
        $service = new CloudBackupService(\App\Core\Database::getInstance());
        $result = $service->test();

        Session::getInstance()->flash($result['ok'] ? 'success' : 'error', $result['message']);
        $this->back();
    }

    public function run(): void
    {
        $service = new CloudBackupService(\App\Core\Database::getInstance());
        $result = $service->run();

        Session::getInstance()->flash($result['status'] === 'success' ? 'success' : 'error', $result['message']);
        $this->back();
    }

    public function restore(string $file): void
    {
        $service = new CloudBackupService(\App\Core\Database::getInstance());
        $result = $service->restore($file);

        Session::getInstance()->flash($result['ok'] ? 'success' : 'error', $result['message']);
        $this->back();
    }

    public function destroy(string $file): void
    {
        $service = new CloudBackupService(\App\Core\Database::getInstance());
        $deleted = $service->deleteRemote($file);

        Session::getInstance()->flash($deleted ? 'success' : 'error', $deleted ? 'Remote backup deleted.' : 'The remote file could not be deleted.');
        $this->back();
    }
}
