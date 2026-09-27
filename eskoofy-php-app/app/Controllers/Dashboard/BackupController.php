<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;
use App\Services\CloudBackup\CloudBackupManager;
use App\Services\CloudBackup\CloudBackupService;
use App\Services\VariantRestoreReconciler;

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

        // The page holds both local backups and the cloud-backup panel; either
        // ability is enough to open it (parity with the Laravel app).
        $user = Auth::user();
        $canLocal = $user && \App\Core\Gate::allows('backup_database');
        $canCloud = $user && \App\Core\Gate::allows('manage_cloud_backup');
        if (! $canLocal && ! $canCloud) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }

        $files = glob($this->backupDir . '/*.zip') ?: [];
        usort($files, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        $files = array_map(function (string $path) {
            return [
                'name'     => basename($path),
                'size'     => filesize($path),
                'modified' => filemtime($path),
            ];
        }, $files);

        $cloud = $this->cloudData();

        $this->view('dashboard.backup.index', array_merge(
            ['files' => $files, 'tab' => ($_GET['tab'] ?? 'local') === 'cloud' ? 'cloud' : 'local'],
            $cloud
        ));
    }

    /**
     * Everything the cloud-backup panel needs, from the shared service.
     *
     * @return array<string, mixed>
     */
    private function cloudData(): array
    {
        $service = new CloudBackupService(new CloudBackupManager(), $this->db);
        $settings = $service->settings();
        $remote = $service->listRemote($settings);

        return [
            'cloudSettings' => $settings,
            'providers' => $this->providers(),
            'bounds' => $this->bounds(),
            'remote' => $remote['files'],
            'remoteNotice' => $remote['ok'] ? null : $remote['message'],
            'runs' => $service->recentRuns(15),
            'configured' => $settings->configuredFields(),
            'isConfigured' => $service->isConfigured($settings),
        ];
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

    public function create(): void
    {
        Auth::requireAuth();

        // Local backups are portable zips (MANIFEST.json + database/tables.json
        // + storage/app/public), the same contract as backup:run in the app.
        $service = new \App\Services\PortableBackupService($this->db);
        $bytes = $service->zip('raw-php');

        $ts = date('Ymd_His');
        $name = 'backup_' . $ts . '_' . bin2hex(random_bytes(3)) . '.zip';
        $path = $this->backupDir . '/' . $name;

        if (file_put_contents($path, $bytes) === false) {
            Session::getInstance()->flash('error', 'Backup failed: unable to write archive.');
            $this->redirect('/dashboard/backups');
        }

        Session::getInstance()->flash('success', "Backup created: {$name} (" . number_format(filesize($path)) . " bytes)");
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

        header('Content-Type: application/zip');
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

    public function restore(string $file): void
    {
        Auth::requireAuth();
        $path = $this->backupDir . '/' . basename((string) $file);
        if (!file_exists($path)) {
            Session::getInstance()->flash('error', 'Backup file not found.');
            $this->redirect('/dashboard/backups');
            return;
        }

        try {
            $service = new \App\Services\PortableBackupService($this->db);

            $zip = new \ZipArchive();
            if ($zip->open($path) !== true) {
                throw new \RuntimeException('Not a readable zip archive.');
            }
            $manifestRaw = $zip->getFromName(\App\Services\PortableBackupService::MANIFEST_FILE);
            $tablesRaw = $zip->getFromName(\App\Services\PortableBackupService::TABLES_FILE);
            $zip->close();

            if ($manifestRaw === false || $tablesRaw === false) {
                throw new \RuntimeException('Not a portable Eskoofy backup (missing MANIFEST.json / database/tables.json).');
            }

            $manifest = json_decode((string) $manifestRaw, true);
            if (! is_array($manifest) || ! $service->isValidManifest($manifest)) {
                throw new \RuntimeException('Unsupported backup format.');
            }

            $service->restoreTables($service->parseTables((string) $tablesRaw));

            $sourceVariant = is_string($manifest['eskoofyVariant'] ?? null) ? $manifest['eskoofyVariant'] : null;
            $targetVariant = (string) (config('eskoolfy.variant') ?? 'bd');
            if ($sourceVariant !== null && $sourceVariant !== $targetVariant) {
                $reconciled = $service->reconcileVariant($sourceVariant);
                Session::getInstance()->flash(
                    'success',
                    'Restore completed. Cross-variant restore ('
                        . $sourceVariant . ' → ' . $targetVariant
                        . '): variant settings reconciled to this profile.'
                        . ($reconciled !== [] ? ' ' . implode(' | ', array_slice($reconciled, 0, 8)) . (count($reconciled) > 8 ? '…' : '') : '')
                );
            } else {
                Session::getInstance()->flash('success', 'Restore completed.');
            }
        } catch (\Throwable $e) {
            Session::getInstance()->flash('error', 'Restore failed: ' . $e->getMessage());
        }

        $this->redirect('/dashboard/backups');
    }
}
