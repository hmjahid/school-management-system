@extends('layouts.dashboard')

@section('title', __('Backups') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Backups')" :description="__('Create and restore portable backups, and upload them to a cloud provider.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Backups')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    {{-- ------------------------------------------------------------ tabs --}}
    <div class="mb-6 flex flex-wrap gap-1 rounded-lg border border-slate-200 bg-white p-1 text-sm" data-backup-tabs>
        <a href="{{ route('dashboard.backup.index', ['tab' => 'local']) }}"
           class="rounded-md px-4 py-2 font-medium transition {{ ($tab ?? 'local') === 'local' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}"
           data-backup-tab="local">{{ __('Local backups') }}</a>
        <a href="{{ route('dashboard.backup.index', ['tab' => 'cloud']) }}"
           class="rounded-md px-4 py-2 font-medium transition {{ ($tab ?? 'local') === 'cloud' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}"
           data-backup-tab="cloud">{{ __('Cloud backup') }}</a>
    </div>

    @if (($tab ?? 'local') === 'local')
        {{-- ------------------------------------------------------- local --}}
        <div data-backup-panel="local">
            @if(session('backup_output'))
                <x-card :title="__('Last backup output')" class="mb-6">
                    <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ session('backup_output') }}</pre>
                </x-card>
            @endif

            <x-card :padding="false">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Local backups') }}</h2>
                        <p class="text-sm text-slate-500">{{ __('Portable archives stored on this server, ready to restore or download.') }}</p>
                    </div>
                    <form method="post" action="{{ route('dashboard.backup.create') }}">
                        @csrf
                        <x-button type="submit">{{ __('Create backup now') }}</x-button>
                    </form>
                </div>
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('File') }}</th>
                            <th class="px-4 py-3">{{ __('Size') }}</th>
                            <th class="px-4 py-3">{{ __('Modified') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($files as $f)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $f['name'] }}</td>
                                <td class="px-4 py-3">{{ number_format($f['size'] / 1024, 1) }} KB</td>
                                <td class="px-4 py-3 text-slate-500">{{ \Carbon\Carbon::createFromTimestamp($f['modified'])->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <x-button :href="route('dashboard.backup.download', ['file' => $f['name']])" variant="ghost" size="sm">{{ __('Download') }}</x-button>
                                    @can('restore_database')
                                        <form method="post" action="{{ route('dashboard.backup.restore', ['file' => $f['name']]) }}" class="inline" data-confirm="{{ __('Restore this backup? Existing files will be overwritten.') }}">
                                            @csrf
                                            <button class="text-xs font-semibold text-amber-700 hover:underline" type="submit">{{ __('Restore') }}</button>
                                        </form>
                                    @endcan
                                    <form method="post" action="{{ route('dashboard.backup.destroy', ['file' => $f['name']]) }}" class="inline" data-confirm="{{ __('Delete this backup file?') }}">
                                        @csrf @method('delete')
                                        <button class="text-xs font-semibold text-red-700 hover:underline" type="submit">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-16"><x-empty-state :title="__('No backups yet')" :message="__('Create your first backup to get started.')" icon="document" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-card>
        </div>
    @else
        {{-- ------------------------------------------------------- cloud --}}
        <div data-backup-panel="cloud">
            @include('partials.dashboard.cloud-backup-panel', [
                'settings' => $cloudSettings,
                'providers' => $providers,
                'bounds' => $bounds,
                'remote' => $remote,
                'remoteNotice' => $remoteNotice,
                'runs' => $runs,
                'configured' => $configured,
                'isConfigured' => $isConfigured,
            ])
        </div>
    @endif
@endsection