<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardBackupController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('backup_database'), 403);

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

        return view('dashboard.backup.index', compact('files'));
    }

    public function create(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('backup_database'), 403);

        try {
            Artisan::call('backup:run');
            $output = Artisan::output();
            return back()->with('status', __('Backup created.'))->with('backup_output', $output);
        } catch (\Throwable $e) {
            return back()->withErrors(['backup' => $e->getMessage()]);
        }
    }

    public function download(Request $request, string $file)
    {
        abort_unless($request->user()?->can('backup_database'), 403);

        $disk = Storage::disk('local');
        if (!$disk->exists('backups/'.$file)) {
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
            return back()->withErrors(['restore' => $e->getMessage()]);
        }
    }
}