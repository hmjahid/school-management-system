<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\CloudBackup\CloudBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardBackupController extends Controller
{
    public function index(Request $request): View
    {
        // The page holds both local backups and the cloud-backup panel; either
        // ability is enough to open it, and the panel-level actions enforce
        // their own permissions.
        abort_unless(
            $request->user()?->can('backup_database') || $request->user()?->can('manage_cloud_backup'),
            403
        );

        $disk = Storage::disk('local');
        $files = collect($disk->files('backups'))
            ->filter(fn ($f) => str_ends_with($f, '.zip'))
            ->sortDesc()
            ->map(fn ($f) => [
                'name' => basename($f),
                'path' => $f,
                'size' => $disk->size($f),
                'modified' => $disk->lastModified($f),
            ])
            ->values();

        // The cloud-backup section lives inside this page (Local / Cloud tabs),
        // so the cloud data is resolved here rather than on a separate route.
        $cloud = $this->cloudData();

        return view('dashboard.backup.index', array_merge(
            compact('files'),
            ['tab' => $request->query('tab', 'local') === 'cloud' ? 'cloud' : 'local'],
            $cloud,
        ));
    }

    /**
     * Everything the cloud-backup panel needs, from the shared service.
     *
     * @return array<string, mixed>
     */
    private function cloudData(): array
    {
        $service = app(CloudBackupService::class);
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

    public function create(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('backup_database'), 403);

        try {
            $exitCode = Artisan::call('backup:run');
            $output = Artisan::output();

            if ($exitCode !== \Illuminate\Console\Command::SUCCESS) {
                report(new \RuntimeException('backup:run failed (exit '.$exitCode.'): '.$output));

                return back()->withErrors(['backup' => __('Backup failed. Please check the logs for details.')]);
            }

            return back()->with('status', __('Backup created.'))->with('backup_output', $output);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['backup' => __('Backup failed. Please check the logs for details.')]);
        }
    }

    public function download(Request $request, string $file)
    {
        abort_unless($request->user()?->can('backup_database'), 403);

        $disk = Storage::disk('local');
        if (! $disk->exists('backups/'.$file)) {
            abort(404);
        }

        return $disk->download('backups/'.$file);
    }

    public function destroy(Request $request, string $file): RedirectResponse
    {
        abort_unless($request->user()?->can('restore_database') || $request->user()?->can('backup_database'), 403);

        $disk = Storage::disk('local');
        $disk->delete('backups/'.$file);

        return back()->with('status', __('Backup deleted.'));
    }

    public function restore(Request $request, string $file): RedirectResponse
    {
        abort_unless($request->user()?->can('restore_database'), 403);

        try {
            Artisan::call('backup:restore', ['file' => $file, '--force' => true]);

            return back()->with('status', __('Restore completed.'));
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['restore' => __('Restore failed. Please check the logs for details.')]);
        }
    }
}
