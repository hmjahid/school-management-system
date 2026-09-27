<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\CloudBackup\CloudBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin surface for the cloud backup feature: the provider/credential settings,
 * a connection test, an on-demand upload, the remote file list with restore and
 * delete, and the run history.
 *
 * Every action is gated on `manage_cloud_backup`, and restore additionally
 * requires `restore_database` — restoring a remote file is exactly as
 * dangerous as restoring a local one, and the two permissions can be held by
 * different people on purpose.
 */
class DashboardCloudBackupController extends Controller
{
    /**
     * The cloud-backup admin is rendered inside the Backups page
     * (dashboard.backup.index, Local / Cloud tabs). This controller only handles
     * the POST actions; the page itself is owned by DashboardBackupController.
     */
    public function update(Request $request, CloudBackupService $service): RedirectResponse
    {
        $this->authorize('manage_cloud_backup');

        $input = $request->validate([
            'provider' => ['required', 'string', 'in:'.implode(',', array_keys($this->providers()))],
            'folder' => ['nullable', 'string', 'max:120'],
            'is_enabled' => ['nullable', 'boolean'],
            'auto_enabled' => ['nullable', 'boolean'],
            // Deliberately loose: the real bounds live in config('backup.auto')
            // and are applied by CloudBackupService::saveSettings(), which is the
            // same code path the artisan command uses. Hardcoding them here too
            // would give the form and the command two different answers.
            'interval_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'keep' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'credentials' => ['nullable', 'array'],
        ]);

        $settings = $service->saveSettings([
            'provider' => $input['provider'],
            'folder' => $input['folder'] ?? null,
            'is_enabled' => $request->boolean('is_enabled'),
            'auto_enabled' => $request->boolean('auto_enabled'),
            'interval_minutes' => $input['interval_minutes'] ?? null,
            'keep' => $input['keep'] ?? null,
            'credentials' => $request->input('credentials', []),
        ]);

        return back()->with('status', __('Cloud backup settings saved.'));
    }

    /**
     * Test the stored credentials against the provider. Runs against a copy of
     * the settings that the admin may have just typed but not saved, so the form
     * can be verified before it is committed.
     */
    public function test(Request $request, CloudBackupService $service): RedirectResponse
    {
        $this->authorize('manage_cloud_backup');

        $request->validate([
            'provider' => ['nullable', 'string', 'in:'.implode(',', array_keys($this->providers()))],
        ]);

        $settings = $service->settings();

        if ($request->filled('provider') && $request->string('provider')->toString() !== $settings->provider) {
            $settings = $service->previewSettings($request->input());
        }

        return back()->with('cloudResult', $this->flashable($service->testConnection($settings)));
    }

    public function run(Request $request, CloudBackupService $service): RedirectResponse
    {
        $this->authorize('manage_cloud_backup');

        return back()->with('cloudResult', $this->flashable($service->run()));
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

    public function restore(Request $request, string $file, CloudBackupService $service): RedirectResponse
    {
        // Both abilities, not either one: managing the provider credentials is
        // not the same authority as overwriting the live database, and the two
        // are granted separately on purpose.
        abort_unless(
            $request->user()?->can('manage_cloud_backup') && $request->user()?->can('restore_database'),
            403
        );

        $result = $service->restore($file);

        if (($result['status'] ?? null) !== 'success') {
            return back()->withErrors(['cloud' => $result['message'] ?? __('Restore failed.')]);
        }

        return back()->with('status', $result['message']);
    }

    public function destroy(Request $request, string $file, CloudBackupService $service): RedirectResponse
    {
        $this->authorize('manage_cloud_backup');

        $deleted = $service->deleteRemote($file);

        return $deleted
            ? back()->with('status', __('Remote backup deleted.'))
            : back()->withErrors(['cloud' => __('The remote file could not be deleted.')]);
    }

    /**
     * The provider registry, plus which fields are already filled, so the view
     * can render one credential input per provider without hardcoding any
     * provider in Blade.
     *
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
