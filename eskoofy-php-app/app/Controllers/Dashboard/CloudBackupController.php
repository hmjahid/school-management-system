<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Gate;
use App\Core\Session;
use App\Core\Support\Collection;
use App\Services\CloudBackup\CloudBackupManager;
use App\Services\CloudBackup\CloudBackupService;

/**
 * Admin surface for the cloud backup feature: provider/credential settings, a
 * connection test, on-demand upload, remote file list with restore and delete,
 * and the run history.
 *
 * Every action is gated on `manage_cloud_backup`, and restore additionally
 * requires `restore_database` — restoring a remote file is exactly as
 * dangerous as restoring a local one, and the two permissions can be held by
 * different people on purpose.
 *
 * Port of eskoofy-laravel-app/app/Http/Controllers/Web/DashboardCloudBackupController.php.
 */
class CloudBackupController extends Controller
{
    private CloudBackupService $service;

    public function __construct(?DatabaseInterface $db = null)
    {
        $db ??= Database::getInstance();
        $this->service = new CloudBackupService(new CloudBackupManager(), $db);
    }

    public function index(): void
    {
        // The cloud-backup admin lives inside the Backups page (Local / Cloud
        // tabs). Keep the GET route for old links by redirecting to the tab.
        $this->redirect(route('dashboard.backup.index', ['tab' => 'cloud']));
    }

    public function update(): void
    {
        Auth::requireAuth();
        abort_unless(Gate::allows('manage_cloud_backup'), 403);

        $input = $this->validate([
            'provider' => 'required|in:' . implode(',', CloudBackupManager::providers()),
            'folder' => 'max:120',
            'interval_minutes' => 'numeric|min:1|max:525600',
            'keep' => 'numeric|min:1|max:100000',
        ]);

        $this->service->saveSettings([
            'provider' => (string) ($input['provider'] ?? ''),
            'folder' => $input['folder'] ?? null,
            'is_enabled' => (bool) ($input['is_enabled'] ?? false),
            'auto_enabled' => (bool) ($input['auto_enabled'] ?? false),
            'interval_minutes' => $input['interval_minutes'] ?? null,
            'keep' => $input['keep'] ?? null,
            'credentials' => $_POST['credentials'] ?? [],
        ]);

        Session::getInstance()->flash('success', __('Cloud backup settings saved.'));
        $this->redirect(route('dashboard.backup.index', ['tab' => 'cloud']));
    }

    public function test(): void
    {
        Auth::requireAuth();
        abort_unless(Gate::allows('manage_cloud_backup'), 403);

        $settings = $this->service->settings();

        $provider = (string) ($_POST['provider'] ?? '');
        if ($provider !== '' && $provider !== $settings->provider) {
            $settings = $this->service->previewSettings($_POST);
        }

        Session::getInstance()->flash('cloudResult', $this->flashable($this->service->testConnection($settings)));
        $this->back();
    }

    public function run(): void
    {
        Auth::requireAuth();
        abort_unless(Gate::allows('manage_cloud_backup'), 403);

        Session::getInstance()->flash('cloudResult', $this->flashable($this->service->run()));
        $this->back();
    }

    public function restore(string $file): void
    {
        Auth::requireAuth();
        abort_unless(
            Gate::allows('manage_cloud_backup') && Gate::allows('restore_database'),
            403
        );

        $result = $this->service->restore($file);

        if (! ($result['ok'] ?? false)) {
            Session::getInstance()->flash('errors', ['cloud' => $result['message'] ?? __('Restore failed.')]);
            $this->back();
        }

        Session::getInstance()->flash('success', $result['message']);
        $this->back();
    }

    public function destroy(string $file): void
    {
        Auth::requireAuth();
        abort_unless(Gate::allows('manage_cloud_backup'), 403);

        $deleted = $this->service->deleteRemote($file);

        if ($deleted) {
            Session::getInstance()->flash('success', __('Remote backup deleted.'));
        } else {
            Session::getInstance()->flash('errors', ['cloud' => __('The remote file could not be deleted.')]);
        }
        $this->back();
    }

    /**
     * testConnection() and run() report differently ({ok,message} vs
     * {status,message}); the page only needs to know whether it worked and what
     * to say, so both are normalised here.
     *
     * @param  array<string, mixed>  $result
     * @return array{ok: bool, message: string}
     */
    private function flashable(array $result): array
    {
        $status = (string) ($result['status'] ?? '');
        $ok = $result['ok'] ?? in_array($status, ['success'], true);

        return ['ok' => (bool) $ok, 'message' => (string) ($result['message'] ?? '')];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function providers(): array
    {
        $providers = (array) config('backup.providers', []);

        return array_map(fn (array $provider) => $provider + [
            'label' => $provider['key'] ?? '',
            'fields' => [],
            'required' => [],
            'alternatives' => [],
        ], $providers);
    }

    /** @return array<string, int> */
    private function bounds(): array
    {
        return [
            'min_interval' => (int) config('backup.auto.min_interval_minutes', 5),
            'max_interval' => (int) config('backup.auto.max_interval_minutes', 10080),
            'min_keep' => (int) config('backup.auto.min_keep', 1),
            'max_keep' => (int) config('backup.auto.max_keep', 365),
        ];
    }
}